<?php
/**
 * datetime: 2023/5/15 0:36
 **/

namespace Sc\Util\HtmlStructure\Html;
use JetBrains\PhpStorm\Language;
use Sc\Util\HtmlElement\El;

/**
 * Html 页面css
 *
 * Class Css
 *
 * @package Sc\Util\HtmlStructure\Html
 * @date    2023/5/15
 */
class Css
{
    private array $loadCss = [];
    private array $css = [];

    /**
     * 加载css
     *
     * @param string $cssPath
     *
     * @date 2023/5/25
     */
    public function load(string $cssPath): void
    {
        in_array($cssPath, $this->loadCss) or $this->loadCss[] = $cssPath;
    }

    public function __toString(): string
    {
        return $this->toCode();
    }

    public function addCss(#[Language('CSS')] $css): void
    {
        $this->css[] = $css;
    }

    public function toCode()
    {
        $this->loadCss();

        return implode("\r\n", $this->css);
    }

    private function loadCss(): void
    {
        $head = Html::html()->find('head');
        foreach ($this->loadCss as $cssPath) {
            $head->append(El::single('link')->setAttr('rel', 'stylesheet')->setAttr('href', $cssPath));
        }
    }
}