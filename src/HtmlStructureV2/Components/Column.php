<?php

namespace Sc\Util\HtmlStructureV2\Components;

use JetBrains\PhpStorm\ExpectedValues;
use Sc\Util\HtmlElement\El;
use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlStructure\Table\ColumnTags;

final class Column
{
    public const SUPPORTED_SEARCH_TYPES = ['=', '!=', '<>', '>', '>=', '<', '<=', 'LIKE', 'LIKE_RIGHT', 'IN', 'BETWEEN'];
    private const COLUMN_TYPE_NORMAL = 'normal';
    private const COLUMN_TYPE_SELECTION = 'selection';
    private const COLUMN_TYPE_INDEX = 'index';
    private const COLUMN_TYPE_EXPAND = 'expand';
    private const COLUMN_TYPE_EVENT = 'event';

    private const DISPLAY_TYPE_MAPPING = 'mapping';
    private const DISPLAY_TYPE_TAG = 'tag';
    private const DISPLAY_TYPE_IMAGE = 'image';
    private const DISPLAY_TYPE_IMAGES = 'images';
    private const DISPLAY_TYPE_BOOLEAN = 'boolean';
    private const DISPLAY_TYPE_BOOLEAN_TAG = 'boolean_tag';
    private const DISPLAY_TYPE_SWITCH = 'switch';
    private const DISPLAY_TYPE_DATETIME = 'datetime';
    private const DISPLAY_TYPE_OPEN_PAGE = 'open_page';

    private int|string|null $width = null;
    private int|string|null $minWidth = null;
    private ?string $align = null;
    private ?string $fixed = null;
    private string $columnType = self::COLUMN_TYPE_NORMAL;
    private bool $showOverflowTooltip = true;
    private bool $sortable = false;
    private ?string $sortField = null;
    private ?string $format = null;
    private ?array $display = null;
    private ?array $search = null;
    private array $attrs = [];
    private array $tip = [];
    private array $appendContent = [];
    private array $exportExcel = [
        'sort' => null,
        'allow' => true,
    ];
    private array $managedOpenPageDialogs = [];
    private bool $hidden = false;
    private string $placeholder = '-';

    public function __construct(
        private readonly string $label,
        private readonly string $prop
    ) {
    }

    /**
     * 直接创建一个表格列实例。
     *
     * @param string $label 列标题。
     * @param string $prop 行数据字段路径。
     * @return self 列实例。
     *
     * 示例：
     * `Column::make('标题', 'title')`
     */
    public static function make(string $label, string $prop): self
    {
        return new self($label, $prop);
    }

    /**
     * 创建普通列。
     * time/date 字段会沿用常见列表页的默认宽度推断。
     *
     * @param string $label 列标题。
     * @param string $prop 行数据字段路径，默认值为空字符串。
     * @return self 普通列实例。
     *
     * 示例：
     * `Column::normal('标题', 'title')`
     */
    public static function normal(string $label, string $prop = ''): self
    {
        $column = new self($label, $prop);

        if ($prop !== '') {
            if (str_contains($prop, 'time')) {
                $column->width(170);
            } elseif (str_contains($prop, 'date')) {
                $column->width(100);
            }
        }

        return $column;
    }

    /**
     * 创建勾选列。
     *
     * @return self 勾选列实例。
     *
     * 示例：
     * `Column::selection()`
     */
    public static function selection(): self
    {
        return (new self('', ''))
            ->type(self::COLUMN_TYPE_SELECTION)
            ->width(48)
            ->align('center');
    }

    /**
     * 创建序号列。
     *
     * @param string $title 列标题，默认值为 序号。
     * @return self 序号列实例。
     *
     * 示例：
     * `Column::index()`
     */
    public static function index(string $title = '序号'): self
    {
        return (new self($title, ''))
            ->type(self::COLUMN_TYPE_INDEX)
            ->width(80)
            ->fixed('left')
            ->align('center');
    }

    /**
     * 创建展开列。
     *
     * @param string $title 列标题，默认值为空字符串。
     * @return self 展开列实例。
     *
     * 示例：
     * `Column::expand()`
     */
    public static function expand(string $title = ''): self
    {
        return (new self($title, ''))
            ->type(self::COLUMN_TYPE_EXPAND);
    }

    /**
     * 创建事件列。
     *
     * @param string $title 列标题，默认值为 操作。
     * @param string $prop 绑定字段名，默认值为空字符串。
     * @return self 操作列实例。
     *
     * 示例：
     * `Column::event('操作')`
     */
    public static function event(string $title = '操作', string $prop = ''): self
    {
        return (new self($title, $prop))
            ->type(self::COLUMN_TYPE_EVENT)
            ->props([
                'mark-event' => 'true',
                'class-name' => 'sc-v2-event-column',
                ':show-overflow-tooltip' => 'false',
            ]);
    }

