<?php

namespace Sc\Util\HtmlStructureV2\Components;

final class DialogAction extends Action
{
    protected function hasForbiddenUrl(): bool
    {
        return $this->hasActionForbiddenUrl()
            || (($dialog = $this->getDialog()) !== null && $this->hasDialogForbiddenUrl($dialog));
    }
}
