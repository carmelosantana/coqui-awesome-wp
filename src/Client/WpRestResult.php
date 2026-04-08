<?php

declare(strict_types=1);

namespace CarmeloSantana\CoquiAwesomeWp\Client;

use CarmeloSantana\PHPAgents\Tool\ToolResult;

final readonly class WpRestResult
{
    public function __construct(
        public int $statusCode,
        public mixed $body,
        public array $headers = [],
        public string $rawBody = '',
    ) {}

    public function succeeded(): bool
    {
        return $this->statusCode >= 200 && $this->statusCode < 300;
    }

    public function totalItems(): int
    {
        return (int) ($this->headers['x-wp-total'][0] ?? 0);
    }

    public function totalPages(): int
    {
        return (int) ($this->headers['x-wp-totalpages'][0] ?? 0);
    }

    public function errorMessage(): string
    {
        if ($this->succeeded()) {
            return '';
        }

        if (is_array($this->body) && isset($this->body['message'])) {
            $code = $this->body['code'] ?? 'error';

            return sprintf('[%s] %s', $code, $this->body['message']);
        }

        return sprintf('HTTP %d: %s', $this->statusCode, $this->rawBody !== '' ? mb_substr($this->rawBody, 0, 500) : 'Unknown error');
    }

    public function toToolResult(): ToolResult
    {
        if (!$this->succeeded()) {
            return ToolResult::error($this->errorMessage());
        }

        $output = is_array($this->body) || is_object($this->body)
            ? json_encode($this->body, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
            : (string) $this->body;

        $meta = '';
        if ($this->totalItems() > 0) {
            $meta = sprintf("\n\n---\nTotal: %d items, Page: showing %d", $this->totalItems(), is_array($this->body) ? count($this->body) : 1);
        }

        return ToolResult::success($output . $meta);
    }
}
