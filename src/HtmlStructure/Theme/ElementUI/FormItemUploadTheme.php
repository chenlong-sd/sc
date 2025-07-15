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
use Sc\Util\ScTool;
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

        $this->createNoticeFunc();
        return $el;
    }

    private function createNoticeFunc(): void
    {
        Html::js()->vue->set("UploadNotices", '@{}');
        Html::js()->vue->addMethod('createUploadNotices', JsFunc::anonymous(["id"])->code(
            Js::assign("this.UploadNotices['N' + id]", JsFunc::call('this.$notify', [
                'message'   => '文件上传中,请稍后...',
                'duration'  => 0,
                'type'      => 'warning',
                'showClose' => false
            ])),
        ));
        Html::js()->vue->addMethod('closeUploadNotices', JsFunc::anonymous(["id"])->code(
            Js::code("this.UploadNotices['N' + id].close();"),
            Js::code("delete this.UploadNotices['N' + id];"),
        ));
    }

    private function commonEventHandle(DoubleLabel $upload): void
    {
        $beforeMethod  = "UIBefore";
        $errorMethod   = "UIError";
        $removeMethod  = "UIRemove";

        $upload->setAttrs([
            ":before-upload" => $beforeMethod,
            ":on-error" => $errorMethod,
            ':on-remove' => $removeMethod,
        ]);

        Html::js()->vue->addMethod($beforeMethod, ['UploadRawFile'], Js::code(
            Js::call("this.createUploadNotices", "@UploadRawFile.uid"),
        ));

        Html::js()->vue->addMethod($errorMethod, ['err', 'uploadFile', 'uploadFiles'], Js::code(
            Js::call("this.closeUploadNotices", "@uploadFile.uid"),
            Js::code("this.\$notify({message: '上传失败' + res, type:'error'});"),
        ));

        Html::js()->vue->addMethod($removeMethod, ['file', 'uploadFiles'], Js::code(
            Js::code("console.log(file, uploadFiles)")
        ));
    }

    private function uploadMake(FormItemUpload|FormItemAttrGetter $formItemUpload): DoubleLabel
    {
        $VModel = $this->getVModel($formItemUpload);
        $upload = El::double('el-upload')->setAttrs([
            'v-model:file-list' => $VModel,
            'action'           => $formItemUpload->getUploadUrl(),
            ':show-file-list'  => str_starts_with($formItemUpload->getUploadType(), 'image') ? 'true' : 'false'
        ]);

        $this->commonEventHandle($upload);

        if (str_starts_with($formItemUpload->getUploadType(), 'image')){
            $upload->addClass('sc-avatar-uploader');
            $this->imageCss();

            $uploadEl = $formItemUpload->getUploadType() === FormItemUpload::UPLOAD_TYPE_IMAGE
                ? $this->image($upload, $VModel)
                : $this->images($upload);
        } else {
            if (!$formItemUpload->getUploadEl()) {
                $uploadEl = "";
            } else {
                $uploadEl = $formItemUpload->getUploadEl() instanceof AbstractHtmlElement
                    ? $formItemUpload->getUploadEl()
                    : El::double('el-button')->setAttr('type', 'primary')->append($formItemUpload->getUploadEl());
            }
        }

        $this->multipleFileHandle($formItemUpload, $upload, $VModel);

        $upload->setAttrs($formItemUpload->getVAttrs());

        $this->limitHandle($upload);

        return $upload->append($uploadEl)->append(El::double('template')->setAttr('#tip')->append($this->tip($formItemUpload->getTip())));
    }

    /**
     * @param DoubleLabel $upload
     * @param string|null $VModel
     *
     * @return AbstractHtmlElement
     */
    private function image(DoubleLabel $upload, ?string $VModel): AbstractHtmlElement
    {
        $successMethod = "UISuccessSingle";

        $upload->setAttrs([
            ':show-file-list'   => 'false',
            'v-model:file-list' => null,
            ":on-success"       => "(res, uploadFile, uploadFiles) => $successMethod(res, uploadFile, uploadFiles, $VModel)",
        ]);

        Html::js()->vue->addMethod($successMethod, ['res', 'uploadFile', 'uploadFiles', 'data'], Js::code(
            Js::if('response.code === 200 && response.data')
                ->then(
                    Js::code("data = response.data"),
                    Js::code("this.\$notify({message: '上传成功', type:'success'});"),
                )->else(
                    Js::code("this.\$notify({message: response.msg, type:'error'});"),
                ),
            Js::call("this.closeUploadNotices", "@uploadFile.uid"),
        ));

        return h([
            h('el-image', [
                'v-if'  => $VModel,
                ':src'  => $VModel,
                'class' => "sc-avatar",
            ]),
            h('el-icon', h('plus'), [
                'v-else' => '',
                'class' => 'sc-avatar-uploader-icon',
            ]),
        ]);
    }

    private function images(AbstractHtmlElement $upload): AbstractHtmlElement
    {
        $previewMethod = "UIPreview";
        $removeMethod  = "UIRemove";
        Html::loadThemeResource('Layui');
        $upload->setAttrs([
            'list-type'   => 'picture-card',
            ':on-preview' => $previewMethod,
            ':on-remove'  => $removeMethod,
        ]);

        Html::js()->vue->addMethod($removeMethod, ['uploadFile', 'uploadFiles'], 'console.log(uploadFile, uploadFiles)');
        Html::js()->vue->addMethod($previewMethod, ['uploadFile'], Js\Layer::photos([
            "photos" => [
                'start' => 0,
                'data' => Js::grammar('[{src:uploadFile.url}]')
            ]
        ]));

        return h('el-icon', h('plus'))->addClass('sc-avatar-uploader-icon');
    }

    private function imageCss(): void
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
     * @param DoubleLabel                       $upload
     * @param string|null                       $VModel
     *
     * @return void
     */
    private function multipleFileHandle(FormItemAttrGetter|FormItemUpload $formItemUpload, DoubleLabel $upload, ?string $VModel): void
    {
        if ($formItemUpload->getUploadType() === 'image') {
            return;
        }
        $successMethod = "UISuccess";

        $upload->setAttrs([
            ":on-success"    => "(res, uploadFile, uploadFiles) => $successMethod(res, uploadFile, uploadFiles, $VModel)",
        ]);

        if (empty($formItemUpload->getVAttrs()['limit']) && empty($formItemUpload->getVAttrs()[':limit']) && !isset($formItemUpload->getVAttrs()['multiple'])) {
            $upload->setAttrs([
                ':limit' => 1,
            ]);
        }

        Html::js()->vue->addMethod($successMethod, ['response', 'uploadFile', 'uploadFiles', 'data'], Js::code(
            Js::call("this.closeUploadNotices", "@uploadFile.uid"),
            Js::if('response.code !== 200 || !response.data')->then(
                Js::code("data.pop()"),
                Js::code("this.\$notify({message: response.msg, type:'error'})"),
            )->else(
                Js::code("data[data.length - 1] = { url: response.data, name: uploadFile.name }"),
                Js::code("this.\$notify({message: '上传成功', type:'success'});")
            ),
        ));
    }

    private function fileFormat(FormItemUpload|FormItemAttrGetter $formItemUpload): DoubleLabel|string
    {
        if (str_starts_with($formItemUpload->getUploadType(), 'image')) {
            return '';
        }

        $progress = $formItemUpload->getProgress() ? El::double('el-progress')->setAttrs([
            ":text-inside" => "@true",
            ":stroke-width" => "24",
            ":percentage" =>"percentage",
            "v-if" => "percentage < 100"
        ])->setStyle("{position: absolute;top: 0;left: 0;z-index:-1;width: 100%;}") : '';

        $data = $formItemUpload->getForm()?->getId() ? $formItemUpload->getForm()?->getId() . '.' . $formItemUpload->getName() : $formItemUpload->getName();
        $table =  Table::create([], 'upts' . strtr($formItemUpload->getName(), ['.' => '_']))->addColumns(
            Table\Column::normal('文件名', 'name')->setFormat(
                El::div()->setStyle("{position:relative;}")->append(
                    El::elText("{{ name }}"),
                    $progress
                )
            ),
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
        )->setPagination(false)
            ->setOpenSetting(false)
            ->render()->find('el-table')
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
            $upload->setAttr(":limit", $limit);
            $upload->setAttr("limit", null);
            $method = 'UIExceedUp';
            $upload->setAttr(":on-exceed", "(files, UploadUserFile) => $method(files, UploadUserFile, $limit)");

            Html::js()->vue->addMethod($method, JsFunc::anonymous(["files", "UploadUserFile", "limit"])->code(
                JsService::message(Js\Grammar::mark('文件限制数量为${limit}', 'line'), 'error')
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
            Js\Layer::photos([
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