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
use Sc\Util\HtmlStructureV2\Components\Display\Media\Image;
use Sc\Util\HtmlStructureV2\Components\Display\Media\Images;
use Sc\Util\HtmlStructureV2\Components\Display\Media\File;
use Sc\Util\HtmlStructureV2\Components\Display\Media\Files;
use Sc\Util\HtmlStructureV2\Components\Display\Media\Audio;
use Sc\Util\HtmlStructureV2\Components\Display\Media\Video;
use Sc\Util\HtmlStructureV2\Components\Display\Media\Videos;
use Sc\Util\HtmlStructureV2\Theme\ElementPlusAdmin\MediaRuntime;
use Sc\Util\HtmlStructureV2\Components\Layout\Card as LayoutCard;
use Sc\Util\HtmlStructureV2\Components\Layout\Grid as LayoutGrid;
use Sc\Util\HtmlStructureV2\Components\Layout\Stack as LayoutStack;
use Sc\Util\HtmlStructureV2\Contracts\EventAware;
use Sc\Util\HtmlStructureV2\Contracts\Renderable;
use Sc\Util\HtmlStructureV2\Contracts\RenderableContainer;
use Sc\Util\HtmlStructureV2\RenderContext;
use Sc\Util\HtmlStructureV2\Theme\ElementPlusAdmin\Concerns\AppliesRenderableAttributes;
use Sc\Util\HtmlStructureV2\Support\ResolvesClassMappedMethod;
use Sc\Util\HtmlStructureV2\Theme\ElementPlusAdmin\Concerns\EncodesJsValues;

final class LightweightComponentRenderer
{
    use AppliesRenderableAttributes;
    use EncodesJsValues;
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
        Image::class => 'renderImage',
        Images::class => 'renderImages',
        File::class => 'renderFile',
        Files::class => 'renderFiles',
        Video::class => 'renderVideo',
        Videos::class => 'renderVideos',
        Audio::class => 'renderAudio',
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
                $this->applyComponentRootAttributes(
                    El::double('div')
                        ->addClass('sc-v2-stack')
                        ->setAttr('style', sprintf('gap:%s', $stack->getGap())),
                    $stack
                ),
                $stack,
                $eventContextExpression,
                $context
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
                $this->applyComponentRootAttributes(
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
                    $grid
                ),
                $grid,
                $eventContextExpression,
                $context
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
            $this->applyComponentRootAttributes(
                $this->sectionCardFactory->make($card->getTitle() ?? ''),
                $card
            ),
            $card,
            $eventContextExpression,
            $context
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

        return $this->applyComponentEvents(
            $this->applyComponentRootAttributes($element, $title),
            $title,
            $eventContextExpression,
            $context
        );
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