    /**
     * 设置列固定宽度。
     *
     * @param int|string $width 列宽。
     * @param bool $showOverflowTooltip 是否显示溢出提示，默认值为 true。
     * @return self 当前列实例。
     *
     * 示例：
     * `Tables::column('标题', 'title')->width(220)`
     */
    public function width(int|string $width, bool $showOverflowTooltip = true): self
    {
        $this->width = $width;
        $this->showOverflowTooltip = $showOverflowTooltip;
        if (!$showOverflowTooltip && $this->align === null) {
            $this->align = 'left';
        }

        return $this;
    }

    /**
     * 设置列最小宽度。
     *
     * @param int|string $minWidth 最小宽度。
     * @return self 当前列实例。
     *
     * 示例：
     * `Tables::column('标题', 'title')->minWidth(180)`
     */
    public function minWidth(int|string $minWidth): self
    {
        $this->minWidth = $minWidth;

        return $this;
    }

    /**
     * 设置列对齐方式，例如 left / center / right。
     *
     * @param string $align 对齐方式。
     * @return self 当前列实例。
     *
     * 示例：
     * `Tables::column('状态', 'status')->align('center')`
     */
    public function align(string $align): self
    {
        $this->align = $align;

        return $this;
    }

    /**
     * 设置列固定位置，例如 left / right。
     *
     * @param string $position 固定位置，默认值为 right。
     * @return self 当前列实例。
     *
     * 示例：
     * `Tables::column('操作', 'id')->fixed('right')`
     */
    public function fixed(string $position = 'right'): self
    {
        $this->fixed = $position;

        return $this;
    }

    /**
     * 设置列类型，主要用于内部特殊列构造。
     *
     * @param string $type 列类型。
     * @return self 当前列实例。
     *
     * 示例：
     * `Column::make('操作', '')->type('event')`
     */
    public function type(string $type): self
    {
        $this->columnType = $type;

        return $this;
    }

    /**
     * 透传底层 el-table-column 属性。
     * 动态属性请自行带上 ":" / "@" 前缀。
     * 若键名以 ":" 开头：
     * - 字符串值按原始前端表达式输出
     * - 数组/布尔/数字/null 会自动转成 JS 字面量
     *
     * @param array $attrs 属性数组。
     * @return self 当前列实例。
     *
     * 示例：
     * `Tables::column('标题', 'title')->props(['class-name' => 'title-column'])`
     */
    public function props(array $attrs): self
    {
        foreach ($attrs as $key => &$item) {
            if (is_bool($item)) {
                $item = $item ? 'true' : 'false';
            }
        }

        $this->attrs = array_merge($this->attrs, $attrs);

        return $this;
    }

    /**
     * 控制列是否可排序，也可直接传真实排序字段名。
     *
     * @param string|bool $sortable 是否开启排序或真实排序字段名，默认值为 true。
     * @return self 当前列实例。
     *
     * 示例：
     * `Tables::column('创建时间', 'create_time')->sortable('create_time')`
     */
    public function sortable(string|bool $sortable = true): self
    {
        if (is_string($sortable)) {
            $this->sortable = true;
            $this->sortField = $sortable;

            return $this;
        }

        $this->sortable = $sortable;

        return $this;
    }

    /**
     * 显式设置排序字段名。
     *
     * @param string $sortField 排序字段名。
     * @return self 当前列实例。
     *
     * 示例：
     * `Tables::column('创建时间', 'create_time')->sortField('create_time')`
     */
    public function sortField(string $sortField): self
    {
        $this->sortField = $sortField;
        $this->sortable = true;

        return $this;
    }

    /**
     * 开启列搜索，也可直接传搜索操作符。
     * 未指定 type 时默认使用 `=`；未指定真实查询字段时默认使用当前列 prop。
     * 开启后会按当前列 prop 自动生成一条搜索协议；若有独立筛选表单，
     * 对应字段名通常应与列 prop 保持一致。
     * 当当前列放在 `List` 里且未显式调用 `filters()` 时，V2 会尝试基于 searchable()
     * 自动生成默认筛选 UI；这些自动生成的筛选项默认隐藏 label，只保留 placeholder，
     * `displayMapping()` / `displayTag()` / `displayBoolean*()` / `displaySwitch()` 会优先推成 select。
     *
     * @param string|bool $searchable 是否启用搜索或直接指定搜索操作符，默认值为 true。
     * @param string|null $field 后端真实字段名；传 null 时默认使用当前列 prop。
     * @return self 当前列实例。
     *
     * 示例：
     * `Tables::column('标题', 'title')->searchable('LIKE')`
     */
    public function searchable(
        #[ExpectedValues(self::SUPPORTED_SEARCH_TYPES)]
        string|bool $searchable = true,
        ?string $field = null
    ): self
    {
        if ($searchable === false) {
            $this->search = null;

            return $this;
        }

        $this->search ??= [];
        $this->search['type'] = is_string($searchable) ? strtoupper($searchable) : '=';
        $this->search['field'] = $field ?: $this->prop;

        return $this;
    }

