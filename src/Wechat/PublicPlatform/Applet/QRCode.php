<?php

namespace Sc\Util\Wechat\PublicPlatform\Applet;

use Psr\SimpleCache\InvalidArgumentException;
use Sc\Util\Wechat\Config;
use Sc\Util\Wechat\Execption\WechatException;
use Sc\Util\Wechat\PublicPlatform\AccessToken;
use Sc\Util\Wechat\Request;

/**
 * Class QRCode
 */
class QRCode
{
    final const HOST = [
        'limit'    => 'https://api.weixin.qq.com/wxa/getwxacode?access_token=ACCESS_TOKEN',
        'unLimit'  => 'https://api.weixin.qq.com/wxa/getwxacodeunlimit?access_token=ACCESS_TOKEN',
        'url'      => 'https://api.weixin.qq.com/wxa/generate_urllink?access_token=ACCESS_TOKEN',
        'shortUrl' => 'https://api.weixin.qq.com/wxa/genwxashortlink?access_token=ACCESS_TOKEN',
    ];
    private array $body = [];
    private string $url = '';


    public function __construct(private readonly Config $config)
    {
    }

    /**
     * <li>access_token    string    是    接口调用凭证，该参数为 URL 参数，非 Body 参数。使用getAccessToken 或者 authorizer_access_token</li>
     * <li>scene    string    是    最大32个可见字符，只支持数字，大小写英文以及部分特殊字符：!#$&'()*+,/:;=?@-._~，其它字符请自行编码为合法字符（因不支持%，中文无法使用 urlencode 处理，请使用其他编码方式）</li>
     * <li>page    string    否    默认是主页，页面 page，例如 pages/index/index，根路径前不要填加 /，不能携带参数（参数请放在scene字段里），如果不填写这个字段，默认跳主页面。</li>
     * <li>check_path    bool    否    默认是true，检查page 是否存在，为 true 时 page 必须是已经发布的小程序存在的页面（否则报错）；为 false 时允许小程序未发布或者 page 不存在， 但page 有数量上限（60000个）请勿滥用。</li>
     * <li>env_version    string    否    要打开的小程序版本。正式版为 "release"，体验版为 "trial"，开发版为 "develop"。默认是正式版。</li>
     * <li>width    number    否    默认430，二维码的宽度，单位 px，最小 280px，最大 1280px</li>
     * <li>auto_color    bool    否    自动配置线条颜色，如果颜色依然是黑色，则说明不建议配置主色调，默认 false</li>
     * <li>line_color    object    否    默认是{"r":0,"g":0,"b":0} 。auto_color 为 false 时生效，使用 rgb 设置颜色 例如 {"r":"xxx","g":"xxx","b":"xxx"} 十进制表示</li>
     *      属性    类型    必填    说明
     *      <li>is_hyaline    bool    否    默认是false，是否需要透明底色，为 true 时，生成透明底色的小程序</li>
     * 返回参数
     * 数量无限制
     * @param array|string $param
     *
     * @return $this
     */
    public function unLimit(array|string $param): static
    {
        if (is_string($param)) {
            $param = ['scene' => $param];
        }
        $this->body = $param;
        $this->url  = self::HOST['unLimit'];

        return $this;
    }

    /**
     * access_token    string    是    接口调用凭证，该参数为 URL 参数，非 Body 参数。使用getAccessToken 或者 authorizer_access_token
     * path    string    是    扫码进入的小程序页面路径，最大长度 1024 个字符，不能为空，scancode_time为系统保留参数，不允许配置；对于小游戏，可以只传入 query 部分，来实现传参效果，如：传入 "?foo=bar"，即可在 wx.getLaunchOptionsSync 接口中的 query 参数获取到 {foo:"bar"}。
     * width    number    否    二维码的宽度，单位 px。默认值为430，最小 280px，最大 1280px
     * auto_color    boolean    否    默认值false；自动配置线条颜色，如果颜色依然是黑色，则说明不建议配置主色调
     * line_color    object    否    默认值{"r":0,"g":0,"b":0} ；auto_color 为 false 时生效，使用 rgb 设置颜色 例如 {"r":"xxx","g":"xxx","b":"xxx"} 十进制表示
     * is_hyaline    boolean    否    默认值false；是否需要透明底色，为 true 时，生成透明底色的小程序码
     * env_version    string    否    要打开的小程序版本。正式版为 "release"，体验版为 "trial"，开发版为 "develop"。默认是正式版。
     *
     * @param array $param
     *
     * @return $this
     */
    public function limit(array $param): static
    {
        $this->body = $param;
        $this->url  = self::HOST['limit'];

        return $this;
    }

