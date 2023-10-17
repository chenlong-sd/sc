<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */
namespace Sc\Util\Tool;

use JetBrains\PhpStorm\Language;
use JetBrains\PhpStorm\Pure;
use Sc\Util\Exception\StopHtmlDocumentEachException;

/**
 * Class Html.
 * @date 2022/2/20
 */
class HtmlDocument
{
    private const DEFAULT_SINGLE_TAG = ['img', 'link', 'meta', 'hr', 'br'];

    /**
     * 单标签集合.
     *
     * @var array|string[]
     */
    private static array $singleTag = [];

    /**
     * 元素的id值
     */
    private string $id = '';

    /**
     * 元素的属性.
     */
    private array $attr = [];

    /**
     * 元素的类.
     */
    private array $class = [];

    /**
     * 子元素.
     *
     * @var array|HtmlDocument[]
     */
    private array $childrenElement = [];

    /**
     * 纯文本dom的文本内容.
     */
    private string|null $htmlText = null;

    public int $level = 0;

    /**
     * @var callable[]
     */
    private array|null $processingBeforeOutput = [];

    /**
     * 父级元素.
     */
    private ?HtmlDocument $parent = null;

    /**
     * Html constructor.
     * @param string $tag
     * @param bool $isASingleTag
     * @date 2022/2/20
     */
    public function __construct(private string $tag = '', private bool $isASingleTag = false)
    {
    }

    /**
     * @date 2022/2/20
     */
    public function __toString(): string
    {
        return $this->output();
    }

    /**
     * @param string $tag
     * @param bool $isASingleLabel
     * @return HtmlDocument
     * @date 2022/2/24
     */
     #[Pure]
     public static function create(string $tag = '', bool $isASingleLabel = false): HtmlDocument
     {
         return new self($tag, $isASingleLabel);
     }

    /**
     * 创建一个纯文本的dom.
     *
     * @return $this|HtmlDocument
     * @date 2022/2/24
     */
    public static function createText(string|int $text): HtmlDocument|static
    {
        return self::create()->setHtmlText($text);
    }

