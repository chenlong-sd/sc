<?php

namespace Sc\Util\HtmlStructureV2\Theme\ElementPlusAdmin;

use InvalidArgumentException;
use Sc\Util\HtmlElement\El;
use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlStructureV2\Components\Block\Alert;
use Sc\Util\HtmlStructureV2\Components\Block\Divider;
use Sc\Util\HtmlStructureV2\Components\Block\Text;
use Sc\Util\HtmlStructureV2\Components\Block\Title;
use Sc\Util\HtmlStructureV2\Components\Display\Descriptions;
use Sc\Util\HtmlStructureV2\Components\Layout\Card as LayoutCard;
use Sc\Util\HtmlStructureV2\Components\Layout\Grid as LayoutGrid;
use Sc\Util\HtmlStructureV2\Components\Layout\Stack as LayoutStack;
use Sc\Util\HtmlStructureV2\Contracts\Renderable;
use Sc\Util\HtmlStructureV2\Contracts\RenderableContainer;
use Sc\Util\HtmlStructureV2\RenderContext;

final class LightweightComponentRenderer
{
    private const RENDERERS = [
        LayoutStack::class => 'renderLayoutStack',
        LayoutGrid::class => 'renderLayoutGrid',
        LayoutCard::class => 'renderLayoutCard',
        Title::class => 'renderBlockTitle',
        Divider::class => 'renderBlockDivider',
        Text::class => 'renderBlockText',
        Alert::class => 'renderBlockAlert',
        Descriptions::class => 'renderDescriptions',
    ];

    public function __construct(
        private readonly SectionCardFactory $sectionCardFactory,
    ) {
    }

    public function supports(Renderable $component): bool
    {
        return $this->resolveRendererMethod($component) !== null;
    }

    public function render(Renderable $component, RenderContext $context): AbstractHtmlElement
    {
        $method = $this->resolveRendererMethod($component);
        if ($method === null) {
            throw new InvalidArgumentException('Unsupported lightweight V2 renderable: ' . $component::class);
        }

        return $this->{$method}($component, $context);
    }

    private function renderLayoutStack(LayoutStack $stack, RenderContext $context): AbstractHtmlElement
    {
        return $this->appendRenderedChildren(
            El::double('div')
                ->addClass('sc-v2-stack')
                ->setAttr('style', sprintf('gap:%s', $stack->getGap())),
            $stack,
            $context
        );
    }

    private function renderLayoutGrid(LayoutGrid $grid, RenderContext $context): AbstractHtmlElement
    {
        return $this->appendRenderedChildren(
            El::double('div')
                ->addClass('sc-v2-grid')
                ->setAttr(
                    'style',
                    sprintf(
                        'grid-template-columns:repeat(%d,minmax(0,1fr));gap:%s',
                        $grid->getColumns(),
                        $grid->getGap()
                    )
                ),
            $grid,
            $context
        );
    }

    private function renderLayoutCard(LayoutCard $card, RenderContext $context): AbstractHtmlElement
    {
        $element = $this->sectionCardFactory->make($card->getTitle() ?? '');

        return $this->appendRenderedChildren($element, $card, $context);
    }

    private function renderBlockTitle(Title $title, RenderContext $context): AbstractHtmlElement
    {
        $element = El::double('div')->addClass('sc-v2-block-title')
            ->append(El::double('h2')->append($title->text()));

        if ($title->getDescription()) {
            $element->append(El::double('p')->append($title->getDescription()));
        }

        return $element;
    }

    private function renderBlockDivider(Divider $divider, RenderContext $context): AbstractHtmlElement
    {
        $element = El::double('el-divider');

        if ($divider->text() !== null && $divider->text() !== '') {
            $element->append($divider->text());
        }

        return $element;
    }

    private function renderBlockText(Text $text, RenderContext $context): AbstractHtmlElement
    {
        $class = $text->getType() === 'muted'
            ? 'sc-v2-block-text sc-v2-block-text--muted'
            : 'sc-v2-block-text';

        return El::double('p')->addClass($class)->append($text->content());
    }

    private function renderBlockAlert(Alert $alert, RenderContext $context): AbstractHtmlElement
    {
        return El::double('el-alert')->setAttrs(array_filter([
            'title' => $alert->title(),
            'description' => $alert->description(),
            'type' => $alert->getType(),
            'show-icon' => '',
            ':closable' => 'false',
        ], static fn(mixed $value): bool => $value !== null));
    }

    private function renderDescriptions(Descriptions $descriptions, RenderContext $context): AbstractHtmlElement
    {
        $element = El::double('el-descriptions')->setAttrs([
            ':column' => (string) $descriptions->getColumns(),
            'border' => '',
        ]);

        if ($descriptions->getTitle()) {
            $element->setAttr('title', $descriptions->getTitle());
        }

        foreach ($descriptions->getItems() as $item) {
            $element->append(
                El::double('el-descriptions-item')
                    ->setAttr('label', $item['label'])
                    ->append((string) $item['value'])
            );
        }

        return $element;
    }

    private function appendRenderedChildren(
        AbstractHtmlElement $element,
        RenderableContainer $container,
        RenderContext $context
    ): AbstractHtmlElement {
        foreach ($container->renderChildren() as $child) {
            $element->append($child->render($context));
        }

        return $element;
    }

    private function resolveRendererMethod(Renderable $component): ?string
    {
        foreach (self::RENDERERS as $class => $method) {
            if ($component instanceof $class) {
                return $method;
            }
        }

        return null;
    }
}