    /**
     * 获取链接
     * access_token    string    是    接口调用凭证，该参数为 URL 参数，非 Body 参数。使用getAccessToken 或者 authorizer_access_token
     * path    string    否    通过 URL Link 进入的小程序页面路径，必须是已经发布的小程序存在的页面，不可携带 query 。path 为空时会跳转小程序主页
     * query    string    否    通过 URL Link 进入小程序时的query，最大1024个字符，只支持数字，大小写英文以及部分特殊字符：!#$&'()*+,/:;=?@-._~%
     * is_expire    boolean    否    默认值false。生成的 URL Link 类型，到期失效：true，30天有效：false。
     * expire_type    number    否    默认值0.小程序 URL Link 失效类型，失效时间：0，失效间隔天数：1
     * expire_time    number    否    到期失效的 URL Link 的失效时间，为 Unix 时间戳。生成的到期失效 URL Link 在该时间前有效。最长有效期为30天。expire_type 为 0 必填
     * expire_interval    number    否    到期失效的URL Link的失效间隔天数。生成的到期失效URL Link在该间隔时间到达前有效。最长间隔天数为30天。expire_type 为 1 必填
     * cloud_base    object    否    云开发静态网站自定义 H5 配置参数，可配置中转的云开发 H5 页面。不填默认用官方 H5 页面
     * 属性    类型    必填    说明
     * env    string    是    云开发环境
     * domain    string    否    静态网站自定义域名，不填则使用默认域名
     * path    string    否    云开发静态网站 H5 页面路径，不可携带 query
     * query    string    否    云开发静态网站 H5 页面 query 参数，最大 1024 个字符，只支持数字，大小写英文以及部分特殊字符：`!#$&'()*+,/:;=?@-._~%``
     * resource_appid    string    否    第三方批量代云开发时必填，表示创建该 env 的 appid （小程序/第三方平台）
     * env_version    string    否    默认值"release"。要打开的小程序版本。正式版为 "release"，体验版为"trial"，开发版为"develop"，仅在微信外打开时生效
     *
     * @param array $param
     *
     * @return $this
     */
    public function url(array $param): static
    {
        $this->body = $param;
        $this->url  = self::HOST['url'];

        return $this;
    }

    /**
     * 获取短链
     *
     * access_token    string    是    接口调用凭证，该参数为 URL 参数，非 Body 参数。使用access_token或者authorizer_access_token
     * page_url    string    是    通过 Short Link 进入的小程序页面路径，必须是已经发布的小程序存在的页面，可携带 query，最大1024个字符
     * page_title    string    否    页面标题，不能包含违法信息，超过20字符会用... 截断代替
     * is_permanent    boolean    否    默认值false。生成的 Short Link 类型，短期有效：false，永久有效：true
     *
     * @param array $param
     *
     * @return $this
     */
    public function shortUrl(array $param): static
    {
        $this->body = $param;
        $this->url  = self::HOST['shortUrl'];

        return $this;
    }

    /**
     * 获取码
     *
     * @return mixed
     * @throws WechatException|InvalidArgumentException
     */
    public function get(): mixed
    {
        return Request::post(strtr($this->url, ['ACCESS_TOKEN' => AccessToken::get($this->config)]), $this->body)->getRaw();
    }
}