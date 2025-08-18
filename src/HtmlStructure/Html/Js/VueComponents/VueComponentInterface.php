<?php

namespace Sc\Util\HtmlStructure\Html\Js\VueComponents;

use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlStructure\Html\Js\Vue;

/**
 * Interface VueComponentInterface
 */
interface VueComponentInterface
{
    public function getName(): string;

    public function register(string $registerVar): string;

    public function getTemplate(): string|AbstractHtmlElement;

    public function getVue(): Vue;
}