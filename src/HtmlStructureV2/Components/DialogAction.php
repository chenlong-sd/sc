<?php

namespace Sc\Util\HtmlStructureV2\Components;

use Sc\Util\HtmlStructureV2\Enums\ActionIntent;

final class DialogAction extends Action
{
    private ?Dialog $dialog = null;

    public function __construct(string $label, ActionIntent $intent)
    {
        parent::__construct($label, $intent);
    }

    /**
     * 直接绑定一个 Dialog 对象，页面构建时会自动收集。
     *
     * @param Dialog $dialog 要绑定的 Dialog 对象。
     * @return static 当前动作实例。
     *
     * 示例：
     * `Action::create()->bindDialog(Dialog::make('qa-info-dialog'))`
     */
    public function bindDialog(Dialog $dialog): static
    {
        $this->dialog = $dialog;
        parent::target($dialog->key());

        return $this;
    }

    /**
     * dialog() 的 DialogAction 版本支持直接传 Dialog 对象。
     * 传字符串时与基类行为一致；传 Dialog 时会自动完成对象绑定和页面收集。
     *
     * @param string|Dialog $dialog Dialog key 或 Dialog 对象。
     * @return static 当前动作实例。
     *
     * 示例：
     * `Action::edit()->dialog('qa-info-dialog')`
     */
    public function dialog(string|Dialog $dialog): static
    {
        if ($dialog instanceof Dialog) {
            return $this->bindDialog($dialog);
        }

        return $this->target($dialog);
    }

    /**
     * 指定 dialog key；如果与已绑定 Dialog 不一致，会解除对象绑定。
     *
     * @param string|null $target 目标 dialog key；传 null 表示清空目标。
     * @return static 当前动作实例。
     *
     * 示例：
     * `Action::create()->target('qa-info-dialog')`
     */
    public function target(?string $target): static
    {
        parent::target($target);

        if ($this->dialog !== null && $this->dialog->key() !== $target) {
            $this->dialog = null;
        }

        return $this;
    }

    public function getDialog(): ?Dialog
    {
        return $this->dialog;
    }
}
