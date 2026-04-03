<?php

namespace Sc\Util\HtmlStructureV2\Page;

use Sc\Util\HtmlStructureV2\Components\Dialog;

final class CrudPage extends ListPage
{
    private ?string $editorDialogKey = null;

    public function editor(Dialog $editorDialog): self
    {
        $this->dialogs($editorDialog);
        $this->editorDialogKey = $editorDialog->key();

        return $this;
    }

    public function getEditorDialog(): ?Dialog
    {
        if ($this->editorDialogKey === null) {
            return null;
        }

        return $this->getDialog($this->editorDialogKey);
    }
}
