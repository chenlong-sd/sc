<?php
/**
 * datetime: 2023/5/25 23:53
 **/

namespace Sc\Util\HtmlStructure;

use JetBrains\PhpStorm\ExpectedValues;
use JetBrains\PhpStorm\Language;
use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlStructure\Form\FormItemInterface;
use Sc\Util\HtmlStructure\Html\Js\JsCode;
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

    /**
     * 分组事件的组名
     *
     * @var string|null
     */
    private ?string $rowGroup = null;

    /**
     * 分组的事件信息
     *
     * @var array
     */
    private array $rowGroupEvent = [];

    /**
     * 搜索表单信息
     *
     * @var array
     */
    protected array $searchForms = [];

    /**
     * 状态切换搜索按钮
     *
     * @var array
     */
    protected array $statusToggleButtons = [];

    /**
     * 最大高度
     *
     * @var int
     */
    protected int $maxHeight = 0;

    /**
     * 拖拽设置
     *
     * @var array
     */
    protected array $draw = [
        'able'         => false,
        'el'           => null,
        'updateHandle' => "",
        'config'       => []
    ];

    /**
     * 多个状态切换按钮是否换行展示
     *
     * @var bool
     */
    private bool $statusToggleButtonsNewLine = false;

    /**
     * 回收站设置
     *
     * @var array
     */
    private array $trash = [];

    /**
     * 虚拟表格，按条件设置的时候，不满足条件时的设置
     *
     * @var null
     */
    private $virtual = null;

    /**
     * 开启导出excel
     *
     * @var true
     */
    private bool   $exportExcel = false;

    /**
     * 导出的excel文件名
     *
     * @var string|null
     */
    private ?string $excelFilename = null;

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
     * @return Table
     * @date 2023/5/26
     */
    public function setAttr(string|array $attr, mixed $value = null): static
    {
        $attrs = is_string($attr) ? [$attr => $value] : $attr;

        $this->attrs = array_merge($this->attrs, $attrs);

        return $this;
    }

    /**
     * @return array
     */
    public function getAttrs(): array
    {
        return $this->attrs;
    }

    /**
     * 条件渲染，用于后续事件，或者闭包里面的事件
     *
     * @param bool          $condition true: 渲染，false: 不渲染
     * @param callable|null $callback
     *
     * @return Table|$this|object
     */
    public function when(bool $condition, callable $callback = null): mixed
    {
        if (!$this->virtual) {
            $this->virtual = new class
            {
                public function __call(string $name, array $arguments){}
            };
        }

        $table = $condition ? $this : $this->virtual;

        if ($callback) {
            $callback($table);
        }

        return $table;
    }

    /**
     * 设置头部事件
     *
     * @param string|AbstractHtmlElement|array $eventLabel 如只是需要改变按钮颜色和添加图标，
     *                                               可使用：@success.icon.title, 会生成 success 风格的包含icon图标，内容为title的button，icon可省略
     *                                               可使用：@success.icon.title[theme], theme可取：default, plain
     *                                               更复杂的请示使用{@see AbstractHtmlElement}
     * @param mixed                      $handler   操作的js代码，可使用的变量 selection => 已选择的数据
     *
     * @date 2023/6/1
     */
    public function setHeaderEvent(string|AbstractHtmlElement|array $eventLabel, #[Language('JavaScript')] mixed $handler): static
    {
        $eventName = Tool::random('HeaderEvent')->get();

        $this->headerEvents[$eventName] = [
            'el'       => $eventLabel,
            'handler'  => $handler instanceof \Closure ? $handler() : $handler,
            'position' => 'left'
        ];

        return $this;
    }

    /**
     * @param string|AbstractHtmlElement|array $eventLabel 如只是需要改变按钮颜色和添加图标，
     *                                               可使用：@success.icon.title, 会生成 success 风格的包含icon图标，内容为title的button，icon可省略
     *                                               可使用：@success.icon.title[theme], theme可取：default, plain
     *                                               更复杂的请示使用{@see AbstractHtmlElement}
     * @param mixed                      $handler
     *
     * @return void
     */
    public function setHeaderRightEvent(string|AbstractHtmlElement|array $eventLabel, #[Language('JavaScript')] mixed $handler): void
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
     * @param string|AbstractHtmlElement|array $eventLabel 如只是需要改变按钮颜色和添加图标，
     *                                               可使用：@success.icon.title, 会生成 success 风格的包含icon图标，内容为title的button，icon可省略
     *                                               更复杂的请示使用{@see AbstractHtmlElement}
     *                                               数组时，第一个元素为数组，会识别为元素的属性值, 例：["@primary.Exit.编辑", ["v-if" => "id == 1"]]
     * @param mixed                      $handler    事件处理代码，行数据变量  row , 取当前行id值：row.id
     *
     * @date 2023/6/1
     */
    public function setRowEvent(string|AbstractHtmlElement|array $eventLabel, #[Language('JavaScript')] mixed $handler): static
    {
        $eventName = Tool::random('RowEvent')->get();

        $this->rowEvents[$eventName] = [
            'el'      => $eventLabel,
            'handler' => $handler instanceof \Closure ? $handler() : $handler,
            'group'   => $this->rowGroup
        ];

        return $this;
    }

    /**
     * 设置行组事件
     *
     * @param string|AbstractHtmlElement $eventLabel
     * @param \Closure                   $closure
     *
     * @return Table
     */
    public function setRowGroupEvent(string|AbstractHtmlElement $eventLabel, \Closure $closure): static
    {
        $this->rowGroup = md5($eventLabel);
        $this->rowGroupEvent[$this->rowGroup] = $eventLabel;

        $closure($this);

        $this->rowGroup = null;

        return $this;
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
    public function addColumns(...$columns): static
    {
        $this->columns = [...$this->columns, ...$columns];

        return $this;
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

        return Theme::getRenderer(TableThemeInterface::class, $theme)->render($this);
    }

    /**
     * @param string      $searchField 切换时搜索的字段名
     * @param array       $mapping     可切换的数据 [['value' => 1, 'label' => '正常'], ['value' => 2, 'label' => '异常'],]， [1 => '正常', 2 => '异常']
     * @param string|null $label
     *
     * @return Table
     */
    public function addStatusToggleButtons(string $searchField, array $mapping, string $label = null): static
    {
        $this->statusToggleButtons[] = compact('searchField', 'mapping', 'label');

        return $this;
    }

    /**
     * 设置状态切换的为新行
     *
     * @param bool $newLine
     *
     * @return $this
     */
    public function setStatusToggleButtonsNewLine(bool $newLine = true): static
    {
        $this->statusToggleButtonsNewLine = $newLine;

        return $this;
    }

    public function getStatusToggleButtonsNewLine(): bool
    {
        return $this->statusToggleButtonsNewLine;
    }

    /**
     * @return array|Column[]
     */
    public function getColumns(bool $isAll = false): array
    {
        if ($isAll) {
            return $this->columns;
        }
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

    public function getStatusToggleButtons(): array
    {
        return $this->statusToggleButtons;
    }

    public function getMaxHeight(): int
    {
        return $this->maxHeight;
    }

    /**
     * 设置最大高度。不设置则自动识别
     *
     * @param int $maxHeight
     *
     * @return Table
     */
    public function setMaxHeight(int $maxHeight = -60): static
    {
        $this->maxHeight = $maxHeight;

        return $this;
    }

    public function getDraw(): array
    {
        return $this->draw;
    }

    /**
     * 设置行拖动
     *
     * @param string|AbstractHtmlElement|array $element        如只是需要改变按钮颜色和添加图标，
     *                                                         可使用：@success.icon.title, 会生成 success 风格的包含icon图标，内容为title的button，icon可省略
     *                                                         更复杂的请示使用{@see AbstractHtmlElement}
     *                                                         数组时，第一个元素为数组，会识别为元素的属性值, 例：["@primary.Exit.编辑", ["v-if" => "id == 1"]]
     * @param callable|JsCode|string           $updateCallable 更新排序时的处理回调代码 ,如果是闭包，则完全由此代码处理  this.{$table->getId()} 表格数据， evt 事件对象
     * @param array                            $sortConfig     更多Sortable实例的配置
     *
     * @return void
     */
    public function setDraw(string|AbstractHtmlElement|array $element = "@primary.rank.", callable|JsCode|string $updateCallable = "", array $sortConfig = []): void
    {
        $this->draw = [
            'able'         => true,
            'el'           => $element,
            'updateHandle' => $updateCallable instanceof \Closure ? [$updateCallable()] : $updateCallable,
            'config'       => $sortConfig
        ];
    }

    /**
     * 启用回收站
     *
     * @param string|null $recoverUrl 恢复数据地址，不填则没有恢复数据操作
     *
     * @return void
     */
    public function enableTrash(?string $recoverUrl = null): void
    {
        $this->trash = [
            'recoverUrl' => $recoverUrl
        ];
    }

    /**
     * @return array
     */
    public function getTrash(): array
    {
        return $this->trash;
    }

    public function openExportExcel(string $filename = 'export.xlsx'): void
    {
        $this->exportExcel = true;
        $this->excelFilename = preg_replace('/\.xlsx$/', '', $filename) . date('Y-m-d');
    }

    public function isOpenExportExcel(): bool
    {
        return $this->exportExcel;
    }

    public function getExcelFilename(): ?string
    {
        return $this->excelFilename;
    }
}