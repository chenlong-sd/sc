<?php
/**
 * datetime: 2023/6/4 11:41
 **/

namespace Sc\Util\HtmlStructure\Form;

use Sc\Util\HtmlStructure\Form;

/**
 * @method string|null getWhen()
 * @method bool|null getHide()
 *
 * Interface FormItemAttrGetter
 */
interface FormItemAttrGetter
{
    public function getDefault();
    public function getOptions();
    public function getFormat();
    public function getOptionsVarName();
    public function getLabelWidth();
    public function getPlaceholder();
    public function getAutoCompleteHandle();
    public function getAutoCompleteFormat();
    public function getVAttrs();
    public function getBeforeRender();
    public function getLabel();
    public function getName();

    /**
     * @return Form
     */
    public function getForm();
    public function getFormModel();
    public function getUploadUrl();

    public function getFullScreen();
    public function getEvents();
    public function getOpenValue();
    public function getCreateUrl();
    public function getUpdateUrl();
    public function getSubmitHandle();
    public function getResetHandle();
    public function getSuccessCloseCode();
    public function getSuccessTipCode();
    public function getSuccess();
    public function getFail();
    public function getCol();
    public function getAfterCol();
    public function getOffsetCol();
    public function getAutoComplete();

    /**
     * @return FormItemInterface[]|FormItemAttrGetter[]|AbstractFormItem[]|FormItemText[]
     */
    public function getChildren(): array;
    public function getTimeType();
    public function getUploadType();
    public function getUploadEl();
    public function getSubmitText();
    public function getResetText();
    public function getMultiple();
    public function getPlain();
    public function getRemoteSearch();
    public function getClosePage();
    public function getRules();
    public function getOptionsRemote();
}