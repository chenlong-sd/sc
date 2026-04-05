<?php

namespace Sc\Util\HtmlStructureV2\Components;

use Sc\Util\HtmlStructureV2\Components\Concerns\HasEvents;
use Sc\Util\HtmlStructureV2\Contracts\EventAware;
use Sc\Util\HtmlStructureV2\DataSource\ArrayDataSource;
use Sc\Util\HtmlStructureV2\DataSource\DataSourceInterface;
use Sc\Util\HtmlStructureV2\DataSource\UrlDataSource;
use Sc\Util\HtmlStructureV2\Contracts\Renderable;
use Sc\Util\HtmlStructureV2\Support\RendersWithTheme;

final class Table implements Renderable, EventAware
{
    use HasEvents;
    use RendersWithTheme;

    private array $columns = [];
    private array $toolbarActions = [];
    private array $rowActions = [];
    private ?DataSourceInterface $dataSource = null;
    private bool $pagination = true;
    private int $pageSize = 20;
    private array $pageSizes = [10, 20, 50, 100];
    private bool $stripe = true;
    private bool $border = true;
    private string $emptyText = '暂无数据';
    private bool $selection = false;
    private array $searchSchema = [];
    private ?string $deleteUrl = null;
    private string $deleteKey = 'id';

    public function __construct(
        private readonly string $key
    ) {
    }

    public static function make(string $key): self
    {
        return new self($key);
    }

    public function addColumns(Column ...$columns): self
    {
        $this->columns = array_merge($this->columns, $columns);

        return $this;
    }

    public function toolbar(Action ...$actions): self
    {
        $this->toolbarActions = array_merge($this->toolbarActions, $actions);

        return $this;
    }

    public function rowActions(Action ...$actions): self
    {
        $this->rowActions = array_merge($this->rowActions, $actions);

        return $this;
    }

    public function dataSource(DataSourceInterface $dataSource): self
    {
        $this->dataSource = $dataSource;

        return $this;
    }

    public function rows(array $rows): self
    {
        return $this->dataSource(ArrayDataSource::make($rows));
    }

    public function dataUrl(string $url, array $query = []): self
    {
        return $this->dataSource(UrlDataSource::make($url, $query));
    }

    public function search(string $name, string $type = '=', ?string $field = null): self
    {
        $this->searchSchema[$name] = $this->normalizeSearchSchemaItem($name, [
            'type' => $type,
            'field' => $field,
        ]);

        return $this;
    }

    public function searchSchema(array $schema): self
    {
        foreach ($schema as $name => $config) {
            if (!is_string($name) || $name === '') {
                continue;
            }

            $this->searchSchema[$name] = $this->normalizeSearchSchemaItem($name, $config);
        }

        return $this;
    }

    public function pagination(bool $pagination = true): self
    {
        $this->pagination = $pagination;

        return $this;
    }

    public function pageSize(int $pageSize): self
    {
        $this->pageSize = $pageSize;

        return $this;
    }

    public function pageSizes(array $pageSizes): self
    {
        $this->pageSizes = array_values($pageSizes);

        return $this;
    }

    public function stripe(bool $stripe = true): self
    {
        $this->stripe = $stripe;

        return $this;
    }

    public function border(bool $border = true): self
    {
        $this->border = $border;

        return $this;
    }

    public function emptyText(string $emptyText): self
    {
        $this->emptyText = $emptyText;

        return $this;
    }

    public function selection(bool $selection = true): self
    {
        $this->selection = $selection;

        return $this;
    }

    public function deleteUrl(?string $deleteUrl): self
    {
        $this->deleteUrl = $deleteUrl;

        return $this;
    }

    public function deleteKey(string $deleteKey): self
    {
        $this->deleteKey = $deleteKey;

        return $this;
    }

    public function key(): string
    {
        return $this->key;
    }

    public function columns(): array
    {
        return $this->columns;
    }

    public function getToolbarActions(): array
    {
        return $this->toolbarActions;
    }

    public function getRowActions(): array
    {
        return $this->rowActions;
    }

    public function getDataSource(): ?DataSourceInterface
    {
        return $this->dataSource;
    }

    public function hasRemoteDataSource(): bool
    {
        return $this->dataSource?->isRemote() ?? false;
    }

    public function usePagination(): bool
    {
        return $this->pagination;
    }

    public function getPageSize(): int
    {
        return $this->pageSize;
    }

    public function getPageSizes(): array
    {
        return $this->pageSizes;
    }

    public function useStripe(): bool
    {
        return $this->stripe;
    }

    public function useBorder(): bool
    {
        return $this->border;
    }

    public function getEmptyText(): string
    {
        return $this->emptyText;
    }

    public function hasSelection(): bool
    {
        return $this->selection;
    }

    public function getSearchSchema(): array
    {
        return array_replace(
            $this->buildColumnSearchSchema(),
            $this->searchSchema
        );
    }

    public function getDeleteUrl(): ?string
    {
        return $this->deleteUrl;
    }

    public function getDeleteKey(): string
    {
        return $this->deleteKey;
    }

    private function buildColumnSearchSchema(): array
    {
        $schema = [];

        foreach ($this->columns as $column) {
            if (!$column->isSearchable()) {
                continue;
            }

            $schema[$column->prop()] = $this->normalizeSearchSchemaItem(
                $column->prop(),
                $column->getSearchConfig() ?? []
            );
        }

        return $schema;
    }

    private function normalizeSearchSchemaItem(string $name, mixed $config): array
    {
        if (is_string($config)) {
            $config = ['type' => $config];
        }

        if (!is_array($config)) {
            $config = [];
        }

        $type = strtoupper((string)($config['type'] ?? '='));
        $field = $config['field'] ?? null;

        $item = ['type' => $type];
        if (is_string($field) && $field !== '') {
            $item['field'] = $field;
        }

        return $item;
    }
}
