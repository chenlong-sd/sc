<?php

namespace Sc\Util\ClassFile\Components;

/**
 * Class ClassFileConstruction
 */
class ClassFileConstruction
{
    private string $className;
    private ?string $declare = null;

    /**
     * @var array|Property[]
     */
    private array $classProperties = [];

    /**
     * @var array |Method[]
     */
    private array $classMethods = [];

    private array $classConstants = [];

    private array $classTraits = [];
    private array $classTraitsAlise = [];

    private array $classUses = [];
    private array $classUsesAlias = [];

    private string $classNamespace = '';

    private ?DocComment $classDocBlock = null;

    private ?string $classExtends = '';
    private array $classImplements = [];

    private array $classAttributes = [];

    private array $classFileContent = [];
    private ?\ReflectionClass $originReflexClass = null;
    private mixed  $originClass = null;

    private bool   $isTrait = false;
    private bool   $isAbstract = false;
    private bool   $isInterface = false;
    private bool   $isFinal = false;
    private bool   $isReadOnly = false;
    private bool   $isEnum = false;
    private ?string $enumBackedType = null;

    public function __construct(string $className)
    {
        $this->className = $className;
    }

    public function __get(string $name)
    {
        return $this->$name;
    }

    public function addProperties(Property|callable ...$classProperties): ClassFileConstruction
    {
        foreach ($classProperties as $property) {
            if ($property instanceof Property){
                $this->classProperties[] = $property;
                continue;
            }

            $this->classProperties[] = $property();
        }

        return $this;
    }

    public function addConstants(Constant|callable ...$classConstants): ClassFileConstruction
    {
        foreach ($classConstants as $constant) {
            if ($constant instanceof Constant) {
                $this->classConstants[] = $constant;
                continue;
            }

            $this->classConstants[] = $constant();
        }

        return $this;
    }

    public function out(): string
    {
        $extends    = $this->classExtends ? " extends {$this->classExtends}" : "";
        $implements = $this->classImplements ? " implements " . implode(', ', $this->classImplements) : "";
        $type       = match (true) {
            $this->isInterface => 'interface',
            $this->isTrait     => 'trait',
            $this->isEnum      => 'enum',
            default            => 'class',
        };
        $embellish = $this->embellishOut();
        if ($this->isEnum) {
            $this->removeClassProperties('name', 'value');
        }

        $code = [
            "<?php", '',
            ...($this->declare ? [$this->declare . ';', ''] : []),
            "namespace {$this->classNamespace};", "",
            ...$this->useOut(),
            ...["", "", ],
            ...($this->classDocBlock?->getCode() ?: []),
            ...($this->classAttributes ? array_map(fn($attribute) => $attribute->out(), $this->classAttributes) : []),
            "$embellish$type {$this->classNameOut()}" . $extends . $implements,
            "{",
            $this->traitsOut(),
            ...array_map(fn($constant) => $constant->outCode(), $this->classConstants),
            ...array_map(fn($property) => $property->outCode(), $this->classProperties),
            ...array_map(fn($method) => $method->out(), $this->classMethods),
            "}",
        ];

        return implode("\r\n", $code);
    }

    private function traitsOut(): string
    {
        $out = [];
        foreach ($this->classTraits as $classTrait) {
            $classTraitShortName = array_reverse(explode('\\', $classTrait))[0];
            $outStr = "use " . (in_array($classTrait, $this->classUses) ? $classTraitShortName : $classTrait);

            $aliasArr = [];
            foreach ($this->classTraitsAlise as $alias => $item) {
                if (str_contains($item, $classTrait)) {
                    $item = str_replace($classTrait . "::", "", $item);
                    $aliasArr[] = "$item as $alias;";
                }
            }

            if ($aliasArr) {
                $aliasArr = ["{", ...$aliasArr];
                $outStr .= implode("\r\n        ", $aliasArr) . "\r\n    }";
            }else{
                $outStr .= ";";
            }

            $out[] = $outStr;
        }

        if (!$out) {
            return '';
        }

        return implode("\r\n    ", ["", ...$out]);
    }

    public function setClassNamespace(string $classNamespace): ClassFileConstruction
    {
        $this->classNamespace = $classNamespace;
        return $this;
    }