    /**
     * 设置列搜索操作符类型。
     *
     * @param string $type 搜索操作符类型。
     * @return self 当前列实例。
     *
     * 示例：
     * `Tables::column('状态', 'status')->searchType('IN')`
     */
    public function searchType(
        #[ExpectedValues(self::SUPPORTED_SEARCH_TYPES)]
        string $type
    ): self
    {
        $this->search ??= [];
        $this->search['type'] = strtoupper($type);

        return $this;
    }

    /**
     * 设置列搜索映射到的真实字段名。
     *
     * @param string $field 后端真实字段名。
     * @return self 当前列实例。
     *
     * 示例：
     * `Tables::column('标题', 'title')->searchField('qa_title')`
     */
    public function searchField(string $field): self
    {
        $this->search ??= [];
        $this->search['field'] = $field;

        return $this;
    }

    /**
     * 设置通用展示格式字符串。
     * 会直接作为列插槽内容输出；可传原始 HTML/Vue 模板片段，当前行变量名是 `scope`，
     * 例如 `{{ scope.row.name }} / {{ scope.row.id }}`。
     *
     * @param string|\Stringable $format 展示模板字符串。
     * @return self 当前列实例。
     *
     * 示例：
     * `Tables::column('标题', 'title')->displayFormat('{{ scope.row.title }}')`
     */
    public function displayFormat(string|\Stringable $format): self
    {
        $this->format = (string)$format;

        return $this;
    }

    /**
     * 把值映射为标签文本，支持数组值拼接展示。
     * 适合枚举值转文案。若单元格本身是数组，会按 separator 把多项映射结果拼接起来；
     * 未命中的值会显示为空。
     * 选项默认支持两种常见写法：
     * - `[1 => '是', 0 => '否']`
     * - `[['value' => 1, 'label' => '是'], ['value' => 0, 'label' => '否']]`
     *
     * @param array $options 映射选项。
     * @param string $separator 多值拼接分隔符，默认值为 `, `。
     * @return self 当前列实例。
     *
     * 示例：
     * `Tables::column('状态', 'status')->displayMapping([1 => '启用', 0 => '停用'])`
     */
    public function displayMapping(array $options, string $separator = ', '): self
    {
        $this->display = [
            'type' => self::DISPLAY_TYPE_MAPPING,
            'options' => $this->normalizeDisplayOptions($options),
            'separator' => $separator,
        ];

        return $this;
    }

    /**
     * 把值映射为标签展示，支持直接传入枚举 `::tagsMapping()` / `::tagMapping()`。
     * 每个选项可包含 `label` / `type` / `effect` / `theme`；
     * 当缺少 `type` 时会回退到 defaultType。
     *
     * @param array|ColumnTags $options 标签配置。
     * @param string $defaultType 默认标签类型，默认值为 info。
     * @return self 当前列实例。
     *
     * 示例：
     * `Tables::column('状态', 'status')->displayTag([1 => ['label' => '启用', 'type' => 'success']])`
     */
    public function displayTag(array|ColumnTags $options, string $defaultType = 'info'): self
    {
        $this->display = [
            'type' => self::DISPLAY_TYPE_TAG,
            'options' => $this->normalizeTagDisplayOptions($options, $defaultType),
            'defaultType' => $defaultType,
        ];

        return $this;
    }

    /**
     * 把字段按单张图片展示。
     * 默认把列值本身当作图片 URL。
     *
     * @param int $width 图片宽度，默认值为 60。
     * @param int $height 图片高度，默认值为 60。
     * @param string $fit 图片适配方式，默认值为 cover。
     * @return self 当前列实例。
     *
     * 示例：
     * `Tables::column('封面', 'cover')->displayImage(80, 80)`
     */
    public function displayImage(
        int $width = 60,
        int $height = 60,
        string $fit = 'cover'
    ): self {
        $this->display = [
            'type' => self::DISPLAY_TYPE_IMAGE,
            'width' => $width,
            'height' => $height,
            'fit' => $fit,
        ];

        return $this;
    }

