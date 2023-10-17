<?php
/**
 * datetime: 2023/6/3 23:16
 **/

namespace Sc\Util\HtmlStructure\Theme\ElementUI;

use Sc\Util\HtmlElement\El;
use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlElement\ElementType\DoubleLabel;
use Sc\Util\HtmlStructure\Form\FormItemAttrGetter;
use Sc\Util\HtmlStructure\Form\FormItemUpload;
use Sc\Util\HtmlStructure\Html\Html;
use Sc\Util\HtmlStructure\Html\Js\JsCode;
use Sc\Util\HtmlStructure\Html\Js\JsFunc;
use Sc\Util\HtmlStructure\Html\Js\JsIf;
use Sc\Util\HtmlStructure\Html\Js\JsVar;
use Sc\Util\HtmlStructure\Theme\Interfaces\FormItemUploadThemeInterface;
use Sc\Util\Tool;

class FormItemUploadTheme extends AbstractFormItemTheme implements FormItemUploadThemeInterface
{
    /**
     * @param FormItemUpload|FormItemAttrGetter $formItemUpload
     *
     * @return AbstractHtmlElement
     * @date 2023/6/4
     */
    public function render(FormItemUpload|FormItemAttrGetter $formItemUpload): AbstractHtmlElement
    {
        $el = $this->getBaseEl($formItemUpload);

        $el->append($this->uploadMake($formItemUpload));

        return $this->afterRender($formItemUpload, $el);
    }

    private function uploadMake(FormItemUpload|FormItemAttrGetter $formItemUpload): DoubleLabel
    {
        $VModel = $this->getVModel($formItemUpload);
        $upload = El::double('el-upload')->setAttrs([
            'v-model:file-list' => $VModel,
            ':on-remove'       => "{$formItemUpload->getName()}remove",
            'action'           => $formItemUpload->getUploadUrl(),
        ]);

        $rand = Tool::random()->get();
        if (str_starts_with($formItemUpload->getUploadType(), 'image')){
            $upload->addClass('sc-avatar-uploader');
            $this->imageCss();

            $uploadEl = $formItemUpload->getUploadType() === FormItemUpload::UPLOAD_TYPE_IMAGE
                ? $this->image($upload, $VModel, $rand)
                : $this->images($upload, $rand);
        }else{
            $uploadEl = $formItemUpload->getUploadEl() instanceof AbstractHtmlElement
                ? $formItemUpload->getUploadEl()
                : El::double('el-button')->setAttr('type', 'primary')->append($formItemUpload->getUploadEl());
        }

        $this->multipleFileHandle($formItemUpload, $rand, $upload, $VModel);

        Html::js()->vue->addMethod("{$formItemUpload->getName()}remove", ['file', 'uploadFiles'], "console.log(file, uploadFiles)");


        return $upload->append($uploadEl);
    }

    /**
     * @param DoubleLabel $upload
     * @param string|null $VModel
     * @param int         $rand
     *
     * @return AbstractHtmlElement
     */
    private function image(DoubleLabel $upload, ?string $VModel, int $rand): AbstractHtmlElement
    {
        $successMethod = "UISuccess" . $rand;
        $beforeMethod  = "UIBefore" . $rand;
        $notify       = "UINotify" . $rand;
        Html::js()->vue->set($notify, '');

        $upload->setAttrs([
            ':show-file-list'   => 'false',
            'v-model:file-list' => null,
            ":on-success"       => $successMethod,
            ":before-upload"    => $beforeMethod,
        ]);

        Html::js()->vue->addMethod($successMethod, ['response', 'uploadFile'], JsCode::make(
            JsIf::when('response.code === 200 && response.data')
                ->then(
                    JsCode::create("this.$VModel = response.data"),
                    JsCode::create("this.\$notify({message: '上传成功', type:'success'});"),
                )->else(
                    JsCode::create("this.\$notify({message: response.msg, type:'error'});"),
                ),
            JsCode::create("this.$notify.close();")
        ));

        Html::js()->vue->addMethod($beforeMethod, ['UploadRawFile'],
            JsVar::assign("this.$notify", JsFunc::call('this.$notify', [
                'message'   => '文件上传中,请稍后...',
                'duration'  => 0,
                'type'      => 'warning',
                'showClose' => false
            ]))
        );

        $uploadEl = El::fictitious()->append(
            El::double('el-image')->setAttrs([
                'v-if'  => $VModel,
                ':src'  => $VModel,
                'class' => "sc-avatar"
            ])
        )->append(
            El::double('el-icon')->setAttr('v-else', )->addClass('sc-avatar-uploader-icon')->append(
                El::double('plus')
            )
        );

        return $uploadEl;
    }

