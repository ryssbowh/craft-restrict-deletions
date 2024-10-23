<?php

namespace Ryssbowh\RestrictDeletion\events;

use yii\base\Event;

class BeforeRenderSidebar extends Event
{
    public array $elements;

    public string $template = 'restrict-deletion/sidebar';
}