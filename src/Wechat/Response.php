<?php

namespace Sc\Util\Wechat;

/**
 * Class Response
 */
class Response
{
    private int $errcode = 0;
    private string $errmsg = 'ok';

    private mixed $raw;

    private array $data;

    public function __construct(string $response)
    {
        $this->raw = $response;

        try {
            $responseArr = json_decode($response, true);

            isset($responseArr['errcode']) and $this->errcode = $responseArr['errcode'];
            isset($responseArr['errmsg']) and $this->errmsg = $responseArr['errmsg'];
            unset($responseArr['errcode'], $responseArr['errmsg']);

            $this->data = $responseArr;
        } catch (\Throwable) {}
    }

    public function isError(): bool
    {
        return $this->errcode !== 0;
    }

    public function getErrmsg(): string
    {
        return $this->errmsg;
    }

    public function getErrcode(): int
    {
        return $this->errcode;
    }

    public function getData(string $key = null): mixed
    {
        if ($key === null) {
            return $this->data;
        }

        return $this->data[$key] ?? null;
    }

    public function getRaw(): mixed
    {
        return $this->raw;
    }
}