<?php

namespace Sc\Util\HtmlStructureV2\Theme\ElementPlusAdmin\Runtime;

use Sc\Util\HtmlStructureV2\RenderContext;

final class SimpleRuntimeBuilder extends AbstractRuntimeBuilder
{
    protected function runtimeFlagKey(): string
    {
        return 'v2.simple.runtime';
    }

    protected function bootFunctionName(): string
    {
        return '__SC_V2_BOOT_SIMPLE__';
    }

    protected function runtimeFiles(): array
    {
        return RuntimeBundleCatalog::simple();
    }

    protected function buildConfig(RenderContext $context): array
    {
        return array_replace_recursive(
            $context->get('v2.simple.config', []),
            [
                'tables' => $context->get('v2.table.configs', []),
            ]
        );
    }
}
