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
use Sc\Util\HtmlStructure\Html\Js\JsFunc;
use Sc\Util\HtmlStructure\Theme\Interfaces\FormItemSwitchThemeInterface;

/**
 * Class FormItemSelectThem
 */
class FormItemSelectTheme extends AbstractFormItemTheme implements FormItemSwitchThemeInterface
{
    /**
     * @param FormItemAttrGetter|FormItemSelect $formItem
     *
     * @return AbstractHtmlElement
     */
    public function renderFormItem($formItem): AbstractHtmlElement
    {
        $base = $this->getBaseEl($formItem);

        $select = El::double('el-select')->setAttrs([
            'v-model'     => $this->getVModel($formItem),
            'placeholder' => $formItem->getPlaceholder(),
            'clearable'   => '',
            'filterable'  => '',
        ]);
        $select->setAttrs($formItem->getVAttrs());

        if (!$optionsVar = $formItem->getOptionsVarName()) {
            mt_srand();
            $optionsVar = $formItem->getName() . 'Rand' .  mt_rand(1, 999);
        }

        $options = El::double('el-option')->setAttrs([
            'v-for'  => "(item, index) in $optionsVar",
            ':key'   => "item.value",
            ':value' => "item.value",
            ':label' => "item.label",
            ':disabled' => "item.disabled",
        ])->setAttrs($formItem->getOptionsAttrs());
        if ($formItem->getFormat()) {
            $options->append($formItem->getFormat());
        }

        if ($formItem->getOptions() && !is_array($formItem->getDefault()) && !in_array($formItem->getDefault(), array_column($formItem->getOptions(), 'value'))) {
            $formItem->default(null);
        }

        if ($formItem->getMultiple()) {
            $select->setAttr('multiple');
        }

        if ((isset($formItem->getForm()?->getConfig()[':inline']) && $formItem->getForm()?->getConfig()[':inline'] === 'true')
            || isset($formItem->getForm()?->getConfig()['inline'])
        ) {
            $select->setAttrIfNotExist('style', 'width:192px');
        }

        $this->setOptions($formItem, $optionsVar);

        $this->remoteSearch($formItem, $select, $optionsVar);

        $this->addEvent($select, $formItem->getEvents(), $formItem->getName());

        return $base->append($select->append($options));
    }

    private function remoteSearch(FormItemSelect|FormItemAttrGetter $formItemSelect, DoubleLabel $select, string $optionsVar): void
    {
        $remoteSearch = $formItemSelect->getRemoteSearch();
        if (!$remoteSearch) return;

        $method = Html::js()->vue->getAvailableMethod($formItemSelect->getName() . "RemoteSearch");
        $select->setAttrs([
            ":remote" => 'true',
            ':remote-method' => $method,
            ':loading' => Html::js()->vue->bind($optionsVar . 'Loading', false),
        ]);

        if ($remoteSearch['code'] instanceof JsFunc) {
            Html::js()->vue->addMethod($method, $remoteSearch['code']);
            return;
        }

        $field = $remoteSearch['code'] ?: $formItemSelect->getName();
        [$showField, $defaultSearchField] = $this->labelAndValue($remoteSearch, $formItemSelect);

        $queryValue = "selectSearchValue" . $formItemSelect->getName();
        Html::js()->vue->set($queryValue, null);
        Html::js()->vue->addMethod($method, JsFunc::anonymous(['query', 'cquery'])->code(
            Js::let('options', $optionsVar),
            Js::if("this.$queryValue === query")->then(
                'return;'
            ),
            Js::assign('this.' . $queryValue, '@query'),
            Js::assign("this.{$optionsVar}Loading", true),
            Axios::get($remoteSearch['url'], [
                'search' => [
                    'search' => [
                        $field => Js::grammar('query'),
                        $defaultSearchField => Js::grammar('cquery')
                    ],
                    'searchType' => [
                        $field => 'like'
                    ],
                ],
                'page' => 1,
                'pageSize' => 20
            ])->success(Js::code(
                Js::for('let i = 0; i < data.data.data.length; i++')->then(
                    Js::if("!data.data.data[i].hasOwnProperty('value')")->then(
                        "data.data.data[i].value = data.data.data[i].id"
                    ),
                    Js::if("!data.data.data[i].hasOwnProperty('label')")->then(
                        "data.data.data[i].label = data.data.data[i].$showField"
                    ),
                ),
                Js::assign("this[options]", '@data.data.data'),
                Js::code($remoteSearch['afterSearchHandle'] ?: ""),
                Js::assign("this.{$optionsVar}Loading", false),
            ))
        ));

        if ($formItemSelect->getForm()) {
            Html::js()->vue->event('mounted', JsFunc::call("this.$method", '', '@this.' . $formItemSelect->getForm()->getId() . '.' . $formItemSelect->getName()));
        }
    }

    private function labelAndValue($remoteSearch, FormItemSelect|FormItemAttrGetter $formItemSelect): array
    {
        $field     = $remoteSearch['code'] ?: $formItemSelect->getName();
        if (str_contains($field, '&')){
            $field = explode('&', $field)[0];
        }

        $fields = explode('.', $field);
        $label = count($fields) == 2 ? $fields[1] : $fields[0];
        $value = $remoteSearch['defaultSearchField'] ?: (count($fields) == 2 ? $fields[0] . '.id' : 'id');

        return [$label, $value];
    }
}