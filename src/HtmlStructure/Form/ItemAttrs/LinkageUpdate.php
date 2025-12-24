<?php

namespace Sc\Util\HtmlStructure\Form\ItemAttrs;

/**
 * 联动更新
 */
trait LinkageUpdate
{
    protected array $linkageUpdate = [];

    /**
     * @param string $currentFormName 当前表单的name
     * @param string $valueFormat   取值字段，选择的值对象对应同名字段如 [{value:1,label:"测试"}], valueForField为@label时，选中后的值则为测试
     *                              如果是树形数据需要联动父级字段，则为 "@field#P(aa)"  #P() 为标识取所有父级字段，aa 为连接字符, @__parent.field 为父级字段
     *                              可以多字段设置展示模版，如 "@field【@fieldRemark】"
     *
     * @return LinkageUpdate
     *@example $select->linkageUpdate('org_name', '@label#P(/)');
     * @example $select->linkageUpdate('name', '@name(@phone)');
     * @example $select->linkageUpdate('role_name', '@label');
     * @example $select->linkageUpdate('test', '@label[展示]');
     */
    public function linkageUpdate(string $currentFormName, string $valueFormat = '@label'): static
    {
        $this->linkageUpdate[$currentFormName] = $valueFormat;

        return $this;
    }
}