<?php

namespace Sc\Util\Wechat\PublicPlatform\PublicAccount;

use Sc\Util\Wechat\PublicPlatform\PublicAccount\MessageData\ImageMessageData;
use Sc\Util\Wechat\PublicPlatform\PublicAccount\MessageData\LinkMessageData;
use Sc\Util\Wechat\PublicPlatform\PublicAccount\MessageData\LocationMessageData;
use Sc\Util\Wechat\PublicPlatform\PublicAccount\MessageData\ShortVideoMessageData;
use Sc\Util\Wechat\PublicPlatform\PublicAccount\MessageData\TextMessageData;
use Sc\Util\Wechat\PublicPlatform\PublicAccount\MessageData\VideoMessageData;
use Sc\Util\Wechat\PublicPlatform\PublicAccount\MessageData\VoiceMessageData;

/**
 * Interface MessageHandlerInterface
 */
interface MessageHandlerInterface
{
    /**
     * 文本消息
     *
     * @param TextMessageData $textMessageData
     *
     * @return mixed
     */
    public function textMessage(TextMessageData $textMessageData): mixed;

    /**
     * 视屏消息
     *
     * @param VideoMessageData $videoMessageData
     *
     * @return mixed
     */
    public function videoMessage(VideoMessageData $videoMessageData): mixed;

    /**
     * 小视屏消息
     *
     * @param ShortVideoMessageData $videoMessageData
     *
     * @return mixed
     */
    public function shortVideoMessage(ShortVideoMessageData $videoMessageData): mixed;

    /**
     * 位置消息
     *
     * @param LocationMessageData $locationMessageData
     *
     * @return mixed
     */
    public function locationMessage(LocationMessageData $locationMessageData): mixed;

    /**
     * 语音消息
     *
     * @param VoiceMessageData $voiceMessageData
     *
     * @return mixed
     */
    public function voiceMessage(VoiceMessageData $voiceMessageData): mixed;

    /**
     * 链接消息
     *
     * @param LinkMessageData $voiceMessageData
     *
     * @return mixed
     */
    public function linkMessage(LinkMessageData $voiceMessageData): mixed;

    /**
     * 图片消息
     *
     * @param ImageMessageData $voiceMessageData
     *
     * @return mixed
     */
    public function imageMessage(ImageMessageData $voiceMessageData): mixed;

    public function default(?array $data = null): ?string;
}