    /**
     * 把布尔值展示为是/否文案。
     * truthy/falsy 判断兼容 `true/false`、`1/0`、`yes/no`、`on/off` 等常见写法。
     *
     * @param string $truthyLabel 真值文案，默认值为 是。
     * @param string $falsyLabel 假值文案，默认值为 否。
     * @return self 当前列实例。
     *
     * 示例：
     * `Tables::column('启用', 'status')->displayBoolean('启用', '停用')`
     */
    public function displayBoolean(string $truthyLabel = '是', string $falsyLabel = '否'): self
    {
        $this->display = [
            'type' => self::DISPLAY_TYPE_BOOLEAN,
            'truthyLabel' => $truthyLabel,
            'falsyLabel' => $falsyLabel,
        ];

        return $this;
    }

    /**
     * 把布尔值展示为标签。
     * truthy/falsy 识别规则与 displayBoolean() 一致。
     *
     * @param string $truthyLabel 真值文案，默认值为 是。
     * @param string $falsyLabel 假值文案，默认值为 否。
     * @param string $truthyType 真值标签类型，默认值为 success。
     * @param string $falsyType 假值标签类型，默认值为 info。
     * @return self 当前列实例。
     *
     * 示例：
     * `Tables::column('启用', 'status')->displayBooleanTag('启用', '停用')`
     */
    public function displayBooleanTag(
        string $truthyLabel = '是',
        string $falsyLabel = '否',
        string $truthyType = 'success',
        string $falsyType = 'info'
    ): self {
        $this->display = [
            'type' => self::DISPLAY_TYPE_BOOLEAN_TAG,
            'truthyLabel' => $truthyLabel,
            'falsyLabel' => $falsyLabel,
            'truthyType' => $truthyType,
            'falsyType' => $falsyType,
        ];

        return $this;
    }

    /**
     * 把列渲染为可直接切换的开关，并按原版 showSwitch() 规则自动提交更新请求。
     * `options` 支持 `[1 => '开启', 0 => '关闭']` 或 `[['value' => 1, 'label' => '开启'], ...]`。
     * `openValue` 不传时默认取 options 第一项作为开启值。
     *
     * @param array $options 开关选项。
     * @param string $requestUrl 更新请求地址。
     * @param mixed $openValue 开启值；传 null 时自动取第一项。
     * @return self 当前列实例。
     *
     * 示例：
     * `Tables::column('状态', 'status')->displaySwitch([1 => '启用', 0 => '停用'], '/admin/qa-info/status')`
     */
    public function displaySwitch(array $options, string $requestUrl, mixed $openValue = null): self
    {
        $resolvedOptions = $this->normalizeSwitchOptions($options, $openValue);
        $activeOption = $resolvedOptions[0] ?? ['value' => 1, 'label' => '开'];
        $inactiveOption = $resolvedOptions[1] ?? ['value' => 0, 'label' => '关'];

        $this->display = [
            'type' => self::DISPLAY_TYPE_SWITCH,
            'options' => $resolvedOptions,
            'requestUrl' => $requestUrl,
            'activeValue' => $activeOption['value'] ?? 1,
            'inactiveValue' => $inactiveOption['value'] ?? 0,
            'activeText' => (string)($activeOption['label'] ?? '开'),
            'inactiveText' => (string)($inactiveOption['label'] ?? '关'),
        ];

        return $this;
    }

    /**
     * 把值按日期格式展示。
     * 支持秒/毫秒时间戳以及常见日期字符串；无法识别时会回退显示原值。
     *
     * @param string $format 日期格式，默认值为 YYYY-MM-DD。
     * @return self 当前列实例。
     *
     * 示例：
     * `Tables::column('创建日期', 'create_time')->displayDate()`
     */
    public function displayDate(string $format = 'YYYY-MM-DD'): self
    {
        $this->display = [
            'type' => self::DISPLAY_TYPE_DATETIME,
            'format' => $format,
        ];

        return $this;
    }

    /**
     * 把值按日期时间格式展示。
     *
     * @param string $format 日期时间格式，默认值为 YYYY-MM-DD HH:mm:ss。
     * @return self 当前列实例。
     *
     * 示例：
     * `Tables::column('创建时间', 'create_time')->displayDatetime()`
     */
    public function displayDatetime(string $format = 'YYYY-MM-DD HH:mm:ss'): self
    {
        return $this->displayDate($format);
    }

