<?php

namespace Sc\Util\HtmlStructureV2\Components\Display;

use Sc\Util\HtmlStructureV2\Components\Concerns\HasElementEvents;
use Sc\Util\HtmlStructureV2\Components\Concerns\HasRenderAttributes;
use Sc\Util\HtmlStructureV2\Contracts\EventAware;
use Sc\Util\HtmlStructureV2\Contracts\Renderable;
use Sc\Util\HtmlStructureV2\Support\RendersWithTheme;

/**
 * 静态表格展示（服务端直接渲染原生 <table>）
 *
 * 适合详情页等数据一次性给定的场景：不创建 el-table，也不进入行数据运行时，
 * 表头与单元格内容在服务端直接拼装成静态 HTML。
 *
 * 示例：
 * - `Displays::table('尺寸明细')
 *      ->column('实测尺寸', ['width' => 220, 'align' => 'center'])
 *      ->column('判定', ['align' => 'center'])
 *      ->rows([['10.02', '合格'], ['9.98', '不合格']]);`
 *
 * 合并单元格：单元格写成数组，如 `['value' => '合并', 'rowspan' => 2, 'colspan' => 2]`；
 * 被 rowspan 覆盖的后续行按顺序省略对应单元格即可（渲染层按网格自动跳过）。
 *
 * 列可声明 key 对应关联行的数据键：`Displays::table()
 *      ->columns(['size' => '实测尺寸', 'judge' => '判定'])
 *      ->rows([['size' => '10.02', 'judge' => '合格']]);`
 *
 * 行样式：`->rowClasses([1 => 'is-bg'])`（或 `->rowClass(1, 'is-bg')`）按行索引给 <tr> 挂 class，
 * 可用于斑马行、合计行等自定义底色。
 */
final class StaticTable implements Renderable, EventAware
{
    use HasElementEvents;
    use HasRenderAttributes;
    use RendersWithTheme;

    private ?string $title;

    /** @var array<int, array{label: string, index: bool, key: ?string, width: ?string, align: ?string, class: ?string, format: ?callable, empty: ?string}> */
    private array $columns = [];

    /** @var array<int, array<int, mixed>> */
    private array $rows = [];

    /** @var array<int, string> */
    private array $rowClasses = [];

    private bool $border = true;
    private bool $striped = false;
    private bool $header = true;
    private ?string $align = null;
    private string $emptyText = '暂无数据';
    private ?string $emptyCell = null;
    private string $size = 'default';
    private ?string $width = null;

    public function __construct(?string $title = null)
    {
        $this->title = $title;
    }

    /**
     * 直接创建一个静态表格实例。
     *
     * 示例：
     * - `Displays::table('尺寸明细')`
     */
    public static function make(?string $title = null): self
    {
        return new self($title);
    }

    /**
     * 设置表格标题，非空时按详情块统一的小竖条标题样式展示。
     */
    public function title(?string $title): self
    {
        $this->title = $title;

        return $this;
    }

    /**
     * 追加一列。
     *
     * @param string $label 列标题。
     * @param array  $options 支持：
     *   - key：对应 rows 里关联数组的行数据键名（行里多余的字段自动忽略）；
     *   - format：单元格格式化回调 `fn(mixed $value, array $row): mixed`，适合枚举转换等；
     *   - empty：该列空值占位文案（优先于 emptyCell()）；
     *   - width / align / class。
     *
     * 示例：
     * - `->column('实测尺寸', ['key' => 'size', 'width' => 220, 'align' => 'center'])`
     * - `->column('类型', ['key' => 'type', 'format' => fn ($value) => $map[$value] ?? '-'])`
     */
    public function column(string $label, array $options = []): self
    {
        $this->columns[] = $this->normalizeColumn(array_replace($options, ['label' => $label]));

        return $this;
    }

