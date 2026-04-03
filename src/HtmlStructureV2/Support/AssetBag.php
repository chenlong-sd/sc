<?php

namespace Sc\Util\HtmlStructureV2\Support;

final class AssetBag
{
    private array $stylesheets = [];
    private array $scripts = [];
    private array $inlineStyles = [];
    private array $inlineScripts = [];

    public function addStylesheet(string $href): void
    {
        in_array($href, $this->stylesheets, true) || $this->stylesheets[] = $href;
    }

    public function addScript(string $src): void
    {
        in_array($src, $this->scripts, true) || $this->scripts[] = $src;
    }

    public function addInlineStyle(string $css): void
    {
        in_array($css, $this->inlineStyles, true) || $this->inlineStyles[] = $css;
    }

    public function addInlineScript(string $script): void
    {
        in_array($script, $this->inlineScripts, true) || $this->inlineScripts[] = $script;
    }

    public function stylesheets(): array
    {
        return $this->stylesheets;
    }

    public function scripts(): array
    {
        return $this->scripts;
    }

    public function inlineStyles(): array
    {
        return $this->inlineStyles;
    }

    public function inlineScripts(): array
    {
        return $this->inlineScripts;
    }
}
