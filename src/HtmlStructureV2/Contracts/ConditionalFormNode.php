<?php

namespace Sc\Util\HtmlStructureV2\Contracts;

use Sc\Util\HtmlStructureV2\Support\JsExpression;

interface ConditionalFormNode
{
    public function isVisible(): bool;

    public function isDisabled(): bool;

    public function getVisibleWhen(): ?JsExpression;

    public function getDisabledWhen(): ?JsExpression;

    public function getReadonlyWhen(): ?JsExpression;
}
