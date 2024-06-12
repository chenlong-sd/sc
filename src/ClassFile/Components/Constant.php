<?php

namespace Sc\Util\ClassFile\Components;

/**
 * Class Property
 */
class Constant
{
    protected string $publicScope = '';

    protected ?DocComment $docBlockComment = null;

    protected mixed $value = null;

    protected bool $isFinal = false;
    protected bool $isEnum = false;

    public function __construct(private readonly string $name)
    {
    }

    public function __get(string $name)
    {
        return $this->$name;
    }

    public function outCode(): string
    {
        $contents = $this->docBlockComment?->getCode() ?: [];

        $default    = $this->valueOut();
        $embellish  = $this->isFinal ? 'final ' : '';
        $label      = $this->isEnum ? 'case' : 'const';

        $contents[] = "{$embellish}{$this->publicScope}$label $this->name" . ($default ? " = " . $default : "") . ';';

        return "\r\n    " . implode("\r\n    ", $contents);
    }

    private function valueOut(): ?string
    {
        if ($this->isEnum && !property_exists($this->value, 'value')) {
            return null;
        }

        return ValueOut::out($this->isEnum ? $this->value->value : $this->value);
    }

    public function __toString(): string
    {
        return $this->outCode();
    }

    public function setDocBlockComment(array|string $docBlockComment): Constant
    {
        $this->docBlockComment = new DocComment($docBlockComment);

        return $this;
    }

    public function setPublicScope(string $publicScope): Constant
    {
        $this->publicScope = $publicScope === 'public' ? '' : $publicScope . ' ';
        return $this;
    }

    public function setValue(mixed $value): Constant
    {
        $this->value = $value;
        return $this;
    }

    public function setIsFinal(bool $isFinal): Constant
    {
        $this->isFinal = $isFinal;
        return $this;
    }

    public function setIsEnum(bool $isEnum): Constant
    {
        $this->isEnum = $isEnum;
        return $this;
    }
}