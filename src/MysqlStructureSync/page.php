<?php

use Sc\Util\HtmlElement\El;
use Sc\Util\HtmlStructure\Html\Html;
use Sc\Util\HtmlStructure\Html\Js;
use Sc\Util\HtmlStructure\Html\Js\Axios;
use Sc\Util\HtmlStructure\Html\Js\JsFunc;
use Sc\Util\HtmlStructure\Layout;

Html::create("结构同步");
Html::loadThemeResource('ElementUI');
Html::loadAdminUtilJs();

$body = h('div');

Html::css()->load('https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/themes/prism-coy.min.css');
Html::css()->load('https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/plugins/line-numbers/prism-line-numbers.min.css');
Html::js()->load('https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/prism.min.js');
Html::js()->load('https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-sql.min.js');
Html::js()->load('https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/plugins/line-numbers/prism-line-numbers.min.js');
Html::css()->addCss(<<<CSS
/* 清除 Prism coy 主题中 pre 伪元素的阴影和装饰 */
/* 精准匹配 Prism coy 主题的 pre 伪元素，强制覆盖 */
.prism-theme-coy pre:before,
.prism-theme-coy pre:after,
pre[class*="language-"]:before,
pre[class*="language-"]:after,
code[class*="language-"]:before,
code[class*="language-"]:after {
  /* 强制移除所有阴影和装饰 */
  box-shadow: none !important;
  border: none !important;
  background: none !important;
  width: 0 !important; /* 消除伪元素宽度 */
}
CSS);


$body->append(
    El::elButton("检测", [
        '@click' => 'detect()',
        'type' => 'primary',
        'style' => 'position:fixed;bottom:50%; right:50%;',
        'v-if' => 'detail.length == 0'
    ]),
);

$layout = h('el-collapse', ['v-if' => 'detail.length > 0']);

$layout->append(h('el-collapse-item', ['title' => '更新SQL'])->append(
    h('pre', ['class' => 'line-numbers'])->append(
        h('code', '{{ detail.filter(v => v.sql).map(item => item.sql).join(\'\n\') }}', ['class' => 'language-sql'])
    )
));
$layout->append(h('el-collapse-item', ['title' => '更新明细'])->append(
    h('div', ['v-for' => 'item in detail'])->append(
        h('div', ['v-if' => 'item.sql'])->append(
            h('el-divider')->append(
                h('div')->append(
                    h('<el-icon><star-filled /></el-icon>'),
                    h(' 【 {{ item.table_name }} 】 表结构差异对比结果 '),
                    h('<el-icon><star-filled /></el-icon>'),
                )->setStyle('{font-size: 20px}'),
            ),
            h('div',  h('b', '对比结果：'))->append(
                h('div', '{{ item.des.replace(\'表结构差异对比结果：\n\n\', \'\') }}')
                    ->setStyle('{white-space: pre-wrap;padding-left: 30px;}')
            ),
            h('div',  h('b', '更新sql：'))->append(
                h('pre', ['class' => 'line-numbers'])->append(h('code', '{{ item.sql.replace(\'\n\', \'\') }}', ['class' => 'language-sql'])),
            ),
        )
    )->setStyle('{overflow-y: auto}')
));

Html::js()->vue->set('detail', []);
Html::js()->vue->addMethod('detect', JsFunc::anonymous()->code(
    Axios::get("", [
        'function' => 'detect'
    ])->success(Js::code(
        Js::assign("this.detail", '@data.data'),
        Js::code('this.$nextTick(() => { Prism.highlightAll(); })')
    ))
));

Html::html()->find('#app')->append($body, $layout);

return Html::toHtml();