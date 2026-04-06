<?php

namespace Sc\Util\HtmlStructureV2\Components\Fields;

use Sc\Util\HtmlStructureV2\Components\Concerns\HasValidation;
use Sc\Util\HtmlStructureV2\Components\Action;
use Sc\Util\HtmlStructureV2\Components\Dialog;
use Sc\Util\HtmlStructureV2\Components\Field;
use Sc\Util\HtmlStructureV2\Contracts\Fields\ValidatableFieldInterface;
use Sc\Util\HtmlStructureV2\Enums\FieldType;
use Sc\Util\HtmlStructureV2\Support\JsExpression;

final class PickerField extends Field implements ValidatableFieldInterface
{
    use HasValidation;

    private ?Dialog $dialog = null;
    private string $selectionPath = '__scV2Selection';
    private string $valueField = 'id';
    private string $labelField = 'name';
    private string $displayTemplate = '@label';
    private string $buttonLabel = '选择';
    private string $confirmLabel = '确定';
    private string $emptyText = '暂无已选项';
    private bool $multiple = true;
    private bool $clearable = true;
    private array $defaultItems = [];
    private bool $dialogPrepared = false;

    public function __construct(string $name, string $label)
    {
        parent::__construct($name, $label, FieldType::PICKER);
    }

    /**
     * 绑定选择器弹窗。
     * 当前 picker 默认面向 iframe 选择页；若弹窗未自定义 footer，会自动补“取消 / 确定”按钮，
     * “确定”会调用内置回填逻辑，把弹窗里的选择结果写回当前字段。
     */
    public function dialog(Dialog $dialog): static
    {
        $this->dialog = $dialog;
        $this->dialogPrepared = false;

        return $this;
    }

    /**
     * 设置在 picker 弹窗 iframe 页面里读取选择结果的路径。
     * 路径从 `dialogIframeRef.contentWindow` 开始解析。
     * V2 列表页在开启 `Table::selection()` 后会自动暴露 `"__scV2Selection"`，
     * 多表页可改用 `"__scV2Selections.tableKey"`；旧页面仍可显式传 `"selection"` 或 `"VueApp.xxxSelection"`。
     */
    public function selectionPath(string $selectionPath): static
    {
        $selectionPath = trim($selectionPath);
        if ($selectionPath !== '') {
            $this->selectionPath = $selectionPath;
        }

        return $this;
    }

    /**
     * 设置写回主字段时使用的值字段名，默认是 id。
     * 例如选择结果行为 `{ id, name }` 时，最终提交值会取 `item.id`。
     */
    public function valueField(string $valueField): static
    {
        $valueField = trim($valueField);
        if ($valueField !== '') {
            $this->valueField = $valueField;
        }

        return $this;
    }

    /**
     * 设置选择结果的主文案字段名，默认是 name。
     * 当 displayTemplate() 仍使用默认 "@label" 时，会直接展示这个字段。
     */
    public function labelField(string $labelField): static
    {
        $labelField = trim($labelField);
        if ($labelField !== '') {
            $this->labelField = $labelField;
        }

        return $this;
    }

    /**
     * 设置已选项展示模板。
     * 可用 token：
     * - "@label": 当前项按 labelField() 取到的主文案
     * - "@value": 当前项按 valueField() 取到的值
     * - "@item.xxx": 当前选择结果对象上的任意字段
     * 例如 "@item.name（@item.dev_number）"。
     */
    public function displayTemplate(string $displayTemplate): static
    {
        $this->displayTemplate = trim($displayTemplate) !== ''
            ? $displayTemplate
            : '@label';

        return $this;
    }

    /**
     * 设置打开选择弹窗的按钮文案。
     */
    public function buttonLabel(string $buttonLabel): static
    {
        $buttonLabel = trim($buttonLabel);
        if ($buttonLabel !== '') {
            $this->buttonLabel = $buttonLabel;
        }

        return $this;
    }

    /**
     * 设置 picker 弹窗默认“确定”按钮文案。
     * 仅在 dialog() 绑定的弹窗未自定义 footer 时生效。
     */
    public function confirmLabel(string $confirmLabel): static
    {
        $confirmLabel = trim($confirmLabel);
        if ($confirmLabel !== '') {
            $this->confirmLabel = $confirmLabel;
            $this->dialogPrepared = false;
        }

        return $this;
    }

    /**
     * 设置无已选项时的提示文案。
     */
    public function emptyText(string $emptyText): static
    {
        $emptyText = trim($emptyText);
        if ($emptyText !== '') {
            $this->emptyText = $emptyText;
        }

        return $this;
    }

    /**
     * 控制是否多选，默认开启。
     * 关闭后主字段最终只会写入单个值，已选展示也只保留第一项。
     */
    public function multiple(bool $multiple = true): static
    {
        $this->multiple = $multiple;

        return $this;
    }

    /**
     * 控制是否展示“清空”按钮，默认开启。
     */
    public function clearable(bool $clearable = true): static
    {
        $this->clearable = $clearable;

        return $this;
    }

    /**
     * 设置选择器展示态的默认项。
     * 适合创建时预填已选数据；若主字段尚未显式 default()，这里只会维护展示态，不会自动改提交值。
     */
    public function defaultItems(array $items): static
    {
        $this->defaultItems = array_values($items);

        return $this;
    }

    public function getDialog(): ?Dialog
    {
        $this->ensureDialogPrepared();

        return $this->dialog;
    }

    public function dialogKey(): ?string
    {
        return $this->getDialog()?->key();
    }

    public function getSelectionPath(): string
    {
        return $this->selectionPath;
    }

    public function getValueField(): string
    {
        return $this->valueField;
    }

    public function getLabelField(): string
    {
        return $this->labelField;
    }

    public function getDisplayTemplate(): string
    {
        return $this->displayTemplate;
    }

    public function getButtonLabel(): string
    {
        return $this->buttonLabel;
    }

    public function getConfirmLabel(): string
    {
        return $this->confirmLabel;
    }

    public function getEmptyText(): string
    {
        return $this->emptyText;
    }

    public function isMultiple(): bool
    {
        return $this->multiple;
    }

    public function isClearable(): bool
    {
        return $this->clearable;
    }

    public function getPickerItemsDefault(): array
    {
        return $this->defaultItems;
    }

    public function getPickerConfig(): array
    {
        $dialogKey = $this->dialogKey();
        if ($dialogKey === null || $dialogKey === '') {
            return [];
        }

        return [
            'dialogKey' => $dialogKey,
            'selectionPath' => $this->selectionPath,
            'valueField' => $this->valueField,
            'labelField' => $this->labelField,
            'displayTemplate' => $this->displayTemplate,
            'initialItems' => $this->defaultItems,
            'multiple' => $this->multiple,
        ];
    }

    public function getDefault(): mixed
    {
        if ($this->default !== null) {
            return $this->default;
        }

        return $this->multiple ? [] : null;
    }

    private function ensureDialogPrepared(): void
    {
        if ($this->dialogPrepared || $this->dialog === null) {
            return;
        }

        $this->dialogPrepared = true;
        if ($this->dialog->getFooterActions() !== []) {
            return;
        }

        $dialogKey = $this->dialog->key();
        $this->dialog->footer(
            Action::close('取消', $dialogKey),
            Action::make($this->confirmLabel)
                ->type('primary')
                ->icon('Check')
                ->dialog($dialogKey)
                ->on('click', JsExpression::make('({ dialogKey, vm }) => vm.applyPickerDialogSelection(dialogKey)'))
        );
    }
}
