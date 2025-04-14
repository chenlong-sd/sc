<?php

namespace Sc\Util\ClassFile\Components;

use Sc\Util\ClassFile\Components\Out\ValueOut;

/**
 * Class Method
 */
class Method
{
    private ?string $returnType = null;

    private string $publicScope = 'public';

    private ?DocComment $docBlockComment = null;
    /**
     * @var array|FunctionParam[]
     */
    private array $parameters = [];

    private bool $isStatic = false;
    private bool $isFinal = false;
    private bool $isAbstract = false;

    private array $code = [];

    /**
     * @var array|Attribute[]
     */
    private array $attribute = [];

    public function __construct(private readonly string $name){}

    public function addParameters(FunctionParam|callable ...$parameters): Method
    {
        foreach ($parameters as $methodsParam) {
            if ($methodsParam instanceof FunctionParam) {
                $this->parameters[] = $methodsParam;
            }else{
                $this->parameters[] = $methodsParam();
            }
        }

        return $this;
    }

    public function setIsStatic(bool $isStatic): Method
    {
        $this->isStatic = $isStatic;
        return $this;
    }

    public function setIsFinal(bool $isFinal): Method
    {
        $this->isFinal = $isFinal;
        return $this;
    }

    public function setDocBlockComment(array|string $docBlockComment): Method
    {
        $this->docBlockComment = new DocComment($docBlockComment);
        return $this;
    }

    public function setPublicScope(string $publicScope): Method
    {
        $this->publicScope = $publicScope;
        return $this;
    }

    public function setReturnType(string|\ReflectionType|null $returnType, ClassFileConstruction $classFileConstruction = null): Method
    {
        if ($returnType instanceof \ReflectionType) {
            if ($returnType instanceof \ReflectionUnionType) {
                $this->returnType = implode('|', array_map(fn($t) => $classFileConstruction->getTypeName($t), $returnType->getTypes()));
                return $this;
            }

            $this->returnType = ($returnType->allowsNull() ? "?" : '') .  $classFileConstruction->getTypeName($returnType);
            return $this;
        }

        if (is_string($returnType) && str_contains($returnType, '\\')) {
            $returnType = $classFileConstruction->getAppropriateClassName($returnType);
        }

        $this->returnType = $returnType;
        return $this;
    }

    public function addAttribute(Attribute|callable ...$attributes): Method
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

    public function out(): string
    {
        $out = $this->docBlockComment?->getCode() ?: [];
        $out = [...$out, ...array_map(fn($attribute) => $attribute->out(), $this->attribute)];

        $out[] = $this->publicScope
            . ($this->isStatic ? ' static' : '')
            . ($this->isFinal ? ' final' : '')
            . ' function ' . $this->name . '('
            . implode(', ', array_map(fn (FunctionParam $methodsParam) => $methodsParam->out(), $this->parameters))
            . ')'
            . ($this->returnType ? ': ' . $this->returnType : '');

        if (!$this->isAbstract) {
            $out[] = '{';
            $out[] = ValueOut::getIndentation(4) . implode("\r\n" . ValueOut::getIndentation(8), $this->code);
            $out[] = '}';
        }

        $separator = "\r\n" . ValueOut::getIndentation(4);
        return $separator . implode($separator, $out);
    }

    public function addCode(string ...$code): Method
    {
        $this->code = [...$this->code, ...$code];
        return $this;
    }

    public function getCode(): array
    {
        return $this->code;
    }

    public function setIsAbstract(bool $isAbstract): Method
    {
        $this->isAbstract = $isAbstract;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }
}