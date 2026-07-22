<?php

namespace Sc\Util\HtmlStructureV2\Theme\ElementPlusAdmin;

use Sc\Util\HtmlElement\El;
use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlStructureV2\RenderContext;
use Sc\Util\HtmlStructureV2\Support\AssetBag;

/**
 * 媒体展示组件的运行时基础设施：
 * - 注入一段 CSS（缩略视频蒙层、播放弹窗自适应尺寸）；
 * - 在页面 body 末尾挂载一个容器节点，并注册 __SC_V2_PAGE__.playMedia(url) 方法；
 * - 该方法内部按需启动一个独立 Vue app + Element Plus，渲染共享 el-dialog 播放视频；
 *   视频自适应尺寸由 `max-width:100%;max-height:70vh` 实现，无需回调修改 dialog 几何。
 *
 * 整个运行时只在页面里出现媒体展示组件时注入一次。
 */
final class MediaRuntime
{
    private const INSTALLED_KEY = 'v2.media.runtime.installed';

    public static function ensureInstalled(RenderContext $context): void
    {
        if ($context->get(self::INSTALLED_KEY, false)) {
            return;
        }

        $context->set(self::INSTALLED_KEY, true);

        $assets = self::assets($context);
        $assets->addInlineStyle(self::css());
        $assets->addInlineScript(self::bootstrapScript());

        // 容器节点：Vue 独立 app 的挂载点
        $context->document()->appendToBody(
            El::double('div')->setAttr('id', '__sc_v2_media_mount__')
        );
    }

    private static function assets(RenderContext $context): AssetBag
    {
        return $context->document()->assets();
    }

    private static function css(): string
    {
        return <<<'CSS'
.sc-v2-media-video{position:relative;display:inline-block;overflow:hidden;background:#000;box-shadow:2px 2px 3px rgba(0,0,0,.15);cursor:pointer}
.sc-v2-media-video video{display:block;width:100%;height:100%;object-fit:cover;pointer-events:none}
.sc-v2-media-video__play{position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);width:36px;height:36px;border-radius:50%;background:rgba(0,0,0,.6);display:flex;align-items:center;justify-content:center;transition:all .3s ease;z-index:3}
.sc-v2-media-video__play-icon{display:block;width:20px;height:20px;color:#fff;transform:translateX(1px)}
.sc-v2-media-video:hover .sc-v2-media-video__play{background:rgba(255,255,255,.2);transform:translate(-50%,-50%) scale(1.1)}
.__sc_v2_media_overlay__ .el-dialog.__sc_v2_media_dialog__{--el-dialog-bg-color:transparent;--el-dialog-padding-primary:0;margin:auto!important;padding:0!important;overflow:hidden;background:transparent!important;border:0!important;border-radius:0!important;box-shadow:none!important}
.__sc_v2_media_overlay__ .el-dialog.__sc_v2_media_dialog__ .el-dialog__header{display:none!important;margin:0!important;padding:0!important}
.__sc_v2_media_overlay__ .el-dialog.__sc_v2_media_dialog__ .el-dialog__body{margin:0!important;padding:0!important;background:transparent!important}
.sc-v2-media-dialog__video{display:block;width:100%;max-width:100%;max-height:70vh;background:#000}
CSS;
    }

    private static function bootstrapScript(): string
    {
        return <<<'JS'
(function () {
  function ensureMedia() {
    if (globalThis.__SC_V2_MEDIA_DIALOG__) {
      return globalThis.__SC_V2_MEDIA_DIALOG__;
    }
    const mountNode = document.getElementById('__sc_v2_media_mount__');
    if (!mountNode || !globalThis.Vue || !globalThis.ElementPlus) {
      return null;
    }
    const app = globalThis.Vue.createApp({
      data() {
        return { visible: false, url: '' };
      },
      watch: {
        visible(val) {
          if (!val) {
            this.url = '';
          }
        },
      },
      template: '<el-dialog v-model="visible" class="__sc_v2_media_dialog__" modal-class="__sc_v2_media_overlay__" :show-close="false" :close-on-click-modal="true" append-to-body align-center width="auto" @close="url = \'\'">'
        + '<video class="sc-v2-media-dialog__video" :src="url" controls autoplay playsinline disablepictureinpicture controlslist="nodownload noremoteplayback"></video>'
        + '</el-dialog>',
    });
    app.use(globalThis.ElementPlus, { locale: globalThis.ElementPlusLocaleZhCn });
    const vm = app.mount(mountNode);
    const api = {
      play(url) {
        if (!url) return;
        vm.url = String(url);
        vm.visible = true;
      },
    };
    globalThis.__SC_V2_MEDIA_DIALOG__ = api;
    return api;
  }

  const pageApi = globalThis.__SC_V2_PAGE__ || (globalThis.__SC_V2_PAGE__ = {});
  pageApi.playMedia = function playMedia(url) {
    const media = ensureMedia();
    if (!media) {
      globalThis.ElementPlus && globalThis.ElementPlus.ElMessage.warning('视频播放组件尚未就绪');
      return;
    }
    media.play(url);
  };
})();
JS;
    }
}
