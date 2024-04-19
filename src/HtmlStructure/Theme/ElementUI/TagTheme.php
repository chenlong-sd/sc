<?php

namespace Sc\Util\HtmlStructure\Theme\ElementUI;

use Sc\Util\HtmlElement\El;
use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlStructure\Tag;
use Sc\Util\HtmlStructure\Theme\Interfaces\TagThemeInterface;

/**
 * Class TagTheme
 */
class TagTheme implements TagThemeInterface
{

    public function render(Tag $tag): AbstractHtmlElement
    {
        $attrs = $tag->getAttrs();
        return El::double('el-tag')
            ->setAttrs($attrs)
            ->setAttr('size', 'small')
            ->setAttr('effect', $attrs['theme'] ?? null)
            ->addClass('ml-2')
            ->append($tag->getContent());
    }
}