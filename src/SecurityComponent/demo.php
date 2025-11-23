<?php

/**
 * ==========================
 *  登录示例
 * ==========================
 */

// 1、客户端获取返回挑战参数

use Sc\Util\SecurityComponent\Crypto\DynamicKeyManager;
use Sc\Util\SecurityComponent\Crypto\UnifiedEncryptionService;

$storage = ""; // 缓存器， \Psr\SimpleCache\CacheInterface::class
$keyId = ""; // 密钥ID, 请自行生成，确保唯一， 简便可使用 sessionId
$unifiedEncryptionService = new UnifiedEncryptionService(
    new DynamicKeyManager(),
    $storage,
);
$challenge = $unifiedEncryptionService->challengeManager->createChallenge(
    $_POST['client_id'], // 客户端ID
);
$unifiedEncryptionService->generateKeyPair($keyId);

// 2、返回给客户端
$res = [
    'challenge' => $challenge,
    'keyId' => $keyId,
    'publicKey' => $unifiedEncryptionService->getPublicKey($keyId)
];

// 3、客户端使用账号密码登录
// 引入 https://cdnjs.cloudflare.com/ajax/libs/jsencrypt/3.3.2/jsencrypt.min.js
// 引入 https://cdnjs.cloudflare.com/ajax/libs/crypto-js/4.1.1/crypto-js.min.js
// 引入 /js/secure-request.js
$js = <<<JS
 var secureLoginClient = new SecureRequestClient(
    'url', // 接口地址
);

// 登录, 会自动获取挑战参数并登录， 获取挑战参数会调用第一步的接口
// 挑战参数默认当前地址，get参数为:get-challenge=1
secureLoginClient.request(
    {
        username, // 用户名
        password, // 密码
        code:'123456', // 验证码
    }
)
JS;

// 4、登录验证
$data = $_POST;
$storage = ""; // 缓存器， \Psr\SimpleCache\CacheInterface::class
$keyId = ""; // 密钥ID, 请自行生成，确保唯一， 简便可使用 sessionId
$unifiedEncryptionService = new UnifiedEncryptionService(
    new DynamicKeyManager(),
    $storage
);

$payload = json_decode(base64_decode($data['encrypted_payload']), true);
$rsaDecrypt = $unifiedEncryptionService->hybridDecrypt(
    $payload['encrypted_data'],
    $payload['encrypted_key'],
    $payload['iv'],
    $keyId
);
$rsaDecrypt = json_decode($rsaDecrypt, true);
$validateChallenge = $unifiedEncryptionService->challengeManager->validateChallenge($data['challenge_id'], $rsaDecrypt['challenge'], $data['client_id']);
if ($validateChallenge['valid']) {
    // 验证成功
    // 密码验证
}