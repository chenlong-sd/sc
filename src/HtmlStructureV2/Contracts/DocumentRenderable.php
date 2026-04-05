<?php

namespace Sc\Util\HtmlStructureV2\Contracts;

interface DocumentRenderable
{
    public function toHtml(): string;
}
