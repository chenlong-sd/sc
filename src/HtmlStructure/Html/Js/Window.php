<?php

namespace Sc\Util\HtmlStructure\Html\Js;

use Sc\Util\HtmlElement\El;
use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlStructure\Html\Html;
use Sc\Util\HtmlStructure\Html\Js\VueComponents\VueComponentInterface;
use Sc\Util\HtmlStructure\Html\JsTheme\Interfaces\WindowThemeInterface;
use Sc\Util\HtmlStructure\Html\JsTheme\JsTheme;

/**
 * Class Window
 */
class Window
{
    /**
     * @var array|mixed
     */
    private array $config = [];
    private null|string|AbstractHtmlElement $content = null;
    private ?string $url = null;
    private array $query;
    private ?VueComponentInterface $component = null;
    protected array $rowData = [];
    protected ?JsCode $beforeOpen = null;
    private string $id = '';
    private ?Window $mountIframeInfo = null;

    public function __construct(private readonly string $title)
    {
    }

    public static function open(string $title): Window
    {
        return new self($title);
    }

    /**
     * 设置窗口配置
     *
     * @param array|string $option
     * @param              $value
     *
     * @return $this
     */
    public function setConfig(array|string $option, $value = null): static
    {
        if (is_array($option)) {
            $this->config = array_merge($this->config, $option);
        }else{
            $this->config[$option] = $value;
        }

        return $this;
    }

    /**
     * 窗口内容
     *
     * @param string|AbstractHtmlElement $content
     *
     * @return Window
     */
    public function setContent(string|AbstractHtmlElement $content): static
    {
        $this->content = $content;

        return $this;
    }

    /**
     * 传vue组件
     *
     * @param VueComponentInterface $component
     *
     * @return Window
     */
    public function setComponent(VueComponentInterface $component): static
    {
        $this->component = $component;

        return $this;
    }

    /**
     * 窗口新页面地址
     *
     * @param string $url
     * @param array  $query ['id' = 1, 'rid' => '@id'] => id=1&rid=row.id, row.id为行数据
     *
     * @return Window
     */
    public function setUrl(string $url, array $query = []): static
    {
        $this->url = $url;
        $this->query = $query;

        return $this;
    }

    /**
     * 设置rowData
     *
     * @param array $data
     *
     * @return $this
     */
    public function setRowData(array $data): static
    {
        $this->rowData = [...$this->rowData, ...$data];

        return $this;
    }


    /**
     * 挂载在iframe的信息， iframe只支持layui
     * 关闭挂载窗口 layer.closeAll()
     * @param Window $window
     *
     * @return Window
     */
    public function mountIframe(Window $window): static
    {
        if (empty($window->getConfig()['width'])) {
            $window->setConfig(['width' => '1000px']);
        }
        $this->mountIframeInfo = $window;

        return $this;
    }

    public function toCode(string $theme = null)
    {
        if ($theme === null && $this->url) {
            $theme = 'Layui';
        }

        $theme = JsTheme::getTheme(WindowThemeInterface::class, $theme);
        $theme = new $theme();

        return $this->mountIframeHandle($theme->render($this));
    }

    private function mountIframeHandle(string $code): string
    {
        if (empty($this->mountIframeInfo)) {
            return $code;
        }

        /**
         * 重新设置一下elementUI的层级，避免层级不够展示在下面了
         */
        Html::css()->addCss('.el-message, .is-message-box {
          z-index: 999999991 !important; /* 设置你想要的z-index值 */
        }');
        if (!Html::html()->find('el-config-provider')) {
            Html::html()->prepend(El::double('el-config-provider')->setAttr(':z-index', '99999999'));
        }

        $this->mountIframeInfo
            ->setConfig('btn', [$this->mountIframeInfo->getTitle()])
            ->setConfig('btnAlign', 'c')
            ->setConfig('yes', JsFunc::arrow(['index'])->code($code));

        return $this->mountIframeInfo->toCode();
    }


    public function render(string $theme = null)
    {
        return $this->toCode($theme);
    }

    public function __toString(): string
    {
        return $this->toCode();
    }

    /**
     * @return array
     */
    public function getConfig(): array
    {
        return $this->config;
    }

    /**
     * @return string|null
     */
    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function getQuery(): array
    {
        return $this->query;
    }

    /**
     * @return AbstractHtmlElement|string|null
     */
    public function getContent(): AbstractHtmlElement|string|null
    {
        return $this->content;
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @return VueComponentInterface|null
     */
    public function getComponent(): ?VueComponentInterface
    {
        return $this->component;
    }

    public function getRowData(): array
    {
        return $this->rowData;
    }

    public function beforeOpen(?JsCode $jsCode): Window
    {
        $this->beforeOpen = $jsCode;
        return $this;
    }

    public function getBeforeOpen(): ?JsCode
    {
        return $this->beforeOpen;
    }

    public function setId(string $id): Window
    {
        $this->id = $id;
        return $this;
    }

    public function getId(): string
    {
        return $this->id;
    }
}