<?php

namespace Sc\Util\HtmlStructure\ElementComponent;

use Sc\Util\HtmlStructure\Html\Html;
use Sc\Util\HtmlStructure\Html\Js\Grammar;
use Sc\Util\HtmlStructure\Html\Js\JsFunc;
use Sc\Util\HtmlStructure\Html\Js\Layer;

class Video
{

    public function __construct(private readonly string $src, private readonly bool $isRealPath = false)
    {
        self::setCss();
        self::playWindow();
    }

    public static function setCss(): void
    {
        Html::css()->addCss(<<<CSS
        .video-show{position: relative;height: 90px;width: 160px;overflow: hidden;display: inline-block;margin: 0 5px;border-radius: 3px; box-shadow: 2px 2px 3px;}
        .video-show video{width: 100%}
        .video-show  .play-btn {
              position: absolute;
              top: 50%;
              left: 50%;
              transform: translate(-50%, -50%);
              width: 36px;
              height: 36px;
              background: rgba(0, 0, 0, 0.6);
              border-radius: 50%;
              padding-left: 2px;
              display: flex;
              align-items: center;
              justify-content: center;
              transition: all 0.3s ease;
              z-index: 3; /* 确保按钮在封面之上 */
            }
        
         .video-show   .play-icon {
              width: 20px;
              height: 20px;
              color: #fff;
            }
        
         .video-show   .play-btn:hover {
              background: rgba(255, 255, 255, 0.2);
              transform: translate(-50%, -50%) scale(1.1);
            }
        CSS);
    }

    public function render(): string
    {
        $this->playWindow();

        $videos = ($this->isRealPath) ? "['$this->src']" : $this->src;

        return h('div', ['class' => 'video-show', 'v-for' => "_video in $videos",])->append(
            h('video', [':src' => "_video",]),
            h(<<<HTML
            <div class="play-btn" @click="playVideo(_video)">
                <svg class="play-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                  <path d="M5 3l14 9-14 9V3z" />
                </svg>
            </div>
            HTML
            )
        );
    }


    public static function playWindow(): void
    {
        Html::js()->vue->addMethod('playVideo', JsFunc::anonymous(['url'])->code(
            Layer::open([
                'type' => 1,
                'title' => false,
                'offset' => 'auto',
                'shade' => 0.8,
                'closeBtn' => 0,
                'shadeClose' => true,
                'content' => Grammar::mark(trim(h('video', ['id' => 'layer-video', 'autoplay' => "", 'controls' => "", 'src' => '${url}'])), 'line'),
                "success" => JsFunc::arrow(['layero', 'index'])->code(<<<JS
                    const video = document.getElementById('layer-video');
                    video.onloadedmetadata = function() {
                        layer.style(index, {
                            top: ((layui.jquery(window).height() - layero.height()) / 2) + 'px'
                        });
                    };
                JS)
            ])
        ));
    }

    public function __toString(): string
    {
        return $this->render();
    }
}