<?php

namespace Sc\Util\Wechat\PublicPlatform\PublicAccount\TemplateMessage;

/**
 * 消息内容
 *
 * Class Message
 */
class Message
{
    /**
     * 必填 是	接收者openid
     *
     * @var string
     */
    public string $touser = '';
    /**
     * 必填 是	模板ID
     *
     * @var string
     */
    public string $template_id = '';
    /**
     * 必填 否	模板跳转链接（海外账号没有跳转能力）
     *
     * @var string|null
     */
    public ?string $url = null;
    /**
     * 必填 否	跳小程序所需数据，不需跳小程序可不用传该数据
     *  appid 必填 是	所需跳转到的小程序appid（该小程序appid必须与发模板消息的公众号是绑定关联关系，暂不支持小游戏）
     *  pagepath 必填 否	所需跳转到小程序的具体页面路径，支持带参数,（示例index?foo=bar），要求该小程序已发布，暂不支持小游戏
     * @var array{'appid':string, 'pagepath': string}|null
     */
    public ?array $miniprogram = null;

    /**
     * 必填 是	模板数据
     *
     * @var array
     */
    public array $data;
    /**
     * 必填 否	防重入id。对于同一个openid + client_msg_id, 只发送一条消息,10分钟有效,超过10分钟不保证效果。若无防重入需求，可不填
     *
     * @var string|null
     */
    public ?string $client_msg_id;

    /**
     * 设置Data, 以键值对，自动处理为微信要的格式
     *
     * @param array $data
     *
     * @return void
     */
    public function setData(array $data): void
    {
        $this->data = array_map(fn($value) => compact('value'), $data);
    }
}