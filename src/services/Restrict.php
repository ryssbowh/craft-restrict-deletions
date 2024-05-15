<?php

namespace Ryssbowh\RestrictDeletion\services;

use Ryssbowh\RestrictDeletion\RestrictDeletion;
use craft\base\Component;
use craft\base\Element;
use craft\commerce\elements\Product;
use craft\db\Query;
use craft\db\Table;
use craft\elements\Asset;
use craft\elements\Category;
use craft\elements\Entry;
use craft\elements\User;

class Restrict extends Component
{
    const POLICY_NONE = 'none';
    const POLICY_ALL = 'all';
    const POLICY_NO_DRAFTS = 'noDrafts';
    const POLICY_NO_REVISIONS = 'noRevisions';
    const POLICY_NO_DRAFTS_OR_REVISIONS = 'noRevisionsOrDrafts';

    /**
     * Can an entry be deleted
     * 
     * @param  Entry $entry
     * @return bool
     */
    public function canDeleteEntry(Entry $entry): bool
    {
        return $this->canDeleteElement($entry, $entry->section->uid);
    }

    /**
     * Can an user be deleted
     * 
     * @param  User $user
     * @return bool
     */
    public function canDeleteUser(User $user): bool
    {
        return $this->canDeleteElement($user, 'users');
    }

    /**
     * Can an asset be deleted
     * 
     * @param  Asset $asset
     * @return bool
     */
    public function canDeleteAsset(Asset $asset): bool
    {
        return $this->canDeleteElement($asset, $asset->volume->uid);
    }

    /**
     * Can an entry be deleted
     * 
     * @param  Category $category
     * @return bool
     */
    public function canDeleteCategory(Category $category): bool
    {
        return $this->canDeleteElement($category, $category->group->uid);
    }

    /**
     * Can a product be deleted
     * 
     * @param  Product $category
     * @return bool
     */
    public function canDeleteProduct(Product $product): bool
    {
        return $this->canDeleteElement($product, $product->type->uid);
    }

    /**
     * Can an element be deleted
     * 
     * @param  Element $element
     * @param  string  $policyUid
     * @return bool
     */
    protected function canDeleteElement(Element $element, string $policyUid): bool
    {
        $user = \Craft::$app->user;
        $settings = RestrictDeletion::$plugin->settings;
        if ($settings->disableForFrontRequests and \Craft::$app->request->getIsSiteRequest()) {
            return true;
        }
        if ($settings->disableForConsoleRequests and \Craft::$app->request->getIsConsoleRequest()) {
            return true;
        }
        if ($user->isAdmin) {
            if ($settings->adminCanOverride) {
                return true;
            }
        } else if ($user->checkPermission('ignoreDeletionRestriction:' . $policyUid)) {
            return true;
        }
        $policy = $settings->getPolicy($policyUid);
        return !$this->isRelated($element, $policy);
    }

    /**
     * Is an element related to other element according to a policy 
     * 
     * @param  Element $element
     * @param  string  $policy
     * @return bool
     */
    protected function isRelated(Element $element, string $policy): bool
    {
        if ($policy == self::POLICY_NONE) {
            return false;
        }
        $query = (new Query())
            ->select(['relations.id'])
            ->from(['relations' => Table::RELATIONS])
            ->leftJoin(['elements' => Table::ELEMENTS], '[[elements.id]] = [[relations.sourceId]]')
            ->where(['targetId' => $element->id]);
        if ($policy == self::POLICY_NO_REVISIONS or $policy == self::POLICY_NO_DRAFTS_OR_REVISIONS) {
            $query->andWhere('[[elements.revisionId]] is NULL');
        }
        if ($policy == self::POLICY_NO_DRAFTS or $policy == self::POLICY_NO_DRAFTS_OR_REVISIONS) {
            $query->andWhere('[[elements.draftId]] is NULL');
        }
        return $query->exists();
    }
}