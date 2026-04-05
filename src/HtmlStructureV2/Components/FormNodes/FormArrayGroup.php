<?php

namespace Sc\Util\HtmlStructureV2\Components\FormNodes;

use InvalidArgumentException;
use Sc\Util\HtmlStructureV2\Components\Concerns\HasSpan;
use Sc\Util\HtmlStructureV2\Components\FormNodes\Concerns\HasFormNodeChildren;
use Sc\Util\HtmlStructureV2\Contracts\FormNode;
use Sc\Util\HtmlStructureV2\Contracts\FormNodeContainer;

class FormArrayGroup implements FormNode, FormNodeContainer
{
    use HasSpan;
    use HasFormNodeChildren;
    private array $defaultRows = [];
    private ?string $title = null;
    private string $addButtonText = '新增一组';
    private bool $addable = true;
    private bool $removable = true;
    private bool $reorderable = false;
    private int $minRows = 0;
    private ?int $maxRows = null;

    public function __construct(
        private readonly string $name,
        FormNode ...$children
    ) {
        if (trim($name) === '') {
            throw new InvalidArgumentException('Form array group name cannot be empty.');
        }

        $this->setFormNodeChildren(...$children);
    }

    public static function make(string $name, FormNode ...$children): static
    {
        return new static($name, ...$children);
    }

    public function addChildren(FormNode ...$children): static
    {
        return $this->appendFormNodeChildren(...$children);
    }

    public function defaultRows(array $rows): static
    {
        $this->defaultRows = array_values($rows);

        return $this;
    }

    public function title(?string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function addButtonText(string $text): static
    {
        $this->addButtonText = $text;

        return $this;
    }

    public function addable(bool $addable = true): static
    {
        $this->addable = $addable;

        return $this;
    }

    public function removable(bool $removable = true): static
    {
        $this->removable = $removable;

        return $this;
    }

    public function reorderable(bool $reorderable = true): static
    {
        $this->reorderable = $reorderable;

        return $this;
    }

    public function minRows(int $minRows): static
    {
        $this->minRows = max(0, $minRows);

        return $this;
    }

    public function maxRows(?int $maxRows): static
    {
        $this->maxRows = $maxRows === null ? null : max(0, $maxRows);

        return $this;
    }

    public function name(): string
    {
        return $this->name;
    }

    /**
     * @return FormNode[]
     */
    public function getChildren(): array
    {
        return $this->getFormNodeChildren();
    }

    public function getDefaultRows(): array
    {
        return $this->defaultRows;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function getAddButtonText(): string
    {
        return $this->addButtonText;
    }

    public function isAddable(): bool
    {
        return $this->addable;
    }

    public function isRemovable(): bool
    {
        return $this->removable;
    }

    public function isReorderable(): bool
    {
        return $this->reorderable;
    }

    public function runtimeType(): string
    {
        return 'array';
    }

    public function getMinRows(): int
    {
        return $this->minRows;
    }

    public function getMaxRows(): ?int
    {
        return $this->maxRows;
    }

    public function buildInitialRows(array $rowDefaults): array
    {
        $rows = array_map(
            fn(mixed $row) => $this->normalizeRow($row, $rowDefaults),
            $this->defaultRows
        );

        while (count($rows) < $this->getMinRows()) {
            $rows[] = $this->normalizeRow([], $rowDefaults);
        }

        return $rows;
    }

    protected function normalizeRow(mixed $row, array $rowDefaults): array
    {
        if (!is_array($row)) {
            return $rowDefaults;
        }

        return array_replace_recursive($rowDefaults, $row);
    }
}
