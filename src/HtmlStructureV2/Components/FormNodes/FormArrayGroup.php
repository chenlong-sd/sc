<?php

namespace Sc\Util\HtmlStructureV2\Components\FormNodes;

use InvalidArgumentException;
use Sc\Util\HtmlStructureV2\Components\Concerns\HasRenderAttributes;
use Sc\Util\HtmlStructureV2\Components\Concerns\HasFormTableColumnAttributes;
use Sc\Util\HtmlStructureV2\Components\Concerns\HasSpan;
use Sc\Util\HtmlStructureV2\Components\FormNodes\Concerns\HasFormNodeChildren;
use Sc\Util\HtmlStructureV2\Contracts\FormNode;
use Sc\Util\HtmlStructureV2\Contracts\FormNodeContainer;

class FormArrayGroup implements FormNode, FormNodeContainer
{
    use HasRenderAttributes;
    use HasFormTableColumnAttributes;
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
        private readonly string $name
    ) {
        if (trim($name) === '') {
            throw new InvalidArgumentException('Form array group name cannot be empty.');
        }
    }

    /**
     * 直接创建一个重复分组节点。
     *
     * @param string $name 数组字段名。
     * @return static 重复分组节点实例。
     *
     * 示例：
     * `FormArrayGroup::make('contacts')`
     */
    public static function make(string $name): static
    {
        return new static($name);
    }

    /**
     * 追加当前数组分组的“每一行 schema”。
     * 推荐使用这个方法表达“这一组数据有哪些字段/结构节点”。
     *
     * @param FormNode ...$children 每一行的 schema 节点。
     * @return static 当前重复分组节点。
     *
     * 示例：
     * `Forms::arrayGroup('contacts')->addSchema(Fields::text('name', '姓名'))`
     */
    public function addSchema(FormNode ...$children): static
    {
        return $this->appendFormNodeChildren(...$children);
    }

    /**
     * 设置初始行数据，适合默认预置多组内容。
     * 每一行最终都会再与子字段默认值合并；若 minRows() 更大，不足的行会继续自动补齐。
     *
     * @param array $rows 默认行数据。
     * @return static 当前重复分组节点。
     *
     * 示例：
     * `Forms::arrayGroup('contacts')->defaultRows([['name' => '张三']])`
     */
    public function defaultRows(array $rows): static
    {
        $this->defaultRows = array_values($rows);

        return $this;
    }

    /**
     * 设置数组分组标题，显示在分组头部。
     *
     * @param string|null $title 分组标题。
     * @return static 当前重复分组节点。
     *
     * 示例：
     * `Forms::arrayGroup('contacts')->title('联系人')`
     */
    public function title(?string $title): static
    {
        $this->title = $title;

        return $this;
    }

    /**
     * 设置“新增一组”按钮文案。
     *
     * @param string $text 新增按钮文案。
     * @return static 当前重复分组节点。
     *
     * 示例：
     * `Forms::arrayGroup('contacts')->addButtonText('新增联系人')`
     */
    public function addButtonText(string $text): static
    {
        $this->addButtonText = $text;

        return $this;
    }

    /**
     * 控制是否允许新增分组行。
     * 新增成功后会触发表单的 `arrayRowAdd` 事件。
     *
     * @param bool $addable 是否允许新增，默认值为 true。
     * @return static 当前重复分组节点。
     *
     * 示例：
     * `Forms::arrayGroup('contacts')->addable(false)`
     */
    public function addable(bool $addable = true): static
    {
        $this->addable = $addable;

        return $this;
    }

    /**
     * 控制是否允许删除分组行。
     * 删除成功后会触发表单的 `arrayRowRemove` 事件。
     *
     * @param bool $removable 是否允许删除，默认值为 true。
     * @return static 当前重复分组节点。
     *
     * 示例：
     * `Forms::arrayGroup('contacts')->removable(false)`
     */
    public function removable(bool $removable = true): static
    {
        $this->removable = $removable;

        return $this;
    }

    /**
     * 控制是否允许上下调整分组顺序。
     * 调整成功后会触发表单的 `arrayRowMove` 事件。
     *
     * @param bool $reorderable 是否允许排序，默认值为 true。
     * @return static 当前重复分组节点。
     *
     * 示例：
     * `Forms::arrayGroup('contacts')->reorderable()`
     */
    public function reorderable(bool $reorderable = true): static
    {
        $this->reorderable = $reorderable;

        return $this;
    }

    /**
     * 设置最少保留的分组行数，不足时会自动补齐默认行。
     * 子字段路径会落到 `name.0.xxx`、`name.1.xxx` 这类数组作用域下。
     *
     * @param int $minRows 最少行数。
     * @return static 当前重复分组节点。
     *
     * 示例：
     * `Forms::arrayGroup('contacts')->minRows(1)`
     */
    public function minRows(int $minRows): static
    {
        $this->minRows = max(0, $minRows);

        return $this;
    }

    /**
     * 设置允许的最大分组行数，传 null 表示不限制。
     *
     * @param int|null $maxRows 最大行数；传 null 表示不限制。
     * @return static 当前重复分组节点。
     *
     * 示例：
     * `Forms::arrayGroup('contacts')->maxRows(5)`
     */
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
