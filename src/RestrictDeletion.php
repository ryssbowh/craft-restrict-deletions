<?php

namespace Ryssbowh\RestrictDeletion;

use Ryssbowh\RestrictDeletion\elements\actions\ViewUsage;
use Ryssbowh\RestrictDeletion\models\Settings;
use Ryssbowh\RestrictDeletion\services\Restrict;
use Ryssbowh\RestrictDeletion\services\Usage;
use Ryssbowh\RestrictDeletion\utilities\ElementUsageUtility;
use craft\base\Element;
use craft\base\Model;
use craft\base\Plugin;
use craft\commerce\elements\Product;
use craft\elements\Asset;
use craft\elements\Category;
use craft\elements\Entry;
use craft\elements\User;
use craft\helpers\ElementHelper;
use craft\services\Elements;
use craft\services\UserPermissions;
use craft\services\Utilities;
use yii\base\Event;

class RestrictDeletion extends Plugin
{
    /**
     * @var Themes
     */
    public static $plugin;

    /**
     * @inheritdoc
     */
    public bool $hasCpSettings = true;

    /**
     * inheritDoc
     */
    public function init(): void
    {
        parent::init();

        self::$plugin = $this;

        $this->registerServices();
        $this->registerElementEvents();
        $this->registerPermissions();
        $this->registerUtilities();
    }

    /**
     * Register deletion events
     */
    protected function registerElementEvents()
    {
        Event::on(Element::class, Element::EVENT_BEFORE_DELETE, function (Event $event) {
            if (ElementHelper::isDraftOrRevision($event->sender)) {
                return;
            }
            $class = get_class($event->sender);
            $service = RestrictDeletion::$plugin->restrict;
            if ($class == Entry::class and !$service->canDeleteEntry($event->sender)) {
                $event->isValid = false;
            }
            if ($class == Category::class and !$service->canDeleteCategory($event->sender)) {
                $event->isValid = false;
            }
            if ($class == Asset::class and !$service->canDeleteAsset($event->sender)) {
                $event->isValid = false;
            }
            if ($class == User::class and !$service->canDeleteUser($event->sender)) {
                $event->isValid = false;
            }
            if (\Craft::$app->plugins->isPluginEnabled('commerce')) {
                if ($class == Product::class and !$service->canDeleteProduct($event->sender)) {
                    $event->isValid = false;
                }
            }
        });
        Event::on(Elements::class, Elements::EVENT_AUTHORIZE_DELETE, function (Event $event) {
            if (ElementHelper::isDraftOrRevision($event->element)) {
                return;
            }
            $class = get_class($event->element);
            $service = RestrictDeletion::$plugin->restrict;
            if ($class == Entry::class and !$service->canDeleteEntry($event->element)) {
                $event->authorized = false;
            }
            if ($class == Category::class and !$service->canDeleteCategory($event->element)) {
                $event->authorized = false;
            }
            if ($class == Asset::class and !$service->canDeleteAsset($event->element)) {
                $event->authorized = false;
            }
            if ($class == User::class and !$service->canDeleteUser($event->element)) {
                $event->authorized = false;
            }
            if (\Craft::$app->plugins->isPluginEnabled('commerce')) {
                if ($class == Product::class and !$service->canDeleteProduct($event->element)) {
                    $event->authorized = false;
                }
            }
        });
        Event::on(Element::class, Element::EVENT_REGISTER_ACTIONS, function (Event $event) {
            $event->actions[] = ViewUsage::class;
        });
    }

    /**
     * Register utilities
     */
    protected function registerUtilities()
    {
        Event::on(Utilities::class, Utilities::EVENT_REGISTER_UTILITY_TYPES, function (Event $event) {
            $event->types[] = ElementUsageUtility::class;
        });
    }

    /**
     * Register permissions
     */
    protected function registerPermissions()
    {
        Event::on(UserPermissions::class, UserPermissions::EVENT_REGISTER_PERMISSIONS, function (Event $event) {
            $event->permissions = RestrictDeletion::$plugin->addPermissions($event->permissions);
        });
    }

    /**
     * Register all services
     */
    protected function registerServices()
    {
        $this->setComponents([
            'restrict' => Restrict::class,
            'usage' => Usage::class,
        ]);
    }

    /**
     * @inheritdoc
     */
    protected function createSettingsModel(): ?Model
    {
        return new Settings();
    }

    /**
     * @inheritdoc
     */
    public function getSettingsResponse(): mixed
    {
        $controller = \Craft::$app->controller;

        return $controller->renderTemplate('restrict-deletion/settings-new', [
            'plugin' => $this,
            'settings' => $this->settings,
            'policies' => [
                '' => \Craft::t('restrict-deletion', 'Use default'),
                Restrict::POLICY_NONE => \Craft::t('restrict-deletion', 'Do not restrict'),
                Restrict::POLICY_ALL => \Craft::t('restrict-deletion', 'Restrict all'),
                Restrict::POLICY_NO_REVISIONS => \Craft::t('restrict-deletion', 'Restrict all but revisions'),
                Restrict::POLICY_NO_DRAFTS => \Craft::t('restrict-deletion', 'Restrict all but drafts'),
                Restrict::POLICY_NO_DRAFTS_OR_REVISIONS => \Craft::t('restrict-deletion', 'Restrict all but drafts and revisions')
            ]
        ]);
    }

    /**
     * Add permissions to each sections/category groups/volumes and users to skip the restrictions
     *
     * @param  array $array
     * @return array
     */
    protected function addPermissions(array $array): array
    {
        $ignorePermission = [
            'label' => \Craft::t('restrict-deletion', 'Ignore the deletion restrictions')
        ];
        $newPerms = [];
        foreach ($array as $permissions) {
            $newPerm = $permissions;
            foreach ($permissions['permissions'] as $name => $perms) {
                $elems = explode(':', $name);
                $subname = $elems[0] ?? false;
                $uid = $elems[1] ?? false;
                if ($subname == 'viewEntries') {
                    if (isset($newPerm['permissions'][$name]['nested']['deleteEntries:' . $uid])) {
                        $newPerm['permissions'][$name]['nested']['deleteEntries:' . $uid]['nested']['ignoreDeletionRestriction:' . $uid] = $ignorePermission;
                    }
                } elseif ($subname == 'viewAssets') {
                    $newPerm['permissions'][$name]['nested']['deleteAssets:' . $uid]['nested']['ignoreDeletionRestriction:' . $uid] = $ignorePermission;
                } elseif ($subname == 'viewCategories') {
                    $newPerm['permissions'][$name]['nested']['deleteCategories:' . $uid]['nested']['ignoreDeletionRestriction:' . $uid] = $ignorePermission;
                } elseif ($subname == 'deleteUsers') {
                    $newPerm['permissions']['deleteUsers']['nested']['ignoreDeletionRestriction:users'] = $ignorePermission;
                } elseif ($subname == 'commerce-editProductType') {
                    $newPerm['permissions'][$name]['nested']['commerce-deleteProducts:' . $uid]['nested']['ignoreDeletionRestriction:' . $uid] = $ignorePermission;
                }
            }
            $newPerms[] = $newPerm;
        }
        return $newPerms;
    }
}
