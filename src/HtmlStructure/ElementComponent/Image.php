<?php

namespace Sc\Util\HtmlStructure\ElementComponent;

class Image
{
    private array $attrs = [];
    private array $slot = [];

    public function __construct(private readonly string $src, private readonly bool $isRealPath = false){}

    /**
     *
     * @link https://element-plus.org/zh-CN/component/image.html#image-api
     * @param array $attrs
     * @return $this
     */
    public function setAttrs(array $attrs): static
    {
        $this->attrs = array_merge($this->attrs, $attrs);
        return $this;
    }

    /**
     *
     * @link https://element-plus.org/zh-CN/component/image.html#image-slots
     * @param string $slotName
     * @param string $content
     * @return $this
     */
    public function slot(string $slotName, string $content): static
    {
        $this->slot[$slotName] = $content;
        return $this;
    }
    
    public function render(): string
    {
        $image = h('el-image', ['fit' => 'cover', 'hide-on-click-modal' => ''])->appendStyle("{height: 90px; width: 90px;border-radius: 3px}");
        $image->setAttr($this->isRealPath ? "src" : ':src', $this->src);
        $style = $this->attrs['style'] ?? '';
        unset($this->attrs['style']);
        $image->setAttrs($this->attrs)->appendStyle($style);
        $image->setAttrs($this->attrs);

        if (!$image->getAttr('preview-src-list') && !$image->getAttr(':preview-src-list')) {
            $image->setAttr(':preview-src-list', "[$this->src]");
        }

        foreach ($this->slot as $slotName => $content) {
            $image->append(
                h('template', $content, ['#' . $slotName => ''])
            );
        }

        return h('div', $image)->setStyle('{display: inline-block; height: 90px; width: 90px; margin: 0 5px;}');
    }

    public function __toString(): string
    {
        return $this->render();
    }
}