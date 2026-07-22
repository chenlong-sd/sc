<?php

namespace Sc\Util\HtmlStructureV2\Dsl;

use Sc\Util\HtmlStructureV2\Components\Display\DescriptionItem;
use Sc\Util\HtmlStructureV2\Components\Display\Descriptions;
use Sc\Util\HtmlStructureV2\Components\Display\Media\Audio;
use Sc\Util\HtmlStructureV2\Components\Display\Media\File;
use Sc\Util\HtmlStructureV2\Components\Display\Media\Files;
use Sc\Util\HtmlStructureV2\Components\Display\Media\Image;
use Sc\Util\HtmlStructureV2\Components\Display\Media\Images;
use Sc\Util\HtmlStructureV2\Components\Display\Media\Video;
use Sc\Util\HtmlStructureV2\Components\Display\Media\Videos;

final class Displays
{
    /**
 * 详情展示 DSL 工厂：
 * - descriptions() / descriptionItem() - 详情块与单项；
 * - image() / images() - 单图 / 多图（点击预览，多图共享统一预览组）；
 * - video() / videos() - 单视频 / 多视频（点击进入页面共享 el-dialog 自适应播放）；
 * - file() / files() - 单个 / 多个附件（el-link + 自动文件类型图标）。
 *
 * 媒体组件可直接作为 descriptions 详情项的值：
 *
 * ```php
 * Displays::descriptions()->title('用户信息')->columns(2)->border(false)
 *     ->item('姓名', '张三')
 *     ->item('头像', Displays::image('/av.jpg', true))
 *     ->item('作品', Displays::images(['p1.jpg', 'p2.jpg'], 'url')->limit(2))
 *     ->item('介绍视频', Displays::video('/intro.mp4', true)->poster('/p.jpg'))
 *     ->item('附件', Displays::files([['name' => '合同.pdf', 'url' => '/c.pdf', 'size' => '1MB']]));
 * ```
 *
 * 静态/动态两态：第二参数 isStatic=true 时按字面量渲染（src="..."），
 * false 时按 JS 表达式渲染（:src="row.xxx"）。Images/Videos/Files 的数组形态走后端渲染，
 * 字符串形态按 JS 变量 v-for 展开。
 */
    /**
     * 创建一个 descriptions 详情展示块。
     */
    public static function descriptions(): Descriptions
    {
        return Descriptions::make();
    }

    /**
     * 创建一个 descriptions item，可单独设置 span/class/style 等属性后加入 descriptions。
     */
    public static function descriptionItem(string $label, mixed $value): DescriptionItem
    {
        return DescriptionItem::make($label, $value);
    }

    /**
     * 创建一个单图展示，默认 90x90、cover、点击预览。
     *
     * @param string $src 图片地址；isStatic=false 时视为 JS 表达式（如 row.avatar）。
     */
    public static function image(string $src, bool $isStatic = false): Image
    {
        return Image::make($src, $isStatic);
    }

    /**
     * 创建多图展示：数组传真实地址列表，字符串传 JS 变量表达式（如 row.photos）。
     *
     * @param array<int, mixed>|string $sources 支持对象数组项，用 prop 指定真实地址字段。
     * @param string $prop 对象项取真实地址的字段名，空串表示元素本身即地址。
     */
    public static function images(array|string $sources, string $prop = 'url'): Images
    {
        return Images::make($sources, $prop);
    }

    /**
     * 创建单视频展示：点击后在页面共享 el-dialog 自适应播放。
     */
    public static function video(string $src, bool $isStatic = false): Video
    {
        return Video::make($src, $isStatic);
    }

    /**
     * 创建音频列表展示（内联 `<audio controls>`）。
     *
     * @param string[]|string $sources 支持对象数组项，用 prop 指定真实地址字段。
     * @param string $prop 对象项取真实地址的字段名，空串表示元素本身即地址。
     */
    public static function audio(array|string $sources, string $prop = 'url'): Audio
    {
        return Audio::make($sources, $prop);
    }

    /**
     * 创建多视频展示；数据来源同 images()。
     */
    public static function videos(array|string $sources, string $prop = 'url'): Videos
    {
        return Videos::make($sources, $prop);
    }

    /**
     * 创建单个附件展示（el-link + 文件图标）。
     */
    public static function file(string $name, string $url, bool $isStatic = false): File
    {
        return File::make($name, $url, $isStatic);
    }

    /**
     * 创建附件列表展示：数组项形如 ['name'=>...,'url'=>...,'size'=>...]，
     * 字符串项视为只有 url，名称从 url 推导。
     */
    public static function files(
        array|string $sources,
        string $urlProp = 'url',
        string $nameProp = 'name',
        string $sizeProp = 'size'
    ): Files {
        return Files::make($sources, $urlProp, $nameProp, $sizeProp);
    }
}
