<?php

namespace Ryssbowh\RestrictDeletion\utilities;

use Exception;
use Ryssbowh\RestrictDeletion\RestrictDeletion;
use craft\base\Element;
use craft\base\Utility;
use craft\commerce\elements\Product;
use craft\elements\Asset;
use craft\elements\Category;
use craft\elements\Entry;
use craft\elements\User;

/**
 * Element usage utility
 *
 * @since 2.2.0
 */
class ElementUsageUtility extends Utility
{
    /**
     * @inheritDoc
     */
    public static function displayName(): string
    {
        return \Craft::t('restrict-deletion', 'Element Usage');
    }

    /**
     * @inheritDoc
     */
    public static function id(): string
    {
        return 'element-usage';
    }

    /**
     * @inheritDoc
     */
    public static function iconPath(): ?string
    {
        return null;
    }

    /**
     * @inheritDoc
     */
    public static function contentHtml(): string
    {
        $selected = $elements = $type = null;
        try {
            list($selected, $type) = self::getElementFromRequest();
        } catch (\Exception $e) {
            $error = $e->getMessage();
        }
        if ($selected) {
            $elements = [];
            foreach (RestrictDeletion::$plugin->usage->getRelated($selected) as $index => $elems) {
                foreach ($elems as $element) {
                    $site = $element->site;
                    if (!isset($elements[$site->id])) {
                        $elements[$site->id] = [
                            'site' => $site,
                            'elements' => []
                        ];
                    }
                    if (!isset($elements[$site->id]['elements'][$index])) {
                        $elements[$site->id]['elements'][$index] = [];
                    }
                    $elements[$site->id]['elements'][$index][] = $element;
                }
            }
        }
        $types = [
            'entry' => [
                'class' => Entry::class,
                'name' => Entry::displayName()
            ],
            'category' => [
                'class' => Category::class,
                'name' => Category::displayName()
            ],
            'asset' => [
                'class' => Asset::class,
                'name' => Asset::displayName()
            ],
            'user' => [
                'class' => User::class,
                'name' => User::displayName()
            ]
        ];
        if (\Craft::$app->plugins->isPluginEnabled('commerce')) {
            $types['product'] = [
                'name' => Product::displayName(),
                'class' => Product::class
            ];
        }
        return \Craft::$app->view->renderTemplate('restrict-deletion/utility', [
            'error' => $error ?? '',
            'selected' => $selected,
            'elements' => $elements ?? null,
            'types' => $types,
            'type' => $type,
        ]);
    }

    /**
     * Get the element chosen from the request, returns [?Element $element, string $type]
     *
     * @return array
     */
    protected static function getElementFromRequest(): array
    {
        $request = \Craft::$app->request;
        $elementId = $request->getQueryParam('asset');
        if ($elementId) {
            $element = Asset::find()->id($elementId)->one();
            if (!$element) {
                throw new Exception(\Craft::t('restrict-deletion', 'Asset was not found'));
            }
            return [$element, 'asset'];
        }
        $elementId = $request->getQueryParam('entry');
        if ($elementId) {
            $element = Entry::find()->anyStatus()->id($elementId)->one();
            if (!$element) {
                throw new Exception(\Craft::t('restrict-deletion', 'Entry was not found'));
            }
            return [$element, 'entry'];
        }
        $elementId = $request->getQueryParam('user');
        if ($elementId) {
            $element = User::find()->anyStatus()->id($elementId)->one();
            if (!$element) {
                throw new Exception(\Craft::t('restrict-deletion', 'User was not found'));
            }
            return [$element, 'user'];
        }
        $elementId = $request->getQueryParam('category');
        if ($elementId) {
            $element = Category::find()->anyStatus()->id($elementId)->one();
            if (!$element) {
                throw new Exception(\Craft::t('restrict-deletion', 'Category was not found'));
            }
            return [$element, 'user'];
        }
        if (\Craft::$app->plugins->isPluginEnabled('commerce')) {
            $elementId = $request->getQueryParam('product');
            if ($elementId) {
                $element = Product::find()->anyStatus()->id($elementId)->one();
                if (!$element) {
                    throw new Exception(\Craft::t('restrict-deletion', 'Product was not found'));
                }
                return [$element, 'product'];
            }
        }
        return [null, 'entry'];
    }
}
