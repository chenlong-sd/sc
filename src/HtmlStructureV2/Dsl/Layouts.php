<?php

namespace Sc\Util\HtmlStructureV2\Dsl;

use Sc\Util\HtmlStructureV2\Components\Layout\Card;
use Sc\Util\HtmlStructureV2\Components\Layout\Grid;
use Sc\Util\HtmlStructureV2\Components\Layout\Stack;
use Sc\Util\HtmlStructureV2\Contracts\Renderable;

final class Layouts
{
    public static function stack(Renderable ...$children): Stack
    {
        return Stack::make(...$children);
    }

    public static function grid(Renderable ...$items): Grid
    {
        return Grid::make(...$items);
    }

    public static function card(?string $title = null, Renderable ...$children): Card
    {
        return Card::make($title, ...$children);
    }
}