    /**
     * 批量设置列。
     *
     * 支持三种写法：
     * - 列标题字符串：`->columns(['实测尺寸', '判定'])`，按顺序对应列表行的第 1、2 个单元格；
     * - 关联写法（键为行数据 key）：`->columns(['size' => '实测尺寸', 'judge' => '判定'])`；
     * - 列定义数组：`->columns([['label' => '实测尺寸', 'key' => 'size', 'align' => 'center']])`。
     *   关联写法也可配合列配置：`->columns(['size' => ['width' => 220], 'judge' => []])`。
     *
     * 示例：
     * - `->columns(['type' => ['label' => '类型', 'format' => fn ($value) => $map[$value] ?? '-']])`
     */
    public function columns(array $columns): self
    {
        foreach ($columns as $index => $column) {
            if (is_string($index)) {
                if (is_array($column)) {
                    $column = array_replace($column, ['key' => $column['key'] ?? $index]);
                } elseif (is_string($column)) {
                    $column = ['label' => $column, 'key' => $index];
                }
            }

            $this->columns[] = $this->normalizeColumn(is_array($column) ? $column : ['label' => (string) $column]);
        }

        return $this;
    }

    /**
     * 设置数据行。
     *
     * 每行可为列表数组（按列顺序对应单元格）或关联数组（按列的 key 取值）；
     * 单元格值可为标量或 Renderable（如 Displays::image()）。列表行的单元格不足时自动补空。
     *
     * 合并单元格：单元格写成数组 `['value' => ..., 'rowspan' => 2, 'colspan' => 2]`，
     * 另支持 cell 级 `align` / `class`。被 rowspan 覆盖的后续行按顺序省略对应单元格，
     * 渲染层会按网格自动跳过这些位置（关联行的被覆盖 key 直接忽略即可）。
     *
     * 示例：
     * - `->rows([['10.02', '合格'], ['9.98', '不合格']])`
     * - `->rows([['size' => '10.02', 'judge' => '合格']])`（配合列的 key）
     * - `->rows([
     *        [['value' => '10.02', 'rowspan' => 2], '合格', 'A'],
     *        ['不合格', 'B'],
     *        [['value' => '合计', 'colspan' => 3]],
     *    ])`
     */
    public function rows(array $rows): self
    {
        $this->rows = array_values(array_map(
            static fn (mixed $row): array => is_array($row) ? $row : [(string) $row],
            $rows
        ));

        return $this;
    }

    /**
     * 是否显示边框，默认 true。
     */
    public function border(bool $border = true): self
    {
        $this->border = $border;

        return $this;
    }

    /**
     * 是否斑马纹，默认 false。
     */
    public function striped(bool $striped = true): self
    {
        $this->striped = $striped;

        return $this;
    }

    /**
     * 为指定行追加 class（行索引从 0 开始，与 rows() 顺序一致）。
     *
     * 示例：
     * - `->rowClass(3, 'is-total')`
     */
    public function rowClass(int $rowIndex, string $className): self
    {
        $className = trim($className);
        if ($rowIndex < 0 || $className === '') {
            return $this;
        }

        $this->rowClasses[$rowIndex] = $className;

        return $this;
    }

    /**
     * 批量设置行 class，键为行索引、值为 class。
     *
     * 示例：
     * - `->rowClasses([1 => 'is-bg', 3 => 'is-bg'])`
     */
    public function rowClasses(array $rowClasses): self
    {
        foreach ($rowClasses as $rowIndex => $className) {
            if (!is_int($rowIndex) && !ctype_digit((string) $rowIndex)) {
                continue;
            }

            $this->rowClass((int) $rowIndex, (string) $className);
        }

        return $this;
    }

    /**
     * @return array<int, string>
     */
    public function getRowClasses(): array
    {
        return $this->rowClasses;
    }

    /**
     * 是否显示表头，默认 true。
     */
    public function header(bool $header = true): self
    {
        $this->header = $header;

        return $this;
    }

    /**
     * 设置单元格默认对齐：left / center / right；列上的 align 优先。
     */
    public function align(?string $align): self
    {
        $this->align = $this->normalizeAlign($align);

        return $this;
    }

    /**
     * 无数据时的提示文案，空串表示不显示提示行。
     */
    public function emptyText(string $emptyText): self
    {
        $this->emptyText = $emptyText;

        return $this;
    }

