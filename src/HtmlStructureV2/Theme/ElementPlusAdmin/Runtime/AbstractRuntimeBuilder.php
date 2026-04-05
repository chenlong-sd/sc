<?php

namespace Sc\Util\HtmlStructureV2\Theme\ElementPlusAdmin\Runtime;

use Sc\Util\HtmlStructureV2\RenderContext;
use Sc\Util\HtmlStructureV2\Support\JsonExpressionEncoder;

abstract class AbstractRuntimeBuilder
{
    private ?RuntimeAssetPublisher $runtimeAssetPublisher = null;

    final public function append(RenderContext $context): void
    {
        if ($context->get($this->runtimeFlagKey())) {
            return;
        }

        $state = JsonExpressionEncoder::encode($context->get('v2.simple.state', []));
        $config = JsonExpressionEncoder::encode($this->buildConfig($context));
        $assets = $context->document()->assets();
        $files = array_merge(RuntimeBundleCatalog::shared(), $this->runtimeFiles());
        $urls = $this->runtimeAssetPublisher()->publishMany($files);

        if ($urls !== null) {
            foreach ($urls as $url) {
                $assets->addScript($url);
            }
            $assets->addInlineScript($this->bootstrapScript($state, $config));
        } else {
            foreach ($files as $filename) {
                $assets->addInlineScript(RuntimeScriptLoader::load($filename));
            }
            $assets->addInlineScript($this->bootstrapScript($state, $config));
        }

        $context->set($this->runtimeFlagKey(), true);
    }

    abstract protected function runtimeFlagKey(): string;

    abstract protected function bootFunctionName(): string;

    abstract protected function runtimeFiles(): array;

    abstract protected function buildConfig(RenderContext $context): array;

    private function bootstrapScript(string $state, string $config): string
    {
        return sprintf('%s(%s, %s);', $this->bootFunctionName(), $state, $config);
    }

    private function runtimeAssetPublisher(): RuntimeAssetPublisher
    {
        return $this->runtimeAssetPublisher ??= new RuntimeAssetPublisher();
    }
}
