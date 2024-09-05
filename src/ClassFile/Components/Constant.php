<?php

namespace Sc\Util\ClassFile\Components;

use Sc\Util\ClassFile\Components\Out\ValueOut;

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
    protected array $attributes = [];

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

        if ($this->attributes) {
            $contents = array_merge($contents, array_map(fn($attr) => $attr->out(), $this->attributes));
        }

        $default    = $this->valueOut();
        $embellish  = $this->isFinal ? 'final ' : '';
        $label      = $this->isEnum ? 'case' : 'const';

        $contents[] = "{$embellish}{$this->publicScope}$label $this->name" . ($default === null ? "" : " = " . $default) . ';';

        return "\r\n    " . implode("\r\n    ", $contents);
    }

    private function valueOut(): ?string
    {
        $outValue = $this->value;
        if (!$this->isEnum) {
            if (is_object($this->value)) {
                if (!property_exists($this->value, 'value')) {
                    return null;
                }
                $outValue = $this->value->value;
            } elseif (empty($this->value)) {
                return null;
            }
        }

        return ValueOut::out($outValue);
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

    public function addAttribute(Attribute|callable ...$attributes): Constant
    {
        foreach ($attributes as $attribute) {
            if ($attribute instanceof Attribute) {
                $this->attributes[] = $attribute;
                continue;
            }
            $this->attributes[] = $attribute();
        }

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