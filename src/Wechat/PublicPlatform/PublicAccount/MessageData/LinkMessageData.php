<?php

namespace Sc\Util\Wechat\PublicPlatform\PublicAccount\MessageData;

/**
 * Class LinkMessageData
 * @property-read $ToUserName	接收方微信号
 * @property-read $FromUserName	发送方微信号，若为普通用户，则是一个OpenID
 * @property-read $CreateTime	消息创建时间
 * @property-read $MsgType	消息类型，链接为link
 * @property-read $Title	消息标题
 * @property-read $Description	消息描述
 * @property-read $Url	消息链接
 * @property-read $MsgId	消息id，64位整型
 * @property-read $MsgDataId	消息的数据ID（消息如果来自文章时才有）
 * @property-read $Idx	多图文时第几篇文章，从1开始（消息如果来自文章时才有）
 */
class LinkMessageData extends MessageData
{

}