    public function addClassUses(string $useClass, string $alias = null): bool
    {
        $useClass = trim($useClass);

        if (str_contains($useClass, ' as ')) {
            [$useClass, $alias] = array_map('trim', explode(' as ', $useClass));
        }

        $uniqueTag = $alias ?: self::getClassShortName($useClass);

        foreach ($this->classUses as $use) {
            if ($this->getClassUseAlias($use) === $uniqueTag) {
                return false;
            }
            if (str_ends_with($use, '\\' . $uniqueTag) && !$this->getClassUseAlias($use)) {
                return false;
            }
        }

        $this->classUses[] = trim($useClass);

        if ($alias) {
            $this->classUsesAlias[$useClass] = $alias;
        }

        return true;
    }

    public function hasClassUse(string $className): bool
    {
        return in_array($className, $this->classUses);
    }

    public function hasClassUseAlias(string $className): bool
    {
        return array_key_exists($className, $this->classUsesAlias);
    }

    public function getClassUseAlias(string $className): ?string
    {
        if ($this->hasClassUseAlias($className)) {
            return $this->classUsesAlias[$className];
        }

        return null;
    }

    public function setClassDocBlock(string|array $classDocBlock): ClassFileConstruction
    {
        $this->classDocBlock = new DocComment($classDocBlock);
        return $this;
    }

    public function addClassTraits(string ...$classTraits): ClassFileConstruction
    {
        $this->classTraits = [...$this->classTraits, ...$classTraits];
        return $this;
    }

    public function addClassTraitsAlise(array|string $classTraitsAlise, string $classTraitsAliseName = null): ClassFileConstruction
    {
        if (is_string($classTraitsAlise)) {
            $classTraitsAlise = [$classTraitsAlise => $classTraitsAliseName];
        }
        $this->classTraitsAlise = array_merge($this->classTraitsAlise, $classTraitsAlise);
        return $this;
    }

    public function setClassExtends(?string $classExtends): ClassFileConstruction
    {
        $this->classExtends = $classExtends ? $this->getAppropriateClassName($classExtends) : '';
        return $this;
    }

    public function addClassImplements(string ...$classImplements): ClassFileConstruction
    {
        array_map(function ($implement) {
            return $this->classImplements[] = $this->getAppropriateClassName($implement);
        }, $classImplements);

        return $this;
    }

    public function getAppropriateClassName(string $classname): string
    {
        return $this->hasClassUse($classname) || self::getClassNamespace($classname) === $this->classNamespace
            ? ($this->getClassUseAlias($classname) ?: self::getClassShortName($classname))
            : self::getClassName($classname);
    }

    public static function getClassShortName(string $classname): string
    {
        $explode = explode('\\', $classname);
        $count   = count($explode);
        if ($count === 1) {
            return "\\" .$classname;
        }
        return $explode[$count - 1];
    }

    /**
     * @param string $classname
     * @return string
     */
    public static function getClassName(string $classname): string
    {
        return str_starts_with($classname, '\\') ? $classname : "\\" . $classname;
    }

    public static function getClassNamespace(string $classname): string
    {
        return preg_replace('/\\\\\w+$/', '', $classname);
    }

    public function addClassAttributes(Attribute|callable...$classAttributes): ClassFileConstruction
    {
        foreach ($classAttributes as $attribute) {
            if ($attribute instanceof Attribute) {
                $this->classAttributes[] = $attribute;
            } else {
                $this->classAttributes[] = $attribute();
            }
        }

        return $this;
    }

    public function addClassMethods(Method|callable ...$classMethods): ClassFileConstruction
    {
        foreach ($classMethods as $method) {
            if ($method instanceof Method) {
                $this->classMethods[] = $method;
            }else{
                $this->classMethods[] = $method();
            }
        }

        return $this;
    }

    public function setIsTrait(bool $isTrait): ClassFileConstruction
    {
        $this->isTrait = $isTrait;
        return $this;
    }

    public function setIsEnum(bool $isEnum): ClassFileConstruction
    {
        $this->isEnum = $isEnum;
        return $this;
    }

