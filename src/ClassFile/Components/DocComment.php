<?php

namespace Sc\Util\ClassFile\Components;

/**
 * Class DocComment
 */
class DocComment
{
    private array $docComment = [];

    public function __construct(string|array $docComment)
    {
        $this->setDocComment($docComment);
    }

    private function handle($docComment): void
    {
        $docComment = is_string($docComment) ? explode("\n", $docComment) : $docComment;

        $docComment = array_map(fn($content) => preg_replace('/^\s*\**\s*/', '', trim($content)), $docComment);
        $docComment = array_filter($docComment, fn($v) => trim($v) !== '/**' && trim($v) !== '*/' && trim($v) !== '/');

        $this->docComment = $docComment;
    }

    public function setDocComment(string|array $document): void
    {
        if ($document) {
            $this->handle($document);
        }
    }

    public function addDocComment(string $comment): void
    {
        $this->docComment[] = preg_replace('/^\s*\**\s*/', '', trim($comment));
    }

    public function getCode(): array
    {
        if (!$this->getDocComment()) {
            return [];
        }

        return ["/**", ...array_map(fn($content) => " * " . $content, $this->docComment), " */"];
    }

    public function getDocComment(): array
    {
        return $this->docComment;
    }

    public function outCode(): string
    {
        $docComment = $this->getCode();
        if (empty($docComment)) {
            return '';
        }

        return "\r\n    " . implode("\r\n    ", $docComment);
    }

    public function __toString(): string
    {
        return $this->outCode();
    }
}