    /**
     * 把数组值按多图列表展示。
     * 默认要求数组项形如 `['url' => '...']`；若数组本身就是 URL 列表，可把 srcPath 设为空字符串。
     *
     * @param int $previewNumber 预览数量，默认值为 3。
     * @param string $srcPath 图片地址字段路径，默认值为 url。
     * @param int $width 图片宽度，默认值为 60。
     * @param int $height 图片高度，默认值为 60。
     * @param string $fit 图片适配方式，默认值为 cover。
     * @return self 当前列实例。
     *
     * 示例：
     * `Tables::column('图片', 'images')->displayImages(3, 'url')`
     */
    public function displayImages(
        int $previewNumber = 3,
        string $srcPath = 'url',
        int $width = 60,
        int $height = 60,
        string $fit = 'cover'
    ): self {
        $this->display = [
            'type' => self::DISPLAY_TYPE_IMAGES,
            'previewNumber' => $previewNumber,
            'srcPath' => $srcPath,
            'width' => $width,
            'height' => $height,
            'fit' => $fit,
        ];

        return $this;
    }

    /**
     * 设置空值占位文案。
     * 在原值为空、映射未命中、图片列表为空等场景下都会使用这个占位内容。
     *
     * @param string $placeholder 空值占位文案。
     * @return self 当前列实例。
     *
     * 示例：
     * `Tables::column('标题', 'title')->displayPlaceholder('--')`
     */
    public function displayPlaceholder(string $placeholder): self
    {
        $this->placeholder = $placeholder;

        return $this;
    }

    /**
     * 配置点击后打开页面。
     * 当前 V2 会按 url + params 生成链接，`tab` 优先走宿主 tab，兜底回退浏览器新标签页，`dialog` 走托管 iframe 弹窗。
     *
     * @param string $url 打开目标地址。
     * @param array $config 打开配置。
     * @param string $type 打开方式，可选 dialog 或 tab，默认值为 dialog。
     * @param string|AbstractHtmlElement|\Stringable|null $element 自定义触发元素。
     * @param array $params 附加参数。
     * @return self 当前列实例。
     *
     * 示例：
     * `Tables::column('标题', 'title')->openPage('/admin/qa-info/form', ['title' => '编辑问答'], 'dialog')`
     */
    public function openPage(
        string $url,
        array $config = [],
        #[ExpectedValues(['dialog', 'tab'])]
        string $type = 'dialog',
        string|AbstractHtmlElement|\Stringable|null $element = null,
        array $params = []
    ): self {
        $defaultParams = ['id' => '@id'];
        if ($this->prop !== '') {
            $defaultParams[$this->prop] = '@' . $this->prop;
        }

        $this->display = [
            'type' => self::DISPLAY_TYPE_OPEN_PAGE,
            'url' => $url,
            'config' => $config,
            'openType' => in_array($type, ['dialog', 'tab'], true) ? $type : 'dialog',
            'element' => $element,
            'params' => array_merge($defaultParams, $params),
        ];

        return $this;
    }

    public function hasManagedOpenPageDialog(): bool
    {
        $display = $this->display ?? [];

        return ($display['type'] ?? null) === self::DISPLAY_TYPE_OPEN_PAGE
            && ($display['openType'] ?? 'dialog') === 'dialog'
            && is_string($display['url'] ?? null)
            && trim((string)($display['url'] ?? '')) !== '';
    }

    public function managedOpenPageDialogKey(string $tableKey): ?string
    {
        if (!$this->hasManagedOpenPageDialog()) {
            return null;
        }

        $normalizedTableKey = preg_replace('/[^A-Za-z0-9_]+/', '_', $tableKey) ?: 'table';

        return sprintf('__sc_v2_open_page_%s_%d', trim($normalizedTableKey, '_'), spl_object_id($this));
    }

