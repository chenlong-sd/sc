<?php

namespace Sc\Util\Tool;

/**
 * 百度翻译
 */
class BaiduFanYi
{
    private const BASE_URL = 'https://fanyi-api.baidu.com/api/trans/vip/translate';

    private static ?string $globalSecret = '';
    private static ?string $globalAppid = '';

    private string $appid = '';
    private string $secret = '';

    public function __construct(public string $text){}

    /**
     * 设置对应账号
     *
     * @param string $appid
     * @param string $secret
     *
     * @return BaiduFanYi
     */
    public function setAccount(string $appid, string $secret): static
    {
        $this->appid  = $appid;
        $this->secret = $secret;

        return $this;
    }

    /**
     * 设置全局账号
     *
     * @param string|null $appid
     * @param string|null $secret
     *
     * @return void
     */
    public static function setGlobalAccount(?string $appid, ?string $secret)
    {
        self::$globalAppid or self::$globalAppid = $appid;
        self::$globalSecret or self::$globalSecret = $secret;
    }

    /**
     * @return string
     */
    public function toZh(): string
    {
        return $this->to('zh');
    }

    /**
     * @return string
     */
    public function toEn(): string
    {
        return $this->to('en');
    }

    /**
     * 翻译为什么语言
     *
     * @param string $to {@link https://api.fanyi.baidu.com/doc/21}
     *
     * @return string
     */
    public function to(string $to): string
    {
        $appid     = $this->appid ?: self::$globalAppid;
        $randomStr = microtime();

        $data = [
            'q'     => $this->text,
            'from'  => 'auto',
            'to'    => $to,
            'appid' => $appid,
            'salt'  => $randomStr,
            'sign'  => self::sign($appid, $randomStr)
        ];

        $response = file_get_contents(self::BASE_URL . '?' . http_build_query($data));
        $response = json_decode($response, true);
        if (!empty($response['trans_result'])) {
            return current($response['trans_result'])['dst'];
        }

        return $this->text;
    }

    /**
     * @param string $appid
     * @param string $randomStr
     *
     * @return string
     */
    private function sign(string $appid, string $randomStr): string
    {
        $secret = $this->secret ?: self::$globalSecret;

        return md5($appid . $this->text . $randomStr . $secret);
    }
}
