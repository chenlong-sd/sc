<?php

namespace Sc\Util\HtmlStructureV2\Components;

use Sc\Util\HtmlStructureV2\DataSource\ArrayDataSource;
use Sc\Util\HtmlStructureV2\DataSource\DataSourceInterface;
use Sc\Util\HtmlStructureV2\DataSource\UrlDataSource;
use Sc\Util\HtmlStructureV2\Contracts\Renderable;
use Sc\Util\HtmlStructureV2\Support\RendersWithTheme;

final class Table implements Renderable
{
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
}
