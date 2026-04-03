<?php

namespace Sc\Util\HtmlStructureV2\Theme\ElementPlusAdmin\Runtime;

use Sc\Util\HtmlStructureV2\RenderContext;
use Sc\Util\HtmlStructureV2\Support\JsonExpressionEncoder;

final class SimpleRuntimeBuilder
{
    public function append(RenderContext $context): void
    {
        if ($context->get('v2.simple.runtime')) {
            return;
        }

        $state = JsonExpressionEncoder::encode($context->get('v2.simple.state', []));
        $config = JsonExpressionEncoder::encode($context->get('v2.simple.config', []));
        $context->document()->assets()->addInlineScript(
            RuntimeScriptLoader::loadMany([
                'runtime-helpers.js',
                'request-action-factory.js',
                'form-runtime-factory.js',
                'managed-dialog-factory.js',
                'simple/form-methods.js',
                'simple/dialog-methods.js',
                'simple/table-methods.js',
                'simple-runtime.js',
            ], [
                '__SC_V2_STATE__' => $state,
                '__SC_V2_CONFIG__' => $config,
            ])
        );
        $context->set('v2.simple.runtime', true);
    }
}
