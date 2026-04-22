<?php

namespace Sc\Util\HtmlStructureV2\Components;

use Sc\Util\HtmlStructureV2\Enums\ActionIntent;

final class DialogAction extends Action
{
    protected function hasForbiddenUrl(): bool
    {
        if (parent::hasForbiddenUrl()) {
            return true;
        }

        $dialog = $this->getDialog();
        if ($dialog === null) {
            return false;
        }

        $save = $dialog->getSaveUrl();
        $create = $dialog->getCreateUrl();
        $update = $dialog->getUpdateUrl();
        $iframe = $dialog->getIframeUrl();

        return match ($this->intent()) {
            ActionIntent::CREATE => $this->dialogUrlForbidden($create, $save, $iframe),
            ActionIntent::EDIT => $this->dialogUrlForbidden($update, $save, $iframe),
            ActionIntent::SUBMIT => $this->dialogUrlForbidden($save, $create, $update, $iframe),
            default => $this->isForbiddenUrl($iframe),
        };
    }

    private function dialogUrlForbidden(?string ...$urls): bool
    {
        foreach ($urls as $url) {
            if ($this->isForbiddenUrl($url)) {
                return true;
            }
        }

        return false;
    }
}
