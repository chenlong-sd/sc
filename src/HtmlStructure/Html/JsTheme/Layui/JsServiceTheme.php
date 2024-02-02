<?php

namespace Sc\Util\HtmlStructure\Html\JsTheme\Layui;

use Sc\Util\HtmlStructure\Html\Js\JsFunc;
use Sc\Util\HtmlStructure\Html\Js\JsService;
use Sc\Util\HtmlStructure\Html\JsTheme\Interfaces\JsServiceThemeInterface;

/**
 * Class JsService
 */
class JsServiceTheme implements JsServiceThemeInterface
{

    private string $parent = '';

    public function __construct(private readonly JsService $jsService)
    {
        if (is_string($this->jsService->serviceConfig)) {
            $this->jsService->serviceConfig = [
                'content' => $this->jsService->serviceConfig,
            ];
        }
        $this->parent = $this->jsService->window !== null ? $this->jsService->window . '.' : '';
    }

    public function message(): string
    {
        $message = $this->jsService->serviceConfig['message'];
        empty($this->jsService->serviceConfig['icon']) and $this->jsService->serviceConfig['icon'] = match ($this->jsService->serviceConfig['type']) {
            'success' => 6,
            'warning' => 0,
            'error'   => 5,
            default   => null
        };
        unset($this->jsService->serviceConfig['message'], $this->jsService->serviceConfig['type'],);

        return JsFunc::call("{$this->parent}layer.msg", $message, $this->jsService->serviceConfig,);
    }

    public function confirm(): string
    {
        $message = $this->jsService->serviceConfig['message'];
        $then    = $this->jsService->serviceConfig['then'];
        unset($this->jsService->serviceConfig['message'], $this->jsService->serviceConfig['then'],);

        $options = array_merge(['icon' => 3, 'title' => '提示'], $this->jsService->serviceConfig);

        return JsFunc::call("{$this->parent}layer.confirm", $message, $options, JsFunc::arrow(['index'], $then));
    }

    public function loading(): string
    {
        $message = $this->jsService->serviceConfig['message'];

        return JsFunc::call("{$this->parent}layer.load", $message);
    }
}