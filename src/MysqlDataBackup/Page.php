<?php

namespace Sc\Util\MysqlDataBackup;

use Sc\Util\HtmlElement\El;
use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlStructure\Form\FormItem;
use Sc\Util\HtmlStructure\Html\Html;
use Sc\Util\HtmlStructure\Html\Js;
use Sc\Util\HtmlStructure\Html\Js\Axios;
use Sc\Util\HtmlStructure\Html\Js\JsCode;
use Sc\Util\HtmlStructure\Html\Js\JsFunc;
use Sc\Util\HtmlStructure\Html\Js\Grammar;
use Sc\Util\HtmlStructure\Html\Js\JsVar;
use Sc\Util\HtmlStructure\Layout;

/**
 * Class Page
 */
class Page
{
    public function __construct(private readonly Connect $connect, private readonly string $saveDir)
    {
        Html::create('Mysql 数据备份', 'ElementUI');
        $this->commonJs();
    }

    public function render(): string
    {
        $layout    = Layout::create(["gutter" => 5, 'style' => 'width:1400px;margin:auto;']);
        $tablesBox = El::double('el-scrollbar')->setAttr(':height', "windowHeight");
        $msgBox    = clone $tablesBox;
        $recoverBox    = clone $tablesBox;

        $this->tablesShowRender($tablesBox);
        $this->messageShowCard($msgBox);
        $this->recover($recoverBox);

        $layout->addCol(6, $tablesBox);
        $layout->addCol(10, $msgBox);
        $layout->addCol(8, $recoverBox);

        Html::html()->find('#app')->append($layout->render());

        return Html::toHtml();
    }


    private function tablesShowRender(AbstractHtmlElement $box): void
    {
        $tables = Query::getTables($this->connect);

        $checkboxAll = FormItem::checkbox('checkAll')->options([1 => "全选"])->event('change', JsFunc::anonymous([],
            JsCode::if('this.checkAll.length > 0',
                'this.tables = this.tablesOptions.map(v => v.value);',
                'this.tables = []'
            )
        ));

        $checkbox = FormItem::checkbox('tables')->beforeRender(function (AbstractHtmlElement $element) {
            $element->find('el-checkbox')
                ?->setAttr('style', 'display:block')
                ->setAttr('v-for', '(item, index) in tablesOptions');
        });

        Html::js()->vue->set('checkAll', []);
        Html::js()->vue->set('tables', []);
        Html::js()->vue->set('tablesOptions', array_map(function ($value) {
            return [
                'value' => $value['table'],
                'label' => sprintf('%s [%s]', $value['table'], $value['comment']),
            ];
        }, $tables));

        $box->append($checkboxAll->render())->append($checkbox->render());
    }

    /**
     * @param AbstractHtmlElement $box
     *
     * @return void
     */
    private function messageShowCard(AbstractHtmlElement $box): void
    {
        $card = El::fromCode(<<<HTML
        <el-card>
            <template #header>
                <el-button :disabled="status != 'wait'" @click="backUp">开始备份</el-button>                                
                <el-button :disabled="status != 'wait'" @click="seeProcess">查看进度</el-button>                                
                <el-button :disabled="status != 'back_up'" @click="cancel">取消备份</el-button>                                
            </template>                            
            <div :style="{height:msgHeight + 'px'}">
                <el-scrollbar ref="scrollArea">
                    <p v-for="item in messages" style="white-space: pre-wrap;word-wrap: break-word" v-html="item"></p>                                
                </el-scrollbar>  
            </div>                          
        </el-card>    
        HTML);

        Html::js()->vue->set('messages', []);
        Html::js()->vue->set('status', 'wait');
        Html::js()->vue->addMethod('backUp', [], JsCode::if('this.tables.length < 1', 'return this.$message.error("请选择备份数据表")')
            ->then('this.status = "back_up"')
            ->then(
            // 发起备份请求
                Axios::post('', ['start' => 1, 'tables' => Grammar::mark('this.tables')])
                    ->then(
                        JsFunc::arrow(['{ data }'])->code(
                            JsCode::if('data.code !== 200', 'return this.$message.error(data.msg)', 'this.getBackUpFile();')
                        )
                    )
            )->then(
                JsFunc::call('this.seeProcess')
            ));

        // 查看进度请求
        Html::js()->vue->addMethod('seeProcess', [], JsCode::create(JsVar::def('seek', 0))
            ->then(
                JsVar::def('getMsg', JsFunc::call('setInterval', JsFunc::arrow()->code(
                    Axios::post('', ['getMessage' => 1, 'seek' => Grammar::mark('seek')])
                        ->then(JsFunc::arrow(['{ data }'])->code(
                            Js::if('data.code !== 200')->then(
                                Js::code('clearInterval(getMsg)'),
                                Js::code('this.status = "wait"'),
                            ),
                            Js::code(
                                Js::code('this.messages.push(...data.messages)'),
                                Js::code('seek = data.seek'),
                                Js::code('this.status = data.type'),
                                Js::if("data.messages.includes('END')")->then(
                                    Js::code('clearInterval(getMsg)'),
                                    Js::code('this.status = "wait"'),
                                ),
                                Js::code('let scrollArea = this.$refs["scrollArea"]'),
                                Js::code('setTimeout(() => scrollArea.setScrollTop(999999999999), 5)'),
                            ),
                        ))
                ), 100))
            ));

        // 取消备份
        Html::js()->vue->addMethod('cancel', [],
            Axios::post('', ['cancel' => 1])->then(JsFunc::arrow([], 'this.status = "wait"'))
        );

        $box->append($card);
    }

