<?php

namespace Sc\Util\HtmlStructureV2\Theme\ElementPlusAdmin\Runtime;

use Sc\Util\HtmlStructureV2\Components\Dialog;

final class DialogConfigBuilder
{
    public function build(array $dialogs): array
    {
        $config = [];

        foreach ($dialogs as $dialog) {
            if (!$dialog instanceof Dialog) {
                continue;
            }

            $form = $dialog->getForm();
            $config[$dialog->key()] = [
                'title' => $dialog->title(),
                'titleTemplate' => $dialog->getTitleTemplate(),
                'type' => $dialog->bodyType(),
                'defaults' => $form?->defaults() ?? [],
                'rules' => $form?->rules() ?? [],
                'remoteOptions' => $form?->remoteOptions() ?? [],
                'selectOptions' => $form?->selectOptions() ?? [],
                'linkages' => $form?->linkages() ?? [],
                'uploads' => $form?->uploads() ?? [],
                'saveUrl' => $dialog->getSaveUrl(),
                'createUrl' => $dialog->getCreateUrl(),
                'updateUrl' => $dialog->getUpdateUrl(),
                'load' => $dialog->getLoadUrl() ? [
                    'url' => $dialog->getLoadUrl(),
                    'method' => $dialog->getLoadMethod(),
                    'payload' => $dialog->getLoadPayload(),
                    'dataPath' => $dialog->getLoadDataPath(),
                    'when' => $dialog->getLoadWhen(),
                ] : null,
                'iframe' => $dialog->getIframeUrl() ? [
                    'url' => $dialog->getIframeUrl(),
                    'query' => $dialog->getIframeQuery() ?? [],
                ] : null,
                'beforeOpen' => $dialog->getBeforeOpenHook(),
                'afterOpen' => $dialog->getAfterOpenHook(),
                'beforeClose' => $dialog->getBeforeCloseHook(),
                'afterClose' => $dialog->getAfterCloseHook(),
            ];
        }

        return $config;
    }
}
