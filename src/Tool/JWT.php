<?php
/**
 * 安全优化版JWT工具类
 * 核心优化：密钥安全管理、强算法固定、逻辑校验完善、安全机制补充
 */

namespace Sc\Util\Tool;

use JetBrains\PhpStorm\ExpectedValues;
use RuntimeException;
use stdClass;

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

    /** @var bool RefreshToken是否显式指定了独立受众 */
    private bool $refreshAudienceExplicit = false;

    /** @var array 自定义携带数据 */
    private array $data = [];

    /** @var array 配置集合 */
    private array $config = [
        'SECRET' => self::DEFAULT_SECRET,
        'REFRESH' => self::DEFAULT_REFRESH,
        'AUDIENCE' => '', // string|string[]，空字符串或空数组表示不限受众
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
        self::normalizeAudiences($this->config['AUDIENCE']);
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
            'aud' => $this->payload['aud'] ?? $this->getConfig('AUDIENCE')
        ];

        $data = array_merge($data, $fill_data);
        self::normalizeAudiences($data['aud']);
        $this->refresh = $this->signRefreshPayload($data);
        $this->refreshAudienceExplicit = array_key_exists('aud', $fill_data);

        $this->payload['rsh'] = $data['jti'];
        return $this;
    }

    /**
     * 签名RefreshToken载荷，保留既有标识和过期时间。
     */
    private function signRefreshPayload(array $data): array
    {
        $payload = self::base64UrlEncode($data);
        $sign = hash_hmac($this->alg, $payload, $this->getConfig('REFRESH'));

        return [
            'refresh_token' => $payload . '.' . self::base64UrlEncode($sign),
            'refresh_token_exp' => $data['exp']
        ];
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
        $refreshData = self::decodePayload($refreshPayload, 'RefreshToken');
        if (empty($refreshData['jti']) || empty($refreshData['exp'])) {
            throw new RuntimeException('RefreshToken数据无效', self::ERROR_CODE);
        }

        // 校验RefreshToken有效性
        if ($refreshData['exp'] < time()) {
            throw new RuntimeException('RefreshToken已过期', self::EXPIRE_CODE);
        }
        if ($refreshData['jti'] !== $originalPayload['rsh']) {
            throw new RuntimeException('RefreshToken与Token不匹配', self::ERROR_CODE);
        }
        $this->validateAudience($refreshData, 'RefreshToken');

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
        $this->setIat()->setNbf()->setExp()->setIss()->setJti();
        // 保留原受众及其类型；原Token未声明受众时不补入配置值。
        if (array_key_exists('aud', $this->data)) {
            $this->payload['aud'] = $this->data['aud'];
        }
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
     * 在验证前读取受众，不需要密钥，不校验签名、算法或时效。
     * 返回值未经验证，仅可用于选择服务端预设的验证配置，不能作为认证或授权依据。
     * @param string $token Token字符串
     * @return list<string> 受众列表；未声明受众或受众为空时返回空数组
     * @throws RuntimeException Token结构、载荷或受众类型无效
     */
    public static function peekAudiences(string $token): array
    {
        [, $payload] = self::splitToken($token);
        $payloadData = self::decodePayload($payload);

        return self::normalizeAudiences($payloadData['aud'] ?? []);
    }

    /**
     * 解析Token的三个非空Base64Url分段，不验证内容真实性。
     * @return array{string, string, string}
     */
    private static function splitToken(string $token): array
    {
        if (!preg_match('/\A[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+\.[A-Za-z0-9_-]+\z/', $token)) {
            throw new RuntimeException('Token格式无效', self::ERROR_CODE);
        }

        return explode('.', $token);
    }

    /**
     * 解析JSON对象载荷，并在关联数组转换前检查受众类型。
     */
    private static function decodePayload(string $payload, string $tokenType = 'Token'): array
    {
        $json = self::base64UrlDecode($payload);
        $payloadData = json_decode($json);
        if (json_last_error() !== JSON_ERROR_NONE || !$payloadData instanceof stdClass) {
            throw new RuntimeException($tokenType . '载荷无效', self::ERROR_CODE);
        }
        if (property_exists($payloadData, 'aud')) {
            self::normalizeAudiences($payloadData->aud);
        }

        // 保持自定义嵌套数据原有的关联数组返回格式。
        return json_decode($json, true);
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
        list($header, $payload, $sign) = self::splitToken($token);

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
        $payloadData = self::decodePayload($payload);

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
     * 受众校验（aud），不限受众或与允许列表存在交集时通过。
     * @param array $payload 载荷数据
     * @param string $tokenType Token类型（用于错误提示）
     * @throws RuntimeException
     */
    private function validateAudience(array $payload, string $tokenType = 'Token'): void
    {
        $allowed = self::normalizeAudiences($this->getConfig('AUDIENCE'));
        $audiences = self::normalizeAudiences($payload['aud'] ?? []);
        if ($allowed === [] || array_intersect($allowed, $audiences) !== []) {
            return;
        }

        throw new RuntimeException($tokenType . '受众不匹配', self::ILLEGAL_AUD_CODE);
    }

    /**
     * 将单个受众或受众列表统一为字符串列表，兼容原有空字符串受众。
     * @return list<string>
     */
    private static function normalizeAudiences(mixed $audiences): array
    {
        if (is_string($audiences)) {
            return $audiences === '' ? [] : [$audiences];
        }
        if (!is_array($audiences) || !array_is_list($audiences)) {
            throw new RuntimeException('受众必须为字符串或字符串列表', self::ILLEGAL_AUD_CODE);
        }
        foreach ($audiences as $audience) {
            if (!is_string($audience) || $audience === '') {
                throw new RuntimeException('受众列表必须包含非空字符串', self::ILLEGAL_AUD_CODE);
            }
        }

        return $audiences;
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
     * @param string|string[] $aud 单个受众或受众列表；空字符串使用配置值
     * @return self
     */
    public function setAud(string|array $aud = ''): self
    {
        $aud = $aud === '' ? $this->getConfig('AUDIENCE') : $aud;
        self::normalizeAudiences($aud);
        // 默认RefreshToken受众跟随Token设置，兼容先调用getRefresh()的链式写法。
        if (!$this->refreshAudienceExplicit && isset($this->refresh['refresh_token'])) {
            [$refreshPayload] = explode('.', $this->refresh['refresh_token']);
            // 此载荷由本实例生成，保留自定义数据中JSON对象和数组的区别。
            $refreshData = (array) json_decode(self::base64UrlDecode($refreshPayload), false, 512, JSON_THROW_ON_ERROR);
            $refreshData['aud'] = $aud;
            $this->refresh = $this->signRefreshPayload($refreshData);
        }
        $this->payload['aud'] = $aud;
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
        $this->refreshAudienceExplicit = false;
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
