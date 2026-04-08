<?php

declare(strict_types=1);

namespace CarmeloSantana\CoquiAwesomeWp\Storage;

use CarmeloSantana\CoquiAwesomeWp\Client\SiteConnection;

final class SiteManager
{
    private const string FILENAME = 'wp-sites.json';
    private array $sites = [];
    private string $defaultSite = '';
    private bool $loaded = false;

    public function __construct(
        private readonly string $storagePath,
    ) {}

    public function addSite(
        string $alias,
        string $url,
        string $username = '',
        string $appPassword = '',
        string $ssh = '',
        string $wpPath = '',
        string $wpCliUrl = '',
        bool $isMultisite = false,
        string $notes = '',
    ): SiteConnection {
        $this->ensureLoaded();

        $alias = $this->normalizeAlias($alias);

        if ($alias === '') {
            throw new \InvalidArgumentException('Site alias cannot be empty');
        }

        if (isset($this->sites[$alias])) {
            throw new \InvalidArgumentException(sprintf('Site "%s" already exists. Use updateSite() to modify it.', $alias));
        }

        $data = [
            'url' => rtrim($url, '/'),
            'username' => $username,
            'ssh' => $ssh,
            'wp_path' => $wpPath,
            'wp_cli_url' => $wpCliUrl,
            'is_multisite' => $isMultisite,
            'notes' => $notes,
            'created_at' => date('c'),
            'updated_at' => date('c'),
        ];

        $this->sites[$alias] = $data;

        if ($appPassword !== '') {
            $this->storePassword($alias, $appPassword);
        }

        if ($this->defaultSite === '') {
            $this->defaultSite = $alias;
        }

        $this->save();

        return $this->resolveConnection($alias);
    }

    public function getSite(string $alias): ?SiteConnection
    {
        $this->ensureLoaded();

        $alias = $this->normalizeAlias($alias);

        if (!isset($this->sites[$alias])) {
            return null;
        }

        return $this->resolveConnection($alias);
    }

    public function listSites(): array
    {
        $this->ensureLoaded();

        $result = [];
        foreach ($this->sites as $alias => $data) {
            $result[$alias] = $this->resolveConnection($alias);
        }

        return $result;
    }

    public function removeSite(string $alias): bool
    {
        $this->ensureLoaded();

        $alias = $this->normalizeAlias($alias);

        if (!isset($this->sites[$alias])) {
            return false;
        }

        unset($this->sites[$alias]);

        $envKey = $this->passwordEnvKey($alias);
        putenv($envKey);

        if ($this->defaultSite === $alias) {
            $first = array_key_first($this->sites);
            $this->defaultSite = $first !== null ? (string) $first : '';
        }

        $this->save();

        return true;
    }

    public function updateSite(string $alias, array $updates): ?SiteConnection
    {
        $this->ensureLoaded();

        $alias = $this->normalizeAlias($alias);

        if (!isset($this->sites[$alias])) {
            return null;
        }

        $allowedKeys = ['url', 'username', 'ssh', 'wp_path', 'wp_cli_url', 'is_multisite', 'notes'];

        foreach ($allowedKeys as $key) {
            if (array_key_exists($key, $updates)) {
                $this->sites[$alias][$key] = $key === 'url'
                    ? rtrim((string) $updates[$key], '/')
                    : $updates[$key];
            }
        }

        if (isset($updates['app_password']) && $updates['app_password'] !== '') {
            $this->storePassword($alias, (string) $updates['app_password']);
        }

        $this->sites[$alias]['updated_at'] = date('c');
        $this->save();

        return $this->resolveConnection($alias);
    }

    public function setDefault(string $alias): bool
    {
        $this->ensureLoaded();

        $alias = $this->normalizeAlias($alias);

        if (!isset($this->sites[$alias])) {
            return false;
        }

        $this->defaultSite = $alias;
        $this->save();

        return true;
    }

    public function getDefault(): ?SiteConnection
    {
        $this->ensureLoaded();

        if ($this->defaultSite === '' || !isset($this->sites[$this->defaultSite])) {
            $first = array_key_first($this->sites);
            if ($first === null) {
                return null;
            }
            $this->defaultSite = (string) $first;
        }

        return $this->resolveConnection($this->defaultSite);
    }

    public function getDefaultAlias(): string
    {
        $this->ensureLoaded();

        return $this->defaultSite;
    }

    public function hasSite(string $alias): bool
    {
        $this->ensureLoaded();

        return isset($this->sites[$this->normalizeAlias($alias)]);
    }

    public function count(): int
    {
        $this->ensureLoaded();

        return count($this->sites);
    }

    public function resolveConnection(string $alias): SiteConnection
    {
        $this->ensureLoaded();

        $alias = $this->normalizeAlias($alias);
        $data = $this->sites[$alias] ?? [];

        $envKey = $this->passwordEnvKey($alias);
        $password = getenv($envKey);
        $data['app_password'] = $password !== false ? $password : '';

        return SiteConnection::fromArray($alias, $data);
    }

    public function resolveSiteOrDefault(string $siteAlias = ''): ?SiteConnection
    {
        if ($siteAlias !== '') {
            return $this->getSite($siteAlias);
        }

        $default = $this->getDefault();
        if ($default !== null) {
            return $default;
        }

        $envConnection = SiteConnection::fromEnv();
        if ($envConnection->url !== '') {
            return $envConnection;
        }

        return null;
    }

    private function storePassword(string $alias, string $password): void
    {
        $envKey = $this->passwordEnvKey($alias);
        putenv(sprintf('%s=%s', $envKey, $password));
    }

    private function passwordEnvKey(string $alias): string
    {
        return 'AWP_' . strtoupper(str_replace('-', '_', $alias)) . '_APP_PASSWORD';
    }

    private function normalizeAlias(string $alias): string
    {
        return strtolower(trim(preg_replace('/[^a-zA-Z0-9\-]/', '-', $alias) ?? $alias, '-'));
    }

    private function ensureLoaded(): void
    {
        if ($this->loaded) {
            return;
        }

        $this->load();
        $this->loaded = true;
    }

    private function load(): void
    {
        $filePath = $this->filePath();

        if (!file_exists($filePath)) {
            $this->sites = [];
            $this->defaultSite = '';

            return;
        }

        $json = file_get_contents($filePath);
        if ($json === false) {
            $this->sites = [];
            $this->defaultSite = '';

            return;
        }

        $data = json_decode($json, true);
        if (!is_array($data)) {
            $this->sites = [];
            $this->defaultSite = '';

            return;
        }

        $this->sites = $data['sites'] ?? [];
        $this->defaultSite = $data['default_site'] ?? '';
    }

    private function save(): void
    {
        $filePath = $this->filePath();
        $dir = dirname($filePath);

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $data = [
            'sites' => $this->sites,
            'default_site' => $this->defaultSite,
        ];

        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        $tmpFile = $filePath . '.tmp';
        file_put_contents($tmpFile, $json, LOCK_EX);
        rename($tmpFile, $filePath);
    }

    private function filePath(): string
    {
        return rtrim($this->storagePath, '/') . '/' . self::FILENAME;
    }
}
