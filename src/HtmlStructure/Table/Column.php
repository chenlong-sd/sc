<?php

namespace Sc\Util\HtmlStructure\Table;

use JetBrains\PhpStorm\ExpectedValues;
use JetBrains\PhpStorm\Language;
use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlStructure\Form\FormItem;
use Sc\Util\HtmlStructure\Form\FormItemInterface;
use Sc\Util\HtmlStructure\Form\FormItemSelect;
use Sc\Util\HtmlStructure\Theme\Interfaces\TableColumnThemeInterface;
use Sc\Util\HtmlStructure\Theme\Theme;

/**
 * Class Column
 * @method static Column selection() 选择列
 * @method static Column index()     索引列
 * @method static Column expand(string $title)    可展开列，仅ElementUI
 * @method static Column normal(string $title, string $prop = '') 常规列
 * @method static Column event(string $title = '') 事件列
 *
 */
class Column
{
    private string|\Stringable $format = '';
    private array $show = [];
    private array $search = [];
    protected ?string $fixedPosition = null;

    public function __construct(private array $attrs = []){}

    /**
     * 设置属性
     *
     * @param string|array $attr
     * @param mixed        $value
     *
     * @return $this
     */
    public function setAttr(string|array $attr, mixed $value): static
    {
        $attrs = is_string($attr) ? [$attr => $value] : $attr;

        $this->attrs = [...$this->attrs, ...$attrs];

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
        if (!$formItem) {
            if($this->show){
                $formItem = FormItem::select($this->attrs['prop'])->options(
                    array_map(function ($options){
                        if ($options instanceof AbstractHtmlElement){
                            $options = trim($options->getContent());
                        }
                        return $options;
                    }, $this->show['config']['options'])
                );
            }else if (str_contains($this->attrs['prop'], 'time')) {
                $formItem = FormItem::datetime($this->attrs['prop'])->setTimeType('datetimerange')->valueFormat();
            }else if (str_contains($this->attrs['prop'], 'date')) {
                $formItem = FormItem::datetime($this->attrs['prop'])->setTimeType('daterange')->valueFormat('YYYY-MM-DD');
            }else if ($type === 'in') {
                $formItem = FormItem::select($this->attrs['prop'])->setVAttrs('allow-create');
            }else{
                $formItem = FormItem::text($this->attrs['prop']);
            }
            $formItem->placeholder($this->attrs['label']);

            if ($type === 'in' && $formItem instanceof FormItemSelect) {
                $formItem->setVAttrs('multiple');
            }
        }

        if (is_string($formItem)) {
            $formItem = FormItem::text($formItem)->placeholder($this->attrs['label']);
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
     * @param string|\Stringable $format 参数规则依照vue语法
     *                                   行参数： 直接使用，例：id => {{ id }}  , <span :name="id"></span>
     *                                   其他参数：前面加@，例：location => {{ @location }}  , <span :name="@location"></span>
     *
     * @return $this
     */
    public function setFormat(#[Language('Vue')]string|\Stringable $format): static
    {
        $this->format     = $format;

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
     * @return $this
     */
    public function notShow(bool $confirm = true): static
    {
        if ($confirm) {
            $this->show = [
                'type' => null
            ];
        }

        return $this;
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
        return Theme::getRender(TableColumnThemeInterface::class, $theme)->render($this);
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
            empty($arguments[0]) || $initAttr['prop'] = $arguments[1];
        } else if ($name === 'event') {
            $initAttr['label']      = $arguments[0] ?? '操作';
            $initAttr['mark-event'] = true;
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
}