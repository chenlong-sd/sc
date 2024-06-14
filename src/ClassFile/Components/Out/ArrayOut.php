<?php

namespace Sc\Util\ClassFile\Components\Out;

/**
 * Class ArrayOut
 */
class ArrayOut
{
    public function __construct(private readonly array $array)
    {
    }

    public function outCode(int $indentation = 4): string
    {
        if (array_is_list($this->array)) {
            return $this->listOut($indentation);
        }

        return $this->mapOut($indentation);
    }

    private function listOut($indentation): string
    {
        $out = [];
        $maxLength = 0;
        foreach ($this->array as $item) {
            $currentOut = ValueOut::out($item, $indentation);
            $out[] = $currentOut;
            $maxLength = max($maxLength, strlen($currentOut));
        }

        return count($this->array) === count($this->array, COUNT_RECURSIVE) && $maxLength < 30
            ? $this->singleLineOutput($out)
            : $this->multiLineOutput($out, $indentation);
    }

    /**
     * @param int $indentation
     *
     * @return string
     */
    private function mapOut(int $indentation): string
    {
        $contents = [];
        foreach ($this->array as $key => $value) {
            $contents[] = ValueOut::out($key) . " => " . ValueOut::out($value, $indentation);
        }

        return $this->multiLineOutput($contents, $indentation);
    }

    private function multiLineOutput($contents, $indentation): string
    {
        $indentationStr     = ValueOut::getIndentation($indentation);
        $indentationBackStr = ValueOut::getIndentation($indentation - 4);

        return "[\r\n$indentationStr" . implode(",\r\n$indentationStr", $contents) . "\r\n$indentationBackStr]";
    }

    public function singleLineOutput($contents): string
    {
        return "[" . implode(", ", $contents) . "]";
    }
}