    private function images(AbstractHtmlElement $upload, int $rand): AbstractHtmlElement
    {
        $previewMethod = "UIPreview" . $rand;
        $removeMethod  = "UIRemove" . $rand;

        $upload->setAttrs([
            'list-type'   => 'picture-card',
            ':on-preview' => $previewMethod,
            ':on-remove'  => $removeMethod,
        ]);
        Html::js()->vue->set("UIVisible$rand", false);
        Html::js()->vue->set("UIImageUrl$rand", '');

        Html::js()->vue->addMethod($removeMethod, ['uploadFile', 'uploadFiles'], 'console.log(uploadFile, uploadFiles)');
        Html::js()->vue->addMethod($previewMethod, ['uploadFile'],
            JsCode::create(JsVar::assign("this.UIVisible$rand", true))
                ->then(JsVar::assign("this.UIImageUrl$rand", '@uploadFile.url'))
        );


        $el = El::double('el-icon')->addClass('sc-avatar-uploader-icon')
            ->append(
                El::double('plus')
            );

        Html::html()->find('#app')->append(<<<HTML
              <el-dialog v-model="UIVisible$rand">
                <el-image :src="UIImageUrl$rand" alt="Preview Image" style="width: 100%;" ></el-image>
              </el-dialog>
        HTML);

        return $el;
    }

    private function imageCss()
    {
        Html::css()->addCss(<<<CSS
            .sc-avatar-uploader .el-upload {
              border: 1px dashed var(--el-border-color);
              border-radius: 6px;
              cursor: pointer;
              position: relative;
              overflow: hidden;
              transition: var(--el-transition-duration-fast);
            }
            
            .sc-avatar-uploader .el-upload:hover {
              border-color: var(--el-color-primary);
            }
            
            .el-icon.sc-avatar-uploader-icon {
              font-size: 28px;
              color: #8c939d;
              width: 178px;
              height: 178px;
              text-align: center;
            }
            .sc-avatar{
                height: 178px;
            }
            CSS
        );

    }

    /**
     * @param FormItemAttrGetter|FormItemUpload $formItemUpload
     * @param string                            $rand
     * @param DoubleLabel                       $upload
     * @param string|null                       $VModel
     *
     * @return void
     */
    private function multipleFileHandle(FormItemAttrGetter|FormItemUpload $formItemUpload, string $rand, DoubleLabel $upload, ?string $VModel): void
    {
        if ($formItemUpload->getUploadType() === 'image') {
            return;
        }
        $successMethod = "UISuccess" . $rand;
        $beforeMethod  = "UIBefore" . $rand;
        $notify        = "UINotify" . $rand;
        Html::js()->vue->set($notify, '');

        $upload->setAttrs([
            ":on-success"    => $successMethod,
            ":before-upload" => $beforeMethod,
        ]);

        Html::js()->vue->addMethod($successMethod, ['response', 'uploadFile'], JsCode::make(
            JsIf::when('response.code !== 200 || !response.data')
                ->then(JsCode::make(
                    JsCode::create("this.$VModel.pop()"),
                    JsCode::create("this.\$notify({message: response.msg, type:'error'})"),
                ))->else(
                    JsCode::create("this.\$notify({message: '上传成功', type:'success'});")
                ),
            JsCode::create("this.$notify.close()")
        ));


        Html::js()->vue->addMethod($beforeMethod, ['UploadRawFile'],
            JsVar::assign("this.$notify", JsFunc::call('this.$notify', [
                'message'   => '文件上传中,请稍后...',
                'duration'  => 0,
                'type'      => 'warning',
                'showClose' => false
            ]))
        );

        $submitVar = preg_replace('/^.+\./', '', $VModel);

        $formItemUpload->getForm()->setSubmitHandle(<<<JS
                let newD$rand = [];
                for(var i = 0; i < data.$submitVar.length; i++) {
                    newD{$rand}[i] = {
                        name: data.{$submitVar}[i].name,
                        url: data.{$submitVar}[i].response !== undefined ? data.{$submitVar}[i].response.data : data.{$submitVar}[i].url
                    }
                }
                data.$submitVar = newD$rand;
            JS
        );

    }

}