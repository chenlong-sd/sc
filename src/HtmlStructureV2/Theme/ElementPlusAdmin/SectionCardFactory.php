<?php

namespace Sc\Util\HtmlStructureV2\Theme\ElementPlusAdmin;

use Sc\Util\HtmlElement\El;
use Sc\Util\HtmlElement\ElementType\DoubleLabel;

final class SectionCardFactory
{
    public function make(string $title = ''): DoubleLabel
    {
        $card = El::double('el-card')->addClass('sc-v2-section');

        if ($title !== '') {
            $card->append(
                El::double('template')->setAttr('#header')->append(
                    El::double('div')->addClass('sc-v2-section__header')->append($title)
                )
            );
        }

        return $card;
    }
}
