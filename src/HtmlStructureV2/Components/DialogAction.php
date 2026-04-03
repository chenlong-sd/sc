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

    public function bindDialog(Dialog $dialog): static
    {
        $this->dialog = $dialog;
        parent::target($dialog->key());

        return $this;
    }

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
