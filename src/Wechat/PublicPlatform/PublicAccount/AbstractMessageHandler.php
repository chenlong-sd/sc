<?php

namespace Sc\Util\Wechat\PublicPlatform\PublicAccount;

use Sc\Util\Wechat\PublicPlatform\PublicAccount\MessageData\ClickEventData;
use Sc\Util\Wechat\PublicPlatform\PublicAccount\MessageData\ImageMessageData;
use Sc\Util\Wechat\PublicPlatform\PublicAccount\MessageData\LinkMessageData;
use Sc\Util\Wechat\PublicPlatform\PublicAccount\MessageData\LocationEventData;
use Sc\Util\Wechat\PublicPlatform\PublicAccount\MessageData\LocationMessageData;
use Sc\Util\Wechat\PublicPlatform\PublicAccount\MessageData\ScanEventData;
use Sc\Util\Wechat\PublicPlatform\PublicAccount\MessageData\ShortVideoMessageData;
use Sc\Util\Wechat\PublicPlatform\PublicAccount\MessageData\SubscribeEventData;
use Sc\Util\Wechat\PublicPlatform\PublicAccount\MessageData\TEMPLATESENDJOBFINISHEventData;
use Sc\Util\Wechat\PublicPlatform\PublicAccount\MessageData\TextMessageData;
use Sc\Util\Wechat\PublicPlatform\PublicAccount\MessageData\UnsubscribeEventData;
use Sc\Util\Wechat\PublicPlatform\PublicAccount\MessageData\VideoMessageData;
use Sc\Util\Wechat\PublicPlatform\PublicAccount\MessageData\ViewEventData;
use Sc\Util\Wechat\PublicPlatform\PublicAccount\MessageData\VoiceMessageData;

/**
 * Class AbstractMessageHandler
 */
abstract class AbstractMessageHandler implements EventHandlerInterface,MessageHandlerInterface
{
    abstract public function default(?array $data = null): ?string;

    /**
     * 订阅
     *
     * @param SubscribeEventData|ScanEventData $eventData
     *
     * @return mixed
     */
    public function subscribeEvent(ScanEventData|SubscribeEventData $eventData): mixed
    {
        return null;
    }

    /**
     * 取消订阅
     *
     * @param UnsubscribeEventData $eventData
     *
     * @return mixed
     */
    public function unsubscribeEvent(UnsubscribeEventData $eventData): mixed
    {
        return null;
    }

    /**
     * 点击菜单
     *
     * @param ClickEventData $eventData
     *
     * @return mixed
     */
    public function clickEvent(ClickEventData $eventData): mixed
    {
        return null;
    }

    /**
     * 点击菜单跳转链接
     *
     * @param ViewEventData $eventData
     *
     * @return mixed
     */
    public function viewEvent(ViewEventData $eventData): mixed
    {
        return null;
    }

    /**
     * 上报位置事件
     *
     * @param LocationEventData $eventData
     *
     * @return mixed
     */
    public function locationEvent(LocationEventData $eventData): mixed
    {
        return null;
    }

    /**
     * 扫描二维码
     *
     * @param ScanEventData $eventData
     *
     * @return mixed
     */
    public function scanEvent(ScanEventData $eventData): mixed
    {
        return null;
    }

    /**
     * 模板消息结果推送
     *
     * @param TEMPLATESENDJOBFINISHEventData $eventData
     *
     * @return mixed
     */
    public function TEMPLATESENDJOBFINISHEvent(TEMPLATESENDJOBFINISHEventData $eventData): mixed
    {
        return null;
    }

    /**
     * 文本消息
     *
     * @param TextMessageData $textMessageData
     *
     * @return mixed
     */
    public function textMessage(TextMessageData $textMessageData): mixed
    {
        return null;
    }

    /**
     * 视屏消息
     *
     * @param VideoMessageData $videoMessageData
     *
     * @return mixed
     */
    public function videoMessage(VideoMessageData $videoMessageData): mixed
    {
        return null;
    }

    /**
     * 小视屏消息
     *
     * @param ShortVideoMessageData $videoMessageData
     *
     * @return mixed
     */
    public function shortVideoMessage(ShortVideoMessageData $videoMessageData): mixed
    {
        return null;
    }

    /**
     * 位置消息
     *
     * @param LocationMessageData $locationMessageData
     *
     * @return mixed
     */
    public function locationMessage(LocationMessageData $locationMessageData): mixed
    {
        return null;
    }

    /**
     * 语音消息
     *
     * @param VoiceMessageData $voiceMessageData
     *
     * @return mixed
     */
    public function voiceMessage(VoiceMessageData $voiceMessageData): mixed
    {
        return null;
    }

    /**
     * 链接消息
     *
     * @param LinkMessageData $voiceMessageData
     *
     * @return mixed
     */
    public function linkMessage(LinkMessageData $voiceMessageData): mixed
    {
        return null;
    }

    /**
     * 图片消息
     *
     * @param ImageMessageData $voiceMessageData
     *
     * @return mixed
     */
    public function imageMessage(ImageMessageData $voiceMessageData): mixed
    {
        return null;
    }
}