    public function managedOpenPageDialog(string $tableKey): ?Dialog
    {
        $dialogKey = $this->managedOpenPageDialogKey($tableKey);
        if ($dialogKey === null) {
            return null;
        }

        if (isset($this->managedOpenPageDialogs[$dialogKey]) && $this->managedOpenPageDialogs[$dialogKey] instanceof Dialog) {
            return $this->managedOpenPageDialogs[$dialogKey];
        }

        $display = $this->display ?? [];
        $config = is_array($display['config'] ?? null) ? $display['config'] : [];
        $titleTemplate = $this->normalizeOpenPageDialogTitleTemplate($config['title'] ?? null);

        $dialog = Dialog::make($dialogKey, $this->normalizeOpenPageDialogStaticTitle($titleTemplate))
            ->iframe((string)($display['url'] ?? ''), $display['params'] ?? [])
            ->width($this->normalizeOpenPageDialogWidth($config['width'] ?? null))
            ->height($this->normalizeOpenPageDialogHeight($config['height'] ?? null))
            ->alignCenter($this->normalizeOpenPageDialogBoolConfig($config, ['align-center', 'alignCenter'], true))
            ->draggable($this->normalizeOpenPageDialogBoolConfig($config, ['draggable'], true))
            ->closeOnClickModal($this->normalizeOpenPageDialogBoolConfig($config, ['close-on-click-modal', 'closeOnClickModal'], false))
            ->fullscreen($this->normalizeOpenPageDialogBoolConfig($config, ['fullscreen'], false))
            ->iframeFullscreenToggle($this->normalizeOpenPageDialogBoolConfig($config, ['fullscreen-toggle', 'fullscreenToggle'], true));

        if ($titleTemplate !== '') {
            $dialog->titleTemplate($titleTemplate);
        }

        return $this->managedOpenPageDialogs[$dialogKey] = $dialog;
    }

    /**
     * 为当前列追加提示信息。
     *
     * @param string|\Stringable $tip 提示内容。
     * @param string|\Stringable $icon 图标名，默认值为 WarningFilled。
     * @param array $attrs 图标附加属性。
     * @return self 当前列实例。
     *
     * 示例：
     * `Tables::column('标题', 'title')->addTip('点击标题可查看详情')`
     */
    public function addTip(string|\Stringable $tip, string|\Stringable $icon = 'WarningFilled', array $attrs = []): self
    {
        $this->tip = [
            'icon' => $icon,
            'tip' => $tip,
            'attrs' => $attrs,
        ];

        return $this;
    }

    /**
     * 在当前列渲染内容后追加额外内容。
     *
     * @param string|AbstractHtmlElement|\Stringable $content 附加内容。
     * @return self 当前列实例。
     *
     * 示例：
     * `Tables::column('标题', 'title')->appendContent('<span class="ml-2 text-muted">新</span>')`
     */
    public function appendContent(string|AbstractHtmlElement|\Stringable $content): self
    {
        $this->appendContent[] = $content;

        return $this;
    }

    /**
     * 设置导出元数据。
     *
     * @param bool $allow 是否允许导出，默认值为 true。
     * @param float|null $sort 导出排序值。
     * @return self 当前列实例。
     *
     * 示例：
     * `Tables::column('标题', 'title')->exportExcel(true, 10)`
     */
    public function exportExcel(bool $allow = true, float $sort = null): self
    {
        $this->exportExcel = [
            'sort' => $sort,
            'allow' => $allow,
        ];

        return $this;
    }

    /**
     * 隐藏当前列，并保留导出配置。
     *
     * @param bool $confirm 是否隐藏当前列，默认值为 true。
     * @param bool $excelExport 是否仍参与导出，默认值为 false。
     * @param float|null $excelSort 导出排序值。
     * @return self 当前列实例。
     *
     * 示例：
     * `Tables::column('内部备注', 'remark')->notShow(true, true)`
     */
    public function notShow(bool $confirm = true, bool $excelExport = false, float $excelSort = null): self
    {
        if ($confirm) {
            $this->hidden = true;
        }

        return $this->exportExcel($excelExport, $excelSort);
    }

    /**
     * 仅导出，不在页面展示。
     *
     * @param float|null $excelSort 导出排序值。
     * @return self 当前列实例。
     *
     * 示例：
     * `Tables::column('导出专用字段', 'export_only')->onlyExportExcel()`
     */
    public function onlyExportExcel(float $excelSort = null): self
    {
        return $this->notShow(true, true, $excelSort);
    }

    public function label(): string
    {
        return $this->label;
    }

    public function prop(): string
    {
        return $this->prop;
    }

    public function getWidth(): int|string|null
    {
        return $this->width ?? $this->getAttr('width');
    }

    public function getMinWidth(): int|string|null
    {
        return $this->minWidth ?? $this->getAttr('min-width');
    }

    public function getAlign(): ?string
    {
        $align = $this->align ?? $this->getAttr('align');

        return is_string($align) && $align !== '' ? $align : null;
    }

    public function getFixed(): ?string
    {
        $fixed = $this->fixed ?? $this->getAttr('fixed');

        return is_string($fixed) && $fixed !== '' ? $fixed : null;
    }

    public function getShowOverflowTooltip(): bool
    {
        if (array_key_exists(':show-overflow-tooltip', $this->attrs)) {
            return $this->normalizeBooleanAttr($this->attrs[':show-overflow-tooltip']);
        }

        if (array_key_exists('show-overflow-tooltip', $this->attrs)) {
            return $this->normalizeBooleanAttr($this->attrs['show-overflow-tooltip']);
        }

        return $this->showOverflowTooltip;
    }

