<?php

namespace Sc\Util\HtmlStructureV2\Components\Concerns;

trait HasRenderAttributes
{
    private array $renderAttributes = [];

    /**
     * 设置当前节点渲染根元素的单个属性。
     * 属性名会原样输出到最终 HTML / 组件根节点上；动态绑定请自行写成 `":prop"` 或 `"@event"`。
     * 其中 `class` / `style` 会与同一节点上此前通过该方法设置的值自动合并；
     * 传 null 可移除该属性；非动态属性传 true 时会按布尔属性输出。
     *
     * @param string $name 属性名。
     * @param mixed $value 属性值。
     * @return static 当前节点实例。
     *
     * 示例：
     * `Blocks::button('查看')->attr(':disabled', 'loading')`
     */
    public function attr(string $name, mixed $value = ''): static
    {
        $name = trim($name);
        if ($name === '') {
            return $this;
        }

        if ($value === null || (!str_starts_with($name, ':') && $value === false)) {
            unset($this->renderAttributes[$name]);

            return $this;
        }

        if (!str_starts_with($name, ':') && $value === true) {
            $value = '';
        }

        if (($name === 'class' || $name === 'style') && is_string($value)) {
            $value = $this->mergeRenderAttributeString($name, $value);
        }

        $this->renderAttributes[$name] = $value;

        return $this;
    }

    /**
     * 批量设置当前节点渲染根元素属性。
     * 规则与 attr() 一致；键名按原样输出，动态绑定请自行写成 `":prop"` / `"@event"`。
     *
     * @param array $attributes 要设置的属性集合。
     * @return static 当前节点实例。
     *
     * 示例：
     * `Layouts::stack()->attrs(['data-role' => 'summary', ':loading' => 'loading'])`
     */
    public function attrs(array $attributes): static
    {
        foreach ($attributes as $name => $value) {
            if (!is_string($name)) {
                continue;
            }

            $this->attr($name, $value);
        }

        return $this;
    }

    /**
     * 追加当前节点渲染根元素的 class。
     * 多次调用会自动合并，适合补充业务 class 标识。
     *
     * @param string|null $className class 名称；传 null 表示移除此前通过该快捷方法设置的 class。
     * @return static 当前节点实例。
     *
     * 示例：
     * `Forms::section('基础信息')->className('profile-section')`
     */
    public function className(?string $className): static
    {
        return $this->attr('class', $className);
    }

    /**
     * 追加当前节点渲染根元素的 style。
     * 多次调用会自动按分号拼接，适合补充少量就地样式。
     *
     * @param string|null $style 内联样式；传 null 表示移除此前通过该快捷方法设置的 style。
     * @return static 当前节点实例。
     *
     * 示例：
     * `Blocks::text('说明')->style('color:#909399;font-size:12px')`
     */
    public function style(?string $style): static
    {
        return $this->attr('style', $style);
    }

    public function getRenderAttributes(): array
    {
        return $this->renderAttributes;
    }

    private function mergeRenderAttributeString(string $name, string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return $this->renderAttributes[$name] ?? '';
        }

        $current = (string)($this->renderAttributes[$name] ?? '');
        $current = trim($current);
        if ($current === '') {
            return $value;
        }

        if ($name === 'style') {
            return rtrim($current, '; ') . '; ' . ltrim($value, '; ');
        }

        return trim($current . ' ' . $value);
    }
}