    /**
     * 设置单元格空值占位文案：值为 null 或空字符串时显示该文案，默认不替换。
     * 列定义里的 `empty` 优先于该默认值；传 null 关闭。
     *
     * 示例：
     * - `->emptyCell('-')`
     */
    public function emptyCell(?string $text = '-'): self
    {
        $text = $text === null ? null : trim($text);
        $this->emptyCell = $text === null || $text === '' ? null : $text;

        return $this;
    }

    /**
     * 在最前面追加自增序号列（从 1 开始），无需在行数据里准备序号字段。
     *
     * @param string $label 列标题，默认"序号"。
     * @param array  $options 同 column() 的列配置（width / align / class / empty 等）。
     *
     * 示例：
     * - `->indexColumn('序号', ['width' => 80])`
     */
    public function indexColumn(string $label = '序号', array $options = []): self
    {
        array_unshift($this->columns, $this->normalizeColumn(array_replace($options, [
            'label' => $label,
            'index' => true,
        ])));

        return $this;
    }

    /**
     * 设置尺寸：default 或 small（紧凑内边距）。
     */
    public function size(string $size): self
    {
        $size = strtolower(trim($size));
        $this->size = $size === 'small' ? 'small' : 'default';

        return $this;
    }

    /**
     * 设置表格整体宽度：数字按 px 处理（如 1500），也可直接传 CSS 宽度（如 '80%' / '1200px'）。
     * 默认撑满容器；设为固定/最小可视宽度时可配合外层横向滚动查看。
     * 传 null 恢复默认。
     *
     * 示例：
     * - `->width(1500)`
     */
    public function width(int|string|null $width): self
    {
        if ($width === null) {
            $this->width = null;

            return $this;
        }

        $width = trim((string) $width);
        if ($width === '' || $width === '0') {
            $this->width = null;

            return $this;
        }

        $this->width = ctype_digit($width) ? $width . 'px' : $width;

        return $this;
    }

    public function getWidth(): ?string
    {
        return $this->width;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * @return array<int, array{label: string, index: bool, key: ?string, width: ?string, align: ?string, class: ?string, format: ?callable, empty: ?string}>
     */
    public function getColumns(): array
    {
        return $this->columns;
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    public function getRows(): array
    {
        return $this->rows;
    }

    public function isBorder(): bool
    {
        return $this->border;
    }

    public function isStriped(): bool
    {
        return $this->striped;
    }

    public function isHeader(): bool
    {
        return $this->header;
    }

    public function getAlign(): ?string
    {
        return $this->align;
    }

    public function getEmptyText(): string
    {
        return $this->emptyText;
    }

    public function getEmptyCell(): ?string
    {
        return $this->emptyCell;
    }

    public function getSize(): string
    {
        return $this->size;
    }

    private function normalizeColumn(array $column): array
    {
        $width = $column['width'] ?? null;
        if ($width !== null) {
            $width = trim((string) $width);
            $width = ctype_digit($width) ? $width . 'px' : $width;
        }

        $class = isset($column['class']) ? trim((string) $column['class']) : '';
        $key = isset($column['key']) ? trim((string) $column['key']) : '';
        $empty = isset($column['empty']) ? trim((string) $column['empty']) : '';
        $index = (bool) ($column['index'] ?? false);

        return [
            'label' => (string) ($column['label'] ?? ''),
            'index' => $index,
            'key' => $index || $key === '' ? null : $key,
            'width' => $width === '' ? null : $width,
            'align' => $this->normalizeAlign($column['align'] ?? null),
            'class' => $class === '' ? null : $class,
            'format' => isset($column['format']) && is_callable($column['format']) ? $column['format'] : null,
            'empty' => $empty === '' ? null : $empty,
        ];
    }

    private function normalizeAlign(mixed $align): ?string
    {
        if (!is_string($align)) {
            return null;
        }

        $align = strtolower(trim($align));

        return in_array($align, ['left', 'center', 'right'], true) ? $align : null;
    }
}