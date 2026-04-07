<?php

namespace Sc\Util\HtmlStructureV2\Components;

use JetBrains\PhpStorm\ExpectedValues;
use Sc\Util\HtmlStructureV2\Components\Concerns\HasEvents;
use Sc\Util\HtmlStructureV2\Contracts\EventAware;
use Sc\Util\HtmlStructureV2\DataSource\ArrayDataSource;
use Sc\Util\HtmlStructureV2\DataSource\DataSourceInterface;
use Sc\Util\HtmlStructureV2\DataSource\UrlDataSource;
use Sc\Util\HtmlStructureV2\Contracts\Renderable;
use Sc\Util\HtmlStructureV2\Contracts\StructuredEventInterface;
use Sc\Util\HtmlStructureV2\Support\Conditionable;
use Sc\Util\HtmlStructureV2\Support\JsExpression;
use Sc\Util\HtmlStructureV2\Support\RendersWithTheme;

final class Table implements Renderable, EventAware
{
    use HasEvents {
        on as private bindTableEventHandler;
    }
    use Conditionable;
    use RendersWithTheme;

    private const SUPPORTED_ON_EVENTS = [
        'loadBefore',
        'loadSuccess',
        'loadFail',
        'pageChange',
        'pageSizeChange',
        'sortChange',
        'selectionChange',
        'deleteSuccess',
        'deleteFail',
    ];

    private array $columns = [];
    private array $toolbarActions = [];
    private array $rowActions = [];
    private ?DataSourceInterface $dataSource = null;
    private bool $pagination = true;
    private int $pageSize = 20;
    private array $pageSizes = [10, 20, 50, 100];
    private bool $stripe = false;
    private bool $border = false;
    private bool $settings = true;
    private string $emptyText = '暂无数据';
    private bool $selection = false;
    private ?string $selectionFixed = null;
    private array $searchSchema = [];
    private ?string $deleteUrl = null;
    private string $deleteKey = 'id';
    private ?int $rowActionColumnWidth = null;
    private int $maxHeight = 0;

    public function __construct(
        private readonly string $key
    ) {
    }

    /**
     * 直接创建一个表格组件实例。
     */
    public static function make(string $key): self
    {
        return new self($key);
    }

    /**
     * 追加表格列定义。
     */
    public function addColumns(Column ...$columns): self
    {
        $this->columns = array_merge($this->columns, $columns);

        return $this;
    }

    /**
     * 设置表格工具栏动作。
     */
    public function toolbar(Action ...$actions): self
    {
        $this->toolbarActions = array_merge($this->toolbarActions, $actions);

        return $this;
    }

    /**
     * 设置每行的操作动作。
     */
    public function rowActions(Action ...$actions): self
    {
        $this->rowActions = array_merge($this->rowActions, $actions);

        return $this;
    }

    /**
     * 设置行操作列固定宽度。
     * 仅影响右侧“操作”列本身；设置后会覆盖默认的自动宽度估算逻辑。
     */
    public function rowActionColumnWidth(int $width): self
    {
        $this->rowActionColumnWidth = $width;

        return $this;
    }

    /**
     * 设置表格最大高度。
     * 传正数时直接作为 el-table 的 max-height；
     * 传负数时按“窗口高度 - 表格顶部距离 + 该偏移”动态计算，
     * 用法与原版 Table::setMaxHeight() 保持一致，默认 -60。
     */
    public function maxHeight(int $maxHeight = -60): self
    {
        $this->maxHeight = $maxHeight;

        return $this;
    }

    /**
     * 直接指定数据源对象。
     */
    public function dataSource(DataSourceInterface $dataSource): self
    {
        $this->dataSource = $dataSource;

        return $this;
    }

    /**
     * 用静态数组作为表格数据源。
     */
    public function rows(array $rows): self
    {
        return $this->dataSource(ArrayDataSource::make($rows));
    }

    /**
     * 用远端接口作为表格数据源。
     * 这里的 `query` 是基础查询参数；运行时会再自动合并当前筛选条件、分页和排序参数。
     */
    public function dataUrl(string $url, array $query = []): self
    {
        return $this->dataSource(UrlDataSource::make($url, $query));
    }

    /**
     * 追加一条搜索协议定义。
     * `name` 是筛选表单里的字段名，`field` 是最终发给后端的真实字段名；
     * 例如 `search('keyword', 'LIKE', 'user_name')`。
     * 若当前表格被放进 `List` 且未显式提供 filters()，这类搜索协议也会参与默认筛选表单推导；
     * 但没有列展示信息时，只能按字段名做通用输入框/范围输入推断。
     */
    public function search(
        string $name,
        #[ExpectedValues(Column::SUPPORTED_SEARCH_TYPES)]
        string $type = '=',
        ?string $field = null
    ): self
    {
        $this->searchSchema[$name] = $this->normalizeSearchSchemaItem($name, [
            'type' => $type,
            'field' => $field,
        ]);

        return $this;
    }

    /**
     * 批量设置搜索协议定义。
     * 常用于需要和外部 filters/form 显式对齐时一次性声明整张表的搜索协议。
     * 在 `List` 自动筛选模式下，未被列 searchable() 覆盖的字段也会尝试按这里的 name/type 推导默认输入控件。
     */
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

    /**
     * 控制是否显示分页。
     */
    public function pagination(bool $pagination = true): self
    {
        $this->pagination = $pagination;

        return $this;
    }

    /**
     * 设置默认每页条数。
     */
    public function pageSize(int $pageSize): self
    {
        $this->pageSize = $pageSize;

        return $this;
    }

    /**
     * 设置分页器可选页大小列表。
     */
    public function pageSizes(array $pageSizes): self
    {
        $this->pageSizes = array_values($pageSizes);

        return $this;
    }

