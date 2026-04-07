<?php

namespace Sc\Util\HtmlStructureV2\Components;

use JetBrains\PhpStorm\ExpectedValues;
use Sc\Util\HtmlElement\El;
use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlStructure\Form\FormItemDatetime as LegacyDatetimeFormItem;
use Sc\Util\HtmlStructure\Form\FormItemInterface as LegacyFormItemInterface;
use Sc\Util\HtmlStructure\Form\FormItemSelect as LegacySelectFormItem;
use Sc\Util\HtmlStructure\Form\FormItemText as LegacyTextFormItem;
use Sc\Util\HtmlStructure\Table\ColumnTags;
use Sc\Util\HtmlStructureV2\Components\Fields\DateField;
use Sc\Util\HtmlStructureV2\Components\Fields\OptionField;
use Sc\Util\HtmlStructureV2\Components\Fields\TextField;
use Sc\Util\HtmlStructureV2\Contracts\Fields\PlaceholderFieldInterface;
use Sc\Util\HtmlStructureV2\Enums\FieldType;

final class Column
{
    public const SUPPORTED_SEARCH_TYPES = ['=', '!=', '<>', '>', '>=', '<', '<=', 'LIKE', 'LIKE_RIGHT', 'IN', 'BETWEEN'];
    private const LEGACY_SEARCH_TYPES = ['=', 'like', 'in', 'between', 'like_right'];
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
    private ?Field $searchFormField = null;
    private ?string $searchName = null;
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
     */
    public static function make(string $label, string $prop): self
    {
        return new self($label, $prop);
    }

    /**
     * 兼容原版 normal() 写法。
     * time/date 字段沿用原版默认宽度推断。
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
     * 兼容原版 selection() 写法。
     */
    public static function selection(): self
    {
        return (new self('', ''))
            ->type(self::COLUMN_TYPE_SELECTION)
            ->width(48)
            ->align('center');
    }

    /**
     * 兼容原版 index() 写法。
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
     * 兼容原版 expand() 写法。
     */
    public static function expand(string $title = ''): self
    {
        return (new self($title, ''))
            ->type(self::COLUMN_TYPE_EXPAND);
    }

    /**
     * 兼容原版 event() 写法。
     */
    public static function event(string $title = '操作', string $prop = ''): self
    {
        return (new self($title, $prop))
            ->type(self::COLUMN_TYPE_EVENT)
            ->setAttr([
                'mark-event' => 'true',
                'class-name' => 'sc-v2-event-column',
                ':show-overflow-tooltip' => 'false',
            ]);
    }

    /**
     * 设置列固定宽度。
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
     */
    public function minWidth(int|string $minWidth): self
    {
        $this->minWidth = $minWidth;

        return $this;
    }

    /**
     * 设置列对齐方式，例如 left / center / right。
     */
    public function align(string $align): self
    {
        $this->align = $align;

        return $this;
    }

    /**
     * 设置列固定位置，例如 left / right。
     */
    public function fixed(string $position = 'right'): self
    {
        $this->fixed = $position;

        return $this;
    }

    /**
     * 设置列类型，主要用于兼容原版静态构造方法。
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
     */
    public function setAttr(string|array $attr, mixed $value = ''): self
    {
        $attrs = is_string($attr)
            ? ($value === '' ? El::getAttrFromStr($attr) : [$attr => $value])
            : $attr;

        foreach ($attrs as $key => &$item) {
            if (is_bool($item)) {
                $item = $item ? 'true' : 'false';
            }
        }

        $this->attrs = array_merge($this->attrs, $attrs);

        return $this;
    }

    /**
     * setAttr() 的批量别名。
     */
    public function props(array $attrs): self
    {
        return $this->setAttr($attrs);
    }

    /**
     * 控制列是否可排序，也可直接传真实排序字段名。
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
     */
    public function searchable(
        #[ExpectedValues(self::SUPPORTED_SEARCH_TYPES)]
        string|bool $searchable = true,
        ?string $field = null
    ): self
    {
        if ($searchable === false) {
            $this->search = null;
            $this->searchFormField = null;
            $this->searchName = null;

            return $this;
        }

        $this->search ??= [];
        $this->search['type'] = is_string($searchable) ? strtoupper($searchable) : '=';
        $this->search['field'] = $field ?: $this->prop;

        return $this;
    }

