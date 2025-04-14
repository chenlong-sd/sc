<?php

namespace Sc\Util\HtmlStructure\Html\Js\VueComponents;

use Sc\Util\HtmlElement\El;
use Sc\Util\HtmlStructure\Html\Html;
use Sc\Util\HtmlStructure\Html\Js;
use Sc\Util\HtmlStructure\Html\Js\JsCode;
use Sc\Util\HtmlStructure\Html\Js\JsFunc;
use Sc\Util\HtmlStructure\Html\Js\Grammar;
use Sc\Util\HtmlStructure\Html\Js\JsVar;

/**
 * Class IconSelector
 */
class IconSelector implements VueComponentInterface
{
    public function getName(): string
    {
        return 'icon-selector';
    }

    public function register(string $registerVar): string
    {
        Html::html()->find('body')->after(El::fromCode($this->template()));

        $config = [
            'template' => Grammar::mark("document.getElementById('vue--icon-selector').innerHTML"),
            'props'    => ['modelValue'],
            'emits'    => ['update:modelValue'],
            'data'     => JsFunc::anonymous()->code(
                Js::code(Js::let('icons', []))
                    ->then(<<<JS
                        for (const [key, component] of Object.entries(ElementPlusIconsVue)) {
                            icons.push({ value: key })
                        }
                    JS)
                    ->then("return {   icons: icons,iconv: this.modelValue,pv:false}")
            ),
            'methods'  => [
                'selected' => JsFunc::anonymous(['value'])->code(
                    Js::code(Js::assign('this.iconv', '@value'))
                        ->then(Js::assign('this.pv', '@false'))
                        ->then("this.\$emit('update:modelValue', this.iconv)")
                )
            ],
            'watch' => [
                'modelValue' => JsFunc::anonymous(['newValue', 'oldValue'])->code('this.iconv = newValue;')
            ]
        ];

        return JsFunc::call("$registerVar.component", $this->getName(), $config)->toCode();
    }

    private function template()
    {
        return <<<HTML
        <script type="text/html" id="vue--icon-selector">
            <el-popover
                    ref="popover"
                    :width="600"
                    v-model:visible="pv"
                    trigger="click"
                    placement="bottom-start"
            >
                <template #reference>
                    <el-input v-model="iconv" style="width: 300px" placeholder="选择图标" clearable>
                        <template #prefix>
                            <el-icon class="el-input__icon"><component v-if="iconv" :is="iconv"></component></el-icon>
                        </template>
                    </el-input>
                </template>
                <el-scrollbar max-height="350px">
                    <el-space v-for="ic in icons" >
                        <div class="icon-select-box" @click.stop="selected(ic.value)" style="width: 100px;text-align: center;margin-bottom: 3px;">
                            <div style="font-size: 20px"><el-icon><component :is="ic.value"></component></el-icon></div>
                            <el-text class="mx-1" type="info" size="small">{{ ic.value }}</el-text>
                        </div>
                    </el-space>
                </el-scrollbar>
            </el-popover>
        </script>
        <style>
            .icon-select-box:hover{
                background-color: #eeeeeeee;
                cursor: pointer;
            }
        </style>
        HTML;

    }
}