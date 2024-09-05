<?php

namespace Sc\Util\Wechat\PublicPlatform\Applet\SubscribeMessage;

use JetBrains\PhpStorm\ExpectedValues;

/**
 * Class Message
 */
class Message
{
    /**
     * 接收者（用户）的 openid
     *
     * @var string
     */
    public string $touser = '';

    /**
     * 所需下发的订阅模板id
     *
     * @var string
     */
    public string $template_id = '';

    /**
     * 点击模板卡片后的跳转页面，仅限本小程序内的页面。支持带参数,（示例index?foo=bar）。该字段不填则模板无跳转
     *
     * @var string
     */
    public string $page = '';


    /**
     * 模板内容，格式形如 { "key1": { "value": any }, "key2": { "value": any } }的object
     *
     * @var array
     */
    public array $data = [];

    /**
     * 跳转小程序类型：developer为开发版；trial为体验版；formal为正式版；默认为正式版
     *
     * @var string
     */
    public string $miniprogram_state = '';

    /**
     * 进入小程序查看”的语言类型，支持zh_CN(简体中文)、en_US(英文)、zh_HK(繁体中文)、zh_TW(繁体中文)，默认为zh_CN
     *
     * @var string 语言
     */
    public string $lang = 'zh_CN';

    public function setData(array $data): void
    {
        $this->data = array_map(fn($value) => compact('value'), $data);
    }

    /**
     * 跳转小程序类型：developer为开发版；trial为体验版；formal为正式版；默认为正式版
     *
     * @param string $miniprogram_state
     *
     * @return $this
     */
    public function setMiniprogramState(#[ExpectedValues(['developer', 'trial', 'formal'])]string $miniprogram_state): Message
    {
        $this->miniprogram_state = $miniprogram_state;
        return $this;
    }

    /**
     * 进入小程序查看”的语言类型，支持zh_CN(简体中文)、en_US(英文)、zh_HK(繁体中文)、zh_TW(繁体中文)，默认为zh_CN
     *
     * @param string $lang
     *
     * @return $this
     */
    public function setLang(#[ExpectedValues(['zh_CN', 'en_US', 'zh_HK', 'zh_TW'])]string $lang): Message
    {
        $this->lang = $lang;
        return $this;
    }
}