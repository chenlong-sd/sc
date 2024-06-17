<?php

namespace Sc\Util\ClassFile\Components;

use JetBrains\PhpStorm\ExpectedValues;
use Sc\Util\ClassFile\Components\Out\ValueOut;

/**
 * Class Property
 */
class Property
{
    protected ?string $type = null;

    protected string $publicScope = 'public';

    protected ?DocComment $docBlockComment = null;

    protected mixed $default = "notDefault";

    protected bool $isStatic = false;

    protected bool $isReadOnly = false;

    protected array $attribute = [];

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

        if ($this->attribute) {
            $contents = array_merge($contents, array_map(fn($attr) => $attr->out(), $this->attribute));
        }

        $default    = $this->defaultOut();
        $static     = $this->isStatic ? 'static ' : '';
        $isReadOnly = $this->isReadOnly ? 'readonly ' : '';
        $contents[] = "$this->publicScope $isReadOnly$static$this->type $$this->name" . ($default ? " = " . $default : "") . ';';

        return "\r\n    " . implode("\r\n    ", $contents);
    }

    private function defaultOut(): ?string
    {
        if ($this->default === 'notDefault') {
            return null;
        }

        return ValueOut::out($this->default);
    }

    public function __toString(): string
    {
        return $this->outCode();
    }

    public function setDefault(mixed $default): Property
    {
        $this->default = $default;
        return $this;
    }

    public function setDocBlockComment(array|string $docBlockComment): Property
    {
        $this->docBlockComment = new DocComment($docBlockComment);

        return $this;
    }

    public function setType(string|\ReflectionType|null $type, ClassFileConstruction $classFileConstruction = null): Property
    {
        if ($type instanceof \ReflectionType) {
            if ($type instanceof \ReflectionUnionType) {
                $this->type = implode('|', array_map(fn($t) => $classFileConstruction->getTypeName($t), $type->getTypes()));
                return $this;
            }

            $this->type = ($type->allowsNull() ? "?" : '') .  $classFileConstruction->getTypeName($type);
            return $this;
        }

        $this->type = $type;
        return $this;
    }

    public function setPublicScope(#[ExpectedValues(values: ['public', 'protected', 'private'])]string $publicScope): Property
    {
        $this->publicScope = $publicScope;
        return $this;
    }

    public function setIsStatic(bool $isStatic): Property
    {
        $this->isStatic = $isStatic;
        return $this;
    }

    public function addAttribute(Attribute|callable ...$attributes): Property
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

    public function setIsReadOnly(bool $isReadOnly): Property
    {
        $this->isReadOnly = $isReadOnly;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }
}