<?php

namespace Sc\Util\HtmlStructure\Theme\ElementUI;

use Sc\Util\HtmlElement\ElementType\AbstractHtmlElement;
use Sc\Util\HtmlStructure\Detail;
use Sc\Util\HtmlStructure\Html\Html;
use Sc\Util\HtmlStructure\Theme\Interfaces\DetailThemeInterface;
use Sc\Util\ScTool;

class DetailTheme implements DetailThemeInterface
{

    public function render(Detail $detail): AbstractHtmlElement
    {
        $baseEl = h('el-descriptions');

        if ($detail->getTitle() instanceof \Stringable) {
            $baseEl->append(
                h('template', h($detail->getTitle()), ['#title' => ''])
            );
        }else{
            $baseEl->setAttr('title', $detail->getTitle());
        }

        if ($detail->getExtra()){
            $baseEl->append(
                h('template', h($detail->getExtra()), ['#extra' => ''])
            );
        }

        $baseEl->setAttrs($detail->getAttr());

        Html::css()->addCss(".di-none{display: none}");
        foreach ($detail->getItems() as $item) {
            if (empty($item['label'])) {
                $item['attr']['label-class-name'] = empty($item['attr']['label-class-name'])
                    ? "di-none"
                    : $item['attr']['label-class-name'] . " di-none";
            }

            $itemEl = h('el-descriptions-item', $item['value'], $item['attr']);
            if ($item['label'] instanceof \Stringable) {
                $itemEl->append(
                    h('template', h($item['label']), ['#label' => ''])
                );
            }else{
                $itemEl->setAttr('label', $item['label']);
            }
            $baseEl->append($itemEl);
        }

        if ($detail->getData() || $detail->getDataModel()) {
            $code = $baseEl->toHtml();
            $model = $detail->getDataModel() ?: ScTool::random('DM')->get(10000, 99999);

            $code = preg_replace_callback('/\{\{(.*)}}/',  function ($match) use ($model){
                return strtr("{{ " . preg_replace('/[^\w@.](\w)/', "$model.$1", $match[1]) . ' }}', ['@' => '']);
            }, $code);
            $baseEl = h($code);

            Html::js()->vue->set($model, $detail->getData() ?: "@{}");
        }


        return $baseEl;
    }
}