<?php

namespace Sc\Util\HtmlStructure\Table;

use JetBrains\PhpStorm\ExpectedValues;
use JetBrains\PhpStorm\Language;
use Sc\Util\HtmlElement\El;
use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlElement\ElementType\FictitiousLabel;
use Sc\Util\HtmlStructure\Form\FormItem;
use Sc\Util\HtmlStructure\Form\FormItemInterface;
use Sc\Util\HtmlStructure\Form\FormItemSelect;
use Sc\Util\HtmlStructure\Html\Html;
use Sc\Util\HtmlStructure\Theme\Interfaces\TableColumnThemeInterface;
use Sc\Util\HtmlStructure\Theme\Theme;
use function Composer\Autoload\includeFile;

/**
 * Class Column
 * @method static Column selection() 选择列
 * @method static Column index(string $title = '')     索引列
 * @method static Column expand(string $title)    可展开列，仅ElementUI
 * @method static Column normal(string $title, string $prop = '') 常规列
 * @method static Column event(string $title = '') 事件列
 *
 */
class Column
{
    /**
     * 展示模板
     *
     * @var string|\Stringable
     */
    private string|\Stringable $format = '';

    /**
     * 展示类型
     *
     * @var array
     */
    private array $show = [];

    /**
     * 搜索信息
     *
     * @var array
     */
    private array $search = [];


    /**
     * 固定位置
     *
     * @var string|null
     */
    protected ?string $fixedPosition = null;

    /**
     * 排序时的字段
     *
     * @var string|null
     */
    protected ?string $sortField = null;

    /**
     * 空置的显示模板
     *
     * @var string|\Stringable|array
     */
    private string|\Stringable $emptyShowTemplate = '';

    /**
     * 显示内容后面的感叹号设置信息
     *
     * @var array|string[]|\Stringable[]
     */
    private array $tip = [];

    /**
     * 导出设置信息
     *
     * @var array
     */
    private array $importExcel = [
        'sort' => null,
        'allow' => true,
    ];

    public function __construct(private array $attrs = []){}

    /**
     * 设置属性
     *
     * @param string|array $attr
     * @param mixed        $value
     *
     * @return $this
     */
    public function setAttr(string|array $attr, mixed $value = ""): static
    {
        $attrs = is_string($attr)
            ? ($value === "" ? El::getAttrFromStr($attr) : [$attr => $value])
            : $attr;

        $this->attrs = [...$this->attrs, ...$attrs];

        return $this;
    }

    public function emptyShow(string|\Stringable $content)
    {
        $this->emptyShowTemplate = $content;

        return $this;
    }

    public function width(int|string $width, $showOverflowTooltip = true): static
    {
        $this->setAttr('width', $width);
        $this->setAttr(':show-overflow-tooltip', $showOverflowTooltip);
        if (!$showOverflowTooltip) {
            $this->align('left');
        }

        return $this;
    }

    /**
     * @param int|string $width
     * @return $this
     */
    public function minWidth(int|string $width): static
    {
        $this->setAttr('min-width', $width);
        return $this;
    }

    /**
     * @return $this
     */
    public function enableSort(string $sortField = null): static
    {
        $this->sortField = $sortField ?: $this->getAttr('prop');

        return $this;
    }