    /**
     * 设置列搜索操作符类型。
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
     */
    public function searchField(string $field): self
    {
        $this->search ??= [];
        $this->search['field'] = $field;

        return $this;
    }

    /**
     * 兼容原版 addSearch() 写法。
     * 支持传 string 字段名、V2 Field、以及常见旧版 FormItem。
     */
    public function addSearch(
        #[ExpectedValues(self::LEGACY_SEARCH_TYPES)]
        string $type = '=',
        Field|LegacyFormItemInterface|string|null $formItem = null
    ): self {
        $searchType = $this->normalizeLegacySearchType($type);
        $searchField = null;
        $searchName = $this->prop;
        $searchFormField = null;

        if (is_string($formItem) && $formItem !== '') {
            $searchField = $formItem;
        } elseif ($formItem instanceof Field) {
            $searchFormField = $formItem;
            $searchName = $formItem->name();
            $searchField = $formItem->name();
            $this->applySearchFieldPlaceholder($searchFormField);
        } elseif ($formItem instanceof LegacyFormItemInterface) {
            $searchFormField = $this->convertLegacySearchFormItem($formItem, $searchType);
            $searchName = $searchFormField->name();
            $searchField = $this->resolveLegacySearchFieldName($formItem, $searchName);
        }

        $this->searchable($searchType, $searchField);
        $this->searchName = $searchName;
        $this->searchFormField = $searchFormField;

        return $this;
    }

    /**
     * 设置通用展示格式字符串。
     * 会直接作为列插槽内容输出；可传原始 HTML/Vue 模板片段，当前行变量名是 `scope`，
     * 例如 `{{ scope.row.name }} / {{ scope.row.id }}`。
     */
    public function displayFormat(string|\Stringable $format): self
    {
        $this->format = (string)$format;

        return $this;
    }

    /**
     * displayFormat() 的兼容别名，保留原版 setFormat() 习惯。
     */
    public function setFormat(string|\Stringable|array $format): self
    {
        return $this->displayFormat(is_array($format) ? $this->arrayFormat($format) : $format);
    }

    /**
     * 把值映射为标签文本，支持数组值拼接展示。
     * 适合枚举值转文案。若单元格本身是数组，会按 separator 把多项映射结果拼接起来；
     * 未命中的值会显示为空。
     * 选项默认支持两种常见写法：
     * - `[1 => '是', 0 => '否']`
     * - `[['value' => 1, 'label' => '是'], ['value' => 0, 'label' => '否']]`
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
     * displaySwitch() 的兼容别名，方便从原版 showSwitch() 直接迁移。
     */
    public function showSwitch(array $options, string $requestUrl, mixed $openValue = null): self
    {
        return $this->displaySwitch($options, $requestUrl, $openValue);
    }

    /**
     * 把值按日期格式展示。
     * 支持秒/毫秒时间戳以及常见日期字符串；无法识别时会回退显示原值。
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
     */
    public function displayDatetime(string $format = 'YYYY-MM-DD HH:mm:ss'): self
    {
        return $this->displayDate($format);
    }

    /**
     * 把数组值按多图列表展示。
     * 默认要求数组项形如 `['url' => '...']`；若数组本身就是 URL 列表，可把 srcPath 设为空字符串。
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
     */
    public function displayPlaceholder(string $placeholder): self
    {
        $this->placeholder = $placeholder;

        return $this;
    }

    /**
     * displayPlaceholder() 的兼容别名。
     */
    public function emptyShow(string|\Stringable $content): self
    {
        return $this->displayPlaceholder((string)$content);
    }

    /**
     * sortable() 的兼容别名。
     */
    public function enableSort(string $sortField = null): self
    {
        return $sortField !== null && $sortField !== ''
            ? $this->sortable($sortField)
            : $this->sortable();
    }

