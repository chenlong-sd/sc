<?php

namespace Sc\Util\ClassFile;

use Sc\Util\ClassFile\Components\Attribute;
use Sc\Util\ClassFile\Components\ClassFileConstruction;
use Sc\Util\ClassFile\Components\Constant;
use Sc\Util\ClassFile\Components\Method;
use Sc\Util\ClassFile\Components\FunctionParam;
use Sc\Util\ClassFile\Components\Out\RawOut;
use Sc\Util\ClassFile\Components\Property;

/**
 * 类文件解析服务
 *
 * Class ClassFileResolveService
 */
class ClassFileResolve
{
    private function startResolve(mixed $class): ClassFileConstruction
    {
        $reflectionClass = new \ReflectionClass($class);
        $classFileConstruction = new ClassFileConstruction($reflectionClass->getShortName());
        $interfaceNames = $reflectionClass->getInterfaceNames();

        $fopen = fopen($reflectionClass->getFileName(), 'r');

        $contents = [''];
        while ($line = fgets($fopen)) {
            $contents[] = $line;
            if (str_starts_with($line, 'declare')) {
                $classFileConstruction->setDeclare(trim($line));
            }
            if (str_starts_with($line, 'use')) {
                $classFileConstruction->addUses(strtr($line, ['use ' => '', ';' => '']));
            }
            if (preg_match("/<<<([A-Za-z]{3,})$/", trim($line), $match)) {
                $EOT = $match[1];
            }
            foreach ($interfaceNames as $interfaceName) {
                if (str_contains($line, $interfaceName)){
                    $classFileConstruction->addImplements($interfaceName);
                }
            }
        }
        fclose($fopen);

        if (is_object($class)) {
            $classFileConstruction->setOriginClass($class);
        }

        $classFileConstruction->setClassFileContent($contents);
        $classFileConstruction->setOriginReflexClass($reflectionClass);
        $classFileConstruction->setNamespace($reflectionClass->getNamespaceName());
        $classFileConstruction->setDocBlock($reflectionClass->getDocComment());
        $classFileConstruction->setExtends($reflectionClass->getParentClass() ? $reflectionClass->getParentClass()->getName() : '');
        $classFileConstruction->addProperties(...$this->propertyResolve($reflectionClass, $classFileConstruction));
        $classFileConstruction->addTraits(...$reflectionClass->getTraitNames());
        $classFileConstruction->addTraitsAlise($reflectionClass->getTraitAliases());
        $classFileConstruction->addConstants(...$this->constantResolve($reflectionClass, $classFileConstruction));

        if (!$reflectionClass->isEnum()) {
            $classFileConstruction->setIsFinal($reflectionClass->isFinal());
        }

        $this->enumResolve($reflectionClass, $classFileConstruction);

        $classFileConstruction->setIsAbstract($reflectionClass->isAbstract());
        $classFileConstruction->setIsTrait($reflectionClass->isTrait());
        $classFileConstruction->setIsInterface($reflectionClass->isInterface());

        foreach ($reflectionClass->getAttributes() as $attribute) {
            $attributeName = $classFileConstruction->getAppropriateClassName($attribute->getName());

            $attributeDes = new Attribute($attributeName);
            array_map(fn($param) => $attributeDes->addParam($param), $attribute->getArguments());
            $classFileConstruction->addAttributes($attributeDes);
        }

        $classFileConstruction->addMethods(...$this->methodsResolve($reflectionClass, $classFileConstruction));

        return $classFileConstruction;
    }

    public static function resolve(mixed $class): ClassFileConstruction
    {
        $classFileResolveService = new self();

        return $classFileResolveService->startResolve($class);
    }

    /**
     * @param \ReflectionClass      $reflectionClass
     * @param ClassFileConstruction $classFileConstruction
     *
     * @return array|Property[]
     */
    private function propertyResolve(\ReflectionClass $reflectionClass, ClassFileConstruction $classFileConstruction): array
    {
        $properties = [];
        foreach ($reflectionClass->getProperties() as $reflectionProperty) {
            if ($reflectionProperty->getDeclaringClass()->getName() !== $reflectionClass->getName()) {
                continue;
            }

            $default = $this->classNameValueResolve($classFileConstruction, $reflectionProperty->getDefaultValue());

            $property = new Property($reflectionProperty->name);
            $property->setType($reflectionProperty->getType(), $classFileConstruction);
            $property->setIsStatic($reflectionProperty->isStatic());
            $property->setDocBlockComment($reflectionProperty->getDocComment());
            $property->setDefault($default);
            $property->setPublicScope($reflectionProperty->isPrivate()
                    ? "private"
                    : ($reflectionProperty->isProtected() ? "protected" : "public")
            );
            $property->setIsReadOnly($reflectionProperty->isReadOnly());

            if ($reflectionProperty->getAttributes()) {
                foreach ($reflectionProperty->getAttributes() as $attribute) {
                    $propertyAttribute = new Attribute($classFileConstruction->getAppropriateClassName($attribute->getName()));
                    array_map(fn($param) => $propertyAttribute->addParam($param), $attribute->getArguments());
                    $property->addAttribute($propertyAttribute);
                }
            }

            $properties[] = $property;
        }

        return $properties;
    }

