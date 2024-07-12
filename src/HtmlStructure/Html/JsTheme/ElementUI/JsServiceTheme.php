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

    private string $callVar;

    public function __construct(private JsService $jsService)
    {
        if (is_string($this->jsService->serviceConfig)) {
            $messageKey = match ($this->jsService->type) {
                'loading' => 'text',
                default   => 'message'
            };

            $this->jsService->serviceConfig = [$messageKey => $this->jsService->serviceConfig];
        }
        $this->callVar = $this->jsService->window !== null ? $this->jsService->window .= '.VueApp' : 'this';

        $this->jsService->serviceConfig = array_map(fn($v) => $v instanceof \Stringable ? (string)$v : $v, $this->jsService->serviceConfig);
    }

    public function message(): string
    {
        return JsFunc::call("$this->callVar.\$message", $this->jsService->serviceConfig);
    }

    public function prompt(): string
    {
        $serviceConfig = array_merge(['confirmButtonText' => '确定', 'cancelButtonText' => '取消',], $this->jsService->serviceConfig);

        $then = $serviceConfig['then'];
        unset($serviceConfig['then']);

        return JsFunc::call("$this->callVar.\$prompt", $serviceConfig['message'], '提示', $serviceConfig)
            ->call('then', JsFunc::arrow(['value'], $then instanceof JsFunc ? $then->code : $then));
    }

    public function confirm(): string
    {
        $serviceConfig = array_merge(['confirmButtonText' => '确定', 'cancelButtonText' => '取消',], $this->jsService->serviceConfig);

        $then = $serviceConfig['then'];
        unset($serviceConfig['then']);

        return JsFunc::call("$this->callVar.\$confirm", $serviceConfig['message'], '提示', $serviceConfig)
            ->call('then', JsFunc::arrow([], $then));
    }

    /**
     * @return string
     */
    public function loading(): string
    {
        return JsFunc::call("$this->callVar.\$loading", $this->jsService->serviceConfig);
    }
}