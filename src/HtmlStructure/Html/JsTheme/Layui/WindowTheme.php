<?php
/**
 * datetime: 2023/6/2 0:04
 **/

namespace Sc\Util\HtmlStructure\Html\JsTheme\Layui;

use Sc\Util\HtmlStructure\Html\Html;
use Sc\Util\HtmlStructure\Html\Js\Grammar;
use Sc\Util\HtmlStructure\Html\Js\JsCode;
use Sc\Util\HtmlStructure\Html\Js\JsFunc;
use Sc\Util\HtmlStructure\Html\Js\JsVar;
use Sc\Util\HtmlStructure\Html\Js\Window;
use Sc\Util\HtmlStructure\Html\JsTheme\Interfaces\WindowThemeInterface;

class WindowTheme implements WindowThemeInterface
{

    public function render(Window $window): string
    {
        $config     = $window->getConfig();
        if (isset($config['width']) && empty($config['area'])){
            $config['area'] = [$config['width'], '90%'];
        }

        $code = JsCode::create('// 打开弹窗')->then(
            JsVar::def('url', $window->getUrl()),
            JsVar::def('query', $window->getQuery()),
        );

        $this->urlHandle($code);

        $baseConfig = is_null($window->getContent())
            ? ['type' => 2, 'area' => ['90%', '90%'], 'content' => Grammar::mark('url')]
            : ['type' => 1, 'content' => $window->getContent()];

        $baseConfig['anim'] = 5;

        $baseConfig = array_merge($baseConfig, $config);

        $baseConfig['title']  = $window->getTitle();
        $baseConfig['maxmin'] = true;

        $originCode = '';
        if (!empty($baseConfig['success']) && $baseConfig['success'] instanceof JsFunc) {
            $originCode = $baseConfig['success']->code;
        }

        $baseConfig['success'] = JsFunc::anonymous(['layero', 'index', 'that'], <<<JS
            layero.find('.layui-layer-content').css('background', 'white')
            let childrenWin = window[layero.find('iframe')[0]['name']];
            $originCode;
        JS);

        Html::loadThemeResource('Layui');

        return $code->then(JsFunc::call('layer.open', $baseConfig));
    }


    public function urlHandle($code): void
    {
        $code->then(<<<JS
            let parsedUrl = new URL(url);

            parsedUrl.searchParams.forEach((v, k, p) => {
                if (/^@/.test(v) && row.hasOwnProperty(v.substring(1))){
                    p.set(k, row[v.substring(1)]);
                }
            })
            
            for(const key in query){
                let value = query[key];
                if (/^@/.test(value) && row.hasOwnProperty(value.substring(1))){
                    parsedUrl.searchParams.set(key, row[value.substring(1)]);
                    continue;
                }
                parsedUrl.searchParams.set(key, value);
            }
            
           url = parsedUrl.href;
        JS);
    }
}