    private function constantResolve(\ReflectionClass $reflectionClass, ClassFileConstruction $classFileConstruction): array
    {
        $constants = [];
        foreach ($reflectionClass->getConstants() as $name => $value) {
            $classConstant = new \ReflectionClassConstant($reflectionClass->getName(), $name);
            if ($classConstant->getDeclaringClass()->getName() !== $reflectionClass->getName()) {
                continue;
            }
            $value = $this->classNameValueResolve($classFileConstruction, $value);
            $constant = new Constant($name);
            $constant->setValue($value);
            $constant->setDocBlockComment($classConstant->getDocComment());
            $constant->setPublicScope($classConstant->isPrivate() ? "private" : ($classConstant->isProtected() ? "protected" : "public"));
            $constant->setIsFinal($classConstant->isFinal());

            if ($reflectionClass->isEnum() && is_object($value)) {
                $constant->setIsEnum(true);
            }

            $constants[] = $constant;
        }

        return $constants;
    }

    /**
     * @param \ReflectionClass $reflectionClass
     * @param ClassFileConstruction $classFileConstruction
     * @return array
     */
    private function methodsResolve(\ReflectionClass $reflectionClass, ClassFileConstruction $classFileConstruction): array
    {
        $addMethods = [];
        foreach ($reflectionClass->getMethods() as $method) {
            if ($method->getFileName() !== $reflectionClass->getFileName()) {
                continue;
            }

            $methods = new Method($method->getName());
            $methods->setIsStatic($method->isStatic());
            $methods->setIsFinal($method->isFinal());
            $methods->setPublicScope($method->isPrivate() ? 'private' : ($method->isProtected() ? 'protected' : 'public'));
            $methods->setDocBlockComment($method->getDocComment());
            $methods->setReturnType($method->getReturnType(), $classFileConstruction);
            $methods->setIsAbstract($method->isAbstract());

            if ($method->getAttributes()) {
                foreach ($method->getAttributes() as $attribute) {
                    $attributeName = $attribute->getName();
                    $attributeName = $classFileConstruction->hasClassUse($attributeName)
                        ? ClassFileConstruction::getClassShortName($attributeName)
                        : ClassFileConstruction::getClassName($attributeName);

                    $attributeRes = new Attribute($attributeName);
                    array_map(fn($param) => $attributeRes->addParam($param), $attribute->getArguments());
                    $methods->addAttribute($attributeRes);
                }
            }

            foreach ($method->getParameters() as $parameter) {
                $methodsParam = new FunctionParam($parameter->getName());
                $methodsParam->setType($parameter->getType(), $classFileConstruction);
                $methodsParam->setIsVariadic($parameter->isVariadic());

                $parameter->isDefaultValueAvailable()
                    ? $methodsParam->setDefault($parameter->getDefaultValue())
                    : $methodsParam->setNotDefault();

                if ($parameter->getAttributes()) {
                    foreach ($parameter->getAttributes() as $attribute) {
                        $attributeName = $attribute->getName();
                        $attributeName = $classFileConstruction->hasClassUse($attributeName)
                            ? ClassFileConstruction::getClassShortName($attributeName)
                            : ClassFileConstruction::getClassName($attributeName);

                        $attributeRes = new Attribute($attributeName);
                        array_map(fn($param) => $attributeRes->addParam($param), $attribute->getArguments());
                        $methodsParam->addAttribute($attributeRes);
                    }
                }

                $methods->addParameters($methodsParam);
            }

            for ($i = $method->getStartLine() + 1; $i <= $method->getEndLine(); $i++) {
                $code = trim($classFileConstruction->getFileContentByLine($i));
                if ($i == $method->getStartLine() + 1 && $code === '{') {
                    continue;
                }
                if ($i == $method->getEndLine() && $code === '}') {
                    continue;
                }

                $methods->addCode(preg_replace('/^\s{0,8}/', '', rtrim($classFileConstruction->getFileContentByLine($i))));
            }

            $addMethods[] = $methods;
        }

        return $addMethods;
    }

    private function enumResolve(\ReflectionClass $reflectionClass, ClassFileConstruction $classFileConstruction): void
    {
        if (!$reflectionClass->isEnum()) {
            return;
        }

        $classFileConstruction->setIsEnum(true);
        $reflectionEnum = new \ReflectionEnum($reflectionClass->getName());
        if ($reflectionEnum->isBacked()) {
            $classFileConstruction->setEnumBackedType($reflectionEnum->getBackingType()->getName());
        }
    }

    private function classNameValueResolve(ClassFileConstruction $classFileConstruction, $value)
    {
        if (is_string($value) && $classFileConstruction->hasClassUse($value)) {
            return new RawOut($value);
        }

        return $value;
    }
}