        return $this->applyComponentEvents(
            $this->applyComponentRootAttributes($element, $divider),
            $divider,
            $eventContextExpression,
            $context
        );
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
            $this->applyComponentRootAttributes(
                El::double('p')->addClass($class)->append($text->content()),
                $text
            ),
            $text,
            $eventContextExpression,
            $context
        );
    }

    private function renderBlockAlert(
        Alert $alert,
        RenderContext $context,
        ?string $eventContextExpression = null
    ): AbstractHtmlElement
    {
        return $this->applyComponentEvents(
            $this->applyComponentRootAttributes(
                El::double('el-alert')->setAttrs(array_filter([
                    'title' => $alert->title(),
                    'description' => $alert->description(),
                    'type' => $alert->getType(),
                    'show-icon' => '',
                    ':closable' => 'false',
                ], static fn(mixed $value): bool => $value !== null)),
                $alert
            ),
            $alert,
            $eventContextExpression,
            $context
        );
    }

    private function renderBlockButton(
        Button $button,
        RenderContext $context,
        ?string $eventContextExpression = null
    ): AbstractHtmlElement
    {
        $element = $this->applyComponentRootAttributes(
            El::double('el-button')->setAttrs(array_filter([
                'type' => $button->buttonType(),
                'size' => $button->buttonSize(),
                'plain' => $button->isPlain() ? '' : null,
                'link' => $button->isLink() ? '' : null,
            ], static fn(mixed $value): bool => $value !== null)),
            $button
        );
        $element->append($button->label());

        return $this->applyComponentEvents($element, $button, $eventContextExpression, $context);
    }

    private function renderFile(
        File $file,
        RenderContext $context,
        ?string $eventContextExpression = null
    ): AbstractHtmlElement {
        $link = $this->buildFileLinkElement(
            $file->getName(),
            $file->getUrl(),
            $file->isStatic(),
            $file->getIcon(),
            $file->getSizeLabel(),
            $file->isDownload(),
            $file->getTarget(),
            $file->getLinkType(),
            $file::guessIconFromName((string) $file->getName()),
        );

        $link = $this->applyComponentRootAttributes($link, $file);

        return $this->applyComponentEvents($link, $file, $eventContextExpression, $context);
    }

    private function renderAudio(
        Audio $audio,
        RenderContext $context,
        ?string $eventContextExpression = null
    ): AbstractHtmlElement {
        $wrapper = $this->applyComponentRootAttributes(
            El::double('div')->addClass('sc-v2-media-audios')->setAttr(
                'style',
                sprintf('display:flex;flex-direction:column;gap:%dpx', $audio->getGap())
            ),
            $audio
        );

        if ($audio->isStatic()) {
            $raw = array_values($audio->getSources());
            $prop = $audio->getProp();
            $list = array_map(static function (mixed $item) use ($prop): string {
                if (!is_array($item)) {
                    return (string) $item;
                }
                return (string) ($item[$prop] ?? $item['url'] ?? '');
            }, $raw);

            if ($list === []) {
                $wrapper->append($this->mediaPlaceholderElement($audio->getPlaceholder()));

                return $this->applyComponentEvents($wrapper, $audio, $eventContextExpression, $context);
            }

            $limit = $audio->getLimit();
            $visible = $limit === null ? $list : array_slice($list, 0, $limit);

            foreach ($visible as $src) {
                $wrapper->append(
                    El::double('audio')->setAttrs([
                        'controls' => '',
                        'preload' => 'metadata',
                        'style' => sprintf('width:%dpx;max-width:100%%', $audio->getWidth()),
                        'src' => (string) $src,
                    ])
                );
            }

            return $this->applyComponentEvents($wrapper, $audio, $eventContextExpression, $context);
        }

        // JS 变量模式
        $varExpr = (string) $audio->getSources();
        $srcExpr = $audio->getProp() === '' ? 'item' : sprintf('item.%s', $audio->getProp());
        $limit = $audio->getLimit();
        $iterExpr = $limit === null ? $varExpr : sprintf('%s.slice(0, %d)', $varExpr, $limit);

        $wrapper->append(El::double('audio')->setAttrs([
            'v-for' => sprintf('(item, audioIndex) in %s', $iterExpr),
            ':key' => 'audioIndex',
            'controls' => '',
            'preload' => 'metadata',
            'style' => sprintf('width:%dpx;max-width:100%%', $audio->getWidth()),
            ':src' => $srcExpr,
        ]));

        return $this->applyComponentEvents($wrapper, $audio, $eventContextExpression, $context);
    }

    private function renderVideo(
        Video $video,
        RenderContext $context,
        ?string $eventContextExpression = null
    ): AbstractHtmlElement {
        MediaRuntime::ensureInstalled($context);

        $wrapper = $this->buildVideoThumb(
            $video->getWidth(),
            $video->getHeight(),
            $video->getRadius(),
            $video->isAutoplay(),
            $video->getPoster(),
            $video->isStatic(),
            $video->getSrc(),
            sprintf('__SC_V2_PAGE__.playMedia(%s)', $this->mediaUrlArg($video->isStatic(), $video->getSrc()))
        );

        $wrapper = $this->applyComponentRootAttributes($wrapper, $video);

        return $this->applyComponentEvents($wrapper, $video, $eventContextExpression, $context);
    }

    private function renderVideos(
        Videos $videos,
        RenderContext $context,
        ?string $eventContextExpression = null
    ): AbstractHtmlElement {
        MediaRuntime::ensureInstalled($context);

        $wrapper = $this->applyComponentRootAttributes(
            El::double('div')->addClass('sc-v2-media-videos')->setAttr(
                'style',
                sprintf('display:flex;flex-wrap:wrap;gap:%dpx', $videos->getGap())
            ),
            $videos
        );

        if ($videos->isStatic()) {
            $raw = array_values($videos->getSources());
            $prop = $videos->getProp();
            $list = array_map(static function (mixed $item) use ($prop): string {
                if (!is_array($item)) {
                    return (string) $item;
                }
                return (string) ($item[$prop] ?? $item['url'] ?? '');
            }, $raw);

            if ($list === []) {
                $wrapper->append($this->mediaPlaceholderElement($videos->getPlaceholder()));

                return $this->applyComponentEvents($wrapper, $videos, $eventContextExpression, $context);
            }

            $limit = $videos->getLimit();
            $visible = $limit === null ? $list : array_slice($list, 0, $limit);

            foreach ($visible as $src) {
                $wrapper->append($this->buildVideoThumb(
                    $videos->getWidth(),
                    $videos->getHeight(),
                    $videos->getRadius(),
                    $videos->isAutoplay(),
                    null,
                    true,
                    $src,
                    sprintf('__SC_V2_PAGE__.playMedia(%s)', $this->mediaUrlArg(true, $src))
                ));
            }

            return $this->applyComponentEvents($wrapper, $videos, $eventContextExpression, $context);
        }

        // JS 变量模式
        $varExpr = (string) $videos->getSources();
        $srcExpr = $videos->getProp() === '' ? 'item' : sprintf('item.%s', $videos->getProp());
        $limit = $videos->getLimit();
        $iterExpr = $limit === null ? $varExpr : sprintf('%s.slice(0, %d)', $varExpr, $limit);

        $thumb = $this->buildVideoThumb(
            $videos->getWidth(),
            $videos->getHeight(),
            $videos->getRadius(),
            $videos->isAutoplay(),
            null,
            false,
            $srcExpr,
            sprintf('__SC_V2_PAGE__.playMedia(%s)', $srcExpr)
        );
        $thumb->setAttr('v-for', sprintf('(item, videoIndex) in %s', $iterExpr));
        $thumb->setAttr(':key', 'videoIndex');
        $wrapper->append($thumb);

        return $this->applyComponentEvents($wrapper, $videos, $eventContextExpression, $context);
    }

    /**
     * 构造单个视频缩略图元素：video（静音封面/autoplay）+ 蒙层播放按钮。
     * $clickExpression 为点击时执行的 JS，通常调用 __SC_V2_PAGE__.playMedia。
     */
    /**
     * 生成传入 playMedia 的 JS 参数：静态地址包成字符串字面量，动态表达式原样返回。
     */
    private function mediaUrlArg(bool $isStatic, string $src): string
    {
        return $isStatic ? $this->jsString($src) : $src;
    }

    /**
     * 媒体数据为空时的占位元素（span 文本），由各媒体渲染器调用。
     */
    private function mediaPlaceholderElement(string $placeholder): AbstractHtmlElement
    {
        return El::double('span')->addClass('sc-v2-media__placeholder')->append($placeholder);
    }

    private function buildVideoThumb(
        int $width,
        int $height,
        ?int $radius,
        bool $autoplay,
        ?string $poster,
        bool $isStatic,
        string $src,
        string $clickExpression
    ): AbstractHtmlElement {
        $radiusStyle = $radius === null ? '' : sprintf('border-radius:%dpx', $radius);
        $wrapper = El::double('div')
            ->addClass('sc-v2-media-video')
            ->setAttr('style', sprintf('width:%dpx;height:%dpx;%s', $width, $height, $radiusStyle))
            ->setAttr('@click', $clickExpression);

        $videoEl = El::double('video')->setAttrs(array_filter([
            $isStatic ? 'src' : ':src' => $src,
            'muted' => $autoplay ? '' : null,
            'autoplay' => $autoplay ? '' : null,
            'loop' => $autoplay ? '' : null,
            'playsinline' => $autoplay ? '' : null,
            'disablepictureinpicture' => '',
            'controlslist' => 'nodownload noremoteplayback',
            'poster' => $poster,
            'preload' => $autoplay ? 'auto' : 'metadata',
        ], static fn(mixed $value): bool => $value !== null));
        $wrapper->append($videoEl);

        // 蒙层播放按钮（SVG）
        $button = El::fromCode('<div class="sc-v2-media-video__play"><svg class="sc-v2-media-video__play-icon" viewBox="0 0 24 24" aria-hidden="true"><path fill="currentColor" d="M7 4.8v14.4c0 .8.9 1.3 1.6.8l10.2-7.2a1 1 0 0 0 0-1.6L8.6 4C7.9 3.5 7 4 7 4.8z" /></svg></div>');
        $wrapper->append($button);

        return $wrapper;
    }

    private function renderFiles(
        Files $files,
        RenderContext $context,
        ?string $eventContextExpression = null
    ): AbstractHtmlElement {
        $wrapper = $this->applyComponentRootAttributes(
            El::double('div')->addClass('sc-v2-media-files')->setAttr(
                'style',
                $files->getLayout() === 'inline'
                    ? sprintf('display:flex;flex-wrap:wrap;gap:%dpx;align-items:center', $files->getGap())
                    : sprintf('display:flex;flex-direction:column;gap:%dpx', $files->getGap())
            ),
            $files
        );

        if ($files->isStatic()) {
            $list = array_values($files->getSources());

            if ($list === []) {
                $wrapper->append($this->mediaPlaceholderElement($files->getPlaceholder()));

                return $this->applyComponentEvents($wrapper, $files, $eventContextExpression, $context);
            }

            foreach ($list as $item) {
                if (is_array($item)) {
                    $url = (string) ($item[$files->getUrlProp()] ?? $item['url'] ?? '');
                    $name = (string) ($item[$files->getNameProp()] ?? basename($url) ?? '附件');
                    $size = isset($item[$files->getSizeProp()]) ? (string) $item[$files->getSizeProp()] : null;
                } else {
                    $url = (string) $item;
                    $name = basename($url) !== '' ? basename($url) : '附件';
                    $size = null;
                }
                $icon = $files->isAutoIcon() ? File::guessIconFromName($name) : $files->getDefaultIcon();
                $wrapper->append($this->buildFileLinkElement(
                    $name,
                    $url,
                    true,
                    $icon,
                    $size,
                    $files->isDownload(),
                    $files->getTarget(),
                    $files->getLinkType(),
                    File::guessIconFromName($name),
                ));
            }

            return $this->applyComponentEvents($wrapper, $files, $eventContextExpression, $context);
        }

        // JS 变量模式
        $varExpr = (string) $files->getSources();
        $urlProp = $files->getUrlProp();
        $nameProp = $files->getNameProp();
        $sizeProp = $files->getSizeProp();

        $urlExpr = $urlProp === '' ? 'item' : sprintf('item.%s', $urlProp);
        $nameExpr = $nameProp === '' ? 'item' : sprintf('item.%s', $nameProp);
        $sizeExpr = $sizeProp === '' ? null : sprintf('item.%s', $sizeProp);

        $item = El::double('el-link')->setAttrs(array_filter([
            'v-for' => sprintf('(item, fileIndex) in %s', $varExpr),
            ':key' => 'fileIndex',
            'type' => $files->getLinkType(),
            ':underline' => 'false',
            ':href' => $urlExpr,
            'target' => $files->getTarget(),
            'download' => $files->isDownload() ? '' : null,
        ], static fn(mixed $value): bool => $value !== null));

        if ($files->getDefaultIcon() !== null) {
            $item->append(El::double('el-icon')->append(El::double((string) $files->getDefaultIcon())));
        }

        $item->append(El::double('span')->setAttr('class', 'sc-v2-media-file__name')->append('{{ ' . $nameExpr . ' }}'));
        if ($sizeExpr !== null) {
            $item->append(El::double('span')->setAttr('class', 'sc-v2-media-file__size')->append('{{ ' . $sizeExpr . ' }}'));
        }
        $wrapper->append($item);

        return $this->applyComponentEvents($wrapper, $files, $eventContextExpression, $context);
    }

    /**
     * 构造单个 el-link 附件元素。name/size 既支持静态字符串，也支持 JS 表达式（isStatic=false）。
     */
    private function buildFileLinkElement(
        ?string $name,
        string $url,
        bool $isStatic,
        ?string $icon,
        ?string $sizeLabel,
        bool $download,
        string $target,
        string $linkType,
        ?string $autoIcon = null,
    ): AbstractHtmlElement {
        $link = El::double('el-link')->setAttrs(array_filter([
            'type' => $linkType,
            ':underline' => 'false',
            $isStatic ? 'href' : ':href' => $url,
            'target' => $target,
            'download' => $download ? '' : null,
        ], static fn(mixed $value): bool => $value !== null));

        $resolvedIcon = $icon ?? $autoIcon;
        if ($resolvedIcon !== null && $resolvedIcon !== '') {
            $link->append(El::double('el-icon')->append(El::double((string) $resolvedIcon)));
        }

        if ($name !== null && $name !== '') {
            // 名字若是 JS 表达式（isStatic=false 且不是纯字面量），用插值；否则直接文本
            $isExpression = !$isStatic && preg_match('/^[A-Za-z_$][\w$.\[\]]*$/', $name) === 1;
            $link->append(El::double('span')
                ->addClass('sc-v2-media-file__name')
                ->append($isExpression ? '{{ ' . $name . ' }}' : $name));
        }

        if ($sizeLabel !== null && $sizeLabel !== '') {
            $isExpr = !$isStatic && preg_match('/^[A-Za-z_$][\w$.\[\]]*$/', $sizeLabel) === 1;
            $link->append(El::double('span')
                ->addClass('sc-v2-media-file__size')
                ->append($isExpr ? '{{ ' . $sizeLabel . ' }}' : $sizeLabel));
        }

        return $link;
    }

    private function renderImages(
        Images $images,
        RenderContext $context,
        ?string $eventContextExpression = null
    ): AbstractHtmlElement {
        $radiusStyle = $images->getRadius() === null
            ? ''
            : sprintf('border-radius:%dpx', $images->getRadius());
        $thumbStyle = sprintf(
            'width:%dpx;height:%dpx;%s',
            $images->getWidth(),
            $images->getHeight(),
            $radiusStyle
        );

        $commonAttrs = array_filter([
            'fit' => $images->getFit(),
            'hide-on-click-modal' => '',
            'lazy' => $images->isLazy() ? '' : null,
            ':preview-teleported' => $images->isPreview() ? 'true' : null,
            'style' => $thumbStyle,
        ], static fn(mixed $value): bool => $value !== null);

        $wrapper = $this->applyComponentRootAttributes(
            El::double('div')->addClass('sc-v2-media-images')->setAttr(
                'style',
                sprintf('display:flex;flex-wrap:wrap;gap:%dpx', $images->getGap())
            ),
            $images
        );

        if ($images->isStatic()) {
            $raw = array_values($images->getSources());
            $prop = $images->getProp();
            // 把（可能为对象的）元素统一拍平成真实地址字符串数组
            $list = array_map(static function (mixed $item) use ($prop): string {
                if (!is_array($item)) {
                    return (string) $item;
                }
                return (string) ($item[$prop] ?? $item['url'] ?? '');
            }, $raw);

            if ($list === []) {
                $wrapper->append($this->mediaPlaceholderElement($images->getPlaceholder()));

                return $this->applyComponentEvents($wrapper, $images, $eventContextExpression, $context);
            }

            $limit = $images->getLimit();
            $visible = $limit === null ? $list : array_slice($list, 0, $limit);
            $previewList = $images->isPreview() ? $this->jsValue($list) : null;

            foreach ($visible as $index => $src) {
                $attrs = $commonAttrs;
                if ($previewList !== null) {
                    $attrs[':preview-src-list'] = $previewList;
                    $attrs[':initial-index'] = (string) $index;
                }
                $wrapper->append(El::double('el-image')->setAttrs($attrs)->setAttr('src', (string) $src));
            }

            return $this->applyComponentEvents($wrapper, $images, $eventContextExpression, $context);
        }

        // JS 变量模式：string 形式，按 v-for 展开
        $varExpr = (string) $images->getSources();
        $prop = $images->getProp();
        $srcExpr = $prop === ''
            ? 'item'
            : sprintf('item.%s', $prop);
        $limit = $images->getLimit();
        $iterExpr = $limit === null
            ? $varExpr
            : sprintf('%s.slice(0, %d)', $varExpr, $limit);

        $attrs = $commonAttrs;
        $attrs['v-for'] = sprintf('(item, imageIndex) in %s', $iterExpr);
        $attrs[':key'] = 'imageIndex';
        $attrs[':src'] = $srcExpr;
        if ($images->isPreview()) {
            $previewMap = $prop === ''
                ? $varExpr
                : sprintf('%s.map((v) => v.%s)', $varExpr, $prop);
            $attrs[':preview-src-list'] = $previewMap;
            $attrs[':initial-index'] = 'imageIndex';
        }

        $wrapper->append(El::double('el-image')->setAttrs($attrs));

        return $this->applyComponentEvents($wrapper, $images, $eventContextExpression, $context);
    }

    private function renderImage(
        Image $image,
        RenderContext $context,
        ?string $eventContextExpression = null
    ): AbstractHtmlElement {
        $radiusStyle = $image->getRadius() === null
            ? ''
            : sprintf('border-radius:%dpx', $image->getRadius());
        $style = sprintf('width:%dpx;height:%dpx;%s', $image->getWidth(), $image->getHeight(), $radiusStyle);

        $attrs = array_filter([
            'fit' => $image->getFit(),
            'hide-on-click-modal' => '',
            'lazy' => $image->isLazy() ? '' : null,
            ':preview-teleported' => $image->isPreview() ? 'true' : null,
        ], static fn(mixed $value): bool => $value !== null);

        if ($image->isPreview()) {
            $srcValue = $image->getSrc();
            $attrs[':preview-src-list'] = $image->isStatic()
                ? $this->jsValue([$srcValue])
                : sprintf('[%s]', $srcValue);
        }

        $element = $this->applyComponentRootAttributes(
            El::double('el-image')->setAttrs($attrs)->setAttr('style', $style),
            $image
        );

        return $this->applyComponentEvents($element, $image, $eventContextExpression, $context);
    }

    private function renderDescriptions(
        Descriptions $descriptions,
        RenderContext $context,
        ?string $eventContextExpression = null
    ): AbstractHtmlElement
    {
        $title = $descriptions->getTitle();

        $descriptionsAttrs = [
            ':column' => (string) $descriptions->getColumns(),
            'border' => $descriptions->isBorder() ? '' : null,
            // 有 title 时不再走 Element Plus 原生 title 属性（与 Card header 的 .sc-v2-section__header 视觉不一致），
            // 改为在外层 div 单独渲染统一的小竖条标题节点，避免和 Card 标题视觉割裂。
            'title' => null,
            'label-width' => $descriptions->getLabelWidth() === null
                ? null
                : (string) $descriptions->getLabelWidth(),
            'direction' => $descriptions->getDirection(),
            'size' => $descriptions->getSize(),
            'extra' => $descriptions->getExtra(),
        ];

        $element = El::double('el-descriptions')->setAttrs(
            // 空字符串具备语义（如 border 表示无值布尔属性），只过滤 null
            array_filter($descriptionsAttrs, static fn(mixed $value): bool => $value !== null)
        );

        foreach ($descriptions->getItems() as $item) {
            $value = $item->getValue();
            $itemEl = El::double('el-descriptions-item')->setAttr('label', $item->getLabel());

            if ($value instanceof Renderable) {
                // 媒体/子组件作为详情项值时走主题渲染，可正确触发运行时注入与事件绑定
                $itemEl->append($context->theme()->render($value, $context));
            } else {
                $itemEl->append((string) $value);
            }

            $element->append($this->applyComponentRootAttributes($itemEl, $item));
        }

        // 无 title：组件根属性 + 事件都挂在 el-descriptions，行为不变
        if ($title === null) {
            return $this->applyComponentEvents(
                $this->applyComponentRootAttributes($element, $descriptions),
                $descriptions,
                $eventContextExpression,
                $context
            );
        }

        // 有 title：外层包一层 wrapper，标题走与 Card header 一致的小竖条节点
        // 此时组件自身的根属性（class/style 等）挂在 wrapper、事件也挂在 wrapper，避免重复
        $wrapper = El::double('div')->addClass('sc-v2-descriptions');
        $wrapper->append(
            El::double('div')->addClass('sc-v2-section__header')->append($title)
        );
        $wrapper->append($element);
        $wrapper = $this->applyComponentRootAttributes($wrapper, $descriptions);

        return $this->applyComponentEvents($wrapper, $descriptions, $eventContextExpression, $context);
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
        ?string $eventContextExpression = null,
        ?RenderContext $renderContext = null
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
                $renderContext !== null
                    ? sprintf(
                        'runPageEventHandlers(%s, %s)',
                        $this->jsString(
                            (new PageRuntimeRegistry($renderContext))->registerPageEventHandlers(array_values($handlers))
                        ),
                        $contextExpression
                    )
                    : sprintf(
                        'runPageEventHandlers(%s, %s)',
                        $this->jsValue(array_values($handlers)),
                        $contextExpression
                    )
            );
        }

        return $element;
    }

    private function applyComponentRootAttributes(AbstractHtmlElement $element, object $component): AbstractHtmlElement
    {
        if (!method_exists($component, 'getRenderAttributes')) {
            return $element;
        }

        /** @var array<string, mixed> $attrs */
        $attrs = $component->getRenderAttributes();

        return $this->applyRenderableAttributes($element, $attrs);
    }

    private function resolveRendererMethod(Renderable $component): ?string
    {
        return $this->resolveClassMappedMethod($component, self::RENDERERS);
    }
}
