<?php

namespace Sc\Util\HtmlStructureV2\Dsl;

use Sc\Util\HtmlStructureV2\Components\Block\Alert;
use Sc\Util\HtmlStructureV2\Components\Block\Button;
use Sc\Util\HtmlStructureV2\Components\Block\Divider;
use Sc\Util\HtmlStructureV2\Components\Block\Text;
use Sc\Util\HtmlStructureV2\Components\Block\Title;

final class Blocks
{
    /**
     * 创建一个页面标题块，可继续追加描述文案。
     */
    public static function title(string $text): Title
    {
        return Title::make($text);
    }

    /**
     * 创建一个分割线块，可选中间标题文字。
     */
    public static function divider(?string $text = null): Divider
    {
        return Divider::make($text);
    }

    /**
     * 创建一个纯文本说明块。
     */
    public static function text(string $content): Text
    {
        return Text::make($content);
    }

    /**
     * 创建一个提示块，适合展示告警、说明或成功反馈。
     */
    public static function alert(string $title, ?string $description = null): Alert
    {
        return Alert::make($title, $description);
    }

    /**
     * 创建一个轻量按钮块，适合放在自定义布局里。
     */
    public static function button(string $label): Button
    {
        return Button::make($label);
    }
}
