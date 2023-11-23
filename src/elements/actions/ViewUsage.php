<?php

namespace Ryssbowh\RestrictDeletion\elements\actions;

use Craft;
use craft\base\ElementAction;

/**
 * View Usage element action
 *
 * @since 2.2.0
 */
class ViewUsage extends ElementAction
{
    public static function displayName(): string
    {
        return Craft::t('restrict-deletion', 'View Usage');
    }

    public function getTriggerHtml(): ?string
    {
        Craft::$app->getView()->registerJsWithVars(fn ($type) => <<<JS
            (() => {
                new Craft.ElementActionTrigger({
                    type: $type,
                    bulk: false,
                    activate: (selectedItems) => {
                        let types = {
                            "craft\\\\elements\\\\Asset": 'asset',
                            "craft\\\\elements\\\\Entry": 'entry',
                            "craft\\\\elements\\\\User": 'user',
                            "craft\\\\elements\\\\Category": 'category',
                            "craft\\\\commerce\\\\elements\\\\Product": 'product',
                        }
                        let data = {};
                        let element = $(selectedItems[0]).find('.element');
                        data[types[element.data('type')]] = $(selectedItems[0]).data('id');
                        window.open(Craft.getCpUrl('utilities/element-usage', data));
                    },
                });
            })();
        JS, [static::class]);
        return null;
    }
}
