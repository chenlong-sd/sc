<?php

namespace Sc\Util\HtmlStructureV2\Page;

use Sc\Util\HtmlStructureV2\Components\Form;

final class FormPage extends AbstractPage
{
    private ?Form $form = null;

    /**
     * 设置页面主表单。
     */
    public function form(Form $form): self
    {
        $this->form = $form;

        return $this;
    }

    public function getForm(): ?Form
    {
        return $this->form;
    }
}
