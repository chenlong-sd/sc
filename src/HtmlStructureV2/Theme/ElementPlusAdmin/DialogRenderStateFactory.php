<?php

namespace Sc\Util\HtmlStructureV2\Theme\ElementPlusAdmin;

use Sc\Util\HtmlStructureV2\Theme\ElementPlusAdmin\Concerns\BuildsJsExpressions;

final class DialogRenderStateFactory
{
    use BuildsJsExpressions;

    private ?FormRenderStateFactory $formRenderStateFactory = null;

    public function createManaged(string $dialogKey, string $dialogFormRef): DialogRenderState
    {
        $quotedKey = $this->jsLiteral($dialogKey);
        $formState = $this->formRenderStateFactory()->createManagedDialog($dialogKey, $dialogFormRef);

        return new DialogRenderState(
            formModel: $formState->model,
            visibleModel: sprintf('dialogVisible[%s]', $quotedKey),
            formOptions: $formState->renderOptions,
            bindings: DialogRenderBindings::make()
                ->title(sprintf('dialogTitles[%s]', $quotedKey))
                ->iframeUrl(sprintf('dialogIframeUrls[%s]', $quotedKey))
                ->componentProps(sprintf('dialogComponentProps[%s] || {}', $quotedKey))
                ->loading(sprintf('dialogLoading[%s] || false', $quotedKey))
                ->beforeClose(sprintf('(done) => handleDialogBeforeClose(%s, done)', $quotedKey))
                ->closed(sprintf('handleDialogClosed(%s)', $quotedKey))
                ->fullscreen(sprintf('dialogFullscreen[%s] || false', $quotedKey))
                ->iframeLoad(sprintf('handleDialogIframeLoad(%s, $event)', $quotedKey))
                ->componentRef(sprintf('(el) => setDialogComponentRef(%s, el)', $quotedKey))
                ->iframeRef(sprintf('(el) => setDialogIframeRef(%s, el)', $quotedKey))
                ->toggleFullscreen(sprintf('toggleDialogFullscreen(%s)', $quotedKey))
        );
    }

    public function createStandalone(): DialogRenderState
    {
        $formState = $this->formRenderStateFactory()->createStandaloneDialog();

        return new DialogRenderState(
            formModel: $formState->model,
            visibleModel: 'dialogVisible',
            formOptions: $formState->renderOptions,
            bindings: null,
        );
    }

    private function formRenderStateFactory(): FormRenderStateFactory
    {
        return $this->formRenderStateFactory ??= new FormRenderStateFactory();
    }
}
