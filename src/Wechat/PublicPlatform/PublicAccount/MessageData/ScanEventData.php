<?php

namespace Sc\Util\Wechat\PublicPlatform\PublicAccount\MessageData;

/**
 * Class ScanEventData
 * @property-read $ToUserName	开发者微信号
 * @property-read $FromUserName	发送方账号（一个OpenID）
 * @property-read $CreateTime	消息创建时间 （整型）
 * @property-read $MsgType	消息类型，event
 * @property-read $Event	事件类型，subscribe,SCAN
 * @property-read $EventKey	subscribe：qrscene_为前缀，后面为二维码的参数值,SCAN：是一个32位无符号整数，即创建二维码时的二维码scene_id
 * @property-read $Ticket	二维码的ticket，可用来换取二维码图片
 */
class ScanEventData extends MessageData
{

}