    private function recover(AbstractHtmlElement $recoverBox): void
    {
        $files = El::fromCode(<<<HTML
            <el-card>
                <template #header>备份数据</template> 
                <div :style="{height:recoverHeight + 'px'}">
                    <el-scrollbar >
                        <p v-for="item in backUpFile">
                            <span style="width: 300px;text-align: left; display: inline-block">
                                <el-popover placement="left" title="备份信息" :width="400" trigger="hover">
                                    <template #reference>
                                      <el-text class="mx-1">{{ item.filename }}</el-text>
                                    </template>
                                    <template #default>
                                        <el-scrollbar :height="500">
                                              <p v-for="t in item.des"><el-text class="mx-1">{{ t }}</el-text></p>
                                        </el-scrollbar>
                                    </template>
                                </el-popover>
                                <el-text class="mx-1" type="success">[{{ item.filesize }}]</el-text>
                            </span>
                            <el-link type="primary" :disabled="status != 'wait'" :underline="false" @click="recover(item.filename)">恢复</el-link> 
                            <el-text class="mx-1" type="info"> | </el-text>               
                            <el-link type="danger" :underline="false" @click="remove(item.filename)">删除</el-link>                        
                        </p>   
                    </el-scrollbar>
                </div>                  
            </el-card>
            HTML);

        Html::js()->vue->addMethod('recover', ['filename'],
            JsFunc::call('this.$prompt', '请输入验证码', '提示', [
                'inputPattern' => Grammar::mark('/^\w{10}$/'),
                'inputErrorMessage' => '请输入正确的验证码',
                'cancelButtonText' => '取消',
                'confirmButtonText' => '确认',
            ])->call('then', JsFunc::arrow(['value'])->code(
                JsCode::create(
                    Axios::post('', [
                        'filename' => Grammar::mark('filename'),
                        'code'     => Grammar::mark('value.value'),
                        'recover'  => 1
                    ])->then(JsFunc::arrow(['{ data }'])->code(
                        JsCode::if('data.code === 200', 'this.$message.success("恢复成功")', 'this.$message.error(data.msg)')
                    ))
                )->then(
                    'this.status = "recover"'
                )->then(
                    JsFunc::call('this.seeProcess')
                )
            ))
        );
        Html::js()->vue->addMethod('remove', ['filename'],
            JsFunc::call('this.$prompt', '请输入验证码', '提示', [
                'inputPattern' => Grammar::mark('/^\\\w{10}$/'),
                'inputErrorMessage' => '请输入正确的验证码',
                'cancelButtonText' => '取消',
                'confirmButtonText' => '确认',
            ])->call('then', JsFunc::arrow(['value'])->code(
                JsCode::if('value.value',
                    Axios::post('', [
                        'filename' => Grammar::mark('filename'),
                        'code'     => Grammar::mark('value.value'),
                        'remove'   => 1
                    ])->then(JsFunc::arrow(['{ data }'])->code(
                        JsCode::if('data.code === 200',
                            JsCode::create('this.$message.success("删除成功")')->then('this.getBackUpFile()'),
                            JsCode::create('this.$message.error(data.msg)')
                        )
                    )),
                    JsCode::create('this.$message.success("请输入验证码")')->then('return false')
                )
            ))
        );

        $recoverBox->append($files);
    }


    public function commonJs()
    {
        Html::js()->vue->set('windowHeight', '@window.innerHeight - 50');
        Html::js()->vue->set('msgHeight', '@window.innerHeight - 161');
        Html::js()->vue->set('recoverHeight', '@window.innerHeight - 150');

        // 备份文件
        Html::js()->vue->set('backUpFile', []);
        Html::js()->vue->addMethod('getBackUpFile', [], Axios::post('', ['getRecover' => 1])->then(
            JsFunc::arrow(['{ data }'])->code('this.backUpFile = data.data')
        ));

        // 创建完成后获取文件
        Html::js()->vue->event('created', 'this.getBackUpFile();');
    }
}