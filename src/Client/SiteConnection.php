<?php

declare(strict_types=1);

namespace CarmeloSantana\CoquiAwesomeWp\Client;

final readonly class SiteConnection
{
    public function __construct(
        public string $alias,
        public string $url = '',
        public string $username = '',
        public string $appPassword = '',
        public string $ssh = '',
        public string $wpPath = '',
        public string $wpCliUrl = '',
        public bool $isMultisite = false,
        public string $notes = '',
    ) {}

    public static function fromArray(string $alias, array $data): self
    {
        return new self(
            alias: $alias,
            url: trim((string) ($data['url'] ?? '')),
            username: trim((string) ($data['username'] ?? '')),
            appPassword: trim((string) ($data['app_password'] ?? '')),
            ssh: trim((string) ($data['ssh'] ?? '')),
            wpPath: trim((string) ($data['wp_path'] ?? '')),
            wpCliUrl: trim((string) ($data['wp_cli_url'] ?? '')),
            isMultisite: (bool) ($data['is_multisite'] ?? false),
            notes: trim((string) ($data['notes'] ?? '')),
        );
    }

    public static function fromEnv(): self
    {
        $url = getenv('AWP_DEFAULT_URL');
        $username = getenv('AWP_DEFAULT_USERNAME');
        $appPassword = getenv('AWP_DEFAULT_APP_PASSWORD');

        return new self(
            alias: 'default',
            url: $url !== false ? $url : '',
            username: $username !== false ? $username : '',
            appPassword: $appPassword !== false ? $appPassword : '',
        );
    }

    public function restBaseUrl(): string
    {
        $base = rtrim($this->url, '/');

        return $base . '/wp-json';
    }

    public function hasRestAccess(): bool
    {
        return $this->url !== '' && $this->username !== '' && $this->appPassword !== '';
    }

    public function hasCliAccess(): bool
    {
        return $this->ssh !== '' || $this->wpPath !== '';
    }

    public function toArray(): array
    {
        return [
            'url' => $this->url,
            'username' => $this->username,
            'ssh' => $this->ssh,
            'wp_path' => $this->wpPath,
            'wp_cli_url' => $this->wpCliUrl,
            'is_multisite' => $this->isMultisite,
            'notes' => $this->notes,
        ];
    }
}
