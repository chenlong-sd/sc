<?php

namespace Sc\Util\Wechat\PublicPlatform\PublicAccount\MessageData;

/**
 * Class ViewEventData
 * @property-read $ToUserName	开发者微信号
 * @property-read $FromUserName	发送方账号（一个OpenID）
 * @property-read $CreateTime	消息创建时间 （整型）
 * @property-read $MsgType	消息类型，event
 * @property-read $Event	事件类型，VIEW
 * @property-read $EventKey	事件KEY值，设置的跳转URL
 */
class ViewEventData extends MessageData
{

}