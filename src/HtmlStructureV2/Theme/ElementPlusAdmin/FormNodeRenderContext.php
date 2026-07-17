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
        public readonly ?string $labelWidth = null,
        public readonly ?string $disabledWhen = null,
        public readonly ?string $readonlyWhen = null,
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
            labelWidth: $this->labelWidth,
            disabledWhen: $this->disabledWhen,
            readonlyWhen: $this->readonlyWhen,
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
            labelWidth: $this->labelWidth,
            disabledWhen: $this->disabledWhen,
            readonlyWhen: $this->readonlyWhen,
        );
    }

    public function mergeReadonly(bool $readonly = true): self
    {
        return $this->withFormReadonly($this->formReadonly || $readonly);
    }

    public function withLabelWidth(?string $labelWidth): self
    {
        if ($labelWidth === $this->labelWidth) {
            return $this;
        }

        return new self(
            modelName: $this->modelName,
            inline: $this->inline,
            formReadonly: $this->formReadonly,
            options: $this->options,
            renderContext: $this->renderContext,
            pathPrefix: $this->pathPrefix,
            arrayPath: $this->arrayPath,
            arrayPathExpression: $this->arrayPathExpression,
            rowIndexExpression: $this->rowIndexExpression,
            arrayDepth: $this->arrayDepth,
            labelWidth: $labelWidth,
            disabledWhen: $this->disabledWhen,
            readonlyWhen: $this->readonlyWhen,
        );
    }

    public function inheritLabelWidth(?string $labelWidth): self
    {
        if ($labelWidth === null) {
            return $this;
        }

        return $this->withLabelWidth($labelWidth);
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
            labelWidth: $this->labelWidth,
            disabledWhen: $this->disabledWhen,
            readonlyWhen: $this->readonlyWhen,
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
            labelWidth: $this->labelWidth,
            disabledWhen: $this->disabledWhen,
            readonlyWhen: $this->readonlyWhen,
        );
    }

    public function mergeDisabledWhen(?string $expression): self
    {
        $merged = $this->mergeBooleanExpression($this->disabledWhen, $expression);
        if ($merged === $this->disabledWhen) {
            return $this;
        }

        return new self(
            modelName: $this->modelName,
            inline: $this->inline,
            formReadonly: $this->formReadonly,
            options: $this->options,
            renderContext: $this->renderContext,
            pathPrefix: $this->pathPrefix,
            arrayPath: $this->arrayPath,
            arrayPathExpression: $this->arrayPathExpression,
            rowIndexExpression: $this->rowIndexExpression,
            arrayDepth: $this->arrayDepth,
            labelWidth: $this->labelWidth,
            disabledWhen: $merged,
            readonlyWhen: $this->readonlyWhen,
        );
    }

    public function mergeReadonlyWhen(?string $expression): self
    {
        $merged = $this->mergeBooleanExpression($this->readonlyWhen, $expression);
        if ($merged === $this->readonlyWhen) {
            return $this;
        }

        return new self(
            modelName: $this->modelName,
            inline: $this->inline,
            formReadonly: $this->formReadonly,
            options: $this->options,
            renderContext: $this->renderContext,
            pathPrefix: $this->pathPrefix,
            arrayPath: $this->arrayPath,
            arrayPathExpression: $this->arrayPathExpression,
            rowIndexExpression: $this->rowIndexExpression,
            arrayDepth: $this->arrayDepth,
            labelWidth: $this->labelWidth,
            disabledWhen: $this->disabledWhen,
            readonlyWhen: $merged,
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

    private function mergeBooleanExpression(?string ...$expressions): ?string
    {
        $conditions = array_values(array_filter(
            $expressions,
            static fn(?string $expression): bool => is_string($expression) && trim($expression) !== ''
        ));

        if ($conditions === []) {
            return null;
        }

        if (in_array('true', $conditions, true)) {
            return 'true';
        }

        if (count($conditions) === 1) {
            return $conditions[0];
        }

        return sprintf('(%s)', implode(') || (', $conditions));
    }
}
