<?php

namespace Sc\Util\HtmlStructure;

use JetBrains\PhpStorm\ExpectedValues;
use Sc\Util\HtmlStructure\Theme\Interfaces\DetailThemeInterface;
use Sc\Util\HtmlStructure\Theme\Theme;

class Detail
{
    private string|\Stringable $title = '';
    private string|\Stringable $extra = '';
    private array $attr = [];
    private array $items = [];

    private array $data = [];

    private string $dataModel = '';

    public function __construct(string|\Stringable $title)
    {
        $this->title = $title;
    }

    public static function create(string|\Stringable $title): static
    {
        return new static($title);
    }

    /**
     * 自定义操作区，显示在右上方
     *
     * @param string|\Stringable $extra
     * @return Detail
     */
    public function setExtra(string|\Stringable $extra): static
    {
        $this->extra = $extra;
        return $this;
    }

    /**
     * 设置属性
     *
     * @param array $attr
     * <br/>border    是否带有边框
     * <br/>column    一行 Descriptions Item 的数量
     * <br/>direction    排列的方向 'vertical' | 'horizontal'
     * <br/>size    列表的尺寸 '' | 'large' | 'default' | 'small'
     * <br/>title    标题文本，显示在左上方
     * <br/>extra    操作区文本，显示在右上方
     * <br/>label-width 2.8.8    每一列的标签宽度
     * @return $this
     */
    public function setAttr(array $attr): static
    {
        $this->attr = $attr;
        return $this;
    }

    /**
     * @param string|\Stringable $label
     * @param string|\Stringable $value
     * @param array $attr
     * <br/>label    标签文本 string ''
     * <br/>span    列的数量 number 1
     * <br/>rowspan 单元格应该跨越的行数 number 1
     * <br/>width    列的宽度，不同行相同列的宽度按最大值设定（如无 border ，宽度包含标签与内容） string / number ''
     * <br/>min-width    列的最小宽度，与 width 的区别是 width 是固定的，min-width 会把剩余宽度按比例分配给设置了 min-width 的列（如无 border，宽度包含标签与内容）
     * <br/>label-width 列标签宽，如果未设置，它将与列宽度相同。 比 Descriptions 的 label-width 优先级高
     * <br/>align    列的内容对齐方式（如无 border，对标签和内容均生效） 'left' | 'center' | 'right'
     * <br/>label-align    列的标签对齐方式，若不设置该项，则使用内容的对齐方式（如无 border，请使用 align 参数） 'left' | 'center' | 'right'
     * <br/>class-name    列的内容自定义类名
     * <br/>label-class-name    column label custom class name
     * @return void
     */
    public function addItem(string|\Stringable $label, string|\Stringable $value, array|int $attr = []): void
    {
        $this->items[] = [
            'label' => $label,
            'value' => $value,
            'attr' => is_array($attr) ? $attr : ['span' => $attr],
        ];
    }

    public function __toString(): string
    {
        return $this->render();
    }

    public function render(#[ExpectedValues(Theme::AVAILABLE_THEME)] string $theme = null)
    {
        return Theme::getRenderer(DetailThemeInterface::class, $theme)->render($this);
    }

    public function getTitle(): \Stringable|string
    {
        return $this->title;
    }

    public function getExtra(): \Stringable|string
    {
        return $this->extra;
    }

    public function getItems(): array
    {
        return $this->items;
    }

    public function getAttr(): array
    {
        return $this->attr;
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function setData(array $data): void
    {
        $this->data = $data;
    }

    public function getDataModel(): string
    {
        return $this->dataModel;
    }

    /**
     * 数据从VUE 中获取时，vue中的 data key
     *
     * @param string $dataModel
     * @return void
     */
    public function setDataModel(string $dataModel): void
    {
        $this->dataModel = $dataModel;
    }
}