<?php

namespace Sc\Util\Wechat\PublicPlatform\PublicAccount\MessageData;

/**
 * Class SubscribeEventData
 * @property-read $ToUserName	开发者微信号
 * @property-read $FromUserName	发送方账号（一个OpenID）
 * @property-read $CreateTime	消息创建时间 （整型）
 * @property-read $MsgType	消息类型，event
 * @property-read $Event	事件类型，CLICK
 * @property-read $EventKey	事件KEY值，与自定义菜单接口中KEY值对应
 */
class ClickEventData extends MessageData
{

}