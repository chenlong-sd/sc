<?php

namespace Sc\Util\Wechat\PublicPlatform\PublicAccount\MessageData;

/**
 * Class LocationEventData
 * @property-read $ToUserName	开发者微信号
 * @property-read $FromUserName	发送方账号（一个OpenID）
 * @property-read $CreateTime	消息创建时间 （整型）
 * @property-read $MsgType	消息类型，event
 * @property-read $Event	事件类型，LOCATION
 * @property-read $Latitude	地理位置纬度
 * @property-read $Longitude	地理位置经度
 * @property-read $Precision	地理位置精度
 */
class LocationEventData extends MessageData
{

}