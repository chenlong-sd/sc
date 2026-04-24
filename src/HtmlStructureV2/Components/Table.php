<?php

namespace Sc\Util\HtmlStructureV2\Components;

use JetBrains\PhpStorm\ExpectedValues;
use Stringable;
use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlStructureV2\Components\Concerns\HasEvents;
use Sc\Util\HtmlStructureV2\Components\Concerns\HasRenderAttributes;
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
    use HasRenderAttributes;
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
        'dragSort',
        'deleteSuccess',
        'deleteFail',
    ];
    private const DEFAULT_TRASH_DIALOG_TITLE = '回收站';
    private const DEFAULT_TRASH_DIALOG_WIDTH = '90%';
    private const DEFAULT_TRASH_DIALOG_HEIGHT = '90vh';
    private const DEFAULT_TRASH_QUERY_KEY = 'is_delete';
    private const DEFAULT_TRASH_QUERY_VALUE = 1;
    private const SETTINGS_SELECTION_COLUMN_KEY = '__sc_v2_selection__';
    private const SETTINGS_ROW_ACTION_COLUMN_KEY = '__sc_v2_row_actions__';
    private const SETTINGS_EVENT_COLUMN_KEY_PREFIX = '__sc_v2_event__';

    private array $columns = [];
    private array $toolbarLeftActions = [];
    private array $toolbarRightActions = [];
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
    private ?string $deleteUrl = null;
    private string $deleteKey = 'id';
    private ?int $rowActionColumnWidth = null;
    private int $maxHeight = 0;
    private ?string $rowKey = null;
    private bool $tree = false;
    private array $treeProps = [
        'children' => 'children',
        'checkStrictly' => false,
    ];
    private bool $dragSort = false;
    private string $dragSortLabel = '排序';
    private string $dragSortType = 'primary';
    private ?string $dragSortIcon = 'Rank';
    private array $dragSortConfig = [];
    private array $statusToggles = [];
    private bool $statusTogglesNewLine = false;
    private bool $export = false;
    private string $exportLabel = '导出Excel';
    private string $exportFilename = 'export';
    private string $exportType = 'primary';
    private ?string $exportIcon = 'TakeawayBox';
    private array $exportQuery = [
        'is_export' => 1,
    ];
    private bool $trashEnabled = false;
    private ?string $trashRecoverUrl = null;
    private ?Dialog $trashDialog = null;
    private ?JsExpression $remoteDataHandle = null;

    public function __construct(
        private readonly string $key
    ) {
    }

    /**
     * 直接创建一个表格组件实例。
     *
     * @param string $key 表格唯一 key。
     * @return self 表格实例。
     *
     * 示例：
     * `Table::make('qa-info-table')`
     */
    public static function make(string $key): self
    {
        return new self($key);
    }

    /**
     * 追加表格列定义。
     *
     * @param Column ...$columns 要追加的列定义。
     * @return self 当前表格实例。
     *
     * 示例：
     * `Tables::make('qa-info-table')->addColumns(Tables::column('标题', 'title'))`
     */
    public function addColumns(Column ...$columns): self
    {
        $this->columns = array_merge($this->columns, $columns);

        return $this;
    }

    /**
     * 追加表格工具栏左侧动作。
     * 适合“新增 / 批量操作 / 主操作”这类放在左边的按钮。
     *
     * @param Action ...$actions 工具栏左侧动作。
     * @return self 当前表格实例。
     *
     * 示例：
     * `Tables::make('qa-info-table')->toolbarLeft(Actions::create()->dialog('qa-info-dialog'))`
     */
    public function toolbarLeft(Action ...$actions): self
    {
        $this->toolbarLeftActions = array_merge($this->toolbarLeftActions, $actions);

        return $this;
    }

    /**
     * 追加表格工具栏右侧动作。
     * 适合“辅助操作 / 次级工具”这类放在右边的按钮；导出、列设置等内置工具也会继续展示在右侧。
     *
     * @param Action ...$actions 工具栏右侧动作。
     * @return self 当前表格实例。
     *
     * 示例：
     * `Tables::make('qa-info-table')->toolbarRight(Actions::refresh('刷新'))`
     */
    public function toolbarRight(Action ...$actions): self
    {
        $this->toolbarRightActions = array_merge($this->toolbarRightActions, $actions);

        return $this;
    }

    /**
     * 设置每行的操作动作。
     * 行按钮渲染在表格列插槽 `#default="scope"` 中，
     * 因此前端属性表达式可直接读取 `scope.row` / `scope.$index`，
     * 例如 `->props(['v-if' => 'scope.row.status == 1'])`。
     *
     * 点击事件则会把当前行解构到 action context 的 `row` 字段中，
     * 因此 `onClick()` / `on('click', ...)` 里通常直接写 `({ row }) => ...` 即可，
     * 不需要再手动从 `scope.row` 取值。
     *
     * `Actions::edit()` / `Actions::request()` / `Events::openDialog()` 等会自动携带当前行；
     * `Actions::delete()` 仍是工具栏批量删除语义，不用于 rowActions() 单条删除。
     *
     * @param Action ...$actions 行操作动作。
     * @return self 当前表格实例。
     *
     * 示例：
     * `Tables::make('qa-info-table')->rowActions(
     *     Actions::make('确认')
     *         ->props(['v-if' => 'scope.row.status == 1'])
     *         ->onClick('({ row }) => console.log(row.id)')
     * )`
     */
    public function rowActions(Action ...$actions): self
    {
        $this->rowActions = array_merge($this->rowActions, $actions);

        return $this;
    }

    /**
     * 设置行操作列固定宽度。
     * 仅影响右侧“操作”列本身；设置后会覆盖默认的自动宽度估算逻辑。
     *
     * @param int $width 操作列宽度。
     * @return self 当前表格实例。
     *
     * 示例：
     * `Tables::make('qa-info-table')->rowActionColumnWidth(220)`
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
     *
     * @param int $maxHeight 最大高度或动态偏移，默认值为 -60。
     * @return self 当前表格实例。
     *
     * 示例：
     * `Tables::make('qa-info-table')->maxHeight(-80)`
     */
    public function maxHeight(int $maxHeight = -60): self
    {
        $this->maxHeight = $maxHeight;

        return $this;
    }

    /**
     * 设置表格行唯一键，对应 Element Plus `row-key`。
     * 树表和拖拽排序都会依赖这个键来识别当前行；通常传主键字段，如 `rowKey('id')`。
     * 支持点路径写法，例如 `rowKey('meta.id')`。
     *
     * @param string $rowKey 行唯一键字段名。
     * @return self 当前表格实例。
     *
     * 示例：
     * `Tables::make('qa-info-table')->rowKey('id')`
     */
    public function rowKey(string $rowKey): self
    {
        $this->rowKey = trim($rowKey);

        return $this;
    }

    /**
     * 将当前表格声明为树表。
     * 会同时写入 Element Plus 的 `tree-props`，默认按 `children` 字段读取子节点。
     * 树表推荐配合 rowKey() 一起使用，否则运行时无法稳定识别节点。
     *
     * @param bool $enabled 是否启用树表，默认值为 true。
     * @param string $childrenKey 子节点字段名，默认值为 children。
     * @param bool $checkStrictly 是否严格关联父子勾选。
     * @param string|null $hasChildrenKey 是否存在子节点的字段名。
     * @return self 当前表格实例。
     *
     * 示例：
     * `Tables::make('qa-info-table')->tree(true, 'children')->rowKey('id')`
     */
    public function tree(
        bool $enabled = true,
        string $childrenKey = 'children',
        bool $checkStrictly = false,
        ?string $hasChildrenKey = null
    ): self {
        $this->tree = $enabled;

        if ($enabled) {
            $this->treeProps($childrenKey, $checkStrictly, $hasChildrenKey);
        }

        return $this;
    }

    /**
     * 细化树表的 `tree-props` 配置。
     * `childrenKey` 对应子节点字段；`checkStrictly` 会透传给 Element Plus；
     * `hasChildrenKey` 主要给后续懒加载/占位树节点预留，不传时不会输出。
     *
     * @param string $childrenKey 子节点字段名。
     * @param bool $checkStrictly 是否严格关联父子勾选。
     * @param string|null $hasChildrenKey 是否存在子节点的字段名。
     * @return self 当前表格实例。
     *
     * 示例：
     * `Tables::make('qa-info-table')->treeProps('children', false, 'has_children')`
     */
    public function treeProps(
        string $childrenKey = 'children',
        bool $checkStrictly = false,
        ?string $hasChildrenKey = null
    ): self {
        $this->tree = true;
        $this->treeProps = [
            'children' => $childrenKey,
            'checkStrictly' => $checkStrictly,
        ];

        if ($hasChildrenKey !== null && $hasChildrenKey !== '') {
            $this->treeProps['hasChildren'] = $hasChildrenKey;
        }

        return $this;
    }

    /**
     * 启用表格行拖拽排序。
     * 启用后会在“操作”列前面自动补一个拖拽手柄按钮，默认文案为“排序”。
     * 推荐同时设置 rowKey()；树表场景还应调用 tree()/treeProps()。
     *
     * 拖拽完成后可通过 `on('dragSort', ...)` 获取：
     * - movedRow: 被拖动的行
     * - anchorRow: 本次排序的参考行；向下拖时等于 previousRow，向上拖时等于 nextRow
     * - previousRow / nextRow: 拖拽完成后，被拖动行前后相邻的可见行
     * - oldIndex / newIndex: Sortable 的索引信息
     * - isDown / isMoveDown / isMoveUp: 更直观的拖拽方向标记
     * - oldParentRow / newParentRow / sameParent: 树表时可读取的原父级与目标父级
     * - visibleRows / flatRows / rows / allRows / selection / filters / vm
     *
     * @param bool $enabled 是否启用拖拽排序，默认值为 true。
     * @return self 当前表格实例。
     *
     * 示例：
     * `Tables::make('qa-info-table')->dragSort()->rowKey('id')`
     */
    public function dragSort(bool $enabled = true): self
    {
        $this->dragSort = $enabled;

        return $this;
    }

    /**
     * 自定义拖拽手柄按钮文案。
     * 仅影响操作列里内置的拖拽手柄显示，不影响排序事件名和运行时行为。
     *
     * @param string $label 手柄文案，默认值为 排序。
     * @return self 当前表格实例。
     *
     * 示例：
     * `Tables::make('qa-info-table')->dragSortLabel('拖动排序')`
     */
    public function dragSortLabel(string $label = '排序'): self
    {
        $this->dragSortLabel = $label;

        return $this;
    }

    /**
     * 自定义拖拽手柄按钮类型，默认 `primary`。
     * 这里沿用 Element Plus 按钮 type，可传 `primary` / `success` / `danger` 等。
     *
     * @param string $type 按钮类型，默认值为 primary。
     * @return self 当前表格实例。
     *
     * 示例：
     * `Tables::make('qa-info-table')->dragSortType('warning')`
     */
    public function dragSortType(string $type = 'primary'): self
    {
        $this->dragSortType = $type;

        return $this;
    }

    /**
     * 自定义拖拽手柄按钮图标，默认 `Rank`。
     * 传 null 或空字符串可移除图标。
     *
     * @param string|null $icon 图标名；传 null 表示移除图标。
     * @return self 当前表格实例。
     *
     * 示例：
     * `Tables::make('qa-info-table')->dragSortIcon('Sort')`
     */
    public function dragSortIcon(?string $icon = 'Rank'): self
    {
        $this->dragSortIcon = $icon !== null && $icon !== '' ? $icon : null;

        return $this;
    }

    /**
     * 追加 Sortable 的附加配置。
     * 适合设置 `group` / `ghostClass` / `fallbackOnBody` 等原生参数；
     * V2 会保留自己的 `handle` 和 `onEnd` 实现，避免把运行时主流程覆盖掉。
     *
     * @param array $config Sortable 附加配置。
     * @return self 当前表格实例。
     *
     * 示例：
     * `Tables::make('qa-info-table')->dragSortConfig(['ghostClass' => 'sortable-ghost'])`
     */
    public function dragSortConfig(array $config): self
    {
        $this->dragSort = true;
        $this->dragSortConfig = array_replace($this->dragSortConfig, $config);

        return $this;
    }

    /**
     * 设置表格顶部的快速状态切换按钮组。
     * `name` 是筛选模型字段名；后端真实字段映射应通过 filters() 字段上的 searchable()/searchField()、
     * 或列上的 searchable() 定义。
     * 若当前表格还没有对应搜索协议，V2 会自动补一条 hidden 的 `=` 搜索项，真实字段默认同名。
     *
     * @param string $name 筛选模型字段名。
     * @param array $options 状态切换选项。
     * @param string|AbstractHtmlElement|\Stringable|null $label 按钮组标签。
     * @return self 当前表格实例。
     *
     * 示例：
     * `Tables::make('qa-info-table')->statusToggle('status', [1 => '启用', 0 => '停用'], '状态')`
     */
    public function statusToggle(
        string $name,
        array $options,
        string|AbstractHtmlElement|\Stringable|null $label = null
    ): self {
        $normalizedName = trim($name);
        if ($normalizedName === '') {
            return $this;
        }

        $this->statusToggles[] = [
            'name' => $normalizedName,
            'label' => $label,
            'options' => $this->normalizeStatusToggleOptions($options),
        ];

        return $this;
    }

    /**
     * 控制多组状态切换按钮是否按换行模式展示。
     *
     * @param bool $newLine 是否换行展示，默认值为 true。
     * @return self 当前表格实例。
     *
     * 示例：
     * `Tables::make('qa-info-table')->statusTogglesNewLine()`
     */
    public function statusTogglesNewLine(bool $newLine = true): self
    {
        $this->statusTogglesNewLine = $newLine;

        return $this;
    }

    /**
     * 启用表格导出能力。
     * 默认会在工具栏右侧补一个“导出Excel”按钮；远程表格会复用当前筛选/排序条件重新拉全量数据，
     * 并自动附加 `is_export=1`，方便沿用旧接口的导出分支。
     *
     * @param string $filename 导出文件名，不含扩展名，默认值为 export。
     * @return self 当前表格实例。
     *
     * 示例：
     * `Tables::make('qa-info-table')->export('qa-info')`
     */
    public function export(string $filename = 'export'): self
    {
        $this->export = true;

        return $this->exportFilename($filename);
    }

    /**
     * 设置导出按钮文案。
     *
     * @param string $label 按钮文案，默认值为 导出Excel。
     * @return self 当前表格实例。
     *
     * 示例：
     * `Tables::make('qa-info-table')->exportLabel('导出问答')`
     */
    public function exportLabel(string $label = '导出Excel'): self
    {
        $this->export = true;
        $this->exportLabel = trim($label) !== '' ? trim($label) : '导出Excel';

        return $this;
    }

    /**
     * 设置导出按钮类型。
     *
     * @param string $type 按钮类型，默认值为 primary。
     * @return self 当前表格实例。
     *
     * 示例：
     * `Tables::make('qa-info-table')->exportType('primary')`
     */
    public function exportType(string $type = 'primary'): self
    {
        $this->export = true;
        $this->exportType = trim($type) !== '' ? trim($type) : 'primary';

        return $this;
    }

    /**
     * 设置导出按钮图标；传 null 或空字符串可移除图标。
     *
     * @param string|null $icon 图标名；传 null 表示移除图标。
     * @return self 当前表格实例。
     *
     * 示例：
     * `Tables::make('qa-info-table')->exportIcon('TakeawayBox')`
     */
    public function exportIcon(?string $icon = 'TakeawayBox'): self
    {
        $this->export = true;
        $this->exportIcon = $icon !== null && trim($icon) !== '' ? trim($icon) : null;

        return $this;
    }

    /**
     * 设置导出文件名，不自动补扩展名。
     *
     * @param string $filename 导出文件名。
     * @return self 当前表格实例。
     *
     * 示例：
     * `Tables::make('qa-info-table')->exportFilename('qa-info-export')`
     */
    public function exportFilename(string $filename): self
    {
        $this->export = true;
        $normalized = trim((string)preg_replace('/\.(xlsx|xls)$/i', '', $filename));
        $this->exportFilename = $normalized !== '' ? $normalized : 'export';

        return $this;
    }

    /**
     * 设置导出请求要额外附带的查询参数。
     * 默认是 `['is_export' => 1]`，用于兼容旧接口的全量导出分支。
     *
     * @param array $query 额外查询参数。
     * @return self 当前表格实例。
     *
     * 示例：
     * `Tables::make('qa-info-table')->exportQuery(['is_export' => 1, 'with_detail' => 1])`
     */
    public function exportQuery(array $query): self
    {
        $this->export = true;
        $this->exportQuery = $query;

        return $this;
    }

    /**
     * 直接指定数据源对象。
     *
     * @param DataSourceInterface $dataSource 数据源对象。
     * @return self 当前表格实例。
     *
     * 示例：
     * `Tables::make('qa-info-table')->dataSource(UrlDataSource::make('/admin/qa-info/list'))`
     */
    public function dataSource(DataSourceInterface $dataSource): self
    {
        $this->dataSource = $dataSource;

        return $this;
    }

    /**
     * 用静态数组作为表格数据源。
     *
     * @param array $rows 静态行数据。
     * @return self 当前表格实例。
     *
     * 示例：
     * `Tables::make('qa-info-table')->rows([['id' => 1, 'title' => '示例']])`
     */
    public function rows(array $rows): self
    {
        return $this->dataSource(ArrayDataSource::make($rows));
    }

    /**
     * 用远端接口作为表格数据源。
     * 这里的 `query` 是基础查询参数；运行时会再自动合并当前筛选条件、分页和排序参数。
     *
     * @param string $url 远端数据接口地址。
     * @param array $query 基础查询参数。
     * @return self 当前表格实例。
     *
     * 示例：
     * `Tables::make('qa-info-table')->dataUrl('/admin/qa-info/list', ['type' => 'normal'])`
     */
    public function dataUrl(string $url, array $query = []): self
    {
        return $this->dataSource(UrlDataSource::make($url, $query));
    }

    /**
     * 控制是否显示分页。
     *
     * @param bool $pagination 是否启用分页，默认值为 true。
     * @return self 当前表格实例。
     *
     * 示例：
     * `Tables::make('qa-info-table')->pagination(false)`
     */
    public function pagination(bool $pagination = true): self
    {
        $this->pagination = $pagination;

        return $this;
    }

    /**
     * 设置默认每页条数。
     *
     * @param int $pageSize 每页条数。
     * @return self 当前表格实例。
     *
     * 示例：
     * `Tables::make('qa-info-table')->pageSize(50)`
     */
    public function pageSize(int $pageSize): self
    {
        $this->pageSize = $pageSize;

        return $this;
    }

    /**
     * 设置分页器可选页大小列表。
     *
     * @param array $pageSizes 可选页大小列表。
     * @return self 当前表格实例。
     *
     * 示例：
     * `Tables::make('qa-info-table')->pageSizes([20, 50, 100])`
     */
    public function pageSizes(array $pageSizes): self
    {
        $this->pageSizes = array_values($pageSizes);

        return $this;
    }

    /**
     * 控制是否启用斑马纹。
     *
     * @param bool $stripe 是否启用斑马纹，默认值为 true。
     * @return self 当前表格实例。
     *
     * 示例：
     * `Tables::make('qa-info-table')->stripe()`
     */
    public function stripe(bool $stripe = true): self
    {
        $this->stripe = $stripe;

        return $this;
    }

    /**
     * 控制是否显示边框。
     *
     * @param bool $border 是否显示边框，默认值为 true。
     * @return self 当前表格实例。
     *
     * 示例：
     * `Tables::make('qa-info-table')->border()`
     */
    public function border(bool $border = true): self
    {
        $this->border = $border;

        return $this;
    }

    /**
     * 控制是否启用原版风格的表格设置。
     * 启用后会在工具栏右侧提供“列设置”，支持拖动调整列顺序，
     * 并通过“展示设置 / 导出设置”两个 tab 分别维护展示顺序和导出顺序；
     * 当前激活的 tab 仅作为界面状态使用，不会写入持久化配置；
     * 同时继续控制斑马纹、边框、列显示、导出、导出顺序、宽度、固定和对齐，并持久化到本地。
     *
     * @param bool $settings 是否启用表格设置，默认值为 true。
     * @return self 当前表格实例。
     *
     * 示例：
     * `Tables::make('qa-info-table')->settings(false)`
     */
    public function settings(bool $settings = true): self
    {
        $this->settings = $settings;

        return $this;
    }

    /**
     * 设置空数据提示文案。
     *
     * @param string $emptyText 空数据提示文案。
     * @return self 当前表格实例。
     *
     * 示例：
     * `Tables::make('qa-info-table')->emptyText('暂无问答数据')`
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
     *
     * @param bool $selection 是否启用勾选列，默认值为 true。
     * @return self 当前表格实例。
     *
     * 示例：
     * `Tables::make('qa-info-table')->selection()`
     */
    public function selection(bool $selection = true): self
    {
        $this->selection = $selection;

        return $this;
    }

    /**
     * 固定勾选列位置，默认固定到左侧。
     * 调用后会自动开启 selection()。
     *
     * @param string $position 固定位置，可选 left 或 right，默认值为 left。
     * @return self 当前表格实例。
     *
     * 示例：
     * `Tables::make('qa-info-table')->selectionFixed('left')`
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
     *
     * @param string|null $deleteUrl 批量删除接口地址；传 null 表示清空。
     * @return self 当前表格实例。
     *
     * 示例：
     * `Tables::make('qa-info-table')->deleteUrl('/admin/qa-info/delete')`
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
     *
     * @param string $deleteKey 主键字段名。
     * @return self 当前表格实例。
     *
     * 示例：
     * `Tables::make('qa-info-table')->deleteKey('id')`
     */
    public function deleteKey(string $deleteKey): self
    {
        $this->deleteKey = $deleteKey;

        return $this;
    }


    /**
     * 为远程表格配置响应数据二次处理逻辑。
     * 推荐传函数表达式，例如：({ payload }) => payload?.data || payload
     *
     * 若传入旧版语句体，框架会自动包成 (ctx) => { ...; return data; }。
     *
     * @param string|\Stringable|JsExpression|null $handler 处理逻辑；传 null 表示清空。
     * @return self 当前表格实例。
     */
    public function remoteDataHandle(string|\Stringable|JsExpression|null $handler): self
    {
        if ($handler === null) {
            $this->remoteDataHandle = null;

            return $this;
        }

        $this->remoteDataHandle = $this->normalizeRemoteDataHandleExpression($handler);

        return $this;
    }

    /**
     * 开启表格回收站能力。
     * 仅对远程数据表格生效；启用后会在工具栏右侧自动补一个“回收站”入口，
     * 点击会以 iframe 弹窗打开当前页，并自动追加 `is_delete=1`；
     * 进入回收站模式后，普通工具栏动作和行操作会隐藏，只保留刷新和恢复。
     *
     * @param string|null $recoverUrl 批量恢复接口地址；传 null 表示只开回收站查看，不提供恢复动作。
     * @return self 当前表格实例。
     *
     * 示例：
     * `Tables::make('qa-info-table')->dataUrl('/admin/qa-info/list')->trash('/admin/qa-info/recover')`
     */
    public function trash(?string $recoverUrl = null): self
    {
        $this->trashEnabled = true;
        $this->trashRecoverUrl = $this->normalizeNullableString($recoverUrl);

        return $this;
    }

    /**
     * 单独设置回收站批量恢复接口。
     * 调用后会自动视为启用回收站能力。
     *
     * @param string|null $recoverUrl 批量恢复接口地址；传 null 表示关闭恢复动作。
     * @return self 当前表格实例。
     *
     * 示例：
     * `Tables::make('qa-info-table')->trash()->recoverUrl('/admin/qa-info/recover')`
     */
    public function recoverUrl(?string $recoverUrl): self
    {
        $this->trashEnabled = true;
        $this->trashRecoverUrl = $this->normalizeNullableString($recoverUrl);

        return $this;
    }

    /**
     * 绑定表格运行时事件。
     * 可用事件：loadBefore / loadSuccess / loadFail / pageChange / pageSizeChange / sortChange / selectionChange / dragSort / deleteSuccess / deleteFail。
     *
     * handler 签名：`(context) => mixed`
     * 推荐写法：`({ tableKey, rows, allRows, selection, filters, page, pageSize, sort, movedRow, anchorRow, oldIndex, newIndex, ids, payload, error, vm }) => {}`
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
     * - dragSort: movedRow / anchorRow / previousRow / nextRow / visibleRows / flatRows / oldIndex / newIndex / isDown / isMoveDown / isMoveUp / oldParentRow / newParentRow / sameParent / event
     * - deleteSuccess: selection / ids / payload / response
     * - deleteFail: selection / ids / error
     *
     * @param string $event 事件名。
     * @param string|JsExpression|StructuredEventInterface $handler 事件处理逻辑。
     * @return static 当前表格实例。
     *
     * 示例：
     * `Tables::make('qa-info-table')->on('loadSuccess', '({ rows }) => console.log(rows)')`
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

    /**
     * 获取当前表格全部工具栏动作。
     * 返回值会合并左侧和右侧两组动作，主要给动作收集逻辑使用。
     *
     * @return Action[]
     */
    public function getToolbarActions(): array
    {
        return array_values(array_merge(
            $this->getToolbarLeftActions(),
            $this->getToolbarRightActions()
        ));
    }

    /**
     * 获取当前表格工具栏左侧动作。
     *
     * @return Action[]
     */
    public function getToolbarLeftActions(): array
    {
        return $this->filterAvailableActions($this->toolbarLeftActions);
    }

    /**
     * 获取当前表格工具栏右侧动作。
     *
     * @return Action[]
     */
    public function getToolbarRightActions(): array
    {
        return $this->filterAvailableActions($this->toolbarRightActions);
    }

    public function getRowActions(): array
    {
        return $this->filterAvailableActions($this->rowActions);
    }

    public function hasManagedRowActionColumn(): bool
    {
        return $this->getRowActions() !== [] || $this->useDragSort();
    }

    /**
     * 返回参与“列设置”的列定义。
     * 普通数据列沿用 prop 作为 key，事件列和自动补齐的行操作列会分配保留 key。
     *
     * @return array<int, array<string, mixed>>
     */
    public function getSettingsColumnDefinitions(): array
    {
        $definitions = [];
        $eventKeyCounters = [];
        $selectionKeyCounter = 0;

        if ($this->selection && !$this->hasExplicitSelectionColumn()) {
            $definitions[] = [
                'key' => self::SETTINGS_SELECTION_COLUMN_KEY,
                'label' => '选择',
                'show' => true,
                'width' => 48,
                'fixed' => $this->selectionFixed,
                'align' => 'center',
                'export' => false,
                'exportSort' => null,
                'allowExportControl' => false,
                'kind' => 'selection',
                'column' => null,
            ];
        }

        foreach ($this->columns as $column) {
            if (!$column->isRenderable()) {
                continue;
            }

            if ($column->isSelectionColumn()) {
                $definitions[] = [
                    'key' => $this->buildSelectionSettingsColumnKey($selectionKeyCounter),
                    'label' => '选择',
                    'show' => true,
                    'width' => $column->getWidth() ?? 48,
                    'fixed' => $column->getFixed(),
                    'align' => $column->getAlign() ?? 'center',
                    'export' => false,
                    'exportSort' => null,
                    'allowExportControl' => false,
                    'kind' => 'selection',
                    'column' => $column,
                ];
                continue;
            }

            if ($column->supportsSettings()) {
                $definitions[] = $this->buildSettingsColumnDefinition($column, $column->prop());
                continue;
            }

            if ($column->isEventColumn()) {
                $definitions[] = $this->buildSettingsColumnDefinition(
                    $column,
                    $this->buildEventSettingsColumnKey($column, $eventKeyCounters),
                    false
                );
            }
        }

        if ($this->hasManagedRowActionColumn()) {
            $definitions[] = [
                'key' => self::SETTINGS_ROW_ACTION_COLUMN_KEY,
                'label' => '操作',
                'show' => true,
                'width' => $this->rowActionColumnWidth,
                'fixed' => 'right',
                'align' => 'center',
                'export' => false,
                'exportSort' => null,
                'allowExportControl' => false,
                'kind' => 'row_actions',
                'column' => null,
            ];
        }

        return $definitions;
    }

    public function getDataSource(): ?DataSourceInterface
    {
        return $this->dataSource;
    }

    public function getRemoteDataHandle(): ?JsExpression
    {
        return $this->remoteDataHandle;
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
        return $this->appendImplicitStatusToggleSearchSchema(
            $this->buildColumnSearchSchema()
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

    public function useTrash(): bool
    {
        return $this->trashEnabled && $this->hasRemoteDataSource();
    }

    public function getTrashRecoverUrl(): ?string
    {
        return $this->trashRecoverUrl;
    }

    public function getTrashDialogKey(): string
    {
        return $this->key . '-trash-dialog';
    }

    public function getTrashDialog(): ?Dialog
    {
        if (!$this->useTrash()) {
            return null;
        }

        if ($this->trashDialog instanceof Dialog) {
            return $this->trashDialog;
        }

        return $this->trashDialog = Dialog::make($this->getTrashDialogKey(), self::DEFAULT_TRASH_DIALOG_TITLE)
            ->width(self::DEFAULT_TRASH_DIALOG_WIDTH)
            ->height(self::DEFAULT_TRASH_DIALOG_HEIGHT)
            ->alignCenter()
            ->iframe('@page.url', [
                self::DEFAULT_TRASH_QUERY_KEY => self::DEFAULT_TRASH_QUERY_VALUE,
            ])
            ->iframeHost(false);
    }

    public function getTrashDialogTitle(): string
    {
        return self::DEFAULT_TRASH_DIALOG_TITLE;
    }

    public function getTrashQueryKey(): string
    {
        return self::DEFAULT_TRASH_QUERY_KEY;
    }

    public function getTrashQueryValue(): int
    {
        return self::DEFAULT_TRASH_QUERY_VALUE;
    }

    public function getRowActionColumnWidth(): ?int
    {
        return $this->rowActionColumnWidth;
    }

    public function getMaxHeight(): int
    {
        return $this->maxHeight;
    }

    public function getRowKey(): ?string
    {
        return $this->rowKey;
    }

    public function isTree(): bool
    {
        return $this->tree;
    }

    public function getTreeProps(): array
    {
        return $this->treeProps;
    }

    public function getTreeChildrenKey(): string
    {
        $childrenKey = $this->treeProps['children'] ?? 'children';

        return is_string($childrenKey) && $childrenKey !== '' ? $childrenKey : 'children';
    }

    public function useDragSort(): bool
    {
        return $this->dragSort;
    }

    public function getStatusToggles(): array
    {
        return $this->statusToggles;
    }

    public function useStatusTogglesNewLine(): bool
    {
        return $this->statusTogglesNewLine;
    }

    public function useExport(): bool
    {
        return $this->export;
    }

    public function getExportLabel(): string
    {
        return $this->exportLabel;
    }

    public function getExportFilename(): string
    {
        return $this->exportFilename;
    }

    public function getExportType(): string
    {
        return $this->exportType;
    }

    public function getExportIcon(): ?string
    {
        return $this->exportIcon;
    }

    public function getExportQuery(): array
    {
        return $this->exportQuery;
    }

    public function getDragSortLabel(): string
    {
        return $this->dragSortLabel;
    }

    public function getDragSortType(): string
    {
        return $this->dragSortType;
    }

    public function getDragSortIcon(): ?string
    {
        return $this->dragSortIcon;
    }

    public function getDragSortConfig(): array
    {
        return $this->dragSortConfig;
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

    private function buildSettingsColumnDefinition(
        Column $column,
        string $key,
        bool $allowExportControl = true
    ): array {
        return [
            'key' => $key,
            'label' => $column->label() !== '' ? $column->label() : $key,
            'show' => true,
            'width' => $column->getWidth(),
            'fixed' => $column->getFixed(),
            'align' => $column->getAlign(),
            'export' => $allowExportControl && ($column->getExportExcel()['allow'] ?? true) === true,
            'exportSort' => $allowExportControl ? ($column->getExportExcel()['sort'] ?? null) : null,
            'allowExportControl' => $allowExportControl,
            'kind' => $column->isEventColumn() ? 'event' : 'column',
            'column' => $column,
        ];
    }

    private function buildEventSettingsColumnKey(Column $column, array &$eventKeyCounters): string
    {
        if ($column->prop() !== '') {
            return $column->prop();
        }

        $base = $this->normalizeSettingsColumnKeySegment($column->label());
        $eventKeyCounters[$base] = ($eventKeyCounters[$base] ?? 0) + 1;

        return sprintf(
            '%s_%s_%d',
            self::SETTINGS_EVENT_COLUMN_KEY_PREFIX,
            $base,
            $eventKeyCounters[$base]
        );
    }

    private function buildSelectionSettingsColumnKey(int &$selectionKeyCounter): string
    {
        $selectionKeyCounter++;

        if ($selectionKeyCounter === 1) {
            return self::SETTINGS_SELECTION_COLUMN_KEY;
        }

        return self::SETTINGS_SELECTION_COLUMN_KEY . '_' . $selectionKeyCounter;
    }

    private function normalizeSettingsColumnKeySegment(string $value): string
    {
        $normalized = preg_replace('/[^A-Za-z0-9_]+/u', '_', trim($value)) ?? '';
        $normalized = trim($normalized, '_');

        return $normalized !== '' ? $normalized : 'actions';
    }

    private function normalizeRemoteDataHandleExpression(string|\Stringable|JsExpression $handler): JsExpression
    {
        $source = trim($handler instanceof JsExpression ? $handler->expression() : (string)$handler);
        if ($source === '') {
            return JsExpression::make('(ctx) => ctx?.payload ?? null');
        }

        if ($this->looksLikeFunctionExpression($source)) {
            return JsExpression::make($source);
        }

        return JsExpression::make($this->wrapStatementAsCallable(
            $source,
            'return data === undefined ? payload : data;'
        ));
    }

    private function wrapStatementAsCallable(string $source, string $tail = ''): string
    {
        $body = trim(sprintf(<<<'JS'
const event = ctx?.event ?? null;
const evt = event;
const response = ctx?.response ?? null;
const request = ctx?.request ?? null;
const row = ctx?.row ?? null;
const tableKey = ctx?.tableKey ?? null;
const tableConfig = ctx?.tableConfig ?? null;
const state = ctx?.state ?? null;
const selection = Array.isArray(ctx?.selection) ? ctx.selection : [];
const rows = Array.isArray(ctx?.rows) ? ctx.rows : [];
const allRows = Array.isArray(ctx?.allRows) ? ctx.allRows : [];
let payload = ctx?.payload ?? null;
let data = payload;
const movedRow = ctx?.movedRow ?? row;
const anchorRow = ctx?.anchorRow ?? null;
const previousRow = ctx?.previousRow ?? null;
const nextRow = ctx?.nextRow ?? null;
const oldIndex = ctx?.oldIndex ?? -1;
const newIndex = ctx?.newIndex ?? -1;
const isDown = ctx?.isDown ?? false;
const isMoveDown = ctx?.isMoveDown ?? false;
const isMoveUp = ctx?.isMoveUp ?? false;
const oldParentRow = ctx?.oldParentRow ?? null;
const newParentRow = ctx?.newParentRow ?? null;
const sameParent = ctx?.sameParent ?? true;
const scope = ctx?.scope ?? { row };
%s
%s
JS, $source, trim($tail)));

        return sprintf(
            "(ctx) => { const vm = ctx?.vm ?? null; const executor = Function('ctx', %s); return executor.call(vm, ctx); }",
            json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)
        );
    }

    private function looksLikeFunctionExpression(string $source): bool
    {
        return str_contains($source, '=>')
            || preg_match('/^(async\s+)?function\b/', $source) === 1;
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
            'dragSort' => '拖拽排序完成后触发，可读取 movedRow / anchorRow / previousRow / nextRow / oldIndex / newIndex / isDown / isMoveDown / isMoveUp / oldParentRow / newParentRow。',
            'deleteSuccess' => '批量删除成功后触发，可读取 selection / ids / payload / response。',
            'deleteFail' => '批量删除失败后触发，可读取 selection / ids / error。',
        ];
    }

    /**
     * @param Action[] $actions
     * @return Action[]
     */
    private function filterAvailableActions(array $actions): array
    {
        return array_values(array_filter(
            $actions,
            static fn (Action $action): bool => $action->isAvailable()
        ));
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

    private function appendImplicitStatusToggleSearchSchema(array $schema): array
    {
        foreach ($this->statusToggles as $toggle) {
            $name = is_string($toggle['name'] ?? null) ? trim($toggle['name']) : '';
            if ($name === '' || isset($schema[$name])) {
                continue;
            }

            $schema[$name] = $this->normalizeSearchSchemaItem($name, [
                'type' => '=',
                'hidden' => true,
            ]);
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
        if (($config['hidden'] ?? false) === true) {
            $item['hidden'] = true;
        }

        return $item;
    }

    private function normalizeStatusToggleOptions(array $options): array
    {
        $normalized = [];

        foreach ($options as $index => $option) {
            if (!is_array($option)) {
                $normalized[] = [
                    'value' => $index,
                    'label' => (string)$option,
                ];

                continue;
            }

            $normalized[] = [
                'value' => $option['value'] ?? $index,
                'label' => (string)($option['label'] ?? ($option['value'] ?? $index)),
            ];
        }

        return $normalized;
    }

    private function normalizeNullableString(?string $value): ?string
    {
        $normalized = is_string($value) ? trim($value) : null;

        return $normalized !== '' ? $normalized : null;
    }
}
