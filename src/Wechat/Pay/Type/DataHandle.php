<?php

namespace Sc\Util\Wechat\Pay\Type;

use Sc\Util\Wechat\Execption\WechatException;
use Sc\Util\Wechat\Request;
use Sc\Util\Wechat\Tool;
use Sc\Util\Wechat\WechatPay;

/**
 * Trait DataHandle
 *
 * @author chenlong<vip_chenlong@163.com>
 * @date   2022/5/27 9:11
 */
trait DataHandle
{
    /**
     * 获取支付数据
     *
     * @param bool $closeFirst 是否需要先关闭订单
     *
     * @return array
     * @throws WechatException
     * @author chenlong<vip_chenlong@163.com>
     * @date   2022/5/27 9:12
     */
    public function getPayData(bool $closeFirst = false): array
    {
        if ($closeFirst) {
            WechatPay::common($this->config)->closeOrder($this->out_trade_no);
        }

        $sign = Tool::config($this->config)->v3Sign(self::HOST, $data = $this->getRequestData(), 'POST');

        $response = Request::postV3(self::HOST, $data, $sign);

        if (empty($response->getData('prepay_id'))) {
            throw new WechatException($response->getData());
        }

        if ($this instanceof Applet) {
            return $this->jsCallData($response->getData('prepay_id'));
        }

        return $response->getData();
    }

    /**
     * @param string $prepay_id
     *
     * @return array
     */
    private function jsCallData(string $prepay_id): array
    {
        mt_srand();

        $signData = [
            'appId'     => $this->config->appid,
            'timeStamp' => (string)time(),
            'nonceStr'  => "S" . time() . mt_rand(0, 888),
            'package'   => 'prepay_id=' . $prepay_id,
        ];

        $signData['paySign']  = Tool::config($this->config)->getSignature($signData);
        $signData['signType'] = 'RSA';

        return $signData;
    }


    /**
     * 获取请求数据
     *
     * @return array
     *
     * @author chenlong<vip_chenlong@163.com>
     * @date   2022/5/27 9:12
     */
    private function getRequestData(): array
    {
        empty($this->appid) and $this->appid = $this->config->get('appid');
        empty($this->mchid) and $this->mchid = $this->config->get('mch_id');
        empty($this->out_trade_no) and $this->out_trade_no = date('Ymd') .mt_rand(10000, 99999) . time();

        if (!str_starts_with($this->notify_url, 'http')) {
            $this->notify_url = sprintf('%s://%s/%s', Tool::isHttps() ? 'https://' : 'http://', $_SERVER['HTTP_HOST'], $this->notify_url);
        }

        $data = json_decode(json_encode($this), true);
        unset($data['config']);

        return $this->filter($data);
    }

    /**
     * 空数据过滤
     *
     * @param array $data
     *
     * @return array
     *
     * @author chenlong<vip_chenlong@163.com>
     * @date   2022/5/26 17:19
     */
    public function filter(array $data = []): array
    {
        return array_filter(array_map(fn($v) => is_array($v) ? $this->filter($v) : $v, $data));
    }
}
