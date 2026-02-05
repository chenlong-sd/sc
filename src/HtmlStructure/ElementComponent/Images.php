<?php

namespace Sc\Util\HtmlStructure\ElementComponent;

use Sc\Util\HtmlElement\El;

class Images
{
    private array $attrs = [];
    private array $slot = [];

    public function __construct(private readonly array|string $images, private readonly string $srcPath = 'url'){}

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
        if (is_array($this->images)) {
            $el = El::fictitious();
            foreach ($this->images as $src) {
                $image = h('el-image', ['fit' => 'cover', 'hide-on-click-modal' => ''])->appendStyle("{height: 90px; width: 90px;border-radius: 3px}");
                $image->setAttr('src', $src);
                $style = $this->attrs['style'] ?? '';
                unset($this->attrs['style']);
                $image->setAttrs($this->attrs)->appendStyle($style);

                if (!$image->getAttr('preview-src-list') && !$image->getAttr(':preview-src-list')) {
                    $image->setAttr(':preview-src-list', [$src]);
                }

                foreach ($this->slot as $slotName => $content) {
                    $image->append(
                        h('template', $content, ['#' . $slotName => ''])
                    );
                }
                $el->append(
                    h('div', $image)->setStyle('{display: inline-block; height: 90px; width: 90px; margin: 0 5px;}')
                );
            }
        }else{
            $image = h('el-image', ['fit' => 'cover', 'hide-on-click-modal' => ''])->appendStyle("{height: 90px; width: 90px;border-radius: 3px}");
            $image->setAttr(':src', $this->srcPath ? "item.$this->srcPath" : 'item');
            $style = $this->attrs['style'] ?? '';
            unset($this->attrs['style']);
            $image->setAttrs($this->attrs)->appendStyle($style);

            if (!$image->getAttr('preview-src-list') && !$image->getAttr(':preview-src-list')) {
                $image->setAttr(':preview-src-list', $this->srcPath
                    ? "$this->images.map(v => v.$this->srcPath)"
                    : $this->images
                )->setAttr(':initial-index', 'index');
            }

            foreach ($this->slot as $slotName => $content) {
                $image->append(
                    h('template', $content, ['#' . $slotName => ''])
                );
            }

            $el = h('div', $image, ['v-for' => "(item, index) in $this->images"])->setStyle('{display: inline-block; height: 90px; width: 90px; margin: 0 5px;}');
        }

        return $el;
    }

    public function __toString(): string
    {
        return $this->render();
    }
}