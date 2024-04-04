<?php

namespace Ryssbowh\RestrictDeletion\services;

use benf\neo\elements\Block;
use craft\base\Component;
use craft\base\Element;
use craft\commerce\elements\Product;
use craft\db\Query;
use craft\elements\Asset;
use craft\elements\Category;
use craft\elements\Entry;
use craft\elements\MatrixBlock;
use craft\elements\User;
use craft\feedme\fields\Assets;
use verbb\navigation\elements\Node;
use verbb\supertable\elements\SuperTableBlockElement;

/**
 * Usage service
 *
 * @since 2.2.0
 */
class Usage extends Component
{
    protected array $baseElements = [
        Asset::class,
        Entry::class,
        Category::class,
        Product::class,
        User::class
    ];

    /**
     * Get all related elements, direct and non direct (through blocks)
     *
     * @param  Element $asset
     * @return array
     */
    public function getRelated(Element $asset): array
    {
        $elements = [];
        if ($elems = $this->getDirectRelated($asset)) {
            $elements['direct'] = $elems;
        }
        if ($elems = $this->getNeoBlocksRelated($asset)) {
            $elements['neo'] = $elems;
        }
        return $elements;
    }

    /**
     * Get all elements directly related to an element
     *
     * @param  Element $element
     * @return array
     */
    public function getDirectRelated(Element $element): array
    {
        $related = Asset::find()->relatedTo(['targetElement' => $element])->site('*')->all();
        $related = array_merge($related, Entry::find()->relatedTo(['targetElement' => $element])->site('*')->anyStatus()->all());
        $related = array_merge($related, Category::find()->relatedTo(['targetElement' => $element])->site('*')->anyStatus()->all());
        $related = array_merge($related, User::find()->relatedTo(['targetElement' => $element])->anyStatus()->all());
        if (\Craft::$app->plugins->isPluginEnabled('commerce')) {
            $related = array_merge($related, Product::find()->relatedTo(['targetElement' => $element])->site('*')->anyStatus()->all());
        }
        if (\Craft::$app->plugins->isPluginEnabled('navigation')) {
            $related = array_merge($related, Node::find()->elementId($element->id)->site('*')->anyStatus()->all());
        }
        return $related;
    }

    /**
     * Get all elements related to an element through neo blocks
     *
     * @param  Element $element
     * @return array
     */
    public function getNeoBlocksRelated(Element $element): array
    {
        if (!\Craft::$app->plugins->isPluginEnabled('neo')) {
            return [];
        }
        $blocks = Block::find()->relatedTo(['targetElement' => $element])->anyStatus()->site('*')->all();
        return array_map(function ($block) {
            return $this->getOwner($block);
        }, $blocks);
    }

    /**
     * Get the primary owner for an element
     *
     * @param  Element $element
     * @return Element
     * @since 3.0.0
     */
    public function getPrimaryOwner(Element $element): Element
    {
        if (!$element->primaryOwnerId) {
            return $element;
        }
        return $this->getPrimaryOwner($element->primaryOwner);
    }

    /**
     * Get the primary owner for an element
     *
     * @param  Element $element
     * @return Element
     */
    protected function getOwner(Element $element): Element
    {
        $owner = $element->owner;
        while (!in_array(get_class($owner), $this->baseElements)) {
            $owner = $owner->owner;
        }
        return $owner;
    }
}
