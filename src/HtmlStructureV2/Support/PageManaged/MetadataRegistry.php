<?php

namespace Sc\Util\HtmlStructureV2\Support\PageManaged;

use Sc\Util\HtmlStructureV2\Components\Dialog;
use Sc\Util\HtmlStructureV2\Contracts\Renderable;

final class MetadataRegistry
{
    /**
     * @param MetadataProviderInterface[] $providers
     */
    public function __construct(
        private readonly array $providers = [
            new ActionMetadataProvider(),
            new FormMetadataProvider(),
            new ListMetadataProvider(),
            new TableMetadataProvider(),
            new DialogMetadataProvider(),
        ],
    ) {
    }

    /**
     * @return Dialog[]
     */
    public function dialogs(Renderable $component): array
    {
        return $this->providerFor($component)?->dialogs($component) ?? [];
    }

    /**
     * @return ManagedActionCollection[]
     */
    public function actionCollections(Renderable $component): array
    {
        return $this->providerFor($component)?->actionCollections($component) ?? [];
    }

    private function providerFor(Renderable $component): ?MetadataProviderInterface
    {
        foreach ($this->providers as $provider) {
            if ($provider->supports($component)) {
                return $provider;
            }
        }

        return null;
    }
}
