<?php

namespace Sc\Util\HtmlStructureV2\Components\FormNodes;

use Sc\Util\HtmlStructureV2\Components\Concerns\HasSpan;
use Sc\Util\HtmlStructureV2\Components\FormNodes\Concerns\HasFormNodeChildren;
use Sc\Util\HtmlStructureV2\Contracts\FormNode;
use Sc\Util\HtmlStructureV2\Contracts\FormNodeContainer;
use Sc\Util\HtmlStructureV2\Support\FormNodePathContext;
use Sc\Util\HtmlStructureV2\Support\FormNodePathScopedContainer;

final class TabsNode implements FormNode, FormNodeContainer, FormNodePathScopedContainer
{
    use HasSpan;
    use HasFormNodeChildren;
    private string $type = '';
    private string $tabPosition = 'top';
    private bool $stretch = false;

    public function __construct(TabPaneNode ...$tabs)
    {
        $this->setFormNodeChildren(...$tabs);
    }

    public static function make(TabPaneNode ...$tabs): self
    {
        return new self(...$tabs);
    }

    public function addTabs(TabPaneNode ...$tabs): self
    {
        return $this->appendFormNodeChildren(...$tabs);
    }

    public function type(string $type): self
    {
        $this->type = trim($type);

        return $this;
    }

    public function tabPosition(string $tabPosition): self
    {
        $tabPosition = trim($tabPosition);
        $this->tabPosition = $tabPosition === '' ? 'top' : $tabPosition;

        return $this;
    }

    public function stretch(bool $stretch = true): self
    {
        $this->stretch = $stretch;

        return $this;
    }

    /**
     * @return TabPaneNode[]
     */
    public function getTabs(): array
    {
        return $this->getFormNodeChildren();
    }

    public function childPathContext(FormNodePathContext $context): FormNodePathContext
    {
        return $context;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getTabPosition(): string
    {
        return $this->tabPosition;
    }

    public function isStretch(): bool
    {
        return $this->stretch;
    }
}
