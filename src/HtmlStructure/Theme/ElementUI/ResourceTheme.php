<?php
/**
 * datetime: 2023/5/28 2:58
 **/

namespace Sc\Util\HtmlStructure\Theme\ElementUI;

use Sc\Util\HtmlStructure\Html\Html;
use Sc\Util\HtmlStructure\Html\Js\Grammar;
use Sc\Util\HtmlStructure\Html\Js\JsCode;
use Sc\Util\HtmlStructure\Html\Js\JsFunc;
use Sc\Util\HtmlStructure\Html\Js\JsLog;
use Sc\Util\HtmlStructure\Html\Js\JsVar;
use Sc\Util\HtmlStructure\Html\Js\VueComponents\ElementIcon;
use Sc\Util\HtmlStructure\Html\StaticResource;
use Sc\Util\HtmlStructure\Theme\Interfaces\ResourceThemeInterface;

/**
 * Class ThemeResource
 *
 * @package Sc\Util\HtmlStructure\Theme\ElementUI
 * @date    2023/5/28
 */
class ResourceTheme implements ResourceThemeInterface
{
    public function load(): void
    {
        // 引入 ElementPlus, 这里先引入 ElementPlus 的原因是保证 Vue 在ElementPlus之前初始化并加载
        Html::js()->vue->use(['@ElementPlus', [
            'locale' => Grammar::mark("ElementPlusLocaleZhCn"),
        ]]);

        // 加载ElementUI的CDN资源
        Html::css()->load(StaticResource::ELEMENT_PLUS_CSS);
        Html::js()->load(StaticResource::ELEMENT_PLUS_ICON);
        Html::js()->load(StaticResource::ELEMENT_PLUS_JS);
        Html::js()->load(StaticResource::ELEMENT_PLUS_LANG);
        Html::js()->vue->addComponents(new ElementIcon());

        $this->utilMethodDef();
    }

    /**
     * @return void
     */
    private function utilMethodDef(): void
    {
        // 地址注入搜索参数
        Html::js()->defFunc("urlInjectSearch", ['url', 'tableId'], JsCode::make(
            JsCode::create(<<<JS
            function buildQuery(obj, parentPrefix = null) {
              var parts = [];
              for (let key in obj) {
                if (obj.hasOwnProperty(key)) {
                  let propName = parentPrefix ? `\${parentPrefix}[\${encodeURIComponent(key)}]` : encodeURIComponent(key);
                  let value = obj[key];
                  
                  if (!value) continue;
                  
                  if (typeof value === 'object' && !(value instanceof Date) && !(value instanceof File)) {
                    parts.push(buildQuery(value, propName));
                  } else {
                    parts.push(propName + '=' + encodeURIComponent(value));
                  }
                }
              }
              return parts.join('&');
            }
            JS),
            JsVar::def('urlObj', '@new URL(url)'),
            JsVar::def("search", JsFunc::call('buildQuery', [
                'search' => [
                    "search"      => Grammar::mark('VueApp[`${tableId}Search`]'),
                    "searchType"  => Grammar::mark('VueApp[`${tableId}SearchType`]'),
                    "searchField" => Grammar::mark('VueApp[`${tableId}SearchField`]'),
                ]
            ])),
            JsCode::create("return  urlObj.origin + urlObj.pathname + '?' +search + urlObj.hash;")
        ));
    }
}