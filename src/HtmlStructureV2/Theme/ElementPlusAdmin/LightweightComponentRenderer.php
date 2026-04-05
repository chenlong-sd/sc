<?php

namespace Sc\Util\HtmlStructureV2\Theme\ElementPlusAdmin;

use InvalidArgumentException;
use Sc\Util\HtmlElement\El;
use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlStructureV2\Components\Block\Alert;
use Sc\Util\HtmlStructureV2\Components\Block\Button;
use Sc\Util\HtmlStructureV2\Components\Block\Divider;
use Sc\Util\HtmlStructureV2\Components\Block\Text;
use Sc\Util\HtmlStructureV2\Components\Block\Title;
use Sc\Util\HtmlStructureV2\Components\Display\Descriptions;
use Sc\Util\HtmlStructureV2\Components\Layout\Card as LayoutCard;
use Sc\Util\HtmlStructureV2\Components\Layout\Grid as LayoutGrid;
use Sc\Util\HtmlStructureV2\Components\Layout\Stack as LayoutStack;
use Sc\Util\HtmlStructureV2\Contracts\EventAware;
use Sc\Util\HtmlStructureV2\Contracts\Renderable;
use Sc\Util\HtmlStructureV2\Contracts\RenderableContainer;
use Sc\Util\HtmlStructureV2\RenderContext;
use Sc\Util\HtmlStructureV2\Support\JsonExpressionEncoder;
use Sc\Util\HtmlStructureV2\Support\ResolvesClassMappedMethod;

final class LightweightComponentRenderer
{
    use ResolvesClassMappedMethod;

