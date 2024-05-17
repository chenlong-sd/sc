<?php

namespace Sc\Util\HtmlStructure\Theme\ElementUI;

use Sc\Util\HtmlElement\El;
use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlElement\ElementType\DoubleLabel;
use Sc\Util\HtmlStructure\Html\Html;
use Sc\Util\HtmlStructure\Form\FormItemAttrGetter;
use Sc\Util\HtmlStructure\Form\FormItemSelect;
use Sc\Util\HtmlStructure\Html\Js;
use Sc\Util\HtmlStructure\Html\Js\Axios;
use Sc\Util\HtmlStructure\Html\Js\Grammar;
use Sc\Util\HtmlStructure\Html\Js\JsCode;
use Sc\Util\HtmlStructure\Html\Js\JsFunc;
use Sc\Util\HtmlStructure\Theme\Interfaces\FormItemSelectThemeInterface;

/**
 * Class FormItemSelectThem
 */
class FormItemSelectTheme extends AbstractFormItemTheme implements FormItemSelectThemeInterface
{

    public function render(FormItemSelect|FormItemAttrGetter $formItemSelect): AbstractHtmlElement
    {
        $base = $this->getBaseEl($formItemSelect);

        $select = El::double('el-select')->setAttrs([
            'v-model'     => $this->getVModel($formItemSelect),
            'placeholder' => $formItemSelect->getPlaceholder(),
            'clearable'   => '',
            'filterable'  => '',
        ]);
        $select->setAttrs($formItemSelect->getVAttrs());

        if (!$optionsVar = $formItemSelect->getOptionsVarName()) {
            mt_srand();
            $optionsVar = $formItemSelect->getName() . 'Rand' .  mt_rand(1, 999);
        }

        $options = El::double('el-option')->setAttrs([
            'v-for'  => "(item, index) in $optionsVar",
            ':key'   => "item.value",
            ':value' => "item.value",
            ':label' => "item.label",
        ]);

        if ($formItemSelect->getOptions() && !is_array($formItemSelect->getDefault()) && array_search($formItemSelect->getDefault(), array_column($formItemSelect->getOptions(), 'value')) === false) {
            $formItemSelect->default(null);
        }

        if ($formItemSelect->getMultiple()) {
            $select->setAttr('multiple');
        }
        if ($formItemSelect->getCol()) {
            $select->setAttrIfNotExist('style', 'width:100%');
        }

        $this->remoteSearch($formItemSelect, $select, $optionsVar);

        $this->addEvent($select, $formItemSelect->getEvents(), $formItemSelect->getName());

        $this->setOptions($formItemSelect, $optionsVar);

        return $this->afterRender($formItemSelect, $base->append($select->append($options)));
    }

    private function remoteSearch(FormItemSelect|FormItemAttrGetter $formItemSelect, DoubleLabel $select, string $optionsVar): void
    {
        $remoteSearch = $formItemSelect->getRemoteSearch();
        if (!$remoteSearch) return;

        $method = Html::js()->vue->getAvailableMethod($formItemSelect->getName() . "RemoteSearch");
        $select->setAttrs([
            ":remote" => 'true',
            ':remote-method' => $method
        ]);

        if ($remoteSearch['code'] instanceof JsFunc) {
            Html::js()->vue->addMethod($method, $remoteSearch['code']);
            return;
        }

        $field     = $remoteSearch['code'] ?: $formItemSelect->getName();
        $fields    = explode('.', $field);
        $showField = count($fields) == 2 ? $fields[1] : $fields[0];

        $defaultSearchField = $remoteSearch['defaultSearchField'] ?: (count($fields) == 2 ? $fields[0] . '.id' : 'id');

        $queryValue = "selectSearchValue" . $formItemSelect->getName();
        Html::js()->vue->set($queryValue, null);
        Html::js()->vue->addMethod($method, JsFunc::anonymous(['query', 'cquery'])->code(
            Js\JsVar::def('options', $optionsVar),
            Js\JsIf::when('this.' . $queryValue . ' === query')->then(
                'return;'
            ),
            Js\JsVar::assign('this.' . $queryValue, '@query'),
            Axios::get($remoteSearch['url'], [
                'search' => [
                    'search' => [
                        $field => Grammar::mark('query'),
                        $defaultSearchField => Grammar::mark('cquery')
                    ],
                    'searchType' => [
                        $field => 'like'
                    ],
                ],
                'page' => 1,
                'pageSize' => 20
            ])->success(JsCode::make(
                Js\JsFor::loop('let i = 0; i < data.data.data.length; i++')->then(
                    Js\JsIf::when("!data.data.data[i].hasOwnProperty('value')")->then(
                        "data.data.data[i].value = data.data.data[i].id"
                    ),
                    Js\JsIf::when("!data.data.data[i].hasOwnProperty('label')")->then(
                        "data.data.data[i].label = data.data.data[i].$showField"
                    ),
                ),
                Js\JsVar::assign("this[options]", '@data.data.data'),
                $remoteSearch['afterSearchHandle'] ?: "",
            ))
        ));

        Html::js()->vue->event('mounted', JsFunc::call("this.$method", '', '@this.' . $formItemSelect->getForm()->getId() . '.' . $formItemSelect->getName()));
    }
}