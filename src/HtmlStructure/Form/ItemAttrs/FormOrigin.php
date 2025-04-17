<?php
/**
 * datetime: 2023/6/4 12:22
 **/

namespace Sc\Util\HtmlStructure\Form\ItemAttrs;

use Sc\Util\HtmlStructure\Form;

trait FormOrigin
{
    protected Form $form;
    protected string $formModel;

    public function setForm(Form $form): static
    {
        $this->form = $form;

        // 由于一般调用此函数时元素已经定好，所以可以直接调用这个
        $this->childrenFormSet('setForm', $this->form);
        $this->setFormModel($this->form->getId());

        return $this;
    }

    public function setFormModel(string $formModel): static
    {
        $this->formModel = $formModel;

        // 由于一般调用此函数时元素已经定好，所以可以直接调用这个
        $this->childrenFormSet('setFormModel', $this->formModel);

        return $this;
    }

    /**
     * 给子元素设置
     *
     * @param string $method
     * @param        $param
     *
     * @return void
     */
    protected function childrenFormSet(string $method, $param): void
    {
        if (!property_exists($this, 'children')) {
            return;
        }

        foreach ($this->children as $child) {
            method_exists($child, $method) and $child->$method($param);
        }
    }
}