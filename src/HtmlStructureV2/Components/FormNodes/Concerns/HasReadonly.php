<?php

namespace Sc\Util\HtmlStructureV2\Components\FormNodes\Concerns;

trait HasReadonly
{
    private bool $readonly = false;

    /**
     * 把当前结构节点及其子树切为只读模式。
     * 会向下作用到内部字段和数组/表格编辑入口；不影响节点自身的布局外壳。
     *
     * @param bool $readonly 是否只读，默认值为 true。
     * @return static 当前节点实例。
     *
     * 示例：
     * `Forms::section('基础信息')->readonly()`
     */
    public function readonly(bool $readonly = true): static
    {
        $this->readonly = $readonly;

        return $this;
    }

    public function isReadonly(): bool
    {
        return $this->readonly;
    }
}
