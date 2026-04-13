<?php

namespace Sc\Util\HtmlStructureV2\Support;

use Sc\Util\HtmlElement\El;
use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlElement\ElementType\DoubleLabel;
use Sc\Util\HtmlStructureV2\Contracts\DocumentRenderable;

final class Document implements DocumentRenderable
{
    private const BASE_CODE = <<<HTML
    <!DOCTYPE html>
    <html lang="zh-cn">
    <head>
      <meta charset="UTF-8">
      <meta name="viewport" content="width=device-width, initial-scale=1.0">
      <title></title>
    </head>
    <body>
      <div id="app"></div>
    </body>
    </html>
    HTML;

    private AbstractHtmlElement $root;
    private AssetBag $assets;
    private bool $finalized = false;

    public function __construct(string $title = '')
    {
        $this->root = El::fromCode(self::BASE_CODE);
        $this->assets = new AssetBag();
        $this->title($title);
    }

    public function title(string $title): self
    {
        $this->head()->find('title')?->setChildren([El::text($title)]);

        return $this;
    }

    public function assets(): AssetBag
    {
        return $this->assets;
    }

    public function head(): DoubleLabel
    {
        return $this->root->find('head');
    }

    public function body(): DoubleLabel
    {
        return $this->root->find('body');
    }

    public function mount(AbstractHtmlElement|string ...$elements): self
    {
        $app = $this->root->find('#app');
        $app?->append(...$elements);
        $app?->setAttr('v-cloak');

        return $this;
    }

    public function appendToBody(AbstractHtmlElement|string ...$elements): self
    {
        $this->body()->append(...$elements);

        return $this;
    }

    public function toHtml(): string
    {
        $this->finalize();

        return trim(El::fictitious()->append(El::text('<!DOCTYPE html>'), $this->root)->toHtml());
    }

    private function finalize(): void
    {
        if ($this->finalized) {
            return;
        }

        foreach ($this->assets->stylesheets() as $href) {
            $this->head()->append(
                El::single('link')->setAttrs([
                    'rel' => 'stylesheet',
                    'href' => $href,
                ])
            );
        }

        if ($css = implode("\n", $this->assets->inlineStyles())) {
            $this->head()->append(
                El::double('style')->text($css)
            );
        }

        foreach ($this->assets->scripts() as $src) {
            $this->body()->append(
                El::double('script')->setAttr('src', $src)
            );
        }

        foreach ($this->assets->inlineScripts() as $script) {
            $this->body()->append(
                El::double('script')->text($script)
            );
        }

        $this->root->find('html')?->eachChildren(function (AbstractHtmlElement $element) {
            $element->setRetraction(0);
        });

        $this->finalized = true;
    }
}