    public function setIsReadOnly(bool $isReadOnly): ClassFileConstruction
    {
        $this->isReadOnly = $isReadOnly;
        return $this;
    }

    public function setIsFinal(bool $isFinal): ClassFileConstruction
    {
        $this->isFinal = $isFinal;
        return $this;
    }

    public function setIsInterface(bool $isInterface): ClassFileConstruction
    {
        $this->isInterface = $isInterface;
        return $this;
    }

    public function setIsAbstract(bool $isAbstract): ClassFileConstruction
    {
        $this->isAbstract = $isAbstract;
        return $this;
    }

    /**
     * @return array|string[]
     */
    public function useOut(): array
    {
        if (!$this->classUses) {
            return [];
        }

        return array_map(function ($use) {
            return $this->hasClassUseAlias($use)
                ? "use $use as {$this->getClassUseAlias($use)};"
                : "use $use;";
        }, $this->classUses);
    }

    public function getTypeName(\ReflectionType $type): string
    {
        if (!$type instanceof \ReflectionNamedType) {
            return '';
        }

        if ($type->isBuiltin() || in_array($type->getName(), ['static', 'self'])) {
            return $type->getName();
        }
        return $this->getAppropriateClassName($type->getName());
    }

    public function setDeclare(?string $declare): ClassFileConstruction
    {
        $this->declare = rtrim($declare, ';');
        return $this;
    }

    public function setEnumBackedType(string $getBackingType): static
    {
        $this->enumBackedType = $getBackingType;

        return $this;
    }

    private function classNameOut(): string
    {
        if ($this->isEnum && $this->enumBackedType) {
            return "{$this->className}: {$this->enumBackedType}";
        }
        return $this->className;
    }

    public function getClassProperties(): array
    {
        return $this->classProperties;
    }

    public function removeClassProperties(string ...$name): void
    {
        $removeIndex = [];
        foreach ($this->classProperties as $index => $classProperty){
            if (in_array($classProperty->getName(), $name)) {
                $removeIndex[] = $index;
            }
        }

        foreach ($removeIndex as $index) {
            unset($this->classProperties[$index]);
        }
    }

    private function embellishOut(): string
    {
        $embellish = "";
        if ($this->isAbstract && !$this->isInterface) {
            $embellish .= 'abstract ';
        }
        if ($this->isFinal) {
            $embellish .= 'final ';
        }
        if ($this->isReadOnly) {
            $embellish .= 'readonly ';
        }

        return $embellish;
    }

    public function getClassMethod(string $name): ?Method
    {
        $methods = array_filter($this->classMethods, fn($method) => $method->getName() === $name);

        return $methods ? current($methods) : null;
    }

    public function setOriginReflexClass(?\ReflectionClass $originReflexClass): ClassFileConstruction
    {
        $this->originReflexClass = $originReflexClass;
        return $this;
    }

    public function setClassFileContent(array $classFileContent): ClassFileConstruction
    {
        $this->classFileContent = $classFileContent;
        return $this;
    }

    public function getClassFileContent(int $startLine = null, int $endLine = null): array
    {
        if ($startLine === null && $endLine === null) {
            return $this->classFileContent;
        }

        if ($startLine === null) {
            return array_slice($this->classFileContent, 0, $endLine);
        }

        if ($endLine === null) {
            return array_slice($this->classFileContent, $startLine);
        }

        if ($startLine > $endLine) {
            return [];
        }

        if ($startLine < 0) {
            $startLine = 0;
        }

        if ($endLine > count($this->classFileContent)) {
            $endLine = count($this->classFileContent);
        }

        return array_slice($this->classFileContent, $startLine, $endLine - $startLine);
    }

    public function getFileContentByLine(int $line)
    {
        return $this->classFileContent[$line];
    }

    public function setOriginClass(mixed $originClass): ClassFileConstruction
    {
        $this->originClass = $originClass;
        return $this;
    }

    public function callOriginMethod(string $method, ...$args)
    {
        return $this->originReflexClass
            ->getMethod($method)
            ->invoke($this->originClass, ...$args);
    }

    public function getOriginProperty(string $property)
    {
        return $this->originClass->$property;
    }

    public function getName(): string
    {
        return $this->className;
    }

}