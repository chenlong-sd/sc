<?php

namespace Sc\Util\HtmlStructure\Form;

use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlStructure\Form\ItemAttrs\Attrs;
use Sc\Util\HtmlStructure\Form\ItemAttrs\DefaultConstruct;
use Sc\Util\HtmlStructure\Form\ItemAttrs\DefaultValue;
use Sc\Util\HtmlStructure\Form\ItemAttrs\Events;
use Sc\Util\HtmlStructure\Form\ItemAttrs\FormOrigin;
use Sc\Util\HtmlStructure\Form\ItemAttrs\LabelWidth;
use Sc\Util\HtmlStructure\Form\ItemAttrs\UploadUrl;
use Sc\Util\HtmlStructure\Theme\Interfaces\FormItemUploadThemeInterface;
use Sc\Util\HtmlStructure\Theme\Theme;

/**
 * Class FormItemUpload
 */
class FormItemUpload extends AbstractFormItem implements FormItemInterface
{
    const UPLOAD_TYPE_FILE = "file";
    const UPLOAD_TYPE_FILES = "files";
    const UPLOAD_TYPE_IMAGE = "image";
    const UPLOAD_TYPE_IMAGES = "images";

    use DefaultConstruct, DefaultValue, UploadUrl, Events, Attrs, FormOrigin, LabelWidth;

    protected string|AbstractHtmlElement $uploadEl = "选择文件";
    protected string $uploadType = self::UPLOAD_TYPE_FILE;

    public function render(string $theme = null): AbstractHtmlElement
    {
        $this->default = $this->default !== null ? $this->default : [];

        $el = Theme::getRender(FormItemUploadThemeInterface::class)->render($this);

        return $this->ExecuteBeforeRendering($el);
    }

    /**
     * 上传多个
     *
     * @return FormItemUpload
     */
    public function multiple(): FormItemUpload
    {
        $this->uploadType = self::UPLOAD_TYPE_FILES;

        return $this->setVAttrs('multiple');
    }

    /**
     * 上传元素/文本
     *
     * @param string|AbstractHtmlElement $element
     *
     * @return $this
     */
    public function uploadEl(string|AbstractHtmlElement $element): static
    {
        $this->uploadEl = $element;

        return $this;
    }

    /**
     * 图片上传
     *
     * @param bool $isMultiple 是否是多图
     *
     * @return FormItemUpload
     */
    public function toImage(bool $isMultiple = false): static
    {
        $this->uploadType = $isMultiple ? self::UPLOAD_TYPE_IMAGES : self::UPLOAD_TYPE_IMAGE;

        if (!$isMultiple) {
            $this->default = $this->default !== null ? $this->default : '';
        }

        return $this;
    }
}