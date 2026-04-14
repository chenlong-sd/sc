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
use Sc\Util\HtmlStructureV2\Contracts\Renderable;

final class Forms
{
    /**
     * 创建一个表单容器，后续通过 addFields()/addContent() 继续挂载字段或结构节点。
     *
     * @param string $key 表单唯一 key。
     * @return Form 表单实例。
     *
     * 示例：
     * `Forms::make('qa-info-form')->addFields(Fields::text('title', '标题'))`
     */
    public static function make(string $key): Form
    {
        return Form::make($key);
    }

    /**
     * 创建一个带标题的表单分组，默认会以卡片区块渲染。
     * 只影响视觉分组，不改变子字段的数据路径。
     *
     * @param string $title 分组标题。
     * @return SectionNode 分组节点实例。
     *
     * 示例：
     * `Forms::section('基础信息')->addContent(Fields::text('title', '标题'))`
     */
    public static function section(string $title): SectionNode
    {
        return SectionNode::make($title);
    }

    /**
     * 创建一个行内布局容器，内部字段会按横向方式排列并自动换行。
     * 只影响排版，不改变子字段的表单作用域。
     *
     * @return InlineNode 行内布局节点实例。
     *
     * 示例：
     * `Forms::inline()->addItems(Fields::text('keyword', '关键词')->span(8))`
     */
    public static function inline(): InlineNode
    {
        return InlineNode::make();
    }

    /**
     * 创建一个栅格布局容器，内部字段继续按各自 span 参与排版。
     * 只影响排版，不改变子字段的数据路径和校验逻辑。
     *
     * @return GridNode 栅格布局节点实例。
     *
     * 示例：
     * `Forms::grid()->addItems(Fields::text('title', '标题')->span(12), Fields::text('code', '编码')->span(12))`
     */
    public static function grid(): GridNode
    {
        return GridNode::make();
    }

    /**
     * 创建一个标签页布局容器，内部必须放 Forms::tab()。
     * 各页签里的字段仍然属于同一张表单，提交、校验、事件作用域都不分裂。
     * 子页签请继续链式调用 `addTabs()`。
     *
     * @return TabsNode 标签页容器实例。
     *
     * 示例：
     * `Forms::tabs()->addTabs(Forms::tab('基础信息'), Forms::tab('扩展信息'))`
     */
    public static function tabs(): TabsNode
    {
        return TabsNode::make();
    }

    /**
     * 创建一个标签页面板，只能作为 Forms::tabs() 的子节点使用。
     *
     * @param string $label 标签名称。
     * @return TabPaneNode 标签面板实例。
     *
     * 示例：
     * `Forms::tab('基础信息')->addContent(Fields::text('title', '标题'))`
     */
    public static function tab(string $label): TabPaneNode
    {
        return TabPaneNode::make($label);
    }

    /**
     * 创建一个折叠面板容器，内部必须放 Forms::collapseItem()。
     * 折叠只影响显示状态，不改变子字段的数据路径。
     * 折叠项请继续链式调用 `addItems()`。
     *
     * @return CollapseNode 折叠容器实例。
     *
     * 示例：
     * `Forms::collapse()->addItems(Forms::collapseItem('高级设置'))`
     */
    public static function collapse(): CollapseNode
    {
        return CollapseNode::make();
    }

    /**
     * 创建一个折叠面板项，只能作为 Forms::collapse() 的子节点使用。
     *
     * @param string $title 折叠项标题。
     * @return CollapseItemNode 折叠项实例。
     *
     * 示例：
     * `Forms::collapseItem('高级设置')->addContent(Fields::text('remark', '备注'))`
     */
    public static function collapseItem(string $title): CollapseItemNode
    {
        return CollapseItemNode::make($title);
    }

    /**
     * 创建一个对象作用域节点，把子节点的数据路径切到指定对象下。
     * 例如 `Forms::object('profile')->addSchema(Fields::text('name'))`
     * 最终字段路径会变成 `profile.name`。
     *
     * @param string $name 对象字段名。
     * @return FormObjectGroup 对象作用域节点实例。
     *
     * 示例：
     * `Forms::object('profile')->addSchema(Fields::text('name', '姓名'))`
     */
    public static function object(string $name): FormObjectGroup
    {
        return FormObjectGroup::make($name);
    }

    /**
     * 创建一个重复分组表单节点，适合“多组同结构数据”编辑。
     * 子字段路径会变成 `name.0.xxx`、`name.1.xxx`；新增/删除/排序会触发表单数组事件。
     *
     * @param string $name 数组字段名。
     * @return FormArrayGroup 数组分组节点实例。
     *
     * 示例：
     * `Forms::arrayGroup('contacts')->addSchema(Fields::text('name', '姓名'))`
     */
    public static function arrayGroup(string $name): FormArrayGroup
    {
        return FormArrayGroup::make($name);
    }

    /**
     * 创建一个表格化数组编辑节点，适合行列式编辑多条数据。
     * 数据路径语义与 arrayGroup() 一致，只是渲染为表格 UI。
     *
     * @param string $name 数组字段名。
     * @return FormTable 表格化数组节点实例。
     *
     * 示例：
     * `Forms::table('items')->addColumns(Fields::text('name', '名称'), Fields::number('qty', '数量'))`
     */
    public static function table(string $name): FormTable
    {
        return FormTable::make($name);
    }

    /**
     * 在表单中插入自定义内容块，适合说明文案、提示块或轻组件树。
     * 若传入 Renderable，当前仅支持轻量 blocks/layouts/displays；
     * 其中绑定事件的轻组件在表单上下文里通常还能直接读取 `model`。
     *
     * @param string|AbstractHtmlElement|Renderable $content 自定义内容。
     * @return CustomNode 自定义内容节点实例。
     *
     * 示例：
     * `Forms::custom('<div class="help-text">请先保存基础信息</div>')`
     */
    public static function custom(string|AbstractHtmlElement|Renderable $content): CustomNode
    {
        return CustomNode::make($content);
    }
}
