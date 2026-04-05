<?php

namespace Sc\Util\HtmlStructureV2\Components;

use Sc\Util\HtmlStructureV2\Components\Concerns\HasEvents;
use Sc\Util\HtmlStructureV2\Contracts\Renderable;
use Sc\Util\HtmlStructureV2\Contracts\FormNode;
use Sc\Util\HtmlStructureV2\Support\FormSchema;
use Sc\Util\HtmlStructureV2\Support\FormSchemaWalker;
use Sc\Util\HtmlStructureV2\Support\RendersWithTheme;

final class Form implements Renderable
{
    use HasEvents;
    use RendersWithTheme;

    /** @var FormNode[] */
    private array $children = [];
    private bool $inline = false;
    private string $labelWidth = '100px';
    private string $submitLabel = '查询';
    private string $resetLabel = '重置';

    public function __construct(
        private readonly string $key
    ) {
    }

    public static function make(string $key): self
    {
        return new self($key);
    }

    public function addFields(Field ...$fields): self
    {
        return $this->addNodes(...$fields);
    }

    public function addNodes(FormNode ...$nodes): self
    {
        $this->children = array_merge($this->children, $nodes);

        return $this;
    }

    public function inline(bool $inline = true): self
    {
        $this->inline = $inline;

        return $this;
    }

    public function labelWidth(string $labelWidth): self
    {
        $this->labelWidth = $labelWidth;

        return $this;
    }

    public function submitLabel(string $submitLabel): self
    {
        $this->submitLabel = $submitLabel;

        return $this;
    }

    public function resetLabel(string $resetLabel): self
    {
        $this->resetLabel = $resetLabel;

        return $this;
    }

    public function key(): string
    {
        return $this->key;
    }

    public function schema(): FormSchema
    {
        return (new FormSchemaWalker())->build($this->children);
    }

    public function fields(): array
    {
        return array_map(
            static fn($fieldSchema) => $fieldSchema->field(),
            $this->schema()->fields()
        );
    }

    /**
     * @return FormNode[]
     */
    public function children(): array
    {
        return $this->children;
    }

    public function isInline(): bool
    {
        return $this->inline;
    }

    public function getLabelWidth(): string
    {
        return $this->labelWidth;
    }

    public function getSubmitLabel(): string
    {
        return $this->submitLabel;
    }

    public function getResetLabel(): string
    {
        return $this->resetLabel;
    }

    public function defaults(): array
    {
        return $this->schema()->defaults();
    }

    public function rules(): array
    {
        return $this->schema()->rules();
    }

    public function remoteOptions(): array
    {
        return $this->schema()->remoteOptions();
    }

    public function uploads(): array
    {
        return $this->schema()->uploads();
    }

    public function selectOptions(): array
    {
        return $this->schema()->selectOptions();
    }

    public function linkages(): array
    {
        return $this->schema()->linkages();
    }
}