    /**
     * displayMapping() 的兼容别名。
     */
    public function showMapping(array $mapping): self
    {
        return $this->displayMapping($mapping);
    }

    /**
     * displayTag() 的兼容别名。
     */
    public function showTag(array $options): self
    {
        return $this->displayTag($options);
    }

    /**
     * displayTag() 的兼容别名。
     */
    public function showTags(array|ColumnTags $tags): self
    {
        return $this->displayTag($tags);
    }

    /**
     * displayImage() 的兼容别名。
     */
    public function showImage(): self
    {
        return $this->displayImage();
    }

    /**
     * displayImages() 的兼容别名。
     */
    public function showImages(int $previewNumber = 1, string $urlPath = 'url'): self
    {
        return $this->displayImages($previewNumber, $urlPath);
    }

    /**
     * 兼容原版 openPage() 写法。
     * 当前 V2 会按 url + params 生成链接，`tab` 走新标签页，`dialog` 走托管 iframe 弹窗。
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
     * 兼容原版 addTip() 写法。
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
     */
    public function appendContent(string|AbstractHtmlElement|\Stringable $content): self
    {
        $this->appendContent[] = $content;

        return $this;
    }

    /**
     * 设置导出元数据。
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
     * exportExcel() 的兼容别名。
     */
    public function importExcel(bool $allow = true, float $sort = null): self
    {
        return $this->exportExcel($allow, $sort);
    }

    /**
     * 隐藏当前列，并保留导出配置。
     */
    public function notShow(bool $confirm = true, bool $excelExport = false, float $excelSort = null): self
    {
        if ($confirm) {
            $this->hidden = true;
        }

        return $this->importExcel($excelExport, $excelSort);
    }

    /**
     * onlyExportExcel() 的兼容别名。
     */
    public function onlyImportExcel(float $excelSort = null): self
    {
        return $this->onlyExportExcel($excelSort);
    }

