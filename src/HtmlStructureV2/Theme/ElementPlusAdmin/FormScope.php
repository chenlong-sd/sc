<?php

namespace Sc\Util\HtmlStructureV2\Theme\ElementPlusAdmin;

final class FormScope
{
    private function __construct(
        private readonly string $value,
    ) {
    }

    public static function filter(): self
    {
        return new self('filter');
    }

    public static function named(string $scope): self
    {
        $scope = trim($scope);

        return new self($scope === '' ? 'form' : $scope);
    }

    public static function standalone(string $key): self
    {
        return new self($key);
    }

    public static function dialog(string $dialogKey = ''): self
    {
        $dialogKey = trim($dialogKey);

        return new self($dialogKey === '' ? 'dialog' : ('dialog:' . $dialogKey));
    }

    public function value(): string
    {
        return $this->value;
    }
}
