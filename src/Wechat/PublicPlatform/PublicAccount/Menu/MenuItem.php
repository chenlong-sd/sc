<?php

namespace Sc\Util\Wechat\PublicPlatform\PublicAccount\Menu;

use JetBrains\PhpStorm\ExpectedValues;

/**
 * 菜单项数据
 *
 * Class MenuItem
 */
class MenuItem
{
    private array $data = [];

    /**
     * @param string $name 菜单标题，不超过16个字节，子菜单不超过60个字节
     *
     * @return MenuItem
     */
    public static function create(string $name): MenuItem
    {
        $item = new self();
        $item->data["name"] = $name;

        return $item;
    }

    /**
     * 添加子菜单，最多5个
     *
     * @param MenuItem $menuItem
     *
     * @return $this
     */
    public function addSubMenu(MenuItem $menuItem): static
    {
        if (!empty($this->data['sub_button']) && count($this->data['sub_button']) >= 5) {
            return $this;
        }

        $this->data['sub_button'][] = $menuItem->toArray();

        return $this;
    }

    /**
     * 设置类型
     *
     * @param string $type
     * <p>click：点击推事件用户点击click类型按钮后，微信服务器会通过消息接口推送消息类型为event的结构给开发者（参考消息接口指南），并且带上按钮中开发者填写的key值，开发者可以通过自定义的key值与用户进行交互；</p>
     * <p>view：跳转URL用户点击view类型按钮后，微信客户端将会打开开发者在按钮中填写的网页URL，可与网页授权获取用户基本信息接口结合，获得用户基本信息。</p>
     * <p>miniprogram：跳转小程序</p>
     * <p>scancode_push：扫码推事件用户点击按钮后，微信客户端将调起扫一扫工具，完成扫码操作后显示扫描结果（如果是URL，将进入URL），且会将扫码的结果传给开发者，开发者可以下发消息。</p>
     * <p>scancode_waitmsg：扫码推事件且弹出“消息接收中”提示框用户点击按钮后，微信客户端将调起扫一扫工具，完成扫码操作后，将扫码的结果传给开发者，同时收起扫一扫工具，然后弹出“消息接收中”提示框，随后可能会收到开发者下发的消息。</p>
     * <p>pic_sysphoto：弹出系统拍照发图用户点击按钮后，微信客户端将调起系统相机，完成拍照操作后，会将拍摄的相片发送给开发者，并推送事件给开发者，同时收起系统相机，随后可能会收到开发者下发的消息。</p>
     * <p>pic_photo_or_album：弹出拍照或者相册发图用户点击按钮后，微信客户端将弹出选择器供用户选择“拍照”或者“从手机相册选择”。用户选择后即走其他两种流程。</p>
     * <p>pic_weixin：弹出微信相册发图器用户点击按钮后，微信客户端将调起微信相册，完成选择操作后，将选择的相片发送给开发者的服务器，并推送事件给开发者，同时收起相册，随后可能会收到开发者下发的消息。</p>
     * <p>location_select：弹出地理位置选择器用户点击按钮后，微信客户端将调起地理位置选择工具，完成选择操作后，将选择的地理位置发送给开发者的服务器，同时收起位置选择工具，随后可能会收到开发者下发的消息。</p>
     * <p>media_id：下发消息（除文本消息）用户点击media_id类型按钮后，微信服务器会将开发者填写的永久素材id对应的素材下发给用户，永久素材类型可以是图片、音频、视频 、图文消息。请注意：永久素材id必须是在“素材管理/新增永久素材”接口上传后获得的合法id。</p>
     * <p>view_limited：跳转图文消息URL用户点击view_limited类型按钮后，微信客户端将打开开发者在按钮中填写的永久素材id对应的图文消息URL，永久素材类型只支持图文消息。请注意：永久素材id必须是在“素材管理/新增永久素材”接口上传后获得的合法id。</p>
     * <p>article_id：用户点击 article_id 类型按钮后，微信客户端将会以卡片形式，下发开发者在按钮中填写的图文消息</p>
     * <p>article_view_limited：类似 view_limited，但不使用 media_id 而使用 article_id</p>
     *
     * @return MenuItem
     */
    public function setType(#[ExpectedValues([
        'click',
        'view',
        'miniprogram',
        'scancode_push',
        'scancode_waitmsg',
        'pic_sysphoto',
        'pic_photo_or_album',
        'pic_weixin',
        'location_select',
        'media_id',
        'view_limited',
        'article_id',
        'article_view_limited',
    ])]string $type): static
    {
        $this->data['type'] = $type;

        return $this;
    }

    /**
     * 设置KEY
     *  click等点击类型必须	菜单KEY值，用于消息接口推送，不超过128字节
     * @param string $key
     *
     * @return $this
     */
    public function setKey(string $key): static
    {
        $this->data['key'] = $key;

        return $this;
    }

    /**
     * view、miniprogram类型必须	网页 链接，用户点击菜单可打开链接，不超过1024字节。 type为miniprogram时，不支持小程序的老版本客户端将打开本url。
     *
     * @param string $url
     *
     * @return $this
     */
    public function setUrl(string $url): static
    {
        $this->data['url'] = $url;

        return $this;
    }

    /**
     * media_id类型和view_limited类型必须	调用新增永久素材接口返回的合法media_id
     *
     * @param string $MediaId
     *
     * @return $this
     */
    public function setMediaId(string $MediaId): static
    {
        $this->data['media_id'] = $MediaId;

        return $this;
    }

    /**
     * miniprogram类型必须	小程序的appid（仅认证公众号可配置）
     *
     * @param string $appid
     *
     * @return $this
     */
    public function setAppid(string $appid): static
    {
        $this->data['appid'] = $appid;

        return $this;
    }

    /**
     * miniprogram类型必须	小程序的页面路径
     *
     * @param string $path
     *
     * @return $this
     */
    public function setPagePath(string $path): static
    {
        $this->data['pagepath'] = $path;

        return $this;
    }

    /**
     * article_id类型和article_view_limited类型必须	发布后获得的合法 article_id
     *
     * @param string $articleId
     *
     * @return $this
     */
    public function setArticleId(string $articleId): static
    {
        $this->data['article_id'] = $articleId;

        return $this;
    }


    public function toArray(): array
    {
        return $this->data;
    }
}