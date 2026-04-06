<?php

namespace Sc\Util\HtmlStructureV2\Components;

use JetBrains\PhpStorm\ExpectedValues;
use Sc\Util\HtmlStructureV2\Components\Concerns\HasEvents;
use Sc\Util\HtmlStructureV2\Contracts\EventAware;
use Sc\Util\HtmlStructureV2\Contracts\Renderable;
use Sc\Util\HtmlStructureV2\Contracts\FormNode;
use Sc\Util\HtmlStructureV2\Contracts\StructuredEventInterface;
use Sc\Util\HtmlStructureV2\Support\FormSchema;
use Sc\Util\HtmlStructureV2\Support\FormSchemaWalker;
use Sc\Util\HtmlStructureV2\Support\JsExpression;
use Sc\Util\HtmlStructureV2\Support\RendersWithTheme;

final class Form implements Renderable, EventAware
{
    use HasEvents {
        on as private bindFormEventHandler;
    }
    use RendersWithTheme;

    private const SUPPORTED_ON_EVENTS = [
        'validateSuccess',
        'validateFail',
        'arrayRowAdd',
        'arrayRowRemove',
        'arrayRowMove',
        'optionsLoaded',
        'optionsLoadFail',
        'uploadSuccess',
        'uploadFail',
    ];

    /** @var FormNode[] */
    private array $children = [];
    private bool $inline = false;
    private string $labelWidth = '100px';
    private string $submitLabel = '查询';
    private string $resetLabel = '重置';

    public function __construct(
        private readonly string $key
    ) {
    }

    /**
     * 直接创建一个表单组件实例。
     */
    public static function make(string $key): self
    {
        return new self($key);
    }

    /**
     * 追加字段叶子节点，适合简单表单直接堆字段。
     */
    public function addFields(Field ...$fields): self
    {
        return $this->addNodes(...$fields);
    }

    /**
     * 追加任意表单节点，支持字段、结构节点、作用域节点和数组节点混排。
     */
    public function addNodes(FormNode ...$nodes): self
    {
        $this->children = array_merge($this->children, $nodes);

        return $this;
    }

    /**
     * 切换整个表单为行内模式，常用于筛选表单。
     */
    public function inline(bool $inline = true): self
    {
        $this->inline = $inline;

        return $this;
    }

    /**
     * 设置表单标签宽度，例如 96px / 120px。
     */
    public function labelWidth(string $labelWidth): self
    {
        $this->labelWidth = $labelWidth;

        return $this;
    }

    /**
     * 设置筛选模式下提交按钮文案。
     */
    public function submitLabel(string $submitLabel): self
    {
        $this->submitLabel = $submitLabel;

        return $this;
    }

    /**
     * 设置筛选模式下重置按钮文案。
     */
    public function resetLabel(string $resetLabel): self
    {
        $this->resetLabel = $resetLabel;

        return $this;
    }

    /**
     * 绑定表单运行时事件。
     * 可用事件：validateSuccess / validateFail / arrayRowAdd / arrayRowRemove / arrayRowMove / optionsLoaded / optionsLoadFail / uploadSuccess / uploadFail。
     *
     * handler 签名：`(context) => mixed`
     * 推荐写法：`({ scope, model, form, formConfig, error, fieldName, payload, vm }) => {}`
     * 不按位置参数传值。
     *
     * 公共上下文：
     * - scope: 当前表单作用域 key
     * - model / form: 当前表单数据模型
     * - formConfig: 当前表单运行时配置
     * - vm: 当前 Vue 实例
     *
     * 事件额外字段：
     * - validateFail: error
     * - arrayRowAdd: arrayPath / groupConfig / row / rowIndex / rows
     * - arrayRowRemove: arrayPath / groupConfig / row / rowIndex / rows
     * - arrayRowMove: arrayPath / groupConfig / row / fromIndex / toIndex / direction / rows
     * - optionsLoaded: fieldName / fieldConfig / response / payload / options
     * - optionsLoadFail: fieldName / fieldConfig / error
     * - uploadSuccess: fieldName / fieldConfig / response / payload / uploadFile / uploadFiles
     * - uploadFail: fieldName / fieldConfig / error / response / uploadFile / uploadFiles
     */
    public function on(
        #[ExpectedValues(self::SUPPORTED_ON_EVENTS)]
        string $event,
        string|JsExpression|StructuredEventInterface $handler
    ): static {
        return $this->bindFormEventHandler($event, $handler);
    }

    public function key(): string
    {
        return $this->key;
    }

    public function schema(): FormSchema
    {
        return (new FormSchemaWalker())->build($this->children);
    }

    public function fields(): array
    {
        return array_map(
            static fn($fieldSchema) => $fieldSchema->field(),
            $this->schema()->fields()
        );
    }

    /**
     * @return FormNode[]
     */
    public function children(): array
    {
        return $this->children;
    }

    public function isInline(): bool
    {
        return $this->inline;
    }

    public function getLabelWidth(): string
    {
        return $this->labelWidth;
    }

    public function getSubmitLabel(): string
    {
        return $this->submitLabel;
    }

    public function getResetLabel(): string
    {
        return $this->resetLabel;
    }

    public function defaults(): array
    {
        return $this->schema()->defaults();
    }

    public function rules(): array
    {
        return $this->schema()->rules();
    }

    public function remoteOptions(): array
    {
        return $this->schema()->remoteOptions();
    }

    public function uploads(): array
    {
        return $this->schema()->uploads();
    }

    public function selectOptions(): array
    {
        return $this->schema()->selectOptions();
    }

    public function linkages(): array
    {
        return $this->schema()->linkages();
    }

    /**
     * @return array<string, string>
     */
    protected function defineSupportedEvents(): array
    {
        return [
            'validateSuccess' => '表单校验通过后触发。',
            'validateFail' => '表单校验失败后触发，可读取 error。',
            'arrayRowAdd' => '表单数组新增一行后触发，可读取 arrayPath / row / rowIndex / rows。',
            'arrayRowRemove' => '表单数组删除一行后触发，可读取 arrayPath / row / rowIndex / rows。',
            'arrayRowMove' => '表单数组调整顺序后触发，可读取 arrayPath / row / fromIndex / toIndex / direction。',
            'optionsLoaded' => '远程选项加载成功后触发，可读取 fieldName / options / payload。',
            'optionsLoadFail' => '远程选项加载失败后触发，可读取 fieldName / error。',
            'uploadSuccess' => '上传成功后触发，可读取 fieldName / uploadFile / uploadFiles / payload。',
            'uploadFail' => '上传失败后触发，可读取 fieldName / uploadFile / uploadFiles / error。',
        ];
    }
}