    public function isSortable(): bool
    {
        return $this->sortable;
    }

    public function getFormat(): ?string
    {
        return $this->format;
    }

    public function getPlaceholder(): string
    {
        return $this->placeholder;
    }

    public function getDisplay(): ?array
    {
        return $this->display;
    }

    public function getColumnType(): string
    {
        return $this->columnType;
    }

    public function getAttrs(): array
    {
        return $this->attrs;
    }

    public function getAttr(?string $attr = null, mixed $default = null): mixed
    {
        if ($attr === null) {
            return $this->attrs;
        }

        return $this->attrs[$attr] ?? $default;
    }

    public function getTip(): array
    {
        return $this->tip;
    }

    public function getAppendContent(): array
    {
        return $this->appendContent;
    }

    public function getExportExcel(): array
    {
        return $this->exportExcel;
    }

    public function isHidden(): bool
    {
        return $this->hidden;
    }

    public function isRenderable(): bool
    {
        return !$this->hidden;
    }

    public function isSelectionColumn(): bool
    {
        return $this->columnType === self::COLUMN_TYPE_SELECTION;
    }

    public function isIndexColumn(): bool
    {
        return $this->columnType === self::COLUMN_TYPE_INDEX;
    }

    public function isExpandColumn(): bool
    {
        return $this->columnType === self::COLUMN_TYPE_EXPAND;
    }

    public function isEventColumn(): bool
    {
        return $this->columnType === self::COLUMN_TYPE_EVENT;
    }

    public function isSpecialColumn(): bool
    {
        return in_array($this->columnType, [
            self::COLUMN_TYPE_SELECTION,
            self::COLUMN_TYPE_INDEX,
            self::COLUMN_TYPE_EXPAND,
            self::COLUMN_TYPE_EVENT,
        ], true);
    }

    public function supportsSettings(): bool
    {
        return $this->isRenderable()
            && !$this->isSpecialColumn()
            && $this->prop !== '';
    }

    public function isSearchable(): bool
    {
        return $this->search !== null;
    }

    public function getSearchConfig(): ?array
    {
        if ($this->search === null) {
            return null;
        }

        return [
            'type' => strtoupper((string)($this->search['type'] ?? '=')),
            'field' => $this->search['field'] ?? $this->prop,
        ];
    }

    public function getSearchName(): string
    {
        return $this->prop;
    }

    public function getSortField(): ?string
    {
        return $this->sortField ?: $this->prop;
    }

    private function normalizeDisplayOptions(array $options): array
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

    private function normalizeBooleanAttr(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        if (is_numeric($value)) {
            return (int)$value !== 0;
        }

        $normalized = strtolower(trim((string)$value));

        return !in_array($normalized, ['', '0', 'false', 'off', 'no', 'null'], true);
    }

    private function normalizeOpenPageDialogWidth(mixed $width): string
    {
        if (is_int($width) || is_float($width)) {
            return max(320, (int)$width) . 'px';
        }

        if (is_string($width) && trim($width) !== '') {
            return trim($width);
        }

        return '90%';
    }

    private function normalizeOpenPageDialogHeight(mixed $height): ?string
    {
        if (is_int($height) || is_float($height)) {
            return max(240, (int)$height) . 'px';
        }

        if (is_string($height) && trim($height) !== '') {
            return trim($height);
        }

        return 'calc(100vh - 200px)';
    }

    private function normalizeOpenPageDialogTitleTemplate(mixed $title): string
    {
        $title = is_string($title) ? trim($title) : '';
        if ($title === '') {
            if ($this->prop !== '') {
                return sprintf('查看【{%s}】详情', $this->prop);
            }

            if ($this->label !== '') {
                return sprintf('查看【%s】详情', $this->label);
            }

            return '查看详情';
        }

        return (string)preg_replace_callback(
            '/{{\s*@?([A-Za-z0-9_.]+)\s*}}/',
            static fn(array $matches): string => '{' . ($matches[1] ?? '') . '}',
            $title
        );
    }

    private function normalizeOpenPageDialogStaticTitle(string $titleTemplate): string
    {
        $staticTitle = preg_replace('/\{[^{}]+\}/', '', $titleTemplate);
        $staticTitle = str_replace(['【】', '[]', '()'], '', (string)$staticTitle);
        $staticTitle = preg_replace('/\s+/', ' ', (string)$staticTitle);
        $staticTitle = trim((string)$staticTitle);

        if ($staticTitle !== '') {
            return $staticTitle;
        }

        if ($this->label !== '') {
            return sprintf('查看%s详情', $this->label);
        }

        return '查看详情';
    }

