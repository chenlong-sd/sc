<?php

namespace Sc\Util\HtmlStructureV2\Contracts;

use Sc\Util\HtmlStructureV2\Support\JsExpression;

interface EventAware
{
    /**
     * @return array<string, array<int, JsExpression|StructuredEventInterface>>
     */
    public function getEventHandlers(?string $event = null): array;

    public function getFirstEventHandler(string $event): JsExpression|StructuredEventInterface|null;

    public function hasEventHandlers(?string $event = null): bool;
}
