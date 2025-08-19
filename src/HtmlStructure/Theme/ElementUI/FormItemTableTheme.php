<?php
/**
 * datetime: 2023/6/3 23:16
 **/

namespace Sc\Util\HtmlStructure\Theme\ElementUI;

use Sc\Util\HtmlElement\El;
use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlElement\ElementType\TextCharacters;
use Sc\Util\HtmlStructure\Form\AbstractFormItem;
use Sc\Util\HtmlStructure\Form\FormItemAttrGetter;
use Sc\Util\HtmlStructure\Form\FormItemInterface;
use Sc\Util\HtmlStructure\Form\FormItemSelect;
use Sc\Util\HtmlStructure\Form\FormItemTable;
use Sc\Util\HtmlStructure\Form\FormItemText;
use Sc\Util\HtmlStructure\Html\Html;
use Sc\Util\HtmlStructure\Html\Js;
use Sc\Util\HtmlStructure\Html\Js\JsFunc;
use Sc\Util\HtmlStructure\Html\StaticResource;
use Sc\Util\HtmlStructure\Layout;
use Sc\Util\HtmlStructure\Table;
use Sc\Util\HtmlStructure\Theme\Interfaces\FormItemTableThemeInterface;
use Sc\Util\ScTool;
use Sc\Util\Tool;

class FormItemTableTheme extends AbstractFormItemTheme implements FormItemTableThemeInterface
{
    /**
     * @param FormItemTable|FormItemAttrGetter $formItem
     *
     * @return AbstractHtmlElement
     * @date 2023/6/4
     */
    public function renderFormItem($formItem): AbstractHtmlElement
    {
        $el = Table::create($this->getVModel($formItem), ScTool::random('TR')->get(111, 999));
        $addHandleEl = $this->addHandle($formItem, $el);

        $el->setPagination(false);
        $el->setOpenSetting(false);

        $this->dataSortIdKeyHandle($el, $formItem);

        $children  = $formItem->getChildren();

        foreach ($children as $index => $child) {
            if ($child instanceof FormItemSelect) {
                $child->setVAttrs('style', 'width:100%');
            }
            $el->addColumns(
                $this->getColumn($child, $formItem, $index)
            );
        }
        if (!$formItem->isReadonly()) {
            $this->handleMake($el, $formItem);
        }

        if ($formItem->getLazyLoadFuncName()) {
            $this->lazy($formItem);
        }

        $el = El::double('el-form-item')->setAttr('label-width', 0)
            ->append($el->render("ElementUI"))
            ->append($formItem->isReadonly() ? "" : $addHandleEl);

        return $el;
    }

    /**
     * @param FormItemTable|FormItemAttrGetter $formItemTable
     *
     * @return AbstractHtmlElement
     */
    private function addHandle(FormItemTable|FormItemAttrGetter $formItemTable, Table $table): AbstractHtmlElement
    {
        Html::css()->addCss('.sc-ft-add.el-icon{font-size: 25px;color: #95d475;margin: 3px;}');
        Html::css()->addCss('.sc-ft-add.el-icon:hover{font-size: 26px;color: #67C23A;margin: 2px;cursor: pointer;transition: all .1s}');

        $method     = "addRow{$formItemTable->getName()}";
        $allowAdd   = "allowAdd" . $formItemTable->getName();
        $rowDefault = [];

        foreach ($formItemTable->getChildren() as $index => $child) {
            $child->setVAttrs(':ref', "'{$table->getId()}I$index' + scope.\$index");
            if ($child instanceof FormItemText) {
                $child->setVAttrs('@focus', "$allowAdd = true");
                $child->setVAttrs('@blur', "$allowAdd = false");
            }

            if ($child->getName()) {
                $rowDefault[$child->getName()] = $child->getDefault();
            }
        }

        $icon = El::double('el-icon')->addClass('sc-ft-add')->setAttr('@click', $method)->append(El::double('Circle-Plus'));
        $text = El::elText("当前可按 Enter 快捷添加行")->setAttrs([
            'type'  => 'warning',
            'style' => 'line-height:28px'
        ]);

        $layout = Layout::create()->addCol(2, $icon)->addCol(21, $text);

        Html::js()->vue->addMethod($method, [], Js::code(
            Js::let('defaultRows', $rowDefault),
            Js::code('defaultRows._id_ = "it-" + Math.random();'),
            Js::code($formItemTable->getLazyLoadFuncName() ? 'defaultRows._edit_ = 1;' : ''),
            Js::call("this.{$this->getVModel($formItemTable)}.push", '@defaultRows'),
            Js::code("this.\$nextTick(() => this.\$refs['{$table->getId()}I0' + (this.{$this->getVModel($formItemTable)}.length - 1)].focus())")
        ));

        Html::js()->vue->event("created", <<<JS
          document.addEventListener("keydown", (event) => {
              if (event.key === "Enter" && this.$allowAdd) {
                  this.{$method}();
              }
            });
        JS);

        return El::fictitious()->append(...$layout->render("ElementUI")->getChildren());
    }


