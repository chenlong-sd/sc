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

            $config[$dialog->key()] = [
                'title' => $dialog->title(),
                'titleTemplate' => $dialog->getTitleTemplate(),
                'type' => $dialog->bodyType(),
                'fullscreen' => $dialog->isFullscreen(),
                'context' => $dialog->getContextData(),
                'component' => $dialog->getComponentName() ? [
                    'name' => $dialog->getComponentName(),
                    'props' => $dialog->getComponentProps(),
                    'attrs' => $dialog->getComponentAttrs(),
                    'openMethod' => $dialog->getComponentOpenMethod(),
                    'closeMethod' => $dialog->getComponentCloseMethod(),
                ] : null,
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
                    'host' => $dialog->isIframeHostEnabled(),
                    'fullscreenToggle' => $dialog->hasIframeFullscreenToggle(),
                ] : null,
                'events' => $dialog->getEventHandlers(),
            ];
        }

        return $config;
    }
}
