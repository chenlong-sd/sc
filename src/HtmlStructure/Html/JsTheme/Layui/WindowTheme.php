<?php
/**
 * datetime: 2023/6/2 0:04
 **/

namespace Sc\Util\HtmlStructure\Html\JsTheme\Layui;

use Sc\Util\HtmlStructure\Html\Js\JsFunc;
use Sc\Util\HtmlStructure\Html\Js\Window;
use Sc\Util\HtmlStructure\Html\JsTheme\Interfaces\WindowThemeInterface;

class WindowTheme implements WindowThemeInterface
{

    public function render(Window $window): string
    {
        $baseConfig = is_null($window->getContent())
            ? ['type' => 2, 'area' => ['90%', '90%'], 'content' => $window->getUrl()]
            : ['type' => 1, 'content' => $window->getContent()];

        $baseConfig['anim'] = 5;

        $baseConfig = array_merge($baseConfig, $window->getConfig());

        $baseConfig['title']  = $window->getTitle();
        $baseConfig['maxmin'] = true;

        $originCode = '';
        if (!empty($baseConfig['success']) && $baseConfig['success'] instanceof JsFunc) {
            $originCode = $baseConfig['success']->code;
        }

        $baseConfig['success'] = JsFunc::anonymous(['layero', 'index', 'that'], <<<JS
            layero.find('.layui-layer-content').css('padding', '0 20px')
            $originCode;
        JS);

        return JsFunc::call('layer.open', $baseConfig);
    }
}