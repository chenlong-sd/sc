<?php
/**
 * datetime: 2023/6/7 0:47
 **/

namespace Sc\Util\HtmlStructure\Form\ItemAttrs;

trait DefaultConstruct
{
    public function __construct(protected ?string $name = null, protected ?string $label = null) { }

}