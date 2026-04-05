<?php

namespace Sc\Util\HtmlStructureV2\Dsl;

use Sc\Util\HtmlStructureV2\Components\Block\Alert;
use Sc\Util\HtmlStructureV2\Components\Block\Button;
use Sc\Util\HtmlStructureV2\Components\Block\Divider;
use Sc\Util\HtmlStructureV2\Components\Block\Text;
use Sc\Util\HtmlStructureV2\Components\Block\Title;

final class Blocks
{
    public static function title(string $text): Title
    {
        return Title::make($text);
    }

    public static function divider(?string $text = null): Divider
    {
        return Divider::make($text);
    }

    public static function text(string $content): Text
    {
        return Text::make($content);
    }

    public static function alert(string $title, ?string $description = null): Alert
    {
        return Alert::make($title, $description);
    }

    public static function button(string $label): Button
    {
        return Button::make($label);
    }
}