    /**
     * 控制是否启用斑马纹。
     */
    public function stripe(bool $stripe = true): self
    {
        $this->stripe = $stripe;

        return $this;
    }

    /**
     * 控制是否显示边框。
     */
    public function border(bool $border = true): self
    {
        $this->border = $border;

        return $this;
    }

    /**
     * 控制是否启用原版风格的表格设置。
     * 启用后会在工具栏右侧提供“列设置”，支持调整斑马纹、边框、列显示、宽度、固定和对齐，并持久化到本地。
     */
    public function settings(bool $settings = true): self
    {
        $this->settings = $settings;

        return $this;
    }

    /**
     * settings() 的兼容别名。
     */
    public function openSetting(bool $settings = true): self
    {
        return $this->settings($settings);
    }

    /**
     * 设置空数据提示文案。
     */
    public function emptyText(string $emptyText): self
    {
        $this->emptyText = $emptyText;

        return $this;
    }

    /**
     * 控制是否开启勾选列。
     * 开启后动作上下文里的 `selection` 会持续可用；如果当前页面是 V2 列表页，还会额外挂到全局：
     * - `window.__scV2Selection`: 主表或默认表的选中结果
     * - `window.__scV2Selections[tableKey]`: 指定表格 key 的选中结果
     * 这样 iframe picker 指向 V2 列表页时，通常不需要再手写 `selectionPath()`。
     */
    public function selection(bool $selection = true): self
    {
        $this->selection = $selection;

        return $this;
    }

    /**
     * 固定勾选列位置，默认固定到左侧。
     * 调用后会自动开启 selection()。
     */
    public function selectionFixed(
        #[ExpectedValues(['left', 'right'])]
        string $position = 'left'
    ): self {
        $this->selection = true;
        $this->selectionFixed = $position;

        return $this;
    }

    /**
     * 设置删除接口地址。
     * 内置 `Actions::delete()` 会向这里发起 POST，默认请求体形如 `{"ids": [selection[*][deleteKey]]}`。
     * 未被 `Actions::delete()->deleteUrl()` 显式覆盖时使用。
     * 若需要单条行删除，建议显式使用 `Actions::request()` 自行组织 payload。
     */
    public function deleteUrl(?string $deleteUrl): self
    {
        $this->deleteUrl = $deleteUrl;

        return $this;
    }

    /**
     * 设置删除接口中主键字段名。
     * 仅影响内置批量删除快捷从 selection 中提取主键时使用的字段名。
     * 未被 `Actions::delete()->deleteKey()` 显式覆盖时使用。
     */
    public function deleteKey(string $deleteKey): self
    {
        $this->deleteKey = $deleteKey;

        return $this;
    }

    /**
     * 绑定表格运行时事件。
     * 可用事件：loadBefore / loadSuccess / loadFail / pageChange / pageSizeChange / sortChange / selectionChange / deleteSuccess / deleteFail。
     *
     * handler 签名：`(context) => mixed`
     * 推荐写法：`({ tableKey, rows, allRows, selection, filters, page, pageSize, sort, ids, payload, error, vm }) => {}`
     * 不按位置参数传值。
     *
     * 公共上下文：
     * - tableKey / tableConfig
     * - tableState / state: 当前表格状态对象
     * - rows / allRows / selection
     * - filters: 当前关联筛选条件
     * - vm: 当前 Vue 实例
     *
     * 事件额外字段：
     * - loadSuccess: payload / response
     * - loadFail: error
     * - pageChange: page
     * - pageSizeChange: pageSize
     * - sortChange: sort / payload
     * - deleteSuccess: selection / ids / payload / response
     * - deleteFail: selection / ids / error
     */
    public function on(
        #[ExpectedValues(self::SUPPORTED_ON_EVENTS)]
        string $event,
        string|JsExpression|StructuredEventInterface $handler
    ): static {
        return $this->bindTableEventHandler($event, $handler);
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

    public function useSettings(): bool
    {
        return $this->settings;
    }

    public function getEmptyText(): string
    {
        return $this->emptyText;
    }

    public function hasSelection(): bool
    {
        return $this->selection || $this->hasExplicitSelectionColumn();
    }

    public function getSelectionFixed(): ?string
    {
        return $this->selectionFixed;
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

    public function getRowActionColumnWidth(): ?int
    {
        return $this->rowActionColumnWidth;
    }

    public function getMaxHeight(): int
    {
        return $this->maxHeight;
    }

    public function hasExplicitSelectionColumn(): bool
    {
        foreach ($this->columns as $column) {
            if ($column->isRenderable() && $column->isSelectionColumn()) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array<string, string>
     */
    protected function defineSupportedEvents(): array
    {
        return [
            'loadBefore' => '表格开始加载数据前触发，返回 false 可取消本次加载。',
            'loadSuccess' => '表格加载成功后触发，可读取 rows / payload / response。',
            'loadFail' => '表格加载失败后触发，可读取 error。',
            'pageChange' => '分页页码变更后触发，可读取 page。',
            'pageSizeChange' => '分页每页条数变更后触发，可读取 pageSize。',
            'sortChange' => '排序变化后触发，可读取 sort / payload。',
            'selectionChange' => '勾选项变化后触发，可读取 selection。',
            'deleteSuccess' => '批量删除成功后触发，可读取 selection / ids / payload / response。',
            'deleteFail' => '批量删除失败后触发，可读取 selection / ids / error。',
        ];
    }

    private function buildColumnSearchSchema(): array
    {
        $schema = [];

        foreach ($this->columns as $column) {
            if (
                !$column->isSearchable()
                || !$column->isRenderable()
                || $column->isSpecialColumn()
            ) {
                continue;
            }

            $schema[$column->getSearchName()] = $this->normalizeSearchSchemaItem(
                $column->getSearchName(),
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
