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
    /**
     * 创建一个表单容器，后续通过 addFields()/addNodes() 继续挂载字段或结构节点。
     */
    public static function make(string $key): Form
    {
        return Form::make($key);
    }

    /**
     * 创建一个带标题的表单分组，默认会以卡片区块渲染。
     * 只影响视觉分组，不改变子字段的数据路径。
     * 子节点请继续链式调用 addNodes()。
     */
    public static function section(string $title): SectionNode
    {
        return SectionNode::make($title);
    }

    /**
     * 创建一个行内布局容器，内部字段会按横向方式排列并自动换行。
     * 只影响排版，不改变子字段的表单作用域。
     */
    public static function inline(FormNode ...$children): InlineNode
    {
        return InlineNode::make(...$children);
    }

    /**
     * 创建一个栅格布局容器，内部字段继续按各自 span 参与排版。
     * 只影响排版，不改变子字段的数据路径和校验逻辑。
     */
    public static function grid(FormNode ...$children): GridNode
    {
        return GridNode::make(...$children);
    }

    /**
     * 创建一个标签页布局容器，内部必须放 Forms::tab()。
     * 各页签里的字段仍然属于同一张表单，提交、校验、事件作用域都不分裂。
     */
    public static function tabs(TabPaneNode ...$tabs): TabsNode
    {
        return TabsNode::make(...$tabs);
    }

    /**
     * 创建一个标签页面板，只能作为 Forms::tabs() 的子节点使用。
     * 子节点请继续链式调用 addNodes()。
     */
    public static function tab(string $label): TabPaneNode
    {
        return TabPaneNode::make($label);
    }

    /**
     * 创建一个折叠面板容器，内部必须放 Forms::collapseItem()。
     * 折叠只影响显示状态，不改变子字段的数据路径。
     */
    public static function collapse(CollapseItemNode ...$items): CollapseNode
    {
        return CollapseNode::make(...$items);
    }

    /**
     * 创建一个折叠面板项，只能作为 Forms::collapse() 的子节点使用。
     * 子节点请继续链式调用 addNodes()。
     */
    public static function collapseItem(string $title): CollapseItemNode
    {
        return CollapseItemNode::make($title);
    }

    /**
     * 创建一个对象作用域节点，把子节点的数据路径切到指定对象下。
     * 例如 `Forms::object('profile', Fields::text('name'))` 最终字段路径会变成 `profile.name`。
     */
    public static function object(string $name, FormNode ...$children): FormObjectGroup
    {
        return FormObjectGroup::make($name, ...$children);
    }

    /**
     * 创建一个重复分组表单节点，适合“多组同结构数据”编辑。
     * 子字段路径会变成 `name.0.xxx`、`name.1.xxx`；新增/删除/排序会触发表单数组事件。
     */
    public static function arrayGroup(string $name, FormNode ...$children): FormArrayGroup
    {
        return FormArrayGroup::make($name, ...$children);
    }

    /**
     * 创建一个表格化数组编辑节点，适合行列式编辑多条数据。
     * 数据路径语义与 arrayGroup() 一致，只是渲染为表格 UI。
     */
    public static function table(string $name, FormNode ...$children): FormTable
    {
        return FormTable::make($name, ...$children);
    }

    /**
     * 在表单中插入自定义内容块，适合说明文案、提示块或轻组件树。
     * 若传入 Renderable，当前仅支持轻量 blocks/layouts/displays；
     * 其中绑定事件的轻组件在表单上下文里通常还能直接读取 `model`。
     */
    public static function custom(string|AbstractHtmlElement|Renderable $content): CustomNode
    {
        return CustomNode::make($content);
    }
}
