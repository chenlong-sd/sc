<?php

namespace Sc\Util\HtmlStructure\ElementComponent;

use Sc\Util\HtmlStructure\Html\Html;
use Sc\Util\HtmlStructure\Html\Js\Grammar;
use Sc\Util\HtmlStructure\Html\Js\JsFunc;
use Sc\Util\HtmlStructure\Html\Js\Layer;

class Videos
{
    public function __construct(private readonly string|array $srcs, private readonly string $urlProp = 'url')
    {
        Video::setCss();
        Video::playWindow();
    }

    public function render(): string
    {
        $videos = is_array($this->srcs)
            ? strtr(json_encode($this->srcs, JSON_UNESCAPED_UNICODE), ['"' => ''])
            : $this->srcs;

        $src = ($this->urlProp) ? "_video.$this->urlProp" : "_video";

        return h('div', ['class' => 'video-show', 'v-for' => "_video in $videos",])->append(
            h('video', [':src' => "$src",]),
            h(<<<HTML
            <div class="play-btn" @click="playVideo($src)">
                <svg class="play-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M5 3l14 9-14 9V3z" />
                </svg>
            </div>
            HTML
            )
        );
    }

    public function __toString(): string
    {
        return $this->render();
    }
}