<?php

namespace Sc\Util\Wechat\PublicPlatform\PublicAccount;

use Sc\Util\Wechat\PublicPlatform\PublicAccount\MessageData\ClickEventData;
use Sc\Util\Wechat\PublicPlatform\PublicAccount\MessageData\LocationEventData;
use Sc\Util\Wechat\PublicPlatform\PublicAccount\MessageData\ScanEventData;
use Sc\Util\Wechat\PublicPlatform\PublicAccount\MessageData\SubscribeEventData;
use Sc\Util\Wechat\PublicPlatform\PublicAccount\MessageData\TEMPLATESENDJOBFINISHEventData;
use Sc\Util\Wechat\PublicPlatform\PublicAccount\MessageData\UnsubscribeEventData;
use Sc\Util\Wechat\PublicPlatform\PublicAccount\MessageData\ViewEventData;

/**
 * 事件处理
 *
 * Interface EventHandlerInterface
 */
interface EventHandlerInterface
{
    /**
     * 订阅
     *
     * @param SubscribeEventData|ScanEventData $eventData
     *
     * @return mixed
     */
    public function subscribeEvent(SubscribeEventData|ScanEventData $eventData): mixed;

    /**
     * 取消订阅
     *
     * @param UnsubscribeEventData $eventData
     *
     * @return mixed
     */
    public function unsubscribeEvent(UnsubscribeEventData $eventData): mixed;

    /**
     * 点击菜单
     *
     * @param ClickEventData $eventData
     *
     * @return mixed
     */
    public function clickEvent(ClickEventData $eventData): mixed;

    /**
     * 点击菜单跳转链接
     *
     * @param ViewEventData $eventData
     *
     * @return mixed
     */
    public function viewEvent(ViewEventData $eventData): mixed;

    /**
     * 上报位置事件
     *
     * @param LocationEventData $eventData
     *
     * @return mixed
     */
    public function locationEvent(LocationEventData $eventData): mixed;

    /**
     * 扫描二维码
     *
     * @param ScanEventData $eventData
     *
     * @return mixed
     */
    public function scanEvent(ScanEventData $eventData): mixed;

    /**
     * 模板消息推送事件
     *
     * @param TEMPLATESENDJOBFINISHEventData $eventData
     *
     * @return mixed
     */
    public function TEMPLATESENDJOBFINISHEvent(TEMPLATESENDJOBFINISHEventData $eventData): mixed;

    public function default(?array $data = null): ?string;
}