    /**
     * 固定列
     *
     * @param string $position
     *
     * @return Column
     */
    public function fixed(#[ExpectedValues(['right', 'left'])]string $position = 'right'): static
    {
        $this->fixedPosition = $position;

        return $this;
    }

    /**
     * 获取属性
     *
     * @param string|null $attr
     * @param mixed|null  $default
     *
     * @return mixed|null
     * @date 2023/5/27
     */
    public function getAttr(?string $attr = null, mixed $default = null): mixed
    {
        if ($attr === null) {
            return $this->attrs;
        }

        return $this->attrs[$attr] ?? $default;
    }

    /**
     * 添加搜索
     *
     * @param string                        $type     搜索类型
     * @param FormItemInterface|string|null $formItem 搜索字段或搜索表单
     *
     * @return $this
     */
    public function addSearch(#[ExpectedValues(['=', 'like', 'in', 'between', 'like_right'])] string $type = '=', FormItemInterface|string $formItem = null): static
    {
        if (!$formItem instanceof FormItemInterface) {
            $formItem = $this->autoMakeFormItem($formItem, $type);
        }else{
            if (!$formItem->getPlaceholder()) {
                $formItem->placeholder($this->getAttr("label"));
            }
        }

        $this->search = [
            'type' => $type,
            'form' => $formItem
        ];

        return $this;
    }

    /**
     * 设置展示模板
     *
     * @param string|\Stringable|array $format 参数规则依照vue语法
     *                                   行参数： 直接使用，例：id => {{ id }}  , <span :name="id"></span>
     *                                   其他参数：前面加@，例：location => {{ @location }}  , <span :name="@location"></span>
     *                                   数组： ["描述" => '值'] =>   <div><b>描述</b>值</div> ，可配合 width(300, false)使用
     *
     * @return $this
     */
    public function setFormat(#[Language('Vue')]string|\Stringable|array $format): static
    {
        $this->format = is_array($format) ? $this->arrayFormat($format) : $format;

        return $this;
    }

    private function arrayFormat(array $data): FictitiousLabel
    {
        $el = El::fictitious();
        foreach ($data as $des => $value) {
            $el->append(El::double('div')->append(El::double('b')->append($des))->append($value));
        }
        return $el;
    }

    /**
     * 导出excel设置
     *
     * @param bool       $allow 是否允许导出
     * @param float|null $sort  导出排序值，默认为null，不排序
     *
     * @return $this
     */
    public function importExcel(bool $allow = true, float $sort = null): static
    {
        $this->importExcel = [
            'allow' => $allow,
            'sort'  => $sort
        ];

        return $this;
    }

    /**
     * 显示开关
     *
     * @param array      $options
     * @param string     $requestUrl
     * @param mixed|null $openValue
     *
     * @return Column
     */
    public function showSwitch(array $options, string $requestUrl, mixed $openValue = null): static
    {
        $this->show = [
            'type' => 'switch',
            'config' => [
                'url'       => $requestUrl,
                'openValue' => $openValue,
                'options'   => $options,
            ]
        ];

        return $this;
    }

    /**
     * @param array $options 这里传值与tag的映射
     *
     * @return $this
     * @deprecated
     * @link showTags()
     */
    public function showTag(array $options): static
    {
        $this->show = [
            'type' => 'tag',
            'config' => [
                'options'  => $options,
            ]
        ];

        return $this;
    }

    /**
     * @param ColumnTags $tags
     *
     * @return $this
     */
    public function showTags(ColumnTags $tags): static
    {
        $this->show = [
            'type' => 'tag',
            'config' => [
                'options'  => $tags->getTags(),
            ]
        ];

        return $this;
    }

    /**
     * @return $this
     */
    public function showImage(): static
    {
        $this->show = [
            'type' => 'image'
        ];

        return $this;
    }

    /**
     * 不显示此列
     *
     * @param bool       $confirm   是否不显示此列
     * @param bool       $excelImport 是否支持导入此列
     * @param float|null $excelSort  导入的排序值
     *
     * @return $this
     */
    public function notShow(bool $confirm = true, bool $excelImport = false, float $excelSort = null): static
    {
        if ($confirm) {
            $this->show = [
                'type' => null
            ];
        }
        $this->importExcel($excelImport, $excelSort);

        return $this;
    }

    public function onlyImportExcel(float $excelSort = null): static
    {
        return $this->notShow(true, true, $excelSort);
    }

    /**
     * @return string|\Stringable
     */
    public function getFormat(): \Stringable|string
    {
        return $this->format;
    }

    /**
     * @param string|null $theme
     *
     * @return AbstractHtmlElement
     * @date 2023/5/27
     */
    public function render(#[ExpectedValues(Theme::AVAILABLE_THEME)] string $theme = null): AbstractHtmlElement
    {
        return Theme::getRenderer(TableColumnThemeInterface::class, $theme)->render($this);
    }

    /**
     * @param string $name
     * @param array  $arguments
     *
     * @return self
     * @throws \Exception
     * @date 2023/5/27
     */
    public static function __callStatic(string $name, array $arguments)
    {
        if (!in_array($name, ['selection', 'index', 'expand', 'normal', 'event'])) {
            throw new \Exception(sprintf("%s method not found.", $name));
        }

        $initAttr = [];
        if ($name === 'normal') {
            $initAttr['label'] = $arguments[0] ?? '';
            empty($arguments[1]) || $initAttr['prop'] = $arguments[1];
        } else if ($name === 'event') {
            $initAttr['label']      = $arguments[0] ?? '操作';
            $initAttr['mark-event'] = true;
            $initAttr['class-name'] = 'sc-event-column';

            Html::css()->addCss('.sc-event-column .el-button+.el-button{ margin-left: 0 }');
            Html::css()->addCss('.sc-event-column .el-button:not(:last-child){ margin-right: 12px }');

        } else if ($name === 'index') {
            $initAttr['label'] = $arguments[0] ?? '序号';
            $initAttr['type']  = $name;
            $initAttr['width'] = 80;
        } else {
            $initAttr['type'] = $name;
        }

        return new self($initAttr);
    }

    /**
     * @return array
     */
    public function getShow(): array
    {
        return $this->show;
    }

    /**
     * @return array
     */
    public function getSearch(): array
    {
        return $this->search;
    }

    /**
     * @param array $mapping 支持 key => value , [value => ', label => ']
     *
     * @return Column
     */
    public function showMapping(array $mapping): static
    {
        $this->show = [
            'type' => 'mapping',
            'config' => [
                'options'  => $mapping,
            ]
        ];

        return $this;
    }

    public function getFixedPosition(): ?string
    {
        return $this->fixedPosition;
    }

    /**
     * @param FormItemInterface|string|null $formItem
     * @param string                        $type
     *
     * @return mixed|\Sc\Util\HtmlStructure\Form\FormItemDatetime|FormItemSelect|\Sc\Util\HtmlStructure\Form\FormItemText
     */
    private function autoMakeFormItem(FormItemInterface|string|null $formItem, string $type): mixed
    {
        $name = $formItem ?: $this->attrs['prop'];
        if (!empty($this->show['config']['options'])) {
            $formItem = FormItem::select($name)->options(
                array_map(function ($options) {
                    if ($options instanceof AbstractHtmlElement) {
                        $options = trim($options->getContent());
                    }
                    return $options;
                }, $this->show['config']['options'])
            );
        } else if (str_contains($name, 'time')) {
            $formItem = FormItem::datetime($name)
                ->setVAttrs([
                    'start-placeholder' => "开始时间",
                    'end-placeholder'   => "结束时间",
                ])
                ->setTimeType('datetimerange')->valueFormat();
        } else if (str_contains($name, 'date')) {
            $formItem = FormItem::datetime($name)
                ->setTimeType('daterange')
                ->setVAttrs([
                    'start-placeholder' => "开始日期",
                    'end-placeholder'   => "结束日期",
                ])
                ->valueFormat('YYYY-MM-DD');
        } else if ($type === 'in') {
            $formItem = FormItem::select($name)->setVAttrs('allow-create');
        } else {
            $formItem = FormItem::text($name);
        }
        $formItem->placeholder($this->attrs['label']);

        if ($type === 'in' && $formItem instanceof FormItemSelect) {
            $formItem->multiple();
        }

        return $formItem;
    }

    public function getSortField(): ?string
    {
        return $this->sortField;
    }

    public function align(#[ExpectedValues(['center', 'left', 'right'])] string $align): static
    {
        if (!$className = $this->getAttr('class-name')) {
            Html::css()->addCss(".el-table .sc-table-left{text-align: left!important;}");
            Html::css()->addCss(".el-table .sc-table-right{text-align: right!important;}");

            $this->setAttr('class-name', 'sc-table-' . $align);
        }else{
            Html::css()->addCss(".el-table .$className{text-align: $align!important;}");
        }

        return $this;
    }

    /**
     * 打开页面
     *
     * @param string                          $url
     * @param array                           $config
     * @param string                          $type
     * @param string|AbstractHtmlElement|null $element
     *
     * @return $this
     */
    public function openPage(string $url, array $config = [], #[ExpectedValues(['dialog', 'tab'])] string $type = 'dialog', string|AbstractHtmlElement $element = null): static
    {
        $this->show = [
            'type'   => 'openPage',
            'config' => [
                'url'     => $url,
                'config'  => $config,
                'type'    => $type,
                'element' => $element,
            ]
        ];

        return $this;
    }

    public function getEmptyShowTemplate(): \Stringable|string
    {
        return $this->emptyShowTemplate;
    }

    public function addTip( string|\Stringable $tip, string|\Stringable $icon = 'WarningFilled', array $attrs = []): static
    {
        $this->tip = [
            'icon'  => $icon,
            'tip'   => $tip,
            'attrs' => $attrs
        ];

        return $this;
    }

    public function getTip(): array
    {
        return $this->tip;
    }

    public function getImportExcel(): array
    {
        return $this->importExcel;
    }
}