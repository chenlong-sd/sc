<?php

namespace Sc\Util\HtmlStructure\Html\Js;

use Sc\Util\HtmlStructure\Html\Js;

/**
 * Class JsSwitch
 */
class JsSwitch
{
    private array $case = [];
    private ?string $default = null;

    public function __construct(private readonly string $param)
    {

    }

    public function case(mixed $value, string|\Stringable $handleCode = null): static
    {
        if (str_starts_with($value, '@')) {
            $value = Js::grammar($value);
        }
        $this->case[$value] = $handleCode;

        return $this;
    }

    public function default(string|\Stringable $handleCode): static
    {
        $this->default = $handleCode;

        return $this;
    }

    public function __toString()
    {
        return $this->toCode();
    }

    public function toCode(): string
    {
        $switch = Js::code("switch({$this->param}){");

        $switch->then(implode('', array_map(function ($value, $handleCode) {
            if (is_string($value)) {
                $value = "'{$value}'";
            }
            if ($handleCode) {
                return "case {$value}:\n{$handleCode};break;";
            }
            return "case {$value}:\n";
        }, array_keys($this->case), $this->case)));

        $switch->then($this->default ? "default:\n{$this->default};" : '');

        return $switch->then("}")->toCode();
    }
}