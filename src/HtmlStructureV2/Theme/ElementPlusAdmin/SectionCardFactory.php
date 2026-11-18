<?php

namespace Sc\Util\HtmlStructureV2\Theme\ElementPlusAdmin;

use Sc\Util\HtmlElement\El;
use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlElement\ElementType\DoubleLabel;

/**
 * 统一构建“分区卡片”结构，表单 section 与 Layouts::card 共用同一套实现。
 * 结构：el-card.sc-v2-form-section-card > .sc-v2-form-section > .sc-v2-section__header + .sc-v2-form-section__body
 */
final class SectionCardFactory
{
    public function card(): DoubleLabel
    {
        return El::double('el-card')->addClass('sc-v2-form-section-card');
    }

    public function container(string $title = ''): DoubleLabel
    {
        $container = El::double('div')->addClass('sc-v2-form-section');

        if ($title !== '') {
            $container->append(
                El::double('div')->addClass('sc-v2-section__header')->append($title)
            );
        }

        $container->append(El::double('div')->addClass('sc-v2-form-section__body'));

        return $container;
    }

    public function make(string $title = ''): DoubleLabel
    {
        return $this->card()->append($this->container($title));
    }

    public function containerOf(AbstractHtmlElement $host): ?DoubleLabel
    {
        if ($host instanceof DoubleLabel && $host->hasClass('sc-v2-form-section')) {
            return $host;
        }

        if (! method_exists($host, 'getChildren')) {
            return null;
        }

        foreach ($host->getChildren() as $child) {
            if ($child instanceof DoubleLabel && $child->hasClass('sc-v2-form-section')) {
                return $child;
            }
        }

        return null;
    }

    public function bodyOf(AbstractHtmlElement $host): ?DoubleLabel
    {
        $container = $this->containerOf($host);
        if ($container === null) {
            return null;
        }

        foreach ($container->getChildren() as $child) {
            if ($child instanceof DoubleLabel && $child->hasClass('sc-v2-form-section__body')) {
                return $child;
            }
        }

        return null;
    }
}