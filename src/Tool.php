<?php

declare(strict_types=1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://hyperf.wiki
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf/hyperf/blob/master/LICENSE
 */
namespace Sc\Util;

use Sc\Util\Tool\BaiduFanYi;
use Sc\Util\Tool\Ciphertext;
use Sc\Util\Tool\ClassProxy;
use Sc\Util\Tool\Dir;
use Sc\Util\Tool\Excel\XlsWriter;
use Sc\Util\Tool\HtmlDocument;
use Sc\Util\Tool\JWT;
use Sc\Util\Tool\Lock;
use Sc\Util\Tool\Nickname;
use Sc\Util\Tool\Random;
use Sc\Util\Tool\RemoteResourceDownload;
use Sc\Util\Tool\SensitiveWord;
use Sc\Util\Tool\Tree;
use Sc\Util\Tool\Url;

/**
 *  Class Tool.
 * @method static Tree tree(array $data, bool $currentIsTreeData = false)
 * @method static RemoteResourceDownload download()
 * @method static HtmlDocument dom(string $tag = '', bool $isASingleLabel = false)
 * @method static HtmlDocument stringToDom(string $htmlCode)
 * @method static BaiduFanYi baiduFanYi(string $text)
 * @method static JWT jwt(array $config = [])
 * @method static Dir dir(string $dir)
 * @method static Nickname nickname()
 * @method static ClassProxy classProxy(object $class)
 * @method static Url url(?string $url = null)
 * @method static SensitiveWord SensitiveWord(string ...$text)
 * @method static Ciphertext ciphertext(string $secret)
 * @method static Random random(?string $prefix = null)
 * @method static XlsWriter xls(array|string $config)
 * @method static Lock lock(string $key, int $ttl = 5, int $waitTime = 0)
 *
 * @see ScTool
 * @date 2022/2/20
 * @deprecated 后续可继续使用，但是不提供代码提示的编写，新的转移到ScTool类中,以便更好的代码提示
 */
class Tool
{
    public static function __callStatic(string $name, array $arguments)
    {
        return ScTool::$name(...$arguments);
    }
}
