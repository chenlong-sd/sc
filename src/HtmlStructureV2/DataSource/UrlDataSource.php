<?php

namespace Sc\Util\HtmlStructureV2\DataSource;

final class UrlDataSource implements DataSourceInterface
{
    private string $method = 'GET';
    private array $query = [];

    public function __construct(
        private readonly string $url
    ) {
    }

    public static function make(string $url, array $query = []): self
    {
        $instance = new self($url);
        $instance->query($query);

        return $instance;
    }

    public function method(string $method): self
    {
        $this->method = strtoupper($method);

        return $this;
    }

    public function query(array $query): self
    {
        $this->query = $query;

        return $this;
    }

    public function initialRows(): array
    {
        return [];
    }

    public function toClientConfig(): array
    {
        return [
            'type' => 'remote',
            'url' => $this->url,
            'method' => $this->method,
            'query' => $this->query,
        ];
    }

    public function isRemote(): bool
    {
        return true;
    }
}
