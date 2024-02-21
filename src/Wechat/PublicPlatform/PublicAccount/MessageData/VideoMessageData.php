<?php

namespace Sc\Util\Wechat\PublicPlatform\PublicAccount\MessageData;

/**
 * Class TextMessage
 * @property-read $ToUserName	开发者微信号
 * @property-read $FromUserName	发送方账号（一个OpenID）
 * @property-read $CreateTime	消息创建时间 （整型）
 * @property-read $MsgType	视频为video
 * @property-read $MediaId	视频消息媒体id，可以调用获取临时素材接口拉取数据。
 * @property-read $ThumbMediaId	视频消息缩略图的媒体id，可以调用多媒体文件下载接口拉取数据。
 * @property-read $MsgId	消息id，64位整型
 * @property-read $MsgDataId	消息的数据ID（消息如果来自文章时才有）
 * @property-read $Idx	多图文时第几篇文章，从1开始（消息如果来自文章时才有）
 */
class VideoMessageData extends MessageData
{

}