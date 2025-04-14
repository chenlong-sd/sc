<?php

namespace Sc\Util\HtmlStructure\Html\Js\VueComponents;

/**
 * Class ElementIcon
 */
class ElementIcon implements VueComponentInterface
{

    public function register(string $registerVar): string
    {
        return <<<JS
            for (const [key, component] of Object.entries(ElementPlusIconsVue)) {
                $registerVar.component(key, component)
            }
        JS;
    }

    public function getName(): string
    {
        return 'element-icon';
    }
}