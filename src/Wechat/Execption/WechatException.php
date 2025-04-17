<?php

namespace Sc\Util\Wechat\Execption;

/**
 * 微信请求异常类
 * Class WechatException
 *
 * @author chenlong<vip_chenlong@163.com>
 * @date   2022/5/27 9:18
 */
class WechatException extends \Exception
{
    /**
     * @var mixed|string
     */
    private mixed $wechatMessage;

    public function __construct(string|array $message = "", int $code = 0, ?\Throwable $previous = null)
        {
            if ($message) {
                $this->wechatMessage = $message['message'] ?? '';
                $message = json_encode($message, JSON_UNESCAPED_UNICODE);
            }

            parent::__construct($message, $code, $previous);
        }

    /**
     * @return mixed|string
     */
    public function getWechatMessage(): mixed
    {
        return $this->wechatMessage;
    }
}
