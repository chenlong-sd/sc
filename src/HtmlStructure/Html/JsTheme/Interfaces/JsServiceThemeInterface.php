<?php

namespace Sc\Util\HtmlStructure\Html\JsTheme\Interfaces;

use Sc\Util\HtmlStructure\Html\Js\JsService;

/**
 * Interface JsServiceThemeInterface
 */
interface JsServiceThemeInterface
{
    public function __construct(JsService $jsService);

    public function message(): string;

    public function confirm(): string;

    public function loading(): string;

    public function prompt(): string;
}