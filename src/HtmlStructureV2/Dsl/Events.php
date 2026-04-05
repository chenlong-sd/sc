<?php

namespace Sc\Util\HtmlStructureV2\Dsl;

use Sc\Util\HtmlStructureV2\Components\Dialog;
use Sc\Util\HtmlStructureV2\Components\ListWidget;
use Sc\Util\HtmlStructureV2\Components\Table;
use Sc\Util\HtmlStructureV2\Support\JsExpression;
use Sc\Util\HtmlStructureV2\Support\StructuredEvent;

final class Events
{
    public static function openUrl(string|JsExpression $url, array|JsExpression $query = []): StructuredEvent
    {
        return StructuredEvent::openUrl($url, $query);
    }

    public static function openDialog(string|Dialog $dialog): StructuredEvent
    {
        return StructuredEvent::openDialog($dialog);
    }

    public static function closeDialog(string|Dialog $dialog): StructuredEvent
    {
        return StructuredEvent::closeDialog($dialog);
    }

    public static function reloadTable(string|Table|null $table = null): StructuredEvent
    {
        return StructuredEvent::reloadTable($table);
    }

    public static function reloadList(string|ListWidget|null $list = null): StructuredEvent
    {
        return StructuredEvent::reloadList($list);
    }

    public static function reloadPage(): StructuredEvent
    {
        return StructuredEvent::reloadPage();
    }

    public static function message(string|JsExpression $message, string $type = 'info'): StructuredEvent
    {
        return StructuredEvent::message($message, $type);
    }

    public static function request(
        string $url,
        string $method = 'post',
        array|JsExpression $query = []
    ): StructuredEvent {
        return StructuredEvent::request($url, $method, $query);
    }
}
