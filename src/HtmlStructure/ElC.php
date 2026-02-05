<?php

namespace Sc\Util\HtmlStructure;

use Sc\Util\HtmlStructure\ElementComponent\Image;
use Sc\Util\HtmlStructure\ElementComponent\Images;
use Sc\Util\HtmlStructure\ElementComponent\Mapping;
use Sc\Util\HtmlStructure\ElementComponent\Video;
use Sc\Util\HtmlStructure\ElementComponent\Videos;
use Sc\Util\StaticCall;

/**
 * 创建元素组件.
 * @method static Image image(string $src, bool $isRealPath = false) 创建图片组件. isRealPath 是否是真是路径，判断是否是变量
 * @method static Images images(array|string $images, string $srcPath = 'url') 创建图片组件 images： 图片数据（数组）或js变量，srcPath：图片路径获取路径，[{src:''}]就是 src
 * @method static Mapping mapping(string|int $value, string $valueKey = 'value', string $labelKey = 'label') 创建链接组件.
 * @method static Video video(string $src, bool $isRealPath = false) 创建视频组件.
 * @method static Videos videos(array|string $videos, string $srcPath = 'url') 创建视频组件 videos： 视频数据（数组）或js变量，srcPath：视频路径获取路径，[{src:''}]就是 src
 */
class ElC extends StaticCall
{

    /**
     * 获取累的完全限定名称.
     *
     * @date 2022/2/20
     */
    protected static function getClassFullyQualifiedName(string $shortClassName): string
    {
        return  __NAMESPACE__ . '\\ElementComponent\\'.$shortClassName;
    }
}