    private function normalizeOpenPageDialogBoolConfig(array $config, array $keys, bool $default): bool
    {
        foreach ($keys as $key) {
            if (!array_key_exists($key, $config)) {
                continue;
            }

            return $this->normalizeBooleanAttr($config[$key]);
        }

        return $default;
    }

    private function arrayFormat(array $data): string
    {
        $el = El::double('div');
        foreach ($data as $des => $value) {
            $el->append(
                El::double('div')
                    ->append(
                        El::double('b')->append((string)$des),
                        is_string($value) ? $value : (string)$value
                    )
            );
        }

        return (string)$el;
    }

    private function normalizeSwitchOptions(array $options, mixed $openValue = null): array
    {
        $normalized = array_values($this->normalizeDisplayOptions($options));

        if ($normalized === []) {
            $normalized[] = [
                'value' => $openValue ?? 1,
                'label' => '开',
            ];
        }

        if ($openValue !== null) {
            foreach ($normalized as $index => $option) {
                if (($option['value'] ?? null) != $openValue) {
                    continue;
                }

                if ($index > 0) {
                    $matched = $option;
                    unset($normalized[$index]);
                    array_unshift($normalized, $matched);
                }
                break;
            }
        }

        $normalized = array_values($normalized);
        if (count($normalized) === 1) {
            $normalized[] = [
                'value' => ($normalized[0]['value'] ?? 1) == 1 ? 0 : 1,
                'label' => '关',
            ];
        }

        return array_values(array_slice($normalized, 0, 2));
    }

    private function normalizeTagDisplayOptions(array|ColumnTags $options, string $defaultType): array
    {
        $normalized = [];
        $tagOptions = $options instanceof ColumnTags ? $options->getTags() : $options;

        foreach ($tagOptions as $value => $option) {
            $normalized[] = $this->normalizeTagDisplayOption($value, $option, $defaultType);
        }

        return $normalized;
    }

    private function normalizeTagDisplayOption(mixed $value, mixed $option, string $defaultType): array
    {
        if (is_array($option)) {
            $normalized = $option;
            $normalized['value'] = $option['value'] ?? $value;
            $normalized['label'] = (string)($option['label'] ?? $value);
            $normalized['type'] = (string)($option['type'] ?? $defaultType);

            if (
                !isset($normalized['effect'])
                && isset($option['theme'])
                && $option['theme'] !== ''
                && $option['theme'] !== null
            ) {
                $normalized['effect'] = (string)$option['theme'];
            }

            return $normalized;
        }

        $legacyTagMeta = $this->extractLegacyTagMeta($value, $option, $defaultType);
        if ($legacyTagMeta !== null) {
            return $legacyTagMeta;
        }

        return [
            'value' => $value,
            'label' => (string)$option,
            'type' => $defaultType,
        ];
    }

    private function extractLegacyTagMeta(mixed $value, mixed $option, string $defaultType): ?array
    {
        if (!is_string($option) && !(is_object($option) && method_exists($option, '__toString'))) {
            return null;
        }

        $tagHtml = trim((string)$option);
        if ($tagHtml === '' || stripos($tagHtml, '<el-tag') === false) {
            return null;
        }

        if (!preg_match('/<el-tag\b([^>]*)>(.*?)<\/el-tag>/is', $tagHtml, $matches)) {
            return null;
        }

        $attrs = $this->parseTagAttributes($matches[1] ?? '');
        $label = trim(html_entity_decode(strip_tags($matches[2] ?? ''), ENT_QUOTES | ENT_HTML5));

        $normalized = [
            'value' => $value,
            'label' => $label,
            'type' => (string)($attrs['type'] ?? $defaultType),
        ];

        $effect = $attrs['effect'] ?? $attrs['theme'] ?? null;
        if (is_string($effect) && $effect !== '') {
            $normalized['effect'] = $effect;
        }

        return $normalized;
    }

    private function parseTagAttributes(string $attributes): array
    {
        if ($attributes === '') {
            return [];
        }

        preg_match_all('/([a-zA-Z_:][a-zA-Z0-9_:\.-]*)\s*=\s*([\'"])(.*?)\2/s', $attributes, $matches, PREG_SET_ORDER);

        $parsed = [];
        foreach ($matches as $match) {
            $parsed[strtolower((string)$match[1])] = html_entity_decode((string)$match[3], ENT_QUOTES | ENT_HTML5);
        }

        return $parsed;
    }
}