    public function handleMake(Table $table, FormItemInterface|FormItemAttrGetter $formItem): void
    {
        Html::js()->load(StaticResource::SORT_ABLE_JS);

        if (!$table->getId()) {
            mt_srand();
            $table->setId("TR" . mt_rand(1, 999));
        }

        Html::css()->addCss('.sc-ft-draw.el-icon{font-weight: bold;font-size: 20px;margin-right: 10px}');
        Html::css()->addCss('.sc-ft-delete.el-icon{font-weight: bold;font-size: 20px;color:#F56C6C}');
        Html::css()->addCss('.sc-ft-h.el-icon:hover{cursor: pointer}');

        $table->addColumns(
            Table\Column::event('操作')->setAttr('width', 80)->setFormat(h([
                h('el-icon', h('rank'))->addClass('sc-ft-draw sc-ft-h'),
                h('el-icon', h('delete'))->addClass('sc-ft-delete sc-ft-h')
                    ->setAttr('@click', "@tableFormRowDel(@scope, @{$table->getData()})"),
            ]))
        );

        if (Html::js()->vue->isComponent()){
            $name = Html::js()->vue->getComponentName();
            $formItem->getForm()->addAfterRender(Js::call("this.\$refs['$name'].Draw{$table->getId()}"));
        }else{
            $formItem->getForm()->addAfterRender(Js::call("this.Draw{$table->getId()}"));
        }

        Html::js()->vue->addMethod("Draw{$table->getId()}", [], Js::code(
            Js::if("!this.\$refs['{$table->getId()}']")->then(
                Js::return()
            ),
            Js::let("ElDraw{$table->getId()}", "@this.\$refs['{$table->getId()}'].\$el.querySelectorAll('table > tbody')[0]"),
            JsFunc::call('new Sortable', "@ElDraw{$table->getId()}", [
                "handle"    => ".sc-ft-draw",
                "animation" => 150,
                'onUpdate'  => JsFunc::arrow(['evt'])->code(
                    Js::let('currRow', "@this.{$table->getData()}.splice(evt.oldIndex, 1)[0];"),
                    Js::code("this.{$table->getData()}.splice(evt.newIndex, 0, currRow)")
                )
            ])
        ));

        Html::js()->vue->addMethod('tableFormRowDel', ['scope', 'data'], Js::code(
            Js::code('let index = scope.$index'),
            Js::code("this.{$table->getData()}.splice(index, 1)")
        ));
    }

    /**
     * @param Table                            $el
     * @param FormItemTable|FormItemAttrGetter $formItemTable
     *
     * @return void
     */
    private function dataSortIdKeyHandle(Table $el, FormItemTable|FormItemAttrGetter $formItemTable): void
    {
        $el->setAttr('row-key', '_id_');

        $formItemTable->getForm()->setSubmitHandle(Js::code(
            Js::for("var i = 0; i < data.{$formItemTable->getName()}.length; i++")->then(
                "delete data.{$formItemTable->getName()}[i]._id_;"
            )
        ));
    }

    /**
     * @param mixed|FormItemAttrGetter|AbstractFormItem $child
     * @param FormItemTable|FormItemAttrGetter $formItem
     * @param int|string $index
     * @return Table\Column
     */
    private function getColumn(mixed $child, FormItemTable|FormItemAttrGetter $formItem, int|string $index): Table\Column
    {
        $format = $child->render("ElementUI")->each(function (AbstractHtmlElement $element) {
            if ($element instanceof TextCharacters) {
                $text = preg_replace_callback("/\{\{(.*)}}/", function ($match) use ($element) {
                    return sprintf("{{ %s }}", preg_replace("/(?<!\w|\.)\w/", '@$0', $match[1]));
                }, $element->getText());

                $element->setText($text);
                return;
            }

            foreach ($element->getAttrs() as $attr => $value) {
                if (preg_match('/^[v:@]/', $attr)) {
                    if ($attr === 'v-for') {
                        $element->setAttr($attr, preg_replace("/\w+$/", '@$0', $value));
                        continue;
                    }

                    $element->setAttr($attr, preg_replace("/(?<!\w|\.)\w/", '@$0', $value));
                }
            }
        })->find('el-form-item')->getContent();

        return Table\Column::normal($child->getLabel(), $child->getName())
            ->setAttr($formItem->getColumnAttrs($index))
            ->setFormat(
                $formItem->getLazyLoadFuncName()
                    ? h([
                        h('div', $format)->setAttr('v-if', '@scope.row._edit_'),
                        h('div', "{{ {$child->getName()} }}")
                            ->setAttr('v-else')
                            ->setAttr('@click', '@scope.row._edit_ = 1'),
                    ])
                    : $format
            );
    }


    private function lazy(FormItemTable|FormItemAttrGetter $formItem): void
    {
        $v = "this.{$formItem->getFormModel()}.{$formItem->getName()}";

        Html::js()->vue->addMethod($formItem->getLazyLoadFuncName(), ['name', 'value'], Js::code(
            Js::if("!this.{$formItem->getFormModel()}")->then(
                Js::return()
            ),
            Js::let('i', 0),
            Js\JsTimer::setInterval("layz", 1)->call(
                Js::code("{$v}[i]._edit_ = 1"),
                Js::if("i === {$v}.length - 1")->then(
                    Js::code("clearInterval(layz)")
                )->else(
                    Js::assign('i', '@i + 1')
                )
            ),
        ));

        $formItem->getForm()->setSubmitHandle(
            Js::for("let i = 0; i < $v.length; i++")->then(
                Js::code("delete data.{$formItem->getName()}[i]._edit_")
            )
        );
    }
}