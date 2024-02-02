<?php

namespace Sc\Util\Tool;

/**
 * Class Url
 * @method string|null getScheme(?string $default = null)
 * @method string|null getHost(?string $default = null)
 * @method int|null    getPort(?int $default = null)
 * @method string|null getUser(?string $default = null)
 * @method string|null getPass(?string $default = null)
 * @method string|null getQuery(?string $default = null)
 * @method string|null getPath(?string $default = null)
 * @method string|null getFragment(?string $default = null)
 * @method $this setScheme(string $Scheme)
 * @method $this setHost(string $Host)
 * @method $this setPort(int $Port)
 * @method $this setUser(string $User)
 * @method $this setPass(string $Pass)
 * @method $this setFragment(string $Fragment)
 */
class Url implements \Stringable
{
    private array $urlInfo = [];
    private array $query = [];

    /**
     * @param string|null $url
     */
    public function __construct(?string $url = null)
    {
        if ($url) {
            $this->urlInfo = parse_url($url);

            parse_str($this->urlInfo['query'] ?? '', $this->query);
        }
    }

    /**
     * 获取域名
     *
     * @return string
     */
    public function getDomain(): string
    {
        return strtr("[scheme]://[user][pass][host][port]", [
            '[scheme]'   => $this->getScheme('http'),
            '[user]'     => $this->getUser() ? $this->getUser() . ':' : '',
            '[pass]'     => $this->getUser() ? $this->getPass() . '@' : '',
            '[host]'     => $this->getHost(),
            '[port]'     => ($this->getPort() && $this->getPort() != 80) ? ":" . $this->getPort() : '',
        ]);
    }

    /**
     * 获取全部query参数
     *
     * @return array
     */
    public function getQueryArr(): array
    {
        return $this->query;
    }

    /**
     * 完整地址
     *
     * @return string
     */
    public function url(): string
    {
        return strtr("[domain][path][query][fragment]", [
            '[domain]'   => $this->getDomain(),
            '[path]'     => $this->getPath(),
            '[query]'    => $this->getQuery() ? "?" . $this->getQuery() : '',
            '[fragment]' => $this->getFragment() ? "#" . $this->getFragment() : '',
        ]);
    }

    /**
     * 获取query参数
     *
     * @param string $paramName
     * @param mixed  $default
     *
     * @return mixed
     */
    public function getQueryParam(string $paramName, mixed $default): mixed
    {
        return $this->query[$paramName] ?? $default;
    }

    /**
     * @param array|null $query
     *
     * @return $this
     */
    public function setQuery(?array $query): static
    {
        $this->query = $query === null
            ? []
            : array_merge($this->query, $query);

        return $this;
    }

    /**
     * @param string|array $path
     *
     * @return $this
     */
    public function setPath(string|array $path): static
    {
        $path = is_string($path)
            ? trim($path, '/')
            : implode('/', $path);

        $this->urlInfo['path'] = '/' . $path;

        return $this;
    }

    public function __call(string $name, array $arguments)
    {
        $handle = lcfirst(substr($name,0, 3));
        $target = lcfirst(substr($name, 3));
        if ($handle === 'get') {
            if ($target === 'query') {
                return http_build_query($this->query);
            }

            return $this->urlInfo[$target] ?? ($arguments[0] ?? null);
        }

        $this->urlInfo[$target] = $arguments[0];

        return $this;
    }

    public function __toString(): string
    {
        return $this->url();
    }
}
