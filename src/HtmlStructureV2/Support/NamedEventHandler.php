<?php

namespace Sc\Util\HtmlStructureV2\Support;

use JsonSerializable;

final class NamedEventHandler implements JsonSerializable
{
    private function __construct(
        private readonly string $name
    ) {
    }

    public static function make(string $name): self
    {
        return new self(trim($name));
    }

    public static function looksLikeReference(string $value): bool
    {
        $value = trim($value);

        return $value !== '' && preg_match('/^[A-Za-z_$][A-Za-z0-9_$]*$/', $value) === 1;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function jsonSerialize(): array
    {
        return [
            '__scV2NamedHandler' => $this->name,
        ];
    }
}
