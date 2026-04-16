<?php

namespace Sc\Util\HtmlStructureV2\Theme\ElementPlusAdmin;

use Sc\Util\HtmlStructureV2\RenderContext;
use Sc\Util\HtmlStructureV2\Support\FormPath;

final class FormNodeRenderContext
{
    public function __construct(
        public readonly string $modelName,
        public readonly bool $inline,
        public readonly bool $formReadonly,
        public readonly FormRenderOptions $options,
        public readonly ?RenderContext $renderContext = null,
        public readonly string $pathPrefix = '',
        public readonly ?string $arrayPath = null,
        public readonly ?string $arrayPathExpression = null,
        public readonly ?string $rowIndexExpression = null,
        public readonly int $arrayDepth = 0,
    ) {
    }

    public function withInline(bool $inline): self
    {
        return new self(
            modelName: $this->modelName,
            inline: $inline,
            formReadonly: $this->formReadonly,
            options: $this->options,
            renderContext: $this->renderContext,
            pathPrefix: $this->pathPrefix,
            arrayPath: $this->arrayPath,
            arrayPathExpression: $this->arrayPathExpression,
            rowIndexExpression: $this->rowIndexExpression,
            arrayDepth: $this->arrayDepth,
        );
    }

    public function withFormReadonly(bool $formReadonly): self
    {
        return new self(
            modelName: $this->modelName,
            inline: $this->inline,
            formReadonly: $formReadonly,
            options: $this->options,
            renderContext: $this->renderContext,
            pathPrefix: $this->pathPrefix,
            arrayPath: $this->arrayPath,
            arrayPathExpression: $this->arrayPathExpression,
            rowIndexExpression: $this->rowIndexExpression,
            arrayDepth: $this->arrayDepth,
        );
    }

    public function mergeReadonly(bool $readonly = true): self
    {
        return $this->withFormReadonly($this->formReadonly || $readonly);
    }

    public function nested(string $modelName, string $pathPrefix, ?bool $inline = null): self
    {
        return new self(
            modelName: $modelName,
            inline: $inline ?? $this->inline,
            formReadonly: $this->formReadonly,
            options: $this->options,
            renderContext: $this->renderContext,
            pathPrefix: $pathPrefix,
            arrayPath: $this->arrayPath,
            arrayPathExpression: $this->arrayPathExpression,
            rowIndexExpression: $this->rowIndexExpression,
            arrayDepth: $this->arrayDepth,
        );
    }

    public function forArrayRow(
        string $modelName,
        string $arrayPath,
        string $arrayPathExpression,
        string $rowIndexExpression,
        string $pathPrefix = '',
        ?int $arrayDepth = null
    ): self
    {
        return new self(
            modelName: $modelName,
            inline: false,
            formReadonly: $this->formReadonly,
            options: $this->options,
            renderContext: $this->renderContext,
            pathPrefix: $pathPrefix,
            arrayPath: $arrayPath,
            arrayPathExpression: $arrayPathExpression,
            rowIndexExpression: $rowIndexExpression,
            arrayDepth: $arrayDepth ?? $this->arrayDepth,
        );
    }

    public function fieldPath(string $fieldName): string
    {
        return FormPath::resolve($this->pathPrefix, $fieldName);
    }

    public function isArrayRow(): bool
    {
        return $this->arrayPath !== null
            && $this->arrayPathExpression !== null
            && $this->rowIndexExpression !== null;
    }
}
