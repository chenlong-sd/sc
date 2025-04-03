<?php

namespace Sc\Util\Wechat\PublicPlatform\PublicAccount;

use Sc\Util\Tool\HtmlDocument;
use Sc\Util\Wechat\Config;
use Sc\Util\Wechat\PublicPlatform\PublicAccount\MessageData\SubscribeEventData;
use Sc\Util\Wechat\PublicPlatform\PublicAccount\MessageData\TextMessageData;
use Sc\Util\Wechat\PublicPlatform\PublicAccount\Tool\WXBizMsgCrypt;

/**
 * 公众号消息管理
 *
 * Class EventListenManger
 */
class MessageManger
{
    public function __construct(private readonly Config $config){}

    /**
     * 域名配置
     *
     * @param int    $timestamp 时间戳
     * @param string $nonce     随机字符
     * @param string $signature 签名值
     * @param mixed  $res       成功返回结果
     *
     * @return string
     */
    public function domainConfig(int $timestamp, string $nonce, string $signature, mixed $res): string
    {
        $signArr = [$timestamp, $nonce, $this->config->pub_token];

        sort($signArr, SORT_STRING);

        return sha1(implode($signArr)) == $signature ? $res : "";
    }

    /**
     * 消息处理
     *
     * @param array                                                $post
     * @param MessageHandlerInterface|EventHandlerInterface|string $handler
     * @param array                                                $query
     *
     * @return mixed
     * @throws \Exception
     */
    public function handler(array $post, MessageHandlerInterface|EventHandlerInterface|string $handler, array $query = []): mixed
    {
        if (is_string($handler)) {
            $handler = new $handler();
        }

        if (!$handler instanceof MessageHandlerInterface && !$handler instanceof EventHandlerInterface) {
            throw new \Exception("handler 错误,未实现 MessageHandlerInterface 和 EventHandlerInterface 接口");
        }

        $isEncrypt = !empty($post['Encrypt']);

        $post = $this->messageDecryption($post, $query);

        $res = $post['MsgType'] === 'event'
            ? $this->eventHandler($post, $handler)
            : $this->messageHandler($post, $handler);

        if ($res && is_string($res)) {
            $res = [
                "ToUserName"   => $post["FromUserName"],    // 是	接收方账号（收到的OpenID）
                "FromUserName" => $post["ToUserName"],      // 是	开发者微信号
                "CreateTime"   => time(),                   // 是	消息创建时间 （整型）
                "MsgType"      => "text",                   // 是	消息类型，文本为text
                "Content"      => $res,                     // 是	回复的消息内容（换行：在content中能够换行，微信客户端就支持换行显示）
            ];

            if ($isEncrypt) {
                $res = $this->messageEncrypt($res);
            }
        }

        return $res;
    }

    /**
     * @param array $post
     * @param array $query
     *
     * @return array
     */
    public function messageDecryption(array $post, array $query): array
    {
        $WXBizMsgCrypt = new WXBizMsgCrypt();
        $message = $WXBizMsgCrypt->decryptMsg(
            $post,
            $query,
            $this->config->appid,
            $this->config->EncodingAESKey,
            $this->config->pub_token,
        );

        return (array)simplexml_load_string($message, options: LIBXML_NOCDATA);
    }

    /**
     * 消息加密
     *
     * @param array $data
     *
     * @return int|array
     */
    private function messageEncrypt(array $data): int|array
    {
        $WXBizMsgCrypt = new WXBizMsgCrypt();

        $xml = "";
        foreach ($data as $k => $v) {
            $xml .= "<$k>$v</$k>";
        }
        $xml = "<xml>$xml</xml>";

        return $WXBizMsgCrypt->encryptMsg($xml, $this->config->pub_token, $this->config->appid, $this->config->EncodingAESKey);
    }

    /**
     * @param array                   $data
     * @param MessageHandlerInterface $handler
     *
     * @return mixed
     */
    private function messageHandler(array $data, MessageHandlerInterface $handler): mixed
    {
        try {
            $msgType = $data['MsgType'] == 'shortvideo' ? "shortVideo" : $data['MsgType'];
            $class   = strtr(TextMessageData::class, ['TextMessageData' => ucfirst($msgType) . "MessageData"]);

            $res = $handler->{"{$msgType}Message"}(new $class($data));

            return is_null($res) ? $handler->default() : $res;
        } catch (\Throwable $throwable) {
            return $handler->default($data);
        }
    }

    /**
     * @param array                 $data
     * @param EventHandlerInterface $handler
     *
     * @return mixed
     */
    private function eventHandler(array $data, EventHandlerInterface $handler): mixed
    {
        try {
            $class = strtr(SubscribeEventData::class, ['SubscribeEventData' => ucfirst($data['Event']) . "EventData"]);

            $res = $handler->{"{$data['Event']}Event"}(new $class($data));

            return is_null($res) ? $handler->default() : $res;
        } catch (\Throwable $throwable) {
            return $handler->default($data);
        }
    }
}