<?php

namespace Sc\Util\HtmlStructure;

use JetBrains\PhpStorm\ExpectedValues;
use Sc\Util\HtmlStructure\Theme\Interfaces\TagThemeInterface;
use Sc\Util\HtmlStructure\Theme\Theme;

/**
 * Class Tag
 */
class Tag
{
    private string $content;

    private array $attrs = [];


    private function __construct(string $content){
        $this->content = $content;
    }

    public static function create(string $content): Tag
    {
        return new Tag($content);
    }

    public function setAttrs(array $attrs): Tag
    {
        $this->attrs = [...$this->attrs, ...$attrs];
        return $this;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getAttrs(): array
    {
        return $this->attrs;
    }

    public function render(#[ExpectedValues(Theme::AVAILABLE_THEME)] string $theme = null)
    {
        return Theme::getRenderer(TagThemeInterface::class, $theme)->render($this);
    }
}