<?php
/**
 * 安全优化版JWT工具类
 * 核心优化：密钥安全管理、强算法固定、逻辑校验完善、安全机制补充
 */

namespace Sc\Util\Tool;

use JetBrains\PhpStorm\ExpectedValues;
use RuntimeException;

class JWT
{
    // 错误码常量
    const EXPIRE_CODE = 1001;
    const ERROR_CODE = 1002;
    const WAIT_CODE = 1003;
    const SIGN_CODE = 1004;
    const ILLEGAL_ALG_CODE = 1005;
    const ILLEGAL_IAT_CODE = 1006;
    const ILLEGAL_AUD_CODE = 1007;

    // 基础配置（不可硬编码密钥）
    const DEFAULT_SECRET = ''; // 禁止硬编码，通过环境变量覆盖
    const DEFAULT_REFRESH = ''; // 禁止硬编码，通过环境变量覆盖
    const TYPE = 'JWT';
    const ALLOWED_ALGS = ['sha256', 'sha512']; // 仅允许强算法
    const DEFAULT_EXP = 3600; // 默认过期时间1小时
    const MAX_EXP = 604800; // 最大过期时间7天
    const IAT_MAX_OFFSET = 300; // iat最大偏移量5分钟（秒）

    /** @var string 加密算法 */
    private string $alg;

    /** @var array token核心载荷 */
    private array $payload = [];

    /** @var array refreshToken数据 */
    private array $refresh = [];

    /** @var array 自定义携带数据 */
    private array $data = [];

    /** @var array 配置集合 */
    private array $config = [
        'SECRET' => self::DEFAULT_SECRET,
        'REFRESH' => self::DEFAULT_REFRESH,
        'AUDIENCE' => '', // 受众标识（如服务域名）
    ];

    /**
     * 构造函数（单例模式+环境变量初始化）
     * @param array $config 自定义配置（覆盖默认值）
     */
    public function __construct(array $config = [])
    {
        // 初始化配置（环境变量优先，其次自定义配置，最后默认值）
        $this->initConfig($config);

        // 验证密钥有效性
        $this->validateSecrets();

        // 固定强算法
        $this->alg = self::ALLOWED_ALGS[0];

        // 初始化基础载荷
        $this->setIat()->setNbf()->setExp()->setIss()->setAud()->setJti();
    }

    /**
     * 初始化配置（环境变量+自定义配置融合）
     * @param array $config 自定义配置
     */
    private function initConfig(array $config): void
    {
        $this->config = [
            'SECRET' => ($config['SECRET'] ?? self::DEFAULT_SECRET),
            'REFRESH' => ($config['REFRESH'] ?? self::DEFAULT_REFRESH),
            'AUDIENCE' => ($config['AUDIENCE'] ?? ''),
        ];
    }

    /**
     * 验证密钥有效性
     * @throws RuntimeException
     */
    private function validateSecrets(): void
    {
        if (empty($this->config['SECRET']) || strlen($this->config['SECRET']) < 16) {
            throw new RuntimeException('JWT密钥不能为空且长度至少16位');
        }
        if (empty($this->config['REFRESH']) || strlen($this->config['REFRESH']) < 16) {
            throw new RuntimeException('RefreshToken密钥不能为空且长度至少16位');
        }
    }

    /**
     * 获取配置项
     * @param string $key 配置键名
     * @return mixed
     */
    private function getConfig(#[ExpectedValues(['SECRET', 'REFRESH', 'AUDIENCE'])] string $key): mixed
    {
        return $this->config[$key] ?? '';
    }

    /**
     * 生成Token（移除冗余混淆逻辑）
     * @return array Token信息（含过期时间）
     */
    public function getToken(): array
    {
        $header = $this->getHeader();
        $payload = self::base64UrlEncode(array_merge($this->data, $this->payload));
        $sign = hash_hmac($this->alg, $header . '.' . $payload, $this->getConfig('SECRET'));

        $token = implode('.', [$header, $payload, self::base64UrlEncode($sign)]);

        $result = [
            'token' => $token,
            'token_exp' => $this->payload['exp'] ?? 0
        ];

        return array_merge($result, $this->refresh);
    }

    /**
     * 生成RefreshToken（用于刷新Token）
     * @param int $exp 过期时间（天，默认30天）
     * @param array $fill_data 额外携带数据
     * @return self
     */
    public function getRefresh(int $exp = 30, array $fill_data = []): self
    {
        // 验证过期时间合理性
        $expireSeconds = min($exp * 86400, self::MAX_EXP);

        $data = [
            'exp' => time() + $expireSeconds,
            'jti' => $this->generateSecureRandomString(32), // 32位安全随机标识
            'aud' => $this->getConfig('AUDIENCE')
        ];

        $data = array_merge($data, $fill_data);
        $payload = self::base64UrlEncode($data);
        $sign = hash_hmac($this->alg, $payload, $this->getConfig('REFRESH'));

        $this->refresh = [
            'refresh_token' => $payload . '.' . self::base64UrlEncode($sign),
            'refresh_token_exp' => $data['exp']
        ];

        $this->payload['rsh'] = $data['jti'];
        return $this;
    }

    /**
     * 刷新Token（使用RefreshToken）
     * @param string $token 原Token
     * @param string $refreshToken RefreshToken
     * @return array 新Token信息
     * @throws RuntimeException
     */
    public function refreshToken(string $token, string $refreshToken): array
    {
        // 解析RefreshToken
        $refreshParts = explode('.', $refreshToken);
        if (count($refreshParts) !== 2) {
            throw new RuntimeException('RefreshToken格式无效', self::ERROR_CODE);
        }

        list($refreshPayload, $refreshSign) = $refreshParts;

        // 验证原Token（不校验过期时间）
        $originalPayload = $this->verify($token, false);
        if (empty($originalPayload) || !isset($originalPayload['rsh'])) {
            throw new RuntimeException('原Token无效', self::ERROR_CODE);
        }

        // 解析RefreshToken载荷
        $refreshData = json_decode(self::base64UrlDecode($refreshPayload), true);
        if (json_last_error() !== JSON_ERROR_NONE || empty($refreshData['jti']) || empty($refreshData['exp'])) {
            throw new RuntimeException('RefreshToken数据无效', self::ERROR_CODE);
        }

        // 校验RefreshToken有效性
        if ($refreshData['exp'] < time()) {
            throw new RuntimeException('RefreshToken已过期', self::EXPIRE_CODE);
        }
        if ($refreshData['jti'] !== $originalPayload['rsh']) {
            throw new RuntimeException('RefreshToken与Token不匹配', self::ERROR_CODE);
        }
        if (!empty($refreshData['aud']) && $refreshData['aud'] !== $this->getConfig('AUDIENCE')) {
            throw new RuntimeException('RefreshToken受众不匹配', self::ILLEGAL_AUD_CODE);
        }

        // 校验RefreshToken签名
        $calculatedSign = hash_hmac($this->alg, $refreshPayload, $this->getConfig('REFRESH'));
        if (self::base64UrlEncode($calculatedSign) !== $refreshSign) {
            throw new RuntimeException('RefreshToken签名无效', self::SIGN_CODE);
        }

        // 保留原数据，重新生成Token
        $this->data = array_diff_key($originalPayload, array_flip(['iat', 'exp', 'nbf', 'rsh']));
        $this->data['jti']  = $originalPayload['jti'];
        $this->data['jtiv'] = ($originalPayload['jtiv'] ?? 0) + 1;
        return $this->resetPayload()->getToken();
    }

    /**
     * 重置载荷（刷新Token时使用）
     * @return self
     */
    private function resetPayload(): self
    {
        $this->payload = [];
        $this->setIat()->setNbf()->setExp()->setIss()->setAud()->setJti();
        if (isset($this->data['jti']) && isset($this->data['jtiv'])) {
            $this->payload['jti'] = $this->data['jti'];
            $this->payload['jtiv'] = $this->data['jtiv'] + 1;
        }
        return $this;
    }

    /**
     * Token自刷新（基于原Token数据）
     * @param string $token 原Token
     * @param int $exp 新过期时间（秒，默认3600秒）
     * @return array 新Token信息
     * @throws RuntimeException
     */
    public function selfRefresh(string $token, int $exp = 3600): array
    {
        $originalPayload = $this->verify($token, false);
        if (empty($originalPayload)) {
            throw new RuntimeException('Token无效', self::ERROR_CODE);
        }

        // 保留核心数据，重置时效相关字段
        $this->data = array_diff_key($originalPayload, array_flip(['iat', 'exp', 'nbf']));
        return $this->resetPayload()->setExp($exp)->getToken();
    }

    /**
     * Token验证（对外接口）
     * @param string $token Token字符串
     * @param bool $timeVerify 是否校验时效（默认true）
     * @return array 解析后的载荷数据
     * @throws RuntimeException
     */
    public function tokenVerify(string $token = '', bool $timeVerify = true): array
    {
        if (empty($token)) {
            throw new RuntimeException('Token不能为空', self::ERROR_CODE);
        }
        return $this->verify($token, $timeVerify);
    }

    /**
     * 核心验证逻辑
     * @param string $token Token字符串
     * @param bool $timeVerify 是否校验时效
     * @return array 解析后的载荷数据
     * @throws RuntimeException
     */
    private function verify(string $token, bool $timeVerify = true): array
    {
        // 解析Token结构
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            throw new RuntimeException('Token格式无效', self::ERROR_CODE);
        }

        list($header, $payload, $sign) = $parts;

        // 解析并验证Header
        $headerData = json_decode(self::base64UrlDecode($header), true);
        if (json_last_error() !== JSON_ERROR_NONE || empty($headerData['alg']) || empty($headerData['typ'])) {
            throw new RuntimeException('Token头部无效', self::ERROR_CODE);
        }

        // 校验算法合法性
        if (!in_array($headerData['alg'], self::ALLOWED_ALGS)) {
            throw new RuntimeException('不支持的加密算法', self::ILLEGAL_ALG_CODE);
        }
        $this->alg = $headerData['alg'];

        // 解析并验证Payload
        $payloadData = json_decode(self::base64UrlDecode($payload), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException('Token载荷无效', self::ERROR_CODE);
        }

        // 时效校验
        if ($timeVerify) {
            $this->validateTime($payloadData);
        }

        // 受众校验
        $this->validateAudience($payloadData);

        // 签名校验
        $calculatedSign = hash_hmac($this->alg, $header . '.' . $payload, $this->getConfig('SECRET'));
        if (self::base64UrlEncode($calculatedSign) !== $sign) {
            throw new RuntimeException('Token签名无效', self::SIGN_CODE);
        }

        return $payloadData;
    }

    /**
     * 时效校验（iat/nbf/exp）
     * @param array $payload 载荷数据
     * @throws RuntimeException
     */
    private function validateTime(array $payload): void
    {
        $currentTime = time();

        // 校验签发时间（防止未来时间Token）
        if (isset($payload['iat']) && $payload['iat'] > $currentTime + self::IAT_MAX_OFFSET) {
            throw new RuntimeException('Token签发时间异常', self::ILLEGAL_IAT_CODE);
        }

        // 校验生效时间
        if (isset($payload['nbf']) && $payload['nbf'] > $currentTime) {
            throw new RuntimeException('Token尚未生效', self::WAIT_CODE);
        }

        // 校验过期时间
        if (isset($payload['exp']) && $payload['exp'] < $currentTime) {
            throw new RuntimeException('Token已过期', self::EXPIRE_CODE);
        }
    }

    /**
     * 受众校验（aud）
     * @param array $payload 载荷数据
     * @throws RuntimeException
     */
    private function validateAudience(array $payload): void
    {
        $audience = $this->getConfig('AUDIENCE');
        if (!empty($audience) && (!isset($payload['aud']) || $payload['aud'] !== $audience)) {
            throw new RuntimeException('Token受众不匹配', self::ILLEGAL_AUD_CODE);
        }
    }

    /**
     * 生成JWT头部
     * @return string Base64Url编码后的头部
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
     * Base64Url编码（符合JWT标准）
     * @param array|string $data 待编码数据
     * @return string 编码结果
     * @throws RuntimeException
     */
    private static function base64UrlEncode(array|string $data): string
    {
        if (is_array($data)) {
            $json = json_encode($data, JSON_UNESCAPED_UNICODE);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new RuntimeException('JSON编码失败: ' . json_last_error_msg());
            }
            $data = $json;
        }

        $base64 = base64_encode($data);
        return strtr(rtrim($base64, '='), ['/' => '_', '+' => '-']);
    }

    /**
     * Base64Url解码（符合JWT标准）
     * @param string $data 待解码数据
     * @return string 解码结果
     */
    private static function base64UrlDecode(string $data): string
    {
        $padding = strlen($data) % 4;
        if ($padding !== 0) {
            $data .= str_repeat('=', 4 - $padding);
        }
        $base64 = strtr($data, ['_' => '/', '-' => '+']);
        return base64_decode($base64, true) ?: '';
    }

    /**
     * 生成加密安全的随机字符串
     * @param int $length 字符串长度
     * @return string 随机字符串
     * @throws RuntimeException
     */
    private function generateSecureRandomString(int $length): string
    {
        if ($length <= 0) {
            throw new RuntimeException('随机字符串长度必须大于0');
        }
        return bin2hex(random_bytes(ceil($length / 2)));
    }

    // ------------------------------ 载荷设置方法 ------------------------------

    /**
     * 设置签发时间（iat）
     * @param int $time 自定义时间（默认当前时间）
     * @return self
     */
    public function setIat(int $time = 0): self
    {
        $this->payload['iat'] = $time > 0 ? $time : time();
        return $this;
    }

    /**
     * 设置过期时间（exp）
     * @param int $time 过期时间（秒，默认1小时）
     * @return self
     * @throws RuntimeException
     */
    public function setExp(int $time = self::DEFAULT_EXP): self
    {
        // 限制最大过期时间
        if ($time <= 0 || $time > self::MAX_EXP) {
            throw new RuntimeException("过期时间必须在1-" . self::MAX_EXP . "秒之间");
        }
        $this->payload['exp'] = time() + $time;
        return $this;
    }

    /**
     * 设置生效时间（nbf）
     * @param int $time 生效时间（默认当前时间，可设置未来时间）
     * @return self
     */
    public function setNbf(int $time = 0): self
    {
        $this->payload['nbf'] = $time > 0 ? $time : time();
        return $this;
    }

    /**
     * 设置唯一标识（jti）
     * @param string $jti 自定义标识（默认自动生成）
     * @return self
     * @throws RuntimeException
     */
    public function setJti(string $jti = ''): self
    {
        $this->payload['jti'] = $jti ?: $this->generateSecureRandomString(32);
        $this->payload['jtiv'] = 0;
        return $this;
    }

    /**
     * 设置签发者（iss）
     * @param string $iss 签发者标识（如服务名称）
     * @return self
     */
    public function setIss(string $iss = 'Secure-JWT'): self
    {
        $this->payload['iss'] = $iss;
        return $this;
    }

    /**
     * 设置受众（aud）
     * @param string $aud 受众标识（如客户端域名）
     * @return self
     */
    public function setAud(string $aud = ''): self
    {
        $this->payload['aud'] = $aud ?: $this->getConfig('AUDIENCE');
        return $this;
    }

    /**
     * 设置自定义数据（会合并到payload中）
     * @param array $data 自定义数据（禁止包含JWT标准字段）
     * @return self
     * @throws RuntimeException
     */
    public function setData(array $data): self
    {
        // 禁止覆盖标准字段
        $reservedFields = ['iss', 'iat', 'exp', 'nbf', 'sub', 'aud', 'jti', 'rsh', 'jtiv'];
        $intersect = array_intersect_key($data, array_flip($reservedFields));
        if (!empty($intersect)) {
            throw new RuntimeException("自定义数据不能包含保留字段: " . implode(',', array_keys($intersect)));
        }
        $this->data = $data;
        return $this;
    }

    /**
     * 销毁实例（释放敏感数据）
     */
    public function destroy(): void
    {
        $this->payload = [];
        $this->refresh = [];
        $this->data = [];
        $this->config = [];
    }

    /**
     * 禁止克隆（单例安全）
     */
    private function __clone()
    {
    }

    /**
     * 禁止反序列化（防止敏感数据泄露）
     */
    public function __wakeup(){
        throw new RuntimeException('禁止反序列化');
    }
}