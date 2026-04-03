<?php

namespace Sc\Util\HtmlStructureV2\Components;

use Sc\Util\HtmlStructureV2\Contracts\Renderable;
use Sc\Util\HtmlStructureV2\Enums\ActionIntent;
use Sc\Util\HtmlStructureV2\Support\JsExpression;
use Sc\Util\HtmlStructureV2\Support\RendersWithTheme;

final class Action implements Renderable
{
    use RendersWithTheme;

    private string $type = 'default';
    private ?string $icon = null;
    private ?string $target = null;
    private string|JsExpression|null $handler = null;
    private ?string $confirmText = null;
    private array $props = [];

    public function __construct(
        private readonly string $label,
        private ActionIntent $intent = ActionIntent::CUSTOM
    ) {
    }

    public static function make(string $label): self
    {
        return new self($label);
    }

    public static function create(string $label = '新建', string $dialog = 'editor'): self
    {
        return (new self($label, ActionIntent::CREATE))
            ->target($dialog)
            ->type('primary')
            ->icon('Plus');
    }

    public static function edit(string $label = '编辑', string $dialog = 'editor'): self
    {
        return (new self($label, ActionIntent::EDIT))
            ->target($dialog)
            ->type('primary')
            ->icon('Edit');
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

    public function type(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function icon(string $icon): self
    {
        $this->icon = $icon;

        return $this;
    }

    public function target(?string $target): self
    {
        $this->target = $target;

        return $this;
    }

    public function onClick(string|JsExpression $handler): self
    {
        $this->handler = $handler;

        return $this;
    }

    public function confirm(?string $text): self
    {
        $this->confirmText = $text;

        return $this;
    }

    public function props(array $props): self
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

    public function targetName(): ?string
    {
        return $this->target;
    }

    public function handler(): string|JsExpression|null
    {
        return $this->handler;
    }

    public function confirmText(): ?string
    {
        return $this->confirmText;
    }

    public function attrs(): array
    {
        return $this->props;
    }
}