    /**
     * 仅导出，不在页面展示。
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
        return $this->searchName ?: $this->prop;
    }

    public function getSearchFormField(): ?Field
    {
        return $this->searchFormField;
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

    private function normalizeLegacySearchType(string $type): string
    {
        return match (strtolower($type)) {
            'like' => 'LIKE',
            'like_right' => 'LIKE_RIGHT',
            'in' => 'IN',
            'between' => 'BETWEEN',
            default => strtoupper($type),
        };
    }

    private function resolveLegacySearchFieldName(LegacyFormItemInterface $formItem, string $fallback): string
    {
        $name = $this->legacyGetter($formItem, 'getName');
        if (!is_string($name) || $name === '') {
            return $fallback;
        }

        return $name;
    }

    private function convertLegacySearchFormItem(LegacyFormItemInterface $formItem, string $searchType): Field
    {
        $legacyName = (string)($this->legacyGetter($formItem, 'getName') ?: $this->prop);
        $fieldName = $this->normalizeLegacySearchName($legacyName);
        $label = (string)($this->legacyGetter($formItem, 'getLabel') ?: '');

        $field = match (true) {
            $formItem instanceof LegacySelectFormItem => $this->convertLegacySelectField($formItem, $fieldName, $label, $searchType),
            $formItem instanceof LegacyDatetimeFormItem => $this->convertLegacyDateField($formItem, $fieldName, $label, $searchType),
            $formItem instanceof LegacyTextFormItem => $this->convertLegacyTextField($formItem, $fieldName, $label, $searchType),
            default => $this->convertLegacyTextField($formItem, $fieldName, $label, $searchType),
        };

        $default = $this->legacyGetter($formItem, 'getDefault');
        if ($default !== null) {
            $field->default($default);
        }

        $this->applyLegacyPlaceholder($field, $formItem);
        $this->applyLegacyFieldProps($field, (array)$this->legacyGetter($formItem, 'getVAttrs', []));

        return $field;
    }

    private function convertLegacyTextField(
        LegacyFormItemInterface $formItem,
        string $fieldName,
        string $label,
        string $searchType
    ): Field {
        $options = (array)$this->legacyGetter($formItem, 'getOptions', []);
        if ($options !== []) {
            $field = new OptionField($fieldName, $label, FieldType::SELECT);
            $field->options($options);
            if ($searchType === 'IN') {
                $field->default([])
                    ->prop('multiple', '')
                    ->prop('filterable', '')
                    ->prop('collapse-tags', '');
            }

            return $field;
        }

        return new TextField($fieldName, $label, FieldType::TEXT);
    }

    private function convertLegacySelectField(
        LegacySelectFormItem $formItem,
        string $fieldName,
        string $label,
        string $searchType
    ): Field {
        $field = new OptionField($fieldName, $label, FieldType::SELECT);
        $field->options((array)$this->legacyGetter($formItem, 'getOptions', []));

        if ((bool)$this->legacyGetter($formItem, 'getMultiple', false) || $searchType === 'IN') {
            $field->default([])
                ->prop('multiple', '')
                ->prop('filterable', '')
                ->prop('collapse-tags', '');
        }

        return $field;
    }

    private function convertLegacyDateField(
        LegacyDatetimeFormItem $formItem,
        string $fieldName,
        string $label,
        string $searchType
    ): Field {
        $timeType = strtolower((string)$this->legacyGetter($formItem, 'getTimeType', ''));
        $field = new DateField(
            $fieldName,
            $label,
            in_array($timeType, ['datetimerange', 'daterange', 'monthrange'], true)
                ? FieldType::DATE_RANGE
                : (str_contains($timeType, 'time') ? FieldType::DATETIME : FieldType::DATE)
        );

        if ($timeType !== '') {
            $field->prop('type', $timeType);
        } elseif ($searchType === 'BETWEEN') {
            $field->prop('type', str_contains($fieldName, 'time') ? 'datetimerange' : 'daterange');
        }

        return $field;
    }

    private function applyLegacyPlaceholder(Field $field, LegacyFormItemInterface $formItem): void
    {
        if (!$field instanceof PlaceholderFieldInterface) {
            return;
        }

        $legacyPlaceholder = $this->legacyGetter($formItem, 'getPlaceholder');
        if (is_string($legacyPlaceholder) && $legacyPlaceholder !== '') {
            $field->placeholder($legacyPlaceholder);
            return;
        }

        if ($this->label !== '') {
            $field->placeholder($this->label);
        }
    }

    private function applySearchFieldPlaceholder(Field $field): void
    {
        if (!$field instanceof PlaceholderFieldInterface || !method_exists($field, 'placeholder')) {
            return;
        }

        $currentPlaceholder = trim($field->getPlaceholder());
        $fieldLabel = trim($field->label());
        $defaultPlaceholders = array_filter([
            '请输入',
            '请选择',
            $fieldLabel !== '' ? '请输入' . $fieldLabel : null,
            $fieldLabel !== '' ? '请选择' . $fieldLabel : null,
        ], static fn ($item) => is_string($item) && $item !== '');

        if ($this->label !== '' && in_array($currentPlaceholder, $defaultPlaceholders, true)) {
            $field->placeholder($this->label);
        }
    }

    private function applyLegacyFieldProps(Field $field, array $attrs): void
    {
        foreach ($attrs as $key => $value) {
            if ($key === 'placeholder' || $key === 'label') {
                continue;
            }

            $field->prop($key, $value);
        }
    }

    private function normalizeLegacySearchName(string $name): string
    {
        $normalized = trim($name);
        if ($normalized === '') {
            return $this->prop;
        }

        if (!str_contains($normalized, '.')) {
            return $normalized;
        }

        return $this->prop !== ''
            ? $this->prop
            : (preg_replace('/[^a-zA-Z0-9_$]+/', '_', $normalized) ?: 'search');
    }

    private function legacyGetter(LegacyFormItemInterface $formItem, string $method, mixed $default = null): mixed
    {
        try {
            return $formItem->{$method}();
        } catch (\Throwable) {
            return $default;
        }
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
