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
use Sc\Util\HtmlStructure\Html\Js;
use Sc\Util\HtmlStructure\Html\Js\JsFunc;
use Sc\Util\HtmlStructure\Html\Js\JsService;
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

        if ($formItem->getUploadType() === FormItemUpload::UPLOAD_TYPE_IMAGE) {
            $el->append($this->imageEnlarge($formItem));
        }

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
        $errorMethod   = "UIError" . $rand;
        $notify        = "UINotify" . $rand;
        Html::js()->vue->set($notify, '');

        $upload->setAttrs([
            ':show-file-list'   => 'false',
            'v-model:file-list' => null,
            ":on-success"       => $successMethod,
            ":before-upload"    => $beforeMethod,
            ":on-error"         => $errorMethod,
        ]);

        Html::js()->vue->addMethod($successMethod, ['response', 'uploadFile'], Js::code(
            Js::if('response.code === 200 && response.data')
                ->then(
                    Js::code("this.$VModel = response.data"),
                    Js::code("this.\$notify({message: '上传成功', type:'success'});"),
                )->else(
                    Js::code("this.\$notify({message: response.msg, type:'error'});"),
                ),
            Js::code("this.$notify.close();")
        ));

        Html::js()->vue->addMethod($beforeMethod, ['UploadRawFile'], Js::code(
            Js::assign("this.$notify", JsFunc::call('this.$notify', [
                'message'   => '文件上传中,请稍后...',
                'duration'  => 0,
                'type'      => 'warning',
                'showClose' => false
            ])),
        ));

        Html::js()->vue->addMethod($errorMethod, ['res', 'uploadFiles'], Js::code(
            Js::code("this.$notify.close();"),
            Js::code("this.\$notify({message: '上传失败' + res, type:'error'});"),
        ));

        $uploadEl = El::fictitious()->append(
            El::double('el-image')->setAttrs([
                'v-if'  => $VModel,
                ':src'  => $VModel,
                'class' => "sc-avatar",
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
        Html::loadThemeResource('Layui');
        $upload->setAttrs([
            'list-type'   => 'picture-card',
            ':on-preview' => $previewMethod,
            ':on-remove'  => $removeMethod,
        ]);

        Html::js()->vue->addMethod($removeMethod, ['uploadFile', 'uploadFiles'], 'console.log(uploadFile, uploadFiles)');
        Html::js()->vue->addMethod($previewMethod, ['uploadFile'], Js::code(
            JsFunc::call('layer.photos', [
                "photos" => [
                    'start' => 0,
                    'data' => Js::grammar('[{src:uploadFile.url}]')
                ]
            ])
        ));

        return El::double('el-icon')
            ->addClass('sc-avatar-uploader-icon')
            ->append(El::double('plus'));
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
        $errorMethod   = "UIError" . $rand;
        Html::js()->vue->set($notify, []);

        $upload->setAttrs([
            ":on-success"    => $successMethod,
            ":before-upload" => $beforeMethod,
            ":on-error"      => $errorMethod,
        ]);

        if (empty($formItemUpload->getVAttrs()['limit']) && empty($formItemUpload->getVAttrs()[':limit']) && !isset($formItemUpload->getVAttrs()['multiple'])) {
            $upload->setAttrs([
                ':limit' => 1,
            ]);
        }

        Html::js()->vue->addMethod($successMethod, ['response', 'uploadFile'], Js::code(
            Js::if('response.code !== 200 || !response.data')->then(
                Js::code("this.$VModel.pop()"),
                Js::code("this.\$notify({message: response.msg, type:'error'})"),
            )->else(
                Js::code("this.\$notify({message: '上传成功', type:'success'});")
            ),
            Js::code("this.{$notify}['I' + uploadFile.uid].close()"),
            Js::code("delete this.{$notify}['I' + uploadFile.uid]"),
        ));


        Html::js()->vue->addMethod($beforeMethod, ['UploadRawFile'], Js::code(
            Js::assign("this.{$notify}['I' + UploadRawFile.uid]", JsFunc::call('this.$notify', [
                'message'   => '文件上传中,请稍后...',
                'duration'  => 0,
                'type'      => 'warning',
                'showClose' => false
            ])),
        ));

        Html::js()->vue->addMethod($errorMethod, ['res', 'uploadFile'], Js::code(
            Js::code("this.$VModel.pop()"),
            Js::code("this.{$notify}['I' + uploadFile.raw.uid].close()"),
            Js::code("this.\$notify({message: uploadFile.raw.name + ' 上传失败，' + res, type:'error'})"),
        ));

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
            Js::code("this.$data.splice(scope.\$index, 1)")
        ));


        return El::div($table)->setAttr('style', 'width:100%');
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

        return El::elText($tip)->setAttr('style', 'margin-left:5px');
    }

    private function imageEnlarge(FormItemAttrGetter|FormItemUpload $formItem): DoubleLabel
    {
        Html::loadThemeResource('Layui');

        $icon   = El::double('el-icon')->addClass('single-image-enlarge')->append("<Search/>");
        $vModel = $this->getVModel($formItem);
        $icon->setAttr('v-if', $vModel)->setAttr('@click', "imageEnlarge({$vModel})");

        Html::js()->vue->addMethod("imageEnlarge", JsFunc::anonymous(['url'])->code(
            Js::if("typeof url === 'string'")->then(
                Js::assign('url', '@[url]')
            ),
            Js::let('data', []),
            Js::for('let i = 0; i < url.length; i++')->then(
                Js::code('data.push({src:url[i]})')
            ),
            JsFunc::call('layer.photos', [
                "photos" => [
                    'start' => 0,
                    'data' => Js::grammar('data')
                ]
            ])
        ));

        Html::css()->addCss(<<<CSS
        .single-image-enlarge{
            position: absolute;
            top: 5px;
            left: 5px;
            cursor: pointer;
            font-size: 18px;
            color: rgba(0, 0, 0, .45);
        }
        .single-image-enlarge:hover{
            color: #00B7EE;
        }
        CSS);

        return $icon;
    }

}