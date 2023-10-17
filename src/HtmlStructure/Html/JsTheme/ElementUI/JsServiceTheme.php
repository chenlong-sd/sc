<?php

namespace Sc\Util\HtmlStructure\Html\JsTheme\ElementUI;

use Sc\Util\HtmlStructure\Html\Js\JsFunc;
use Sc\Util\HtmlStructure\Html\Js\JsService;
use Sc\Util\HtmlStructure\Html\JsTheme\Interfaces\JsServiceThemeInterface;

/**
 * Class JsService
 */
class JsServiceTheme implements JsServiceThemeInterface
{

    public function __construct(private JsService $jsService)
    {
        if (is_string($this->jsService->serviceConfig)) {
            $messageKey = match ($this->jsService->type) {
                'loading' => 'text',
                default   => 'message'
            };

            $this->jsService->serviceConfig = [$messageKey => $this->jsService->serviceConfig];
        }
        $this->jsService->serviceConfig = array_map(fn($v) => $v instanceof \Stringable ? (string)$v : $v, $this->jsService->serviceConfig);
    }

    public function message(): string
    {
        return JsFunc::call('this.$message', $this->jsService->serviceConfig);
    }

    public function confirm(): string
    {
        $serviceConfig = array_merge(['confirmButtonText' => '确定', 'cancelButtonText' => '取消',], $this->jsService->serviceConfig);

        $then = $serviceConfig['then'];
        unset($serviceConfig['then']);

        return JsFunc::call('this.$confirm', $serviceConfig['message'], '提示', $serviceConfig)
            ->call('then', JsFunc::arrow([], $then));
    }

    /**
     * @return string
     */
    public function loading(): string
    {
        return JsFunc::call('this.$loading', $this->jsService->serviceConfig);
    }
}