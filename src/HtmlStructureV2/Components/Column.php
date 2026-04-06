<?php

namespace Sc\Util\HtmlStructureV2\Components;

use Sc\Util\HtmlStructure\Table\ColumnTags;

final class Column
{
    private const DISPLAY_TYPE_MAPPING = 'mapping';
    private const DISPLAY_TYPE_TAG = 'tag';
    private const DISPLAY_TYPE_IMAGE = 'image';
    private const DISPLAY_TYPE_IMAGES = 'images';
    private const DISPLAY_TYPE_BOOLEAN = 'boolean';
    private const DISPLAY_TYPE_BOOLEAN_TAG = 'boolean_tag';
    private const DISPLAY_TYPE_DATETIME = 'datetime';

    private ?int $width = null;
    private ?int $minWidth = null;
    private ?string $align = null;
    private bool $sortable = false;
    private ?string $sortField = null;
    private ?string $format = null;
    private ?array $display = null;
    private ?array $search = null;
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
     * 设置列固定宽度。
     */
    public function width(int $width): self
    {
        $this->width = $width;

        return $this;
    }

    /**
     * 设置列最小宽度。
     */
    public function minWidth(int $minWidth): self
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
     * 开启后会按当前列 prop 自动生成一条搜索协议；若有独立筛选表单，
     * 对应字段名通常应与列 prop 保持一致。
     */
    public function searchable(string|bool $searchable = true, ?string $field = null): self
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
     */
    public function searchType(string $type): self
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
     * 设置通用展示格式字符串。
     * 会直接作为列插槽内容输出；可传原始 HTML/Vue 模板片段，当前行变量名是 `scope`，
     * 例如 `{{ scope.row.name }} / {{ scope.row.id }}`。
     */
    public function displayFormat(string $format): self
    {
        $this->format = $format;

        return $this;
    }

    /**
     * 把值映射为标签文本，支持数组值拼接展示。
     * 适合枚举值转文案。若单元格本身是数组，会按 separator 把多项映射结果拼接起来；
     * 未命中的值会显示为空。
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

    public function label(): string
    {
        return $this->label;
    }

    public function prop(): string
    {
        return $this->prop;
    }

    public function getWidth(): ?int
    {
        return $this->width;
    }

    public function getMinWidth(): ?int
    {
        return $this->minWidth;
    }

    public function getAlign(): ?string
    {
        return $this->align;
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

    public function getSortField(): ?string
    {
        return $this->sortField ?: $this->prop;
    }

    private function normalizeDisplayOptions(array $options): array
    {
        $normalized = [];
        foreach ($options as $value => $label) {
            if (is_array($label) && array_key_exists('value', $label) && array_key_exists('label', $label)) {
                $normalized[] = [
                    'value' => $label['value'],
                    'label' => (string)$label['label'],
                ];

                continue;
            }

            $normalized[] = [
                'value' => $value,
                'label' => (string)$label,
            ];
        }

        return $normalized;
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
