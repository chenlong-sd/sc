<?php

namespace Sc\Util\ClassFile\Components;

use Sc\Util\ClassFile\Components\Out\ValueOut;

class FunctionParam
{
    private array $type = [];

    private mixed $default = null;

    private bool $haveDefault = false;

    private bool $isVariadic = false;

    /**
     * @var array|Attribute[]
     */
    private array $attribute = [];

    public function __construct(private readonly string $name)
    {
    }

    public function setType(string|\ReflectionType|null $type, ClassFileConstruction $classFileConstruction = null): self
    {
        if ($type instanceof \ReflectionType) {
            if ($type instanceof \ReflectionUnionType) {
                $this->type = array_map(fn($t) => $classFileConstruction->getTypeName($t), $type->getTypes());
                return $this;
            }

            $this->type = [($type->allowsNull() ? "?" : '') .  $classFileConstruction->getTypeName($type)];
            return $this;
        }

        if (is_string($type) && str_contains($type, '\\')) {
            $type = $classFileConstruction->getAppropriateClassName($type);
        }

        $this->type = $type ? [$type] : [];
        return $this;
    }


    public function setDefault(mixed $default): self
    {
        $this->default = $default;
        $this->haveDefault = true;
        return $this;
    }

    public function out(): string
    {
        $variadic = $this->isVariadic ? '...' : '';

        $out = $this->type
            ? (implode("|", $this->type) . " $variadic$$this->name")
            : "$variadic$$this->name";

        if ($this->haveDefault) {
            $out .= ' = ' . ValueOut::out($this->default);
        }

        if (!$this->attribute) {
            return $out;
        }

        return implode(array_map(fn($attr) => $attr->out(), $this->attribute)) . ' ' . $out;
    }

    public function setNotDefault(): FunctionParam
    {
        $this->haveDefault = false;
        $this->default     = null;

        return $this;
    }

    public function addAttribute(Attribute|callable ...$attributes): FunctionParam
    {
        foreach ($attributes as $attribute) {
            if ($attribute instanceof Attribute) {
                $this->attribute[] = $attribute;
                continue;
            }
            $this->attribute[] = $attribute();
        }

        return $this;
    }

    public function setIsVariadic(bool $isVariadic): FunctionParam
    {
        $this->isVariadic = $isVariadic;
        return $this;
    }
}