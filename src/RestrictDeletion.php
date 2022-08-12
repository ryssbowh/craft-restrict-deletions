<?php

namespace Ryssbowh\RestrictDeletion;

use Ryssbowh\RestrictDeletion\models\Settings;
use Ryssbowh\RestrictDeletion\services\Restrict;
use craft\base\Element;
use craft\base\Model;
use craft\base\Plugin;
use craft\elements\Asset;
use craft\elements\Category;
use craft\elements\Entry;
use craft\elements\User;
use craft\helpers\ElementHelper;
use craft\services\UserPermissions;
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
    public $hasCpSettings = true;

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
            if (\Craft::$app->plugins->isPluginInstalled('commerce')) {
                if ($class == Product::class and !$service->canDeleteProduct($event->sender)) {
                    $event->isValid = false;
                }
            }
        });
        Event::on(Element::class, Element::EVENT_DEFINE_IS_DELETABLE, function (Event $event) {
            if (ElementHelper::isDraftOrRevision($event->sender)) {
                return;
            }
            $class = get_class($event->sender);
            $service = RestrictDeletion::$plugin->restrict;
            if ($class == Entry::class and !$service->canDeleteEntry($event->sender)) {
                $event->value = false;
            }
            if ($class == Category::class and !$service->canDeleteCategory($event->sender)) {
                $event->value = false;
            }
            if ($class == Asset::class and !$service->canDeleteAsset($event->sender)) {
                $event->value = false;
            }
            if ($class == User::class and !$service->canDeleteUser($event->sender)) {
                $event->value = false;
            }
            if (\Craft::$app->plugins->isPluginInstalled('commerce')) {
                if ($class == Product::class and !$service->canDeleteProduct($event->sender)) {
                    $event->value = false;
                }
            }
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
            'restrict' => Restrict::class
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
     * @inheritDoc
     */
    protected function settingsHtml(): string
    {
        return \Craft::$app->view->renderTemplate(
            'restrict-deletion/settings',
            [
                'settings' => $this->getSettings(),
                'policies' => [
                    Restrict::POLICY_NONE => \Craft::t('restrict-deletion', 'Do not restrict'),
                    Restrict::POLICY_ALL => \Craft::t('restrict-deletion', 'Restrict all'),
                    Restrict::POLICY_NO_REVISIONS => \Craft::t('restrict-deletion', 'Restrict all but revisions'),
                    Restrict::POLICY_NO_DRAFTS => \Craft::t('restrict-deletion', 'Restrict all but drafts'),
                    Restrict::POLICY_NO_DRAFTS_OR_REVISIONS => \Craft::t('restrict-deletion', 'Restrict all but drafts and revisions')
                ]
            ]
        );
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
        foreach ($array as $label => $permissions) {
            $newPerm = $permissions;
            foreach ($permissions as $name => $perms) {
                $elems = explode(':', $name);
                $subname = $elems[0] ?? false;
                $uid = $elems[1] ?? false;
                if ($subname == 'editEntries') {
                    if (isset($newPerm[$name]['nested']['deleteEntries:' . $uid])) {
                        $newPerm[$name]['nested']['deleteEntries:' . $uid]['nested']['ignoreDeletionRestriction:' . $uid] = $ignorePermission;
                    }
                } else if ($subname == 'viewVolume') {
                    $newPerm[$name]['nested']['deleteFilesAndFoldersInVolume:' . $uid]['nested']['ignoreDeletionRestriction:' . $uid] = $ignorePermission;
                } else if ($subname == 'editCategories') {
                    $newPerm[$name]['nested']['ignoreDeletionRestriction:' . $uid] = $ignorePermission;
                } else if ($subname == 'deleteUsers') {
                    $newPerm['deleteUsers']['nested']['ignoreDeletionRestriction:users'] = $ignorePermission;
                } else if ($subname == 'commerce-manageProducts') {
                    foreach ($perms['nested'] as $name2 => $perm) {
                        $elems = explode(':', $name2);
                        $subname = $elems[0] ?? false;
                        $uid = $elems[1] ?? false;
                        if ($subname == 'commerce-manageProductType') {
                            $newPerm[$name]['nested'][$name2]['nested']['ignoreDeletionRestriction:' . $uid] = $ignorePermission;
                        }
                    }
                }
            }
            $newPerms[$label] = $newPerm;
        }
        return $newPerms;
    }
}