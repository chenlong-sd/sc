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
use Sc\Util\HtmlStructure\Html\Js\JsService;
use Sc\Util\HtmlStructure\Html\Js\JsVar;
use Sc\Util\HtmlStructure\Table;
use Sc\Util\HtmlStructure\Theme\Interfaces\FormItemUploadThemeInterface;
use Sc\Util\Tool;

class FormItemUploadTheme extends AbstractFormItemTheme implements FormItemUploadThemeInterface
{
    /**
     * @param FormItemUpload|FormItemAttrGetter $formItem
     *
     * @return AbstractHtmlElement
     * @date 2023/6/4
     */
    public function renderFormItem($formItem): AbstractHtmlElement
    {
        $el = $this->getBaseEl($formItem);

        $fileFormat = $this->fileFormat($formItem);
        $el->append($this->uploadMake($formItem))->append($fileFormat);

        return $el;
    }

    private function uploadMake(FormItemUpload|FormItemAttrGetter $formItemUpload): DoubleLabel
    {
        $VModel = $this->getVModel($formItemUpload);
        $upload = El::double('el-upload')->setAttrs([
            'v-model:file-list' => $VModel,
            ':on-remove'       => "{$formItemUpload->getName()}remove",
            'action'           => $formItemUpload->getUploadUrl(),
            ':show-file-list'  => str_starts_with($formItemUpload->getUploadType(), 'image') ? 'true' : 'false'
        ]);

        $rand = Tool::random()->get();
        if (str_starts_with($formItemUpload->getUploadType(), 'image')){
            $upload->addClass('sc-avatar-uploader');
            $this->imageCss();

            $uploadEl = $formItemUpload->getUploadType() === FormItemUpload::UPLOAD_TYPE_IMAGE
                ? $this->image($upload, $VModel, $rand)
                : $this->images($upload, $rand);
        } else {
            if (!$formItemUpload->getUploadEl()) {
                $uploadEl = "";
            } else {
                $uploadEl = $formItemUpload->getUploadEl() instanceof AbstractHtmlElement
                    ? $formItemUpload->getUploadEl()
                    : El::double('el-button')->setAttr('type', 'primary')->append($formItemUpload->getUploadEl());
            }
        }

        $this->multipleFileHandle($formItemUpload, $rand, $upload, $VModel);

        Html::js()->vue->addMethod(strtr($formItemUpload->getName(), ['.' => '_']) . "remove", ['file', 'uploadFiles'], "console.log(file, uploadFiles)");

        $upload->setAttrs($formItemUpload->getVAttrs());

        $this->limitHandle($upload);

        return $upload->append($uploadEl)->append(El::double('template')->setAttr('#tip')->append($this->tip($formItemUpload->getTip())));
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
        $notify        = "UINotify" . $rand;
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

        if (empty($formItemUpload->getVAttrs()['limit']) && empty($formItemUpload->getVAttrs()[':limit']) && !isset($formItemUpload->getVAttrs()['multiple'])) {
            $upload->setAttrs([
                ':limit' => 1,
            ]);
        }

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

        $formItemUpload->getForm()?->setSubmitHandle(<<<JS
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

    private function fileFormat(FormItemUpload|FormItemAttrGetter $formItemUpload)
    {
        if (str_starts_with($formItemUpload->getUploadType(), 'image')) {
            return '';
        }

        $data = $formItemUpload->getForm()?->getId() ? $formItemUpload->getForm()?->getId() . '.' . $formItemUpload->getName() : $formItemUpload->getName();
        $table =  Table::create([], 'upts' . strtr($formItemUpload->getName(), ['.' => '_']))->addColumns(
            Table\Column::normal('文件名', 'name'),
            Table\Column::event('下载', '')->setAttr('width', 80)->setFormat(El::double('el-link')->setAttrs([
                'type' => 'primary',
                ':href' => 'url',
                ':download' => 'name',
                'icon' => 'download',
            ])->append('下载'))->notShow($formItemUpload->getDisableDownload()),
            Table\Column::event('删除', '')->setAttr('width', 80)->setFormat(El::double('el-button')->setAttrs([
                'link' => '',
                'type' => 'danger',
                'icon' => 'delete',
                '@click' => 'uprm' . strtr($formItemUpload->getName(), ['.' => '_']) . '(@scope)'
            ])),
        )->setPagination(false)->render()->find('el-table')
            ->setAttr(":data", $data)
            ->setAttr(":show-header", 'false')
            ->setAttr("empty-text", '暂无上传文件')
            ->setAttr('header-cell-class-name', null)
            ->setAttr('cell-class-name', null);

        Html::js()->vue->addMethod( 'uprm' . strtr($formItemUpload->getName(), ['.' => '_']), JsFunc::anonymous(['scope'])->code(
            JsCode::create("this.$data.splice(scope.\$index, 1)")
        ));


        return El::double('div')->setAttr('style', 'width:100%')->append($table);
    }

    private function limitHandle(DoubleLabel $upload): void
    {
        if ((($limit = $upload->getAttr("limit")) || ($limit = $upload->getAttr(':limit'))) && !$upload->hasAttr(":on-exceed")) {
            $method = "uploadOnExceed" . Tool::random('up')->get(11, 55);
            $upload->setAttr(":on-exceed", $method);

            Html::js()->vue->addMethod($method, JsFunc::anonymous()->code(
                JsService::message("文件限制数量为" . $limit, 'error')
            ));
        }
    }

    /**
     * @param String|AbstractHtmlElement|null $tip
     *
     * @return DoubleLabel|AbstractHtmlElement
     */
    private function tip(String|AbstractHtmlElement|null $tip): DoubleLabel|AbstractHtmlElement
    {
        if ($tip instanceof AbstractHtmlElement) {
            return $tip;
        }

        return El::double('el-text')->setAttr('style', 'margin-left:5px')->append($tip);
    }

}