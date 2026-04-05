<?php

namespace Sc\Util\HtmlStructureV2\Components;

use Sc\Util\HtmlStructureV2\Components\Concerns\HasEvents;
use Sc\Util\HtmlStructureV2\Contracts\Renderable;
use Sc\Util\HtmlStructureV2\Enums\ActionIntent;
use Sc\Util\HtmlStructureV2\Support\JsExpression;
use Sc\Util\HtmlStructureV2\Support\RendersWithTheme;

class Action implements Renderable
{
    use HasEvents;
    use RendersWithTheme;

    private string $type = 'default';
    private ?string $icon = null;
    private ?string $key = null;
    private ?string $target = null;
    private ?string $tableTarget = null;
    private ?string $listTarget = null;
    private string|JsExpression|null $handler = null;
    private ?string $confirmText = null;
    private array $props = [];

    public function __construct(
        private readonly string $label,
        private ActionIntent $intent = ActionIntent::CUSTOM
    ) {
    }

    public static function make(string $label): static
    {
        return new static($label);
    }

    public static function create(string|Dialog $labelOrDialog = '新建', string|Dialog|null $dialog = null): DialogAction
    {
        [$label, $target, $dialogDefinition] = self::resolveDialogActionArguments(
            $labelOrDialog,
            $dialog,
            ActionIntent::CREATE
        );

        $action = (new DialogAction($label, ActionIntent::CREATE))
            ->type('primary')
            ->icon('Plus');

        if ($dialogDefinition !== null) {
            return $action->bindDialog($dialogDefinition);
        }

        return $action->target($target);
    }

    public static function edit(string|Dialog $labelOrDialog = '编辑', string|Dialog|null $dialog = null): DialogAction
    {
        [$label, $target, $dialogDefinition] = self::resolveDialogActionArguments(
            $labelOrDialog,
            $dialog,
            ActionIntent::EDIT
        );

        $action = (new DialogAction($label, ActionIntent::EDIT))
            ->type('primary')
            ->icon('Edit');

        if ($dialogDefinition !== null) {
            return $action->bindDialog($dialogDefinition);
        }

        return $action->target($target);
    }

    public static function delete(string $label = '删除'): self
    {
        return (new self($label, ActionIntent::DELETE))
            ->type('danger')
            ->icon('Delete')
            ->confirm('确认删除当前记录？');
    }

    public static function refresh(string $label = '刷新'): self
    {
        return (new self($label, ActionIntent::REFRESH))
            ->icon('Refresh');
    }

    public static function submit(string $label = '保存', string $dialog = 'editor'): self
    {
        return (new self($label, ActionIntent::SUBMIT))
            ->target($dialog)
            ->type('primary');
    }

    public static function close(string $label = '取消', string $dialog = 'editor'): self
    {
        return (new self($label, ActionIntent::CLOSE))
            ->target($dialog);
    }

    public static function custom(string $label, string|JsExpression $handler): self
    {
        return (new self($label, ActionIntent::CUSTOM))->onClick($handler);
    }

    public function type(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function icon(string $icon): static
    {
        $this->icon = $icon;

        return $this;
    }

    public function key(string $key): static
    {
        $this->key = $key;

        return $this;
    }

    public function target(?string $target): static
    {
        $this->target = $target;

        return $this;
    }

    public function dialog(string $dialog): static
    {
        return $this->target($dialog);
    }

    public function forTable(string|Table $table): static
    {
        $this->tableTarget = $table instanceof Table ? $table->key() : $table;
        $this->listTarget = null;

        return $this;
    }

    public function forList(string|ListWidget $list): static
    {
        $this->listTarget = $list instanceof ListWidget ? $list->key() : $list;
        $this->tableTarget = null;

        return $this;
    }

    public function onClick(string|JsExpression $handler): static
    {
        $this->handler = $handler;

        return $this;
    }

    public function confirm(?string $text): static
    {
        $this->confirmText = $text;

        return $this;
    }

    public function props(array $props): static
    {
        $this->props = array_merge($this->props, $props);

        return $this;
    }

    public function label(): string
    {
        return $this->label;
    }

    public function intent(): ActionIntent
    {
        return $this->intent;
    }

    public function buttonType(): string
    {
        return $this->type;
    }

    public function iconName(): ?string
    {
        return $this->icon;
    }

    public function getKey(): ?string
    {
        return $this->key;
    }

    public function targetName(): ?string
    {
        return $this->target;
    }

    public function handler(): string|JsExpression|null
    {
        return $this->handler;
    }

    public function tableTarget(): ?string
    {
        return $this->tableTarget;
    }

    public function listTarget(): ?string
    {
        return $this->listTarget;
    }

    public function confirmText(): ?string
    {
        return $this->confirmText;
    }

    public function attrs(): array
    {
        return $this->props;
    }

    private static function resolveDialogActionArguments(
        string|Dialog $labelOrDialog,
        string|Dialog|null $dialogOrLabel,
        ActionIntent $intent
    ): array {
        $defaultLabel = $intent === ActionIntent::CREATE ? '新建' : '编辑';
        $label = $defaultLabel;
        $target = 'editor';
        $dialog = null;

        if ($labelOrDialog instanceof Dialog) {
            $dialog = $labelOrDialog;
            $target = $dialog->key();
            if (is_string($dialogOrLabel) && $dialogOrLabel !== '') {
                $label = $dialogOrLabel;
            } elseif ($intent === ActionIntent::CREATE) {
                $label = $dialog->title();
            }

            return [$label, $target, $dialog];
        }

        if ($labelOrDialog !== '') {
            $label = $labelOrDialog;
        }

        if ($dialogOrLabel instanceof Dialog) {
            $dialog = $dialogOrLabel;
            $target = $dialog->key();

            return [$label, $target, $dialog];
        }

        if (is_string($dialogOrLabel) && $dialogOrLabel !== '') {
            $target = $dialogOrLabel;
        }

        return [$label, $target, $dialog];
    }
}
