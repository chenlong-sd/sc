<?php
/**
 * datetime: 2023/5/25 23:52
 **/

namespace Sc\Util\HtmlStructure;

use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlStructure\Form\AbstractFormItem;
use Sc\Util\HtmlStructure\Form\FormItemAttrGetter;
use Sc\Util\HtmlStructure\Form\FormItemCustomize;
use Sc\Util\HtmlStructure\Form\FormItemEditor;
use Sc\Util\HtmlStructure\Form\FormItemInterface;
use Sc\Util\HtmlStructure\Form\FormItemSubmit;
use Sc\Util\HtmlStructure\Form\FormItemText;
use Sc\Util\HtmlStructure\Form\FormItemTextarea;
use Sc\Util\HtmlStructure\Html\Common;
use Sc\Util\HtmlStructure\Html\Js\Grammar;
use Sc\Util\HtmlStructure\Theme\Interfaces\FormThemeInterface;
use Sc\Util\HtmlStructure\Theme\Theme;

class Form
{
    /**
     * @var array|mixed
     */
    private array $config = [];
    /**
     * @var array|FormItemInterface[]|AbstractFormItem[]|FormItemAttrGetter[]
     */
    private array $formItems = [];
    private array $submitHandle = [];
    private array $extraData = [];

    private bool $readonly = false;

    public function __construct(private readonly string $id) { }

    /**
     * @param string $id
     *
     * @return Form
     * @date 2023/6/3
     */
    public static function create(string $id): Form
    {
        return new self($id);
    }

    /**
     * @param FormItemInterface ...$formItems
     *
     * @date 2023/6/3
     */
    public function addFormItems(...$formItems): static
    {
        $this->formItems = [...$this->formItems, ...$formItems];

        return $this;
    }


    /**
     * @param string|array $option
     * @param null         $value
     *
     * @return Form
     * @date 2023/6/3
     */
    public function config(string|array $option, $value = null): static
    {
        is_array($option)
            ? $this->config = [...$this->config, ...$option]
            : $this->config[$option] = $value;

        return $this;
    }

    public function setData(array|string $data): static
    {
        if (is_string($data)) {
            $this->config['dataUrl'] = $data;
        }else{
            $this->config['data'] = $data;
        }

        return $this;
    }

    /**
     * 默认清空form只会接收有表单项的数据，如需额外的数据，则用此函数
     *
     * @param array $data
     * @param bool  $isReplace
     *
     * @return void
     */
    public function setExtraData(array $data, bool $isReplace = false): void
    {
        if ($isReplace) {
            $this->extraData = $data;
            return;
        }
        $this->extraData = array_merge($this->extraData, $data);
    }

    /**
     * @return string
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return array
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * 获取表单默认值
     *
     * @return array
     * @date 2023/6/7
     */
    public function getDefaults(): array
    {
        $data = array_merge(...array_map(function ($v) {
            if ($v->getName()) {
                $value = $v->getDefault();
                if ($v instanceof FormItemEditor || $v instanceof FormItemText || $v instanceof FormItemTextarea || is_string($value)) {
                    if ($value && str_contains($value, '`')) {
                        $defJsFnBase64Decode = Common::defJsFnBase64Decode();
                        $value = base64_encode($value);
                        $value = Grammar::mark("$defJsFnBase64Decode(`$value`)");
                    }else{
                        $value = Grammar::mark($value !== null ? $value : '', 'line');
                    }
                }
                return [$v->getName() => $value];
            }

            return $v->getDefault();
        }, array_filter($this->getFormItems(), fn($v) => !$v instanceof FormItemSubmit && !$v instanceof FormItemCustomize)));

        return array_merge($this->extraData, $data);
    }

    /**
     * @return array
     */
    public function getFormItems(): array
    {
        return array_filter($this->formItems, fn($formItems) => !$formItems->getHide());
    }

    /**
     * @param string|null $theme
     *
     * @return AbstractHtmlElement
     * @date 2023/6/3
     */
    public function render(string $theme = null): AbstractHtmlElement
    {
        array_map(function (FormItemInterface|FormItemAttrGetter $formItem) {
            // 默认值重设
            if (method_exists($formItem, 'default') && !empty($this->config['data'])) {
                $defaultData = $formItem->getName()
                    ? $this->config['data'][$formItem->getName()] ?? null
                    : $this->config['data'];

                $formItem->default($defaultData);
            }
            if ($this->readonly) {
                if ($formItem instanceof FormItemSubmit) {
                    $formItem->setHide(true);
                }
                $formItem->readonly();
            }

            return $formItem->setForm($this);
        }, $this->getFormItems());

        return Theme::getRenderer(FormThemeInterface::class, $theme)->render($this);
    }

    /**
     * @return string
     */
    public function getSubmitHandle(): string
    {
        return implode(";\n", $this->submitHandle);
    }

    /**
     * @param string $submitHandle
     *
     * @return Form
     */
    public function setSubmitHandle(string $submitHandle): Form
    {
        $this->submitHandle[] = $submitHandle;

        return $this;
    }

    public function readonly(bool $readonly = true): Form
    {
        $this->readonly = $readonly;
        return $this;
    }
}