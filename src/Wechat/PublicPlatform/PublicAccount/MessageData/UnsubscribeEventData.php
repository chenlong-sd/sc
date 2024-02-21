<?php

namespace Sc\Util\Wechat\PublicPlatform\PublicAccount\MessageData;

/**
 * Class UnsubscribeEventData
 * @property-read $ToUserName	开发者微信号
 * @property-read $FromUserName	发送方账号（一个OpenID）
 * @property-read $CreateTime	消息创建时间 （整型）
 * @property-read $MsgType	消息类型，event
 * @property-read $Event	事件类型，subscribe(订阅)、unsubscribe(取消订阅)
 */
class UnsubscribeEventData extends MessageData
{

}