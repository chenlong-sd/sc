<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2019/2/28
 * Time: 10:35
 */

namespace Sc\Util\Tool;

use JetBrains\PhpStorm\ExpectedValues;

/**
 * Class JWT
 *
 * @example
 *         JWT::getToken($var); 直接获取token ，$var 为自己的参数可不传
 *         JWT::getRefresh(7, $var)::getToken($var);需要获取refreshToken参数时
 *         JWT::setExp(86400)::getToken($var); 设置过期时间
 * @package app\interactive\controller
 */

class JWT
{
    const EXPIRE_CODE = 1001;
    const ERROR_CODE = 1002;
    const WAIT_CODE = 1003;
    const SIGN_CODE = 1004;


    /** @var string 秘钥 */
    const SECRET = 'SD_CL-CS_LOVE**KK';

    /** @var string token类型 */
    const TYPE = 'JWT';

    /** @var string 更新token的秘钥 */
    const REFRESH = 'SD_LO_VE_CL_CS';

    /** @var int 加密串前置随机子串 */
    const START_LEN = 3;

    /** @var string 加密串插入子串 */
    const MIDDLE_LEN = "8:3";

    /** @var int 加密串后置随机子串 */
    const END_LEN = 3;

    /** @var string 加密算法类型 */
    private string $alg = 'sha256';

    /**
     * token的基本数据
     * @var array
     */
    private array $payload = [];

    /**
     * @var array refreshToken 数据
     */
    private array $refresh = [];

    /**
     * 携带的数据
     * @var array
     */
    private array $data = [];

    /**
     * 配置
     *
     * @var array
     */
    private array $config = [
        'SECRET'     => self::SECRET,
        'REFRESH'    => self::REFRESH,
        'START_LEN'  => self::START_LEN,
        'MIDDLE_LEN' => self::MIDDLE_LEN,
        'END_LEN'    => self::END_LEN,
    ];

    /**
     * JWT constructor.
     *
     * @param array{"SECRET":int, "REFRESH":int, "START_LEN":int, "MIDDLE_LEN":string, "END_LEN":int} $config 载荷，有效信息
     *
     * @example
     *        以下为data的默认参数，可以有额外参数
     *           $data = [
     *              'iss' => 'jwt_admin',               // 签发者
     *              'iat' => time(),                    // 签发时间
     *              'exp' => time()+7200,               //  jwt的过期时间，这个过期时间必须要大于签发时间
     *              'nbf' => time()+60,                 // 定义在什么时间之前，该jwt都是不可用的
     *              'sub' => 'www.admin.com',           // 主题
     *              'aud' => 'www.admin.com',           //  接收jwt的一方
     *              'jti' => md5(uniqid('JWT').time())  // 该Token唯一标识
     *              'rsh' => 'asdasd'                   // 需要刷新操作的时候,refreshToken的唯一标识jti值，必须
     *           ]
     */
    public function __construct(array $config = [])
    {
        $this->alg  = array_rand(array_flip(hash_hmac_algos()));

        $this->injectStrConfig($config);
        $this->setIat()->setNbf()->setExp()->setIss()->setJti();
    }

    /**
     * 注入配置
     *
     * @param array $config
     *
     * @return JWT
     *
     * @author chenlong<vip_chenlong@163.com>
     * @date   2022/5/16 14:31
     */
    public function injectStrConfig(array $config): static
    {
        foreach ($this->config as $key => $value) {
            empty($config[$key]) or $this->config[$key] = $value;
        }

        return $this;
    }

