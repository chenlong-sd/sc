<?php

namespace Sc\Util\Tool;

use JetBrains\PhpStorm\ArrayShape;

/**
 * Class Ciphertext
 */
class Ciphertext
{
    /**
     * 加密方式
     * @var string
     */
    private string $cipher = 'aes-128-gcm';

    public function __construct(private string $secret)
    {
    }

    /**
     * 加|解密方式
     *
     * @param string $method
     *
     * @return $this
     */
    public function method(string $method): static
    {
        if (in_array($method, openssl_get_cipher_methods())) {
            $this->cipher = $method;
        }

        return $this;
    }

    /**
     * @param string $text
     * @param string $iv
     *
     * @return array
     */
    #[ArrayShape(['ciphertext' => "string", 'iv' => "string", 'tag' => "string", "cipher" => "string"])]
    public function encrypt(string $text, string $iv = ''): array
    {
        $iv         = $this->getIv($iv);
        $ciphertext = openssl_encrypt(gzcompress($text, 9), $this->cipher, $this->getSecret(), OPENSSL_RAW_DATA, $iv, $tag);

        return [
            'ciphertext' => base64_encode($ciphertext),
            'iv'         => base64_encode($iv),
            'tag'        => base64_encode($tag),
            "cipher"     => $this->cipher,
        ];
    }

    /**
     * 解密
     *
     * @param string $ciphertext
     * @param string $iv
     * @param string $tag
     *
     * @return bool|string
     */
    public function decrypt(string $ciphertext, string $iv, string $tag = ''): bool|string
    {
        $originText = openssl_decrypt(base64_decode($ciphertext), $this->cipher, $this->getSecret(), OPENSSL_RAW_DATA, base64_decode($iv), base64_decode($tag));

        return $originText ? gzuncompress($originText) : false;
    }


    private function getIv(string $iv): string
    {
        return $iv ?: openssl_random_pseudo_bytes(openssl_cipher_iv_length($this->cipher));
    }

    private function getSecret(): bool|string
    {
        return base64_decode($this->secret);
    }
}