    /**
     * 用html代码解析出的 dom 对象
     *
     * @param string|\Stringable|int $htmlCode
     * @param array                  $declarationLabel 声明单标签
     *
     * @return $this
     * @date 2022/2/24
     */
    public static function fromCode(#[Language('HTML')] string|\Stringable|int $htmlCode, array $declarationLabel = []): static
    {
        self::$singleTag = array_merge(self::DEFAULT_SINGLE_TAG, $declarationLabel);

        $htmlCode = $htmlCode === 0 ? "0" : (string)$htmlCode;

        [$children, $_] = self::codeResolve(self::annotationFiltering($htmlCode));

        self::$singleTag = self::DEFAULT_SINGLE_TAG;

        return count($children) === 1 ? current($children) : self::create()->setElement($children);
    }

    /**
     * 设置标签内容.
     *
     * @param array|HtmlDocument[] $elements
     * @return HtmlDocument
     * @date 2022/2/20
     */
    public function setElement(array $elements): static
    {
        $this->childrenElement = [];
        array_map(fn ($element) => $this->append($element), $elements);

        return $this;
    }

    /**
     * 追加元素.
     *
     * @param HtmlDocument|int|string|\Stringable ...$elements
     *
     * @return HtmlDocument
     * @date 2022/2/20
     */
    public function append(#[Language('Vue')] HtmlDocument | int | string | \Stringable ...$elements): static
    {
        foreach ($elements as $element) {
            $doc = $this->getHtmlDocument($element);
            $doc->parent = $this;
            $this->childrenElement[] = $doc;
        }

        return $this;
    }

    /**
     * 再前面追加元素.
     *
     * @param HtmlDocument|int|string|\Stringable ...$elements
     *
     * @return $this
     */
    public function prepend(#[Language('Vue')] HtmlDocument | int | string | \Stringable ...$elements): static
    {
        array_unshift($this->childrenElement, array_map(function ($element) {
            $doc = $this->getHtmlDocument($element);
            $doc->parent = $this;
            return $doc;
        }, $elements));

        return $this;
    }

    /**
     * 根据选择器删除对应的元素,更复杂的选择判断请用 "HtmlDocument::each()".
     *
     * 选择器支持：
     * "abc"        标签为bac
     * "#abc"       id为bac
     * ".abc"       class 包含abc
     * "[abc=def]"  包含abc属性且值等于def
     * "[abc]"      包含abc属性
     * 以上选择器可联合使用：
     * "abc#abc.abc[abc=def][abc]"
     * @param \Closure|string $selector 选择器
     * @return HtmlDocument
     * @date 2022/3/2
     */
    public function removeElement(#[Language('JQuery-CSS')] string | \Closure $selector): static
    {
        try {
            $this->recursionEach(function (HtmlDocument $htmlDocument, HtmlDocument $parent, int $index) {
                unset($parent->childrenElement[$index]);
            }, $this->tmp(), $this->selectorResolve($selector));
        } catch (StopHtmlDocumentEachException) {
        }

        return $this;
    }

    /**
     * 设置ID.
     *
     * @return $this
     * @date 2022/2/20
     */
    public function setId(string $id): static
    {
        $this->id = $id;

        return $this->setAttr('id', $id);
    }

    /**
     * 添加data属性.
     *
     * @return $this
     * @date 2022/2/25
     */
    public function addData(string|array $data, string $value = ''): static
    {
        is_string($data) and $data = [$data => $value];

        foreach ($data as $k => $v) {
            $this->setAttributes("data-{$k}", $v);
        }

        return $this;
    }

    /**
     * 删除data属性.
     *
     * @return $this
     * @date 2022/2/20
     */
    public function removeData(string|array $data): static
    {
        $data = is_string($data) ? "^data-{$data}" : array_map(fn ($v) => "data-{$v}", $data);

        return $this->removeAttr($data);
    }

    /**
     * 添加class.
     *
     * @return $this
     * @date 2022/2/20
     */
    public function addClass(string|array $class): static
    {
        if (is_array($class)) {
            array_map(fn ($class_) => $this->setAttributes('class', $class_), $class);

            return $this;
        }

        return $this->setAttributes('class', $class);
    }

    /**
     * 删除class.
     *
     * @return $this
     * @date 2022/2/20
     */
    public function removeClass(string|array $class): static
    {
        is_string($class) and $class = array_filter(explode(' ', $class));
        $newClass = implode(' ', array_diff($this->class, $class));
        $this->class = [];
        $this->setAttr('class', $newClass);

        return $this;
    }

    /**
     * 添加 Style
     * 为友好提示，可使用 tag{css}, 例如标签是 div 可写作 div{color:red;}
     *
     * @param string $style
     *
     * @return $this
     */
    public function addStyle(#[Language('CSS')] string $style): static
    {
        if (str_contains($style, '{')) {
            $style = preg_replace("/^[\w\-]+\{(.+)}$/s", '$1', $style);
        }

        $this->attr['style'] = sprintf('%s%s%s', $this->attr['style'] ?? '', isset($this->attr['style']) ? ';' : '', $style);

        return $this;
    }

    /**
     * 设置style
     * 为友好提示，可使用 tag{css}, 例如标签是 div 可写作 div{color:red;}
     *
     * @param string $style
     *
     * @return HtmlDocument|$this
     */
    public function setStyle(#[Language('CSS')] string $style): HtmlDocument|static
    {
        if (str_contains($style, '{')) {
            $style = preg_replace("/^[\w\-]+\{(.+)}$/s", '$1', $style);
        }

        return $this->setAttr('style', $style);
    }

    /**
     * 设置属性.
     *
     * @param array|string $attr
     * @param mixed        $value
     * @param bool         $isAppend
     *
     * @return HtmlDocument
     * @date   2022/2/20
     */
    public function setAttr(array|string $attr, mixed $value = '', bool $isAppend = false): static
    {
        if (is_array($attr)) {
            foreach ($attr as $key => $value) {
                $this->setAttr($key, $value);
            }

            return $this;
        }

        return $this->setAttributes($attr, $value, $isAppend);
    }

    /**
     * 设置属性.
     *
     * @return $this
     */
    public function setAttrIfNotExists(string $attr, mixed $value = ''): static
    {
        $this->hasAttr($attr) or $this->setAttributes($attr, $value);

        return $this;
    }

    /**
     * 删除属性.
     *
     * @param array|string $attr
     * @return $this
     * @date 2022/2/20
     */
    public function removeAttr(#[Language('RegExp')] string | array $attr = '*'): static
    {
        $this->attr = is_array($attr)
            ? array_diff_key($this->attr, array_flip($attr))
            : array_diff_key($this->attr, $this->getAttrs($attr));

        return $this;
    }

    /**
     * 设置为单标签.
     *
     * @return $this
     * @date 2022/2/20
     */
    public function setIsASingleTag(bool $isASingleTag): static
    {
        $this->isASingleTag = $isASingleTag;
        return $this;
    }

    public function getTag(): string
    {
        return $this->tag;
    }

    /**
     * @return $this
     * @date 2022/2/27
     */
    public function setTag(string $tag): static
    {
        $this->tag = $tag;
        return $this;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getHtmlText(): ?string
    {
        return $this->htmlText;
    }

    /**
     * 获取属性值
     */
    public function getAttr(string $attr, string $default = null): string|null
    {
        return $this->attr[$attr] ?? $default;
    }

    /**
     * @date 2022/2/28
     */
    public function hasAttr(string $attr): bool
    {
        return isset($this->attr[$attr]);
    }

    /**
     * 获取属性 支持正则
     * eg: /^slot/
     * 也可以以下方式
     * "*" 标识任意数量的任意字符
     * "^" 在头部，标识以什么开始
     * "$" 在尾部，标识以什么结束
     * ^slot slot$.
     *
     * @param string $attr
     *
     * @return array
     * @date    2022/2/27
     *
     * @example "^data-" 所有的data
     */
    public function getAttrs(#[Language('PhpRegExp')] string $attr = '*'): array
    {
        $return = [];

        if ($attr === '*') {
            return $this->attr;
        }

        $attr = strtr($attr, ['*' => '(.*)']);
        foreach ($this->attr as $attrs => $value) {
            preg_match(str_starts_with($attr, '/') ? $attr : ('/' . $attr . '/'), $attrs) and $return[$attrs] = $value;
        }

        return $return;
    }

    public function getClass(): array
    {
        return $this->class;
    }

    /**
     * @date 2022/3/1
     */
    public function hasClass(string $class): bool
    {
        return in_array($class, $this->class);
    }

    /**
     * 获取data属性.
     */
    public function getData(string $key = '*'): array
    {
        return $this->getAttrs("^data-{$key}");
    }

    public function isASingleTag(): bool
    {
        return $this->isASingleTag;
    }

    /**
     * 获取子dom.
     *
     * @param null|\Closure|string $filter
     * @return HtmlDocument[]
     * @date 2022/2/24
     */
    public function getChildrenElement(#[Language('JQuery-CSS')] \Closure | string $filter = null): array
    {
        return $filter ? array_values(array_filter($this->childrenElement, current($this->selectorResolve($filter)))) : $this->childrenElement;
    }

    public function getContent(): string
    {
        return implode($this->childrenElement);
    }

    /**
     * 获取元素
     * 选择器支持：
     * "abc"        标签为bac
     * "#abc"       id为bac
     * ".abc"       class 包含abc
     * "[abc=def]"  包含abc属性且值等于def
     * "[abc]"      包含abc属性
     * 以上选择器可联合使用：
     * "abc#abc.abc[abc=def][abc]".
     * @param null|\Closure|string $filter
     * @return null|HtmlDocument|HtmlDocument[]
     * @date 2022/3/1
     */
    public function getElement(#[Language('JQuery-CSS')] \Closure | string $filter = null, bool $isAll = false): HtmlDocument|array|null
    {
        $element = [];

        try {
            $this->recursionEach(function (HtmlDocument $htmlDocument) use ($isAll, &$element) {
                $element[] = $htmlDocument;
                $isAll or $this->stopEach();
            }, $this->tmp(), $this->selectorResolve($filter));
        } catch (StopHtmlDocumentEachException) {
        }

        return $element ? ($isAll ? $element : current($element)) : null;
    }

    /**
     * 输出html代码
     * @date 2022/2/20
     */
    public function output(): string
    {
        if ($this->processingBeforeOutput) {
            sort($this->processingBeforeOutput);
            array_map(fn ($handle) => call_user_func($handle, $this), $this->processingBeforeOutput);
        }

        /*
         * 没有标签， 认定为一个合并其他多项元素的dom,为保持子集的缩进和同级元素一致， 缩进级别减一
         */
        if ($this->parent) {
            $this->level = $this->tag ? ($this->parent->level + 1) : $this->parent->level;
        }

        $indentation = str_repeat(' ', max($this->level, 0) * 4);
        $children = implode($this->getChildrenElement());

        if ($this->htmlText !== null) {
            return $indentation . $this->htmlText . "\n";
        }
        if (! $this->tag) {
            return $children;
        }

        $attrString = $this->makeElementAttr();

        return $this->isASingleTag()
            ? sprintf('%s<%s %s>%s', $indentation, $this->tag, $attrString, "\n")
            : sprintf('%s<%s %s>%s%s</%2$s>%s', $indentation, $this->tag, $attrString, $children ? "\n{$children}" : '', $children ? $indentation : '', "\n");
    }

    /**
     * 循环处理每一个Dom.
     *
     * @return $this
     * @date 2022/2/25
     */
    public function each(callable $each): static
    {
        try {
            $this->recursionEach($each, $this->tmp());
        } catch (StopHtmlDocumentEachException) {
        }

        return $this;
    }

    /**
     * 输出之前的处理.
     *
     * @return $this
     */
    public function outputBefore(callable $processing, int $index = null): static
    {
        $index
            ? $this->processingBeforeOutput[$index] = $processing
            : $this->processingBeforeOutput[] = $processing;

        return $this;
    }

    /**
     * 根据选择器更新对应的元素,更复杂的选择判断请用 "HtmlDocument::each()".
     *
     * 选择器支持：
     * "#abc"       id为bac
     * ".abc"       class 包含abc
     * "[abc=def]"  包含abc属性且值等于def
     * "[abc]"      包含abc属性
     * 以上选择器可联合使用：
     * #abc.abc[abc=def][abc]
     *
     * @param \Closure|string $selector
     * @param callable        $callable
     *
     * @return HtmlDocument
     * @date 2022/3/2
     */
    public function updateElement(#[Language('JQuery-CSS')] string | \Closure $selector, callable $callable): static
    {
        try {
            $this->recursionEach($callable, $this->tmp(), $this->selectorResolve($selector));
        } catch (StopHtmlDocumentEachException) {
        }

        return $this;
    }

    /**
     * 停止循环.
     *
     * @throws StopHtmlDocumentEachException
     * @date 2022/2/26
     */
    public function stopEach()
    {
        throw new StopHtmlDocumentEachException();
    }

    /**
     * 在后面插入元素.
     *
     * @return $this
     */
    public function after(string|HtmlDocument $htmlDocument): static
    {
        return $this->addBrother($htmlDocument);
    }

    /**
     * 在前面插入元素.
     *
     * @return $this|HtmlDocument
     */
    public function before(string|HtmlDocument $htmlDocument): HtmlDocument|static
    {
        return $this->addBrother($htmlDocument, false);
    }

    /**
     * 在指定的dom后面插入dom.
     *
     * @param \Closure|string                     $selector     指定规则的匿名函数
     * @param HtmlDocument|string|int|\Stringable $htmlDocument 插入的dom或字符串
     * @param bool                                $isAll        是否查找所有匹配的
     *
     * @return HtmlDocument
     * @date 2022/2/25
     */
    public function afterInsertingElement(#[Language('JQuery-CSS')] \Closure | string $selector, HtmlDocument|string|int | \Stringable $htmlDocument, bool $isAll = true): static
    {
        $elements = $this->getElement($selector, $isAll);
        if ($elements) {
            $elements = $isAll ? $elements : [$elements];
            array_map(fn ($element) => $element->after($htmlDocument), $elements);
        }
        return $this;
    }

    /**
     * 获取父级.
     */
    public function parent(): ?HtmlDocument
    {
        return $this->parent;
    }

    /**
     * 寻早指定得祖辈级.
     *
     * @param \Closure|string $selector
     *
     * @return HtmlDocument|null
     */
    public function parents(#[Language('JQuery-CSS')] \Closure | string $selector): ?HtmlDocument
    {
        $filter = current($this->selectorResolve($selector));
        $parent = $this->parent;

        while ($parent && ! call_user_func($filter, $parent)) {
            $parent = $parent->parent;
        }

        return $parent;
    }

    /**
     * 在指定的dom前面插入dom.
     *
     * @param \Closure|string                     $selector     指定规则的匿名函数
     * @param HtmlDocument|string|int|\Stringable $htmlDocument 插入的dom或字符串
     * @param bool                                $isAll        是否查找所有匹配的
     *
     * @return $this
     * @date 2022/2/25
     */
    public function beforeInsertingElement(#[Language('JQuery-CSS')] \Closure | string $selector, HtmlDocument|string|int | \Stringable $htmlDocument, bool $isAll = true): static
    {
        $elements = $this->getElement($selector, $isAll);
        if ($elements) {
            $elements = $isAll ? $elements : [$elements];
            array_map(fn ($element) => $element->before($htmlDocument), $elements);
        }
        return $this;
    }

    /**
     * 设置当前为纯文字html.
     *
     * @return $this
     * @date 2022/3/1
     */
    public function setHtmlText(string|null $htmlText): static
    {
        $this->htmlText = $htmlText;
        $this->setTag('text');

        return $this;
    }

    /**
     * 代码解析.
     */
    private static function codeResolve(string $code, string $parentTag = null): array
    {
        $collectsOfElement = [];
        while ($code !== '') {
            if (preg_match('/^<(?<tag>[\w\-]+)(?<attr>((\S+=(?<q>[\'"]).*?\k<q>)|[^<>\'"\/])*)(?<single>\/?)>/s', $code, $match)) {
                $code = preg_replace('/^<[\w\-]+((\S+=(?<q>[\'"]).*?\k<q>)|[^<>\'"])*\/?>/s', '', $code);

                $elementDocument = self::create($match['tag'])->setIsASingleTag(in_array($match['tag'], self::$singleTag) || $match['single']);

                $elementDocument->setAttr(self::tagAttrResolve($match['attr']));

                if (! $elementDocument->isASingleTag() && $code) {
                    [$children, $code] = self::codeResolve($code, $match['tag']);

                    $elementDocument->append(...$children);
                }
                $collectsOfElement[] = $elementDocument;
                continue;
            }

            if ($parentTag && preg_match("/^<\\/{$parentTag}>/", $code, $match)) {
                return [$collectsOfElement, preg_replace("/^<\\/{$parentTag}>/", '', $code)];
            }

            if ($parentTag === 'script') {
                preg_match("/^(.*?)<\\/{$parentTag}>/s", $code, $match);
                $collectsOfElement[] = self::createText($match[1]);
                return [$collectsOfElement, preg_replace("/^(.*?)<\\/{$parentTag}>/s", '', $code)];
            }

            if (! preg_match('/^((?!<\/?[\w\-]+).)+/s', $code, $match)) {
                break;
            }

            $code = preg_replace('/^((?!<\/?[\w\-]+).)+/s', '', $code);

            if (($text = trim($match[0])) || $text === '0') {
                $collectsOfElement[] = self::createText($text);
            }
        }

        return [$collectsOfElement, $code];
    }

    /**
     * 过滤注释.
     */
    private static function annotationFiltering(string $code): string
    {
        return preg_replace('/<!--.*?-->/s', '', $code);
    }

    /**
     * 解析标签属性.
     *
     * @date 2022/2/25
     */
    private static function tagAttrResolve(string $attrString): array
    {
        preg_match_all('/(?<=\s)*([^=\s]+)(=([\'"])(.*?[^\\\\])?\3)?/', $attrString, $matches);

        return array_combine($matches[1], $matches[4]);
    }

    private function tmp(): HtmlDocument
    {
        $tmp = self::create();
        $tmp->childrenElement[] = $this;

        return $tmp;
    }

    /**
     * 设置属性.
     *
     * @return $this
     * @date   2022/3/1
     */
    private function setAttributes(string $attributes, mixed $value, bool $isAppend = false): static
    {
        if ($attributes === 'id') {
            $this->id = $value;
        } elseif ($attributes === 'class') {
            $this->class = array_merge($this->class, array_filter(explode(' ', $value)));
            $value = implode(' ', $this->class);
        }

        if ($value === false) {
            unset($this->attr[$attributes]);
        } elseif ($value === true) {
            $this->attr[$attributes] = '';
        } else {
            if (! in_array($attributes, ['id', 'class']) && $isAppend) {
                $value = ($this->attr[$attributes] ?? '') . $value;
            }

            $this->attr[$attributes] = $value;
        }

        return $this;
    }

    /**
     * 附加兄弟元素.
     *
     * @param string|HtmlDocument|\Stringable $htmlDocument
     * @param bool                            $isAfter 是否是后面
     *
     * @return $this|HtmlDocument
     */
    private function addBrother(string|HtmlDocument | \Stringable $htmlDocument, bool $isAfter = true): HtmlDocument|static
    {
        if ($this->parent) {
            $tmpChildren = [];
            foreach ($this->parent->childrenElement as $element) {
                if (! $isAfter && $element === $this) {
                    $tmpChildren[] = $this->getHtmlDocument($htmlDocument);
                }

                $tmpChildren[] = $element;

                if ($isAfter && $element === $this) {
                    $tmpChildren[] = $this->getHtmlDocument($htmlDocument);
                }
            }
            $this->parent->setElement($tmpChildren);
        } else {
            $clone = clone $this;
            foreach ($this->childrenElement as $children) {
                $children->parent = $clone;
            }
            $this->tag = '';
            $isAfter
                ? $this->setElement([$clone, $this->getHtmlDocument($htmlDocument)])
                : $this->setElement([$this->getHtmlDocument($htmlDocument), $clone]);
        }

        return $this;
    }

    /**
     * 根据传入的对象，返回一个HtmlDocument对象
     *
     * @param string|int|HtmlDocument|\Stringable $htmlDocument
     *
     * @return $this|HtmlDocument
     * @date 2022/3/1
     */
    private function getHtmlDocument(string|int|HtmlDocument|\Stringable $htmlDocument): HtmlDocument|static
    {
        if ($htmlDocument instanceof HtmlDocument) {
            return $htmlDocument;
        }

        return self::fromCode($htmlDocument instanceof \Stringable ? (string)$htmlDocument : $htmlDocument);
    }

    /**
     * 构建HTML代码的HTML标签属性.
     *
     * @date 2022/2/20
     */
    private function makeElementAttr(): string
    {
        $attr = [];
        foreach ($this->attr as $key => $value) {
            $attr[] = $value !== '' ? sprintf('%s="%s"', $key, $value) : $key;
        }

        return implode(' ', array_filter($attr));
    }

    /**
     * 递归的循环处理每一个对象
     *
     * @param callable[] $filter
     * @date 2022/3/3
     */
    private function recursionEach(callable $handle, HtmlDocument $htmlDocument, array $filter = [])
    {
        foreach ($htmlDocument->childrenElement as $index => $childrenElement) {
            $tmpFilter = $filter;

            /**
             * 没有过滤条件时，从最底层开始执行，避免陷入死循环
             */
            if (! $tmpFilter) {
                $this->recursionEach($handle, $childrenElement, $filter);
                call_user_func($handle, $childrenElement, $htmlDocument, $index);
                continue;
            }

            /**
             * 有过滤条件的时候
             * 取出当前的过滤规则，
             * 判断成功： 还有下一个规则 ，往子集寻找否则执行处理函数
             * 判断失败： 往子集寻找.
             */
            $currentFilter = array_shift($tmpFilter);
            if (call_user_func($currentFilter, $childrenElement, $index)) {
                $tmpFilter
                    ? $this->recursionEach($handle, $childrenElement, $tmpFilter)
                    : call_user_func($handle, $childrenElement, $htmlDocument, $index);
            } else {
                $this->recursionEach($handle, $childrenElement, $filter);
            }
        }
    }

    /**
     * 选择器|过滤器 解析.
     *
     * @param \Closure|string $selector
     * @return callable[]
     * @date 2022/3/3
     */
    private function selectorResolve(#[Language('JQuery-CSS')] \Closure | string $selector): array
    {
        if ($selector instanceof \Closure) {
            return [$selector];
        }

        /**
         * 看看是否有空格，找后代.
         */
        $selector = preg_replace('/\s+/', ' ', trim($selector));

        $selectorFilters = [];

        foreach (explode(' ', $selector) as $selectorItem) {
            /*
             * 匹配选择器
             */
            preg_match_all('/^[\w\-]*|#[\w\-]+|\.[\w\-]+|\[[^\s=]+(?:=.*?)?]/', $selectorItem, $match);
            $matchRule = array_filter($match[0]);

            $selectorFilters[] = function (HtmlDocument $htmlDocument) use ($matchRule) {
                try {
                    foreach ($matchRule as $matchItem) {
                        switch (substr($matchItem, 0, 1)) {
                            case '#':
                                self::assert($htmlDocument->getId() === substr($matchItem, 1));
                                break;
                            case '.':
                                self::assert(in_array(substr($matchItem, 1), $htmlDocument->class));
                                break;
                            case '[':
                                $attrInfo = trim($matchItem, '[]');
                                if (str_contains($attrInfo, '=')) {
                                    [$key, $value] = explode('=', $attrInfo);
                                    self::assert($htmlDocument->getAttr($key) === $value);
                                } else {
                                    self::assert($htmlDocument->hasAttr($attrInfo));
                                }
                                break;
                            default:
                                self::assert($htmlDocument->getTag() === $matchItem);
                        }
                    }
                } catch (\AssertionError) {
                    return false;
                }

                return (bool) $matchRule;
            };
        }

        return $selectorFilters;
    }

    /**
     * @date 2022/3/3
     */
    private static function assert(bool $expression)
    {
        $expression or throw new \AssertionError();
    }
}
