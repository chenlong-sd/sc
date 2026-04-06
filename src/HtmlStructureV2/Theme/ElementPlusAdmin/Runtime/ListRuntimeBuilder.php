<?php

namespace Sc\Util\HtmlStructureV2\Theme\ElementPlusAdmin\Runtime;

use Sc\Util\HtmlStructureV2\RenderContext;

final class ListRuntimeBuilder extends AbstractRuntimeBuilder
{
    protected function runtimeFlagKey(): string
    {
        return 'v2.list.runtime';
    }

    protected function bootFunctionName(): string
    {
        return '__SC_V2_BOOT_LIST__';
    }

    protected function runtimeFiles(): array
    {
        return RuntimeBundleCatalog::list();
    }

    protected function buildConfig(RenderContext $context): array
    {
        return array_replace_recursive(
            $context->get('v2.simple.config', []),
            [
                'actions' => $context->get('v2.action.configs', []),
                'pageEvents' => $context->get('v2.page.event.configs', []),
                'tables' => $context->get('v2.table.configs', []),
                'lists' => $context->get('v2.list.configs', []),
                'primaryList' => $context->get('v2.primary.list'),
            ]
        );
    }
}
