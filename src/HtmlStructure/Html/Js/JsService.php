<?php

namespace Sc\Util\HtmlStructure\Html\Js;

use JetBrains\PhpStorm\ExpectedValues;
use JetBrains\PhpStorm\Language;
use Sc\Util\HtmlStructure\Html\JsTheme\Interfaces\JsServiceThemeInterface;
use Sc\Util\HtmlStructure\Html\JsTheme\JsTheme;

/**
 * Class JsService
 */
class JsService
{
    /**
     * @var true
     */
    public bool $isParent = false;
    public ?string $window = null;

    private function __construct(public mixed $serviceConfig, public readonly string $type) { }

    /**
     * @param string|array $message
     *
     * @return JsService
     */
    public static function loading(string|array $message = '请稍后...'): JsService
    {
        return new self($message, 'loading');
    }

    /**
     * @param string|array $message
     * @param mixed|null   $then
     *
     * @return JsService
     */
    public static function confirm(string|array $message = '确认此操作吗？', #[Language('JavaScript')] mixed $then = null): JsService
    {
        return new self(is_array($message) ? $message : [
            'message' => $message,
            'then'    => $then,
        ], 'confirm');
    }

    /**
     * @param string|array $message
     * @param string       $type
     * @param mixed|null   $then
     *
     * @return JsService
     */
    public static function prompt(string|array $message, #[ExpectedValues(['text', 'textarea'])] string $type = 'text', #[Language('JavaScript')] mixed $then = null): JsService
    {
        return new self(is_array($message) ? $message : [
            'message' => $message,
            'type'    => $type,
            'then'    => $then,
        ], 'prompt');
    }

    /**
     * @param string $message
     * @param string $type
     *
     * @return JsService
     */
    public static function message(string $message, #[ExpectedValues(['success', 'info', 'warning', 'error'])] string $type = 'success'): JsService
    {
        return new self([
            'message' => $message,
            'type'    => $type,
        ], 'message');
    }

    public function toCode(): string
    {
        $theme = JsTheme::getTheme(JsServiceThemeInterface::class);

        return (new $theme($this))->{$this->type}();
    }

    public function toParent(): static
    {
        return $this->toWindow('parent');
    }

    /**
     * @param string $window
     *
     * @return $this
     */
    public function toWindow(string $window): static
    {
        $this->window = $window;

        return $this;
    }

    public function __toString(): string
    {
        return $this->toCode();
    }
}