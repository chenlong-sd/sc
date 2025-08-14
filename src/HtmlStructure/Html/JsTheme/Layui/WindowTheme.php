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
use Sc\Util\HtmlStructure\Html\Js\Layer;
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

        $code = JsCode::create('// 打开弹窗');
        $code->then($window->getBeforeOpen());

        if ($window->getUrl()) {
            $code->then(
                JsVar::def('url', $window->getUrl()),
                JsVar::def('query', array_map(fn($v) => str_starts_with($v, '@') ? strtr($v, ['@' => '@row.']) : $v, $window->getQuery()) ?: '@{}'),
            );

            $this->urlHandle($code);
        }

        $baseConfig = is_null($window->getContent())
            ? ['type' => 2, 'area' => ['90%', '90%'], 'content' => Grammar::mark('url')]
            : ['type' => 1, 'content' => Grammar::mark($window->getContent()->toHtml(), 'line')];

        $baseConfig = array_merge($baseConfig, $config);

        $baseConfig['title']  = Grammar::mark(sprintf('`%s`', preg_replace('/\{(.*?)}/', "\${row.$1}", $window->getTitle())));
        $baseConfig['maxmin'] = true;
        $baseConfig['moveOut'] = true;

        $originCode = $window->getAfterOpen();
        if (!empty($baseConfig['success']) && $baseConfig['success'] instanceof JsFunc) {
            $originCode = $baseConfig['success']->code;
        }

        $code->then(JsVar::def("childrenWin"));
        $window = empty($baseConfig['parent']) ? 'window' : "parent";

        if ($baseConfig['type'] == 2){
            $baseConfig['success'] = JsFunc::anonymous(['layero', 'index', 'that'], <<<JS
                layero.find('.layui-layer-content').css('background', 'white')
                let childrenWin = {$window}[layero.find('iframe')[0]['name']];
                childrenWin.LayerIndex = index;
                $originCode;
            JS);
        }else{
            $baseConfig['success'] = JsFunc::anonymous(['layero', 'index', 'that'], <<<JS
                $originCode;
            JS);
        }

        Html::loadThemeResource('Layui');

        return $code->then(Layer::open($baseConfig));
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
                if (!parsedUrl.searchParams.get(key)){
                    parsedUrl.searchParams.set(key, value);
                }
            }
            
           url = parsedUrl.href;
        JS);
    }
}