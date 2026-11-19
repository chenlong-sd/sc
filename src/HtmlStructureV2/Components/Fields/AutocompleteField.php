<?php

namespace Sc\Util\HtmlStructureV2\Components\Fields;

use Sc\Util\HtmlStructureV2\Components\Concerns\HasPlaceholder;
use Sc\Util\HtmlStructureV2\Components\Concerns\HasValidation;
use Sc\Util\HtmlStructureV2\Components\Field;
use Sc\Util\HtmlStructureV2\Contracts\Fields\PlaceholderFieldInterface;
use Sc\Util\HtmlStructureV2\Contracts\Fields\ValidatableFieldInterface;
use Sc\Util\HtmlStructureV2\Enums\FieldType;

/**
 * 输入联想字段（el-autocomplete）
 *
 * 输入框允许自由输入，同时根据输入内容展示建议项；建议项可只显示文案，
 * 也可通过 tableColumns() 以表格列展示，适合“线盘”这类原版 input + 表格联想的场景。
 */
class AutocompleteField extends Field implements PlaceholderFieldInterface, ValidatableFieldInterface
{
    use HasPlaceholder;
    use HasValidation;

    /** @var array<int, array<string, mixed>> */
    protected array $suggestions = [];

    protected ?array $remote = null;

    protected string $labelField = 'label';

    protected string $valueKey = 'label';

    /** @var array<int, array{field: string, label: string, width: ?string}> */
    protected array $tableColumns = [];

    public function __construct(string $name, string $label)
    {
        parent::__construct($name, $label, FieldType::AUTOCOMPLETE);
    }

    /**
     * 设置静态建议项。
     * 支持 value => label、字符串列表，或带 label/其它展示字段的数组列表。
     *
     * @param array $suggestions 建议项列表
     * @return static 当前字段实例
     */
    public function options(array $suggestions): static
    {
        $this->suggestions = [];

        foreach ($suggestions as $key => $item) {
            if (is_array($item)) {
                $option = $item;
                if (! array_key_exists('label', $option) && array_key_exists('name', $option)) {
                    $option['label'] = $option['name'];
                }
                if (! array_key_exists('value', $option) && array_key_exists('label', $option)) {
                    $option['value'] = $option['label'];
                }
                $this->suggestions[] = $option;
                continue;
            }

            $this->suggestions[] = [
                'value' => $key,
                'label' => (string) $item,
            ];
        }

        return $this;
    }

    /**
     * 开启远端建议加载（按输入关键字搜索）。
     *
     * 请求格式与 sc 现有远端下拉一致：
     * `search[search][$searchField]=keyword`，并附带 `page` / `pageSize`。
     *
     * @param string $url 建议接口地址
     * @param string $searchField 搜索字段
     * @param array $params 附加请求参数
     * @return static 当前字段实例
     */
    public function remoteSearch(string $url, string $searchField = 'keyword', array $params = []): static
    {
        $this->remote = [
            'url' => $url,
            'method' => 'get',
            'searchField' => trim($searchField) !== '' ? trim($searchField) : 'keyword',
            'params' => $params,
            'pageSize' => 20,
        ];

        return $this;
    }

    public function remoteMethod(string $method): static
    {
        if ($this->remote !== null) {
            $this->remote['method'] = strtolower($method);
        }

        return $this;
    }

    public function pageSize(int $pageSize): static
    {
        if ($this->remote !== null) {
            $this->remote['pageSize'] = max(1, $pageSize);
        }

        return $this;
    }

    /**
     * 设置远端建议项中作为展示文案的字段，默认 label。
     */
    public function labelField(string $field): static
    {
        $field = trim($field);

        if ($field !== '') {
            $this->labelField = $field;
        }

        return $this;
    }

    /**
     * 设置选中建议项时写入输入框（即表单 model）的字段，默认 label。
     */
    public function valueKey(string $field): static
    {
        $field = trim($field);

        if ($field !== '') {
            $this->valueKey = $field;
        }

        return $this;
    }

    /**
     * 以表格列展示建议项，列配置与 tableSelect() 一致。
     *
     * @param array<string, string>|array<int, array{field: string, label?: string, width?: string}> $columns
     * @return static 当前字段实例
     */
    public function tableColumns(array $columns): static
    {
        $normalized = [];

        foreach ($columns as $key => $column) {
            if (is_array($column)) {
                $field = trim((string) ($column['field'] ?? ''));
                $label = (string) ($column['label'] ?? $field);
                $width = isset($column['width']) ? trim((string) $column['width']) : null;
            } else {
                $field = trim((string) $key);
                $label = (string) $column;
                $width = null;
            }

            if ($field === '') {
                continue;
            }

            $normalized[] = [
                'field' => $field,
                'label' => $label,
                'width' => $width !== '' ? $width : null,
            ];
        }

        $this->tableColumns = $normalized;

        return $this;
    }

    public function hasTableColumns(): bool
    {
        return $this->tableColumns !== [];
    }

    /**
     * @return array<int, array{field: string, label: string, width: ?string}>
     */
    public function getTableColumns(): array
    {
        return $this->tableColumns;
    }

    public function getValueKey(): string
    {
        return $this->valueKey;
    }

    /**
     * 渲染器使用的建议配置。
     */
    public function suggestionConfig(): array
    {
        $base = [
            'labelField' => $this->labelField,
            'valueKey' => $this->valueKey,
        ];

        if ($this->remote !== null) {
            return array_merge($this->remote, $base);
        }

        return array_merge($base, ['staticOptions' => $this->suggestions]);
    }
}