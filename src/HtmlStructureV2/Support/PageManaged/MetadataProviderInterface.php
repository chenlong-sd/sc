<?php

namespace Sc\Util\HtmlStructureV2\Support\PageManaged;

use Sc\Util\HtmlStructureV2\Components\Dialog;
use Sc\Util\HtmlStructureV2\Contracts\Renderable;

interface MetadataProviderInterface
{
    public function supports(Renderable $component): bool;

    /**
     * @return Dialog[]
     */
    public function dialogs(Renderable $component): array;

    /**
     * @return ManagedActionCollection[]
     */
    public function actionCollections(Renderable $component): array;
}
