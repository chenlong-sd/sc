<?php

namespace Sc\Util\HtmlStructureV2\Dsl;

use Sc\Util\HtmlStructureV2\Components\Form;
use Sc\Util\HtmlStructureV2\Components\FormNodes\CollapseItemNode;
use Sc\Util\HtmlStructureV2\Components\FormNodes\CollapseNode;
use Sc\Util\HtmlStructureV2\Components\FormNodes\FormArrayGroup;
use Sc\Util\HtmlStructureV2\Components\FormNodes\FormObjectGroup;
use Sc\Util\HtmlStructureV2\Components\FormNodes\FormTable;
use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlStructureV2\Components\FormNodes\CustomNode;
use Sc\Util\HtmlStructureV2\Components\FormNodes\GridNode;
use Sc\Util\HtmlStructureV2\Components\FormNodes\InlineNode;
use Sc\Util\HtmlStructureV2\Components\FormNodes\SectionNode;
use Sc\Util\HtmlStructureV2\Components\FormNodes\TabPaneNode;
use Sc\Util\HtmlStructureV2\Components\FormNodes\TabsNode;
use Sc\Util\HtmlStructureV2\Contracts\FormNode;
use Sc\Util\HtmlStructureV2\Contracts\Renderable;

final class Forms
{
    public static function make(string $key): Form
    {
        return Form::make($key);
    }

    public static function section(string $title, FormNode ...$children): SectionNode
    {
        return SectionNode::make($title)->addChildren(...$children);
    }

    public static function inline(FormNode ...$children): InlineNode
    {
        return InlineNode::make(...$children);
    }

    public static function grid(FormNode ...$children): GridNode
    {
        return GridNode::make(...$children);
    }

    public static function tabs(TabPaneNode ...$tabs): TabsNode
    {
        return TabsNode::make(...$tabs);
    }

    public static function tab(string $label, FormNode ...$children): TabPaneNode
    {
        return TabPaneNode::make($label, ...$children);
    }

    public static function collapse(CollapseItemNode ...$items): CollapseNode
    {
        return CollapseNode::make(...$items);
    }

    public static function collapseItem(string $title, FormNode ...$children): CollapseItemNode
    {
        return CollapseItemNode::make($title, ...$children);
    }

    public static function object(string $name, FormNode ...$children): FormObjectGroup
    {
        return FormObjectGroup::make($name, ...$children);
    }

    public static function arrayGroup(string $name, FormNode ...$children): FormArrayGroup
    {
        return FormArrayGroup::make($name, ...$children);
    }

    public static function table(string $name, FormNode ...$children): FormTable
    {
        return FormTable::make($name, ...$children);
    }

    public static function custom(string|AbstractHtmlElement|Renderable $content): CustomNode
    {
        return CustomNode::make($content);
    }
}