    private const RENDERERS = [
        LayoutStack::class => 'renderLayoutStack',
        LayoutGrid::class => 'renderLayoutGrid',
        LayoutCard::class => 'renderLayoutCard',
        Title::class => 'renderBlockTitle',
        Divider::class => 'renderBlockDivider',
        Text::class => 'renderBlockText',
        Alert::class => 'renderBlockAlert',
        Button::class => 'renderBlockButton',
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

    public function supportsTree(Renderable $component): bool
    {
        if (!$this->supports($component)) {
            return false;
        }

        if (!$component instanceof RenderableContainer) {
            return true;
        }

        foreach ($component->renderChildren() as $child) {
            if (!$this->supportsTree($child)) {
                return false;
            }
        }

        return true;
    }

    public function render(
        Renderable $component,
        RenderContext $context,
        ?string $eventContextExpression = null
    ): AbstractHtmlElement
    {
        $method = $this->resolveRendererMethod($component);
        if ($method === null) {
            throw new InvalidArgumentException('Unsupported lightweight V2 renderable: ' . $component::class);
        }

        return $this->{$method}($component, $context, $eventContextExpression);
    }

    private function renderLayoutStack(
        LayoutStack $stack,
        RenderContext $context,
        ?string $eventContextExpression = null
    ): AbstractHtmlElement
    {
        return $this->appendRenderedChildren(
            $this->applyComponentEvents(
                El::double('div')
                    ->addClass('sc-v2-stack')
                    ->setAttr('style', sprintf('gap:%s', $stack->getGap())),
                $stack,
                $eventContextExpression
            ),
            $stack,
            $context,
            $eventContextExpression
        );
    }

    private function renderLayoutGrid(
        LayoutGrid $grid,
        RenderContext $context,
        ?string $eventContextExpression = null
    ): AbstractHtmlElement
    {
        return $this->appendRenderedChildren(
            $this->applyComponentEvents(
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
                $eventContextExpression
            ),
            $grid,
            $context,
            $eventContextExpression
        );
    }

    private function renderLayoutCard(
        LayoutCard $card,
        RenderContext $context,
        ?string $eventContextExpression = null
    ): AbstractHtmlElement
    {
        $element = $this->applyComponentEvents(
            $this->sectionCardFactory->make($card->getTitle() ?? ''),
            $card,
            $eventContextExpression
        );

        return $this->appendRenderedChildren($element, $card, $context, $eventContextExpression);
    }

    private function renderBlockTitle(
        Title $title,
        RenderContext $context,
        ?string $eventContextExpression = null
    ): AbstractHtmlElement
    {
        $element = El::double('div')->addClass('sc-v2-block-title')
            ->append(El::double('h2')->append($title->text()));

        if ($title->getDescription()) {
            $element->append(El::double('p')->append($title->getDescription()));
        }

        return $this->applyComponentEvents($element, $title, $eventContextExpression);
    }

    private function renderBlockDivider(
        Divider $divider,
        RenderContext $context,
        ?string $eventContextExpression = null
    ): AbstractHtmlElement
    {
        $element = El::double('el-divider');

        if ($divider->text() !== null && $divider->text() !== '') {
            $element->append($divider->text());
        }

        return $this->applyComponentEvents($element, $divider, $eventContextExpression);
    }

    private function renderBlockText(
        Text $text,
        RenderContext $context,
        ?string $eventContextExpression = null
    ): AbstractHtmlElement
    {
        $class = $text->getType() === 'muted'
            ? 'sc-v2-block-text sc-v2-block-text--muted'
            : 'sc-v2-block-text';

        return $this->applyComponentEvents(
            El::double('p')->addClass($class)->append($text->content()),
            $text,
            $eventContextExpression
        );
    }

    private function renderBlockAlert(
        Alert $alert,
        RenderContext $context,
        ?string $eventContextExpression = null
    ): AbstractHtmlElement
    {
        return $this->applyComponentEvents(El::double('el-alert')->setAttrs(array_filter([
            'title' => $alert->title(),
            'description' => $alert->description(),
            'type' => $alert->getType(),
            'show-icon' => '',
            ':closable' => 'false',
        ], static fn(mixed $value): bool => $value !== null)), $alert, $eventContextExpression);
    }

    private function renderBlockButton(
        Button $button,
        RenderContext $context,
        ?string $eventContextExpression = null
    ): AbstractHtmlElement
    {
        $element = El::double('el-button')->setAttrs(array_filter([
            'type' => $button->buttonType(),
            'size' => $button->buttonSize(),
            'plain' => $button->isPlain() ? '' : null,
            'link' => $button->isLink() ? '' : null,
        ], static fn(mixed $value): bool => $value !== null));
        $element->append($button->label());

        return $this->applyComponentEvents($element, $button, $eventContextExpression);
    }

    private function renderDescriptions(
        Descriptions $descriptions,
        RenderContext $context,
        ?string $eventContextExpression = null
    ): AbstractHtmlElement
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

        return $this->applyComponentEvents($element, $descriptions, $eventContextExpression);
    }

    private function appendRenderedChildren(
        AbstractHtmlElement $element,
        RenderableContainer $container,
        RenderContext $context,
        ?string $eventContextExpression = null
    ): AbstractHtmlElement {
        foreach ($container->renderChildren() as $child) {
            $element->append(
                $this->supports($child)
                    ? $this->render($child, $context, $eventContextExpression)
                    : $child->render($context)
            );
        }

        return $element;
    }

    private function applyComponentEvents(
        AbstractHtmlElement $element,
        Renderable $component,
        ?string $eventContextExpression = null
    ): AbstractHtmlElement {
        if (!$component instanceof EventAware || !$component->hasEventHandlers()) {
            return $element;
        }

        foreach ($component->getEventHandlers() as $eventName => $handlers) {
            if (!is_string($eventName) || trim($eventName) === '' || $handlers === []) {
                continue;
            }

            $contextExpression = $eventContextExpression === null || trim($eventContextExpression) === ''
                ? '{ event: $event }'
                : sprintf('Object.assign({ event: $event }, %s)', $eventContextExpression);

            $element->setAttr(
                '@' . ltrim(trim($eventName), '@'),
                sprintf(
                    'runPageEventHandlers(%s, %s)',
                    JsonExpressionEncoder::encodeCompact(array_values($handlers)),
                    $contextExpression
                )
            );
        }

        return $element;
    }

    private function resolveRendererMethod(Renderable $component): ?string
    {
        return $this->resolveClassMappedMethod($component, self::RENDERERS);
    }
}
