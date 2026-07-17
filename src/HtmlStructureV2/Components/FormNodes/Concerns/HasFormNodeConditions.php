<?php

namespace Sc\Util\HtmlStructureV2\Components\FormNodes\Concerns;

use Sc\Util\HtmlStructureV2\Support\JsExpression;

trait HasFormNodeConditions
{
    private bool $visible = true;
    private bool $disabled = false;
    private ?JsExpression $visibleWhen = null;
    private ?JsExpression $disabledWhen = null;
    private ?JsExpression $readonlyWhen = null;

    /**
     * 控制当前结构节点是否在 PHP 层生效。
     *
     * 不可见时节点及其子树不会参与渲染和表单 schema 收集。
     */
    public function visible(bool $visible = true): static
    {
        $this->visible = $visible;

        return $this;
    }

    /**
     * 禁用当前结构节点子树。
     */
    public function disabled(bool $disabled = true): static
    {
        $this->disabled = $disabled;

        return $this;
    }

    /**
     * 按前端表达式控制当前结构节点显示。
     *
     * 表达式会按原样作为前端 JS 表达式注入，不会再包裹引号。
     * 在 object/arrayGroup/table 等子作用域中，`model` 会自动切到当前子模型。
     * 当前默认可用变量包括：
     * - `model`：当前结构节点所在的局部模型。
     *   顶层节点时通常就是整个表单；object 分组里是当前对象；arrayGroup/table 行内通常是当前行对象。
     * - `form`：当前表单根模型，适合跨分组、跨行读取其它字段。
     * - `state`：当前页面运行时 state。
     *   包含 `Pages::state()` 写入的数据，也包含 `Forms::state()` 写入的数据；
     *   表单级 state 通常挂在 `state.forms[scope]` 下。
     * - `pageState`：`state` 的语义化别名，当前实现里两者指向同一份对象。
     * - `scope`：当前表单 scope / key，例如 `article-form`、`dialog:detail`。
     *   若当前上下文没有显式 scope，可能为 `null`；它不是 Element Plus 表格插槽的 `scope`。
     * - `dialogRow`：当结构节点渲染在弹窗 body/footer 内时可用，表示打开弹窗的当前表格行数据。
     *   它不属于表单 `model`，不会随表单提交；适合只用于显示、禁用、只读条件判断。
     * - `fieldName`：当前节点在表单中的路径。
     *   object/arrayGroup/table 节点通常有路径；grid/section/tabs 等纯布局节点通常为 `null`。
     * - `vm`：当前页面根 Vue 实例 / runtime 宿主对象。
     *   可用于调用公开 runtime 方法，但纯条件判断通常优先使用前面的结构化变量。
     * - `options`：当前路径对应字段已解析完成的选项数组。
     *   对纯结构节点通常是空数组。
     * - `fieldConfig`：当前路径对应字段的运行时配置对象。
     *   对纯结构节点通常为 `{}`。
     * - `optionLoading`：当前路径对应字段选项是否正在加载。
     *   对纯结构节点通常为 `false`。
     * - `optionLoaded`：当前路径对应字段选项是否至少完成过一次加载/写入。
     *   对纯结构节点通常为 `false`。
     * - `field`：当前结构节点的静态元信息快照。
     *   结构节点通常包含 `node`、`visible`、`disabled`、`readonly` 等状态。
     * - `props`：`field.props` 的快捷别名；结构节点通常为 `{}`。
     *
     * 在 `Forms::table()` 的条件表达式里读取当前行时，优先使用 `model.xxx`；
     * `scope.row` / `scope.$index` 只适合表格单元格自定义模板内容本身。
     * 行操作弹窗表单里若只需要读取来源表格行而不提交该值，使用 `dialogRow.xxx`。
     *
     * @param string|JsExpression $expression 前端可执行表达式。
     * @return static 当前结构节点实例。
     *
     * 示例：
     * - `Forms::grid()->visibleWhen('model.type === "advanced"')`
     * - `Forms::custom('提示')->visibleWhen('model.enabled === true')`
     */
    public function visibleWhen(string|JsExpression $expression): static
    {
        $this->visibleWhen = $expression instanceof JsExpression
            ? $expression
            : JsExpression::make($expression);

        return $this;
    }

    /**
     * 按前端表达式禁用当前结构节点子树。
     * 表达式上下文与 visibleWhen() 一致，详细变量说明见 visibleWhen() 注释。
     *
     * @param string|JsExpression $expression 前端可执行表达式。
     * @return static 当前结构节点实例。
     *
     * 示例：
     * - `Forms::section('高级设置')->disabledWhen('model.locked === true')`
     */
    public function disabledWhen(string|JsExpression $expression): static
    {
        $this->disabledWhen = $expression instanceof JsExpression
            ? $expression
            : JsExpression::make($expression);

        return $this;
    }

    /**
     * 按前端表达式将当前结构节点子树切为只读。
     * 表达式上下文与 visibleWhen() 一致，详细变量说明见 visibleWhen() 注释。
     *
     * @param string|JsExpression $expression 前端可执行表达式。
     * @return static 当前结构节点实例。
     *
     * 示例：
     * - `Forms::object('profile')->readonlyWhen('form.mode === "view"')`
     */
    public function readonlyWhen(string|JsExpression $expression): static
    {
        $this->readonlyWhen = $expression instanceof JsExpression
            ? $expression
            : JsExpression::make($expression);

        return $this;
    }

    public function isVisible(): bool
    {
        return $this->visible;
    }

    public function isDisabled(): bool
    {
        return $this->disabled;
    }

    public function getVisibleWhen(): ?JsExpression
    {
        return $this->visibleWhen;
    }

    public function getDisabledWhen(): ?JsExpression
    {
        return $this->disabledWhen;
    }

    public function getReadonlyWhen(): ?JsExpression
    {
        return $this->readonlyWhen;
    }
}