    /**
     * 获取配置
     *
     * @param string $key
     *
     * @return mixed
     *
     * @author chenlong<vip_chenlong@163.com>
     * @date   2022/5/16 14:37
     */
    private function getConfig(#[ExpectedValues(['SECRET', 'REFRESH', 'START_LEN', 'MIDDLE_LEN', 'END_LEN',])] string $key): mixed
    {
        return $this->config[$key];
    }

    /**
     * 获取token
     * @return array
     */
    public function getToken(): array
    {
        $payload = self::base64UrlEncode(array_merge($this->data, $this->payload));
        $header  = self::getHeader();
        $sign    = hash_hmac($this->alg, $header . '.' . $payload, $this->getConfig('SECRET'));
        $token   = implode('.', [$this->strInject($header), $this->strInject($payload), $this->strInject(self::base64UrlEncode($sign))]);

        $tokenData = [
            'token' => $token,
            'token_exp' => $this->payload['exp'] ?? 0
        ];
        return array_merge($tokenData, $this->refresh);
    }

    /**
     * 获取刷新token 的 refreshToken，当token过期的时候可以用此refreshToken来刷新token
     * 返回refreshToken 以及他的唯一标识 jti
     * @param int $exp 过期时间（单位：天）
     * @param array $fill_data 额外参数
     * @return self
     */
    public function getRefresh(int $exp = 30, array $fill_data = []): JWT
    {
        $data = [
            'exp' => time() + 60 * 60 * 24 * $exp,
            'jti' => md5(uniqid($this->getConfig('REFRESH')) . mt_rand(0, 99))
        ];

        $data = array_merge($data, $fill_data);
        // 加密
        $base64UrlEncode = self::base64UrlEncode($data);
        //        签名
        $sign = hash_hmac($this->alg, $base64UrlEncode, $this->getConfig('REFRESH'));

        $refreshToken = $base64UrlEncode . '.' . self::base64UrlEncode($sign);

        $this->refresh = [
            'refresh_token'     => $refreshToken,
            'refresh_token_exp' => $data['exp']
        ];

        $this->payload['rsh'] = $data['jti'];
        return $this;
    }

    /**
     * 刷新token并返回新的token
     * @param string $refreshToken 刷新token 需要用到的refreshToken参数
     * @param string $token 原token
     * @return array
     * @throws \Exception
     */
    public function refreshToken(string $token, string $refreshToken): array
    {
        $refreshToken = explode('.', $refreshToken);
        $tokenPayload = $this->verify($token, false);

        // 数据格式不对 或 token 验证失败
        if (count($refreshToken) != 2 || empty($tokenPayload)) {
            throw new \Exception('Refresh Token format error');
        }

        list($data, $sign) = $refreshToken;
        $data = json_decode(self::base64UrlDecode($data), true);

        // 已超时
        if (empty($data['exp']) || $data['exp'] < time()) {
            throw new \Exception("Refresh Token has expired");
        }
        // refreshToken的唯一ID和当前的token对不上
        if (empty($data['jti']) || empty($tokenPayload['rsh']) || $data['jti'] != $tokenPayload['rsh']) {
            throw new \Exception("RefreshToken and Token do not match");
        }
        $refreshSign = hash_hmac($this->alg, self::base64UrlEncode($data), $this->getConfig('REFRESH'));

        // 签名不对
        if (self::base64UrlEncode($refreshSign) != $sign) {
            throw new \Exception('RefreshToken signature error');
        }

        unset($tokenPayload['iat'],$tokenPayload['exp'],$tokenPayload['nbf']);
        if ($this->refresh) {
            unset($tokenPayload['rsh']);
        }

        $this->data = $tokenPayload;
        return $this->getToken();
    }

    /**
     * token 根据自身数据刷新
     *
     * @param string $token
     * @param int    $exp
     *
     * @return array
     * @throws \Exception
     */
    public function selfRefresh(string $token, int $exp = 3600): array
    {
        $tokenPayload = $this->verify($token, false);

        unset($tokenPayload['iat'], $tokenPayload['exp'], $tokenPayload['nbf']);
        $this->data = $tokenPayload;
        $this->payload['jti']  = $tokenPayload['jti'];
        $this->payload['jtiv'] = ($tokenPayload['jtiv'] ?? 0) + 1;

        $this->setExp($exp);

        return $this->getToken();
    }

    /**
     * token 验证外部接口
     * @param string $token token值
     * @param bool  $time_verify 时间验证
     * @return mixed
     * @throws \Exception
     */
    public function tokenVerify(string $token = '', bool $time_verify = true): mixed
    {
        return $this->verify($token, $time_verify);
    }

    /**
     * 设置签发时间
     * @param int $time
     * @return JWT
     */
    public function setIat(int $time = 0): JWT
    {
        if(empty($time)){
            $this->payload['iat'] = $this->payload['iat'] ?? time();
        }else{
            $this->payload['iat'] = time() + $time;
        }
        return $this;
    }

    /**
     * 设置过期时间
     * @param int $time 单位秒,设置null则不过期
     * @return JWT
     */
    public function setExp(int $time = 0): JWT
    {
        if ($time ===  null) {
            $this->payload['exp'] = null;
        }else if (empty($time)){
            $exp = ($this->payload['iat'] ?? time()) + 60;
            $this->payload['exp'] = $this->payload['exp'] ?? $exp;
        }else{
            $this->payload['exp'] = time() + $time;
        }
        return $this;
    }

    /**
     * 设置生效时间
     * @param int $time
     * @return JWT
     */
    public function setNbf(int $time = 0): JWT
    {
        if (!empty($time)) {
            $this->payload['nbf'] = $time;
        }
        return $this;
    }

    /**
     * 设置token唯一标识
     *
     * @param string $jti
     *
     * @return JWT
     */
    public function setJti(string $jti = ''): JWT
    {
        if (empty($jti)){
            mt_srand();
            $this->payload['jti'] = $this->payload['jti'] ?? uniqid('jti') . mt_rand(0, 9999);
        }else{
            $this->payload['jti'] = $jti;
        }
        $this->payload['jtiv'] = 0;
        return $this;
    }

    /**
     * 设置签发者
     *
     * @param string $iss
     *
     * @return JWT
     */
    public function setIss(string $iss = ''): JWT
    {
        if(empty($iss)){
            $this->payload['iss'] = $this->payload['iss'] ?? 'SD_CL';
        }else{
            $this->payload['iss'] = $iss;
        }
        return $this;
    }

    /**
     * token验证并返回payload数据
     * @param string $token
     * @param bool $time_verify 是否验证时效性
     * @return mixed
     * @throws \Exception
     */
    private function verify(string $token, bool $time_verify = true): mixed
    {
        $data = explode('.', $token);

        //  格式对不上，失败
        if (count($data) != 3) {
            throw new \Exception("Token format error", self::ERROR_CODE);
        }

        list($header, $payload, $sign) = $data;

        $header  = json_decode(self::base64UrlDecode($this->strDetach($header)), true);
        $payload = json_decode(self::base64UrlDecode($this->strDetach($payload)), true);

        // 数据对不上， 失败
        if (!$header || empty($header['alg']) || !$payload){
            throw new \Exception("Token data format error", self::ERROR_CODE);
        }
        $this->alg = $header['alg'];

        // 未达到使用时间
        if (!empty($payload['nbf']) && $payload['nbf'] > time() && $time_verify) {
            throw new \Exception('Token Unused time', self::WAIT_CODE);
        }

        // 时间已过期
        if (!empty($payload['exp']) && $payload['exp'] < time() && $time_verify){
            throw new \Exception('Token has expired', self::EXPIRE_CODE);
        }

        $sign_base = hash_hmac($this->alg, self::base64UrlEncode($header) . '.' . self::base64UrlEncode($payload), $this->getConfig('SECRET'));

        // 和我们自己的签名对不上
        if (self::base64UrlEncode($sign_base) !== $this->strDetach($sign)){
            throw new \Exception('Token signature error', self::SIGN_CODE);
        }

        return $payload;
    }
    /**
     * 获取头部信息
     * @return string
     */
    private function getHeader(): string
    {
        $header = [
            'alg' => $this->alg,
            'typ' => self::TYPE
        ];

        return self::base64UrlEncode($header);
    }

    /**
     * 对数据进行 base64Url 加密
     *
     * @param array|string $data
     *
     * @return string
     */
    private static function base64UrlEncode(array|string $data): string
    {
        if (is_array($data)) {
            $data = json_encode($data, JSON_UNESCAPED_UNICODE);
        }
        $base64 = base64_encode($data);

        return strtr(rtrim($base64, '='), ['/' => '_', '+' => '-']);
    }

    /**
     * baseUrl 解密
     * @param string $data
     * @return bool|string
     */
    private static function base64UrlDecode(string $data): bool|string
    {
        $data   = $data . str_repeat('=', strlen($data) % 4);
        $base64 = strtr($data, ['_' => '/', '-' => '+']);
        return base64_decode($base64);
    }

    /**
     * 签名字符串注入
     *
     * @param string $str
     *
     * @return string
     */
    private function strInject(string $str): string
    {
        list($middleStart, $middleLen) = explode(':', $this->getConfig('MIDDLE_LEN'));
        $base64Sign = substr_replace($str,  $this->strRandom($middleLen), $middleStart, 0);

        return implode([$this->strRandom($this->getConfig('START_LEN')), $base64Sign, $this->strRandom($this->getConfig('END_LEN'))]);
    }

    /**
     * 随机字符串
     *
     * @param int $length
     *
     * @return string
     */
    public function strRandom(int $length): string
    {
        $str = '0123456789abcdefghijklimopqrstuvwxyzABCDEFGHIJKLIMOPQRSTUVWXYZ';
        $str = str_shuffle($str);

        return substr($str, 0, $length);
    }

    /**
     * 签名字符串分离
     * @param string $str
     * @return string|string[]
     */
    private function strDetach(string $str): array|string
    {
        list($middleStart, $middleLen) = explode(':', $this->getConfig('MIDDLE_LEN'));
        $str = substr(substr($str, $this->getConfig('START_LEN')), 0, -$this->getConfig('END_LEN'));

        return substr_replace($str, '', $middleStart, $middleLen);
    }

    /**
     * 设置数据
     *
     * @param array $data
     *
     * @return JWT
     */
    public function setData(array $data): JWT
    {
        $this->data = $data;
        return $this;
}
}
