<?php

namespace Sc\Util\HtmlStructureV2\Components\FormNodes\Concerns;

trait HasFormNodeNoSubmit
{
    private bool $noSubmit = false;

    /**
     * 控制当前结构节点及其子树是否参与“提交态 payload”。
     * 开启后内部字段仍会参与渲染、表单 schema、默认值、校验和运行时 model；
     * 只是通过 submitForm()/payloadFromForm()/__SC_V2_PAGE__.submit()/cloneFormModel()
     * 这类提交态读取时会被自动剔除。列表筛选 query 构建时也会跳过对应子树。
     *
     * @param bool $noSubmit 是否在提交态 payload 中排除，默认值为 true。
     * @return static 当前节点实例。
     *
     * 示例：
     * - `Forms::section('预览信息')->noSubmit()`
     */
    public function noSubmit(bool $noSubmit = true): static
    {
        $this->noSubmit = $noSubmit;

        return $this;
    }

    public function isNoSubmit(): bool
    {
        return $this->noSubmit;
    }
}
