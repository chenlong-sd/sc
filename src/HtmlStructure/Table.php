<?php
/**
 * datetime: 2023/5/25 23:53
 **/

namespace Sc\Util\HtmlStructure;

use JetBrains\PhpStorm\ExpectedValues;
use JetBrains\PhpStorm\Language;
use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlStructure\Form\FormItemInterface;
use Sc\Util\HtmlStructure\Html\Js\Grammar;
use Sc\Util\HtmlStructure\Table\Column;
use Sc\Util\HtmlStructure\Table\EventHandler;
use Sc\Util\HtmlStructure\Theme\Interfaces\TableThemeInterface;
use Sc\Util\HtmlStructure\Theme\Theme;
use Sc\Util\Tool;

class Table
{
    /**
     * 属性
     *
     * @var array
     */
    private array $attrs = [];

    /**
     * 事件
     *
     * @var mixed|array
     */
    private mixed $headerEvents = [];

    /**
     * 行事件
     *
     * @var array|EventHandler[]
     */
    private array $rowEvents = [];

    /**
     * @var array|Column[]
     */
    private array $columns = [];

    /**
     * 开启分页
     * @var bool
     */
    private bool $openPagination = true;
    private ?string $rowGroup = null;
    private array $rowGroupEvent = [];
    protected array $searchForms = [];

    public function __construct(private readonly string|array $data, private ?string $id = null)
    {
    }

    /**
     * @param string|array $data
     * @param string|null  $id
     *
     * @return Table
     * @date 2023/5/26
     */
    public static function create(string|array $data = '', ?string $id = null): Table
    {
        return new self($data, $id);
    }

    /**
     * 设置属性
     *
     * @param string|array $attr
     * @param mixed        $value
     *
     * @date 2023/5/26
     */
    public function setAttr(string|array $attr, mixed $value = null): void
    {
        $attrs = is_string($attr) ? [$attr => $value] : $attr;

        $this->attrs = array_merge($this->attrs, $attrs);
    }

    /**
     * @return array
     */
    public function getAttrs(): array
    {
        return $this->attrs;
    }

    /**
     * 设置头部事件
     *
     * @param string|AbstractHtmlElement $eventLabel 如只是需要改变按钮颜色和添加图标，
     *                                               可使用：@success.icon.title, 会生成 success 风格的包含icon图标，内容为title的button，icon可省略
     *                                               可使用：@success.icon.title[theme], theme可取：default, plain
     *                                               更复杂的请示使用{@see AbstractHtmlElement}
     * @param mixed                      $handler   操作的js代码，可使用的变量 selection => 已选择的数据
     *
     * @date 2023/6/1
     */
    public function setHeaderEvent(string|AbstractHtmlElement $eventLabel, #[Language('JavaScript')] mixed $handler): void
    {
        $eventName = Tool::random('HeaderEvent')->get();

        $this->headerEvents[$eventName] = [
            'el'       => $eventLabel,
            'handler'  => $handler instanceof \Closure ? $handler() : $handler,
            'position' => 'left'
        ];
    }

    /**
     * @param string|AbstractHtmlElement $eventLabel 如只是需要改变按钮颜色和添加图标，
     *                                               可使用：@success.icon.title, 会生成 success 风格的包含icon图标，内容为title的button，icon可省略
     *                                               可使用：@success.icon.title[theme], theme可取：default, plain
     *                                               更复杂的请示使用{@see AbstractHtmlElement}
     * @param mixed                      $handler
     *
     * @return void
     */
    public function setHeaderRightEvent(string|AbstractHtmlElement $eventLabel, #[Language('JavaScript')] mixed $handler): void
    {
        $eventName = Tool::random('HeaderEvent')->get();

        $this->headerEvents[$eventName] = [
            'el'       => $eventLabel,
            'handler'  => $handler instanceof \Closure ? $handler() : $handler,
            'position' => 'right'
        ];
    }

    public function addSearch(FormItemInterface $formItem, #[ExpectedValues([
        '=', 'like', 'in', 'between', 'like_right'
    ])] string $type = '='): static
    {
        $this->searchForms[] = [
            "form" => $formItem,
            'type' => $type
        ];

        return $this;
    }

    /**
     * 是否开启分页
     *
     * @param bool $open
     *
     * @return $this
     */
    public function setPagination(bool $open): static
    {
        $this->openPagination = $open;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getHeaderEvents(): mixed
    {
        return $this->headerEvents;
    }

    /**
     * @param string|AbstractHtmlElement $eventLabel 如只是需要改变按钮颜色和添加图标，
     *                                               可使用：@success.icon.title, 会生成 success 风格的包含icon图标，内容为title的button，icon可省略
     *                                               更复杂的请示使用{@see AbstractHtmlElement}
     * @param mixed                      $handler    事件处理代码，行数据变量  row , 取当前行id值：row.id
     *
     * @date 2023/6/1
     */
    public function setRowEvent(string|AbstractHtmlElement $eventLabel, #[Language('JavaScript')] mixed $handler): void
    {
        $eventName = Tool::random('RowEvent')->get();

        $this->rowEvents[$eventName] = [
            'el'      => $eventLabel,
            'handler' => $handler instanceof \Closure ? $handler() : $handler,
            'group'   => $this->rowGroup
        ];
    }

    /**
     * 设置行组事件
     *
     * @param string|AbstractHtmlElement $eventLabel
     * @param \Closure                   $closure
     *
     * @return void
     */
    public function setRowGroupEvent(string|AbstractHtmlElement $eventLabel, \Closure $closure): void
    {
        $this->rowGroup = md5($eventLabel);
        $this->rowGroupEvent[$this->rowGroup] = $eventLabel;

        $closure($this);

        $this->rowGroup = null;
    }

    /**
     * @return array
     */
    public function getRowEvents(): array
    {
        return $this->rowEvents;
    }
    public function getRowGroupEvents(): array
    {
        return $this->rowGroupEvent;
    }

    /**
     * @param string|null $id
     *
     * @return Table
     */
    public function setId(?string $id): Table
    {
        $this->id = $id;
        return $this;
    }

    /**
     * @return string|null
     */
    public function getId(): ?string
    {
        return $this->id;
    }

    /**
     * 添加列
     *
     * @param Column ...$columns
     *
     * @date 2023/5/26
     */
    public function addColumns(...$columns): void
    {
        $this->columns = [...$this->columns, ...$columns];
    }

    /**
     * @param string|null $theme
     *
     * @return AbstractHtmlElement
     * @date 2023/5/27
     */
    public function render(#[ExpectedValues(Theme::AVAILABLE_THEME)] string $theme = null): AbstractHtmlElement
    {
        foreach ($this->getColumns() as $column) {
            if ($search = $column->getSearch()){
                $this->addSearch($search['form'], $search['type']);
            }
        }

        return Theme::getRender(TableThemeInterface::class, $theme)->render($this);
    }

    /**
     * @return array|Column[]
     */
    public function getColumns(): array
    {
        return array_values(array_filter($this->columns, fn(Column $column) => !empty($column->getShow()['type']) || !$column->getShow()));
    }

    public function getSearchForms(): array
    {
        return $this->searchForms;
    }

    /**
     * @return array|string
     */
    public function getData(): array|string
    {
        return $this->data;
    }

    /**
     * @return bool
     */
    public function isOpenPagination(): bool
    {
        return $this->openPagination;
    }
}