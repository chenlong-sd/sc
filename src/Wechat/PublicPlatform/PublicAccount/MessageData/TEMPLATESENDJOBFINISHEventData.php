<?php

namespace Sc\Util\Wechat\PublicPlatform\PublicAccount\MessageData;

/**
 * Class TEMPLATESENDJOBFINISHEventData
 * @property-read $ToUserName	公众号微信号
 * @property-read $FromUserName	接收模板消息的用户的openid
 * @property-read $CreateTime	创建时间
 * @property-read $MsgType	    消息类型是事件
 * @property-read $Event	    事件为模板消息发送结束
 * @property-read $MsgID	    消息id
 * @property-read $Status	    发送状态为成功
 */
class TEMPLATESENDJOBFINISHEventData extends MessageData
{

}