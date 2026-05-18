<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Exceptions;

use Illuminate\Http\Client\Response;
use RuntimeException;

/**
 * Chatwork が 4xx/5xx レスポンスを返した場合にスローされる。
 *
 * スローする戻り値モード（`asArray()` / `asDto()` / `asCollection()`）では
 * HTTP ディスパッチ後に送出される。スローしないモード
 * （`asResponse()` / `asPsrResponse()` / `asResult()`）では失敗を値として返す。
 * Chatwork のエラーボディ形式を両方公開する。通常 API の `{"errors":[...]}` は
 * {@see self::errors()}、OAuth の `{"error":..., "error_description":...}` は
 * {@see self::error()} / {@see self::errorDescription()} で取得する。
 * 保持するボディはアクセストークン・リフレッシュトークン・クライアントシークレットを
 * マスクした状態で保存する。
 */
final class ChatworkRequestException extends RuntimeException
{
    /** @var list<string> */
    private array $errors = [];

    private ?string $error = null;

    private ?string $errorDescription = null;

    /** @var array{limit: int, remaining: int, reset: int}|null */
    private ?array $rateLimit;

    private string $redactedBody;

    /**
     * @param  array{limit: int, remaining: int, reset: int}|null  $rateLimit
     */
    public function __construct(
        private readonly int $status,
        private readonly string $method,
        private readonly string $path,
        private readonly ?string $operationId,
        string $body,
        ?array $rateLimit = null,
        string $message = '',
    ) {
        parent::__construct(
            $message !== ''
                ? $message
                : sprintf(
                    'Chatwork API request failed: %s %s (%d)',
                    $method,
                    explode('?', $path, 2)[0],
                    $status,
                ),
        );

        $this->redactedBody = self::redactBody($body);
        $this->rateLimit = $rateLimit;

        $decoded = json_decode($body, associative: true);
        if (is_array($decoded)) {
            if (isset($decoded['errors']) && is_array($decoded['errors'])) {
                $this->errors = array_values(array_filter(
                    $decoded['errors'],
                    static fn (mixed $v): bool => is_string($v),
                ));
            }
            if (isset($decoded['error']) && is_string($decoded['error'])) {
                $this->error = $decoded['error'];
            }
            if (isset($decoded['error_description']) && is_string($decoded['error_description'])) {
                $this->errorDescription = $decoded['error_description'];
            }
        }
    }

    /**
     * 失敗した Chatwork HTTP レスポンスから生成する。
     *
     * `x-ratelimit-limit` ヘッダーが存在する場合（主に 429 レスポンス）のみ
     * レートリミットの3値を設定し、それ以外は null とする。
     */
    public static function fromResponse(
        Response $response,
        string $method,
        string $path,
        ?string $operationId = null,
    ): self {
        $rateLimit = null;
        $limit = $response->header('x-ratelimit-limit');
        if ($limit !== '') {
            $rateLimit = [
                'limit' => (int) $limit,
                'remaining' => (int) $response->header('x-ratelimit-remaining'),
                'reset' => (int) $response->header('x-ratelimit-reset'),
            ];
        }

        return new self(
            status: $response->status(),
            method: $method,
            path: $path,
            operationId: $operationId,
            body: $response->body(),
            rateLimit: $rateLimit,
        );
    }

    public function status(): int
    {
        return $this->status;
    }

    public function method(): string
    {
        return $this->method;
    }

    public function path(): string
    {
        return $this->path;
    }

    public function operationId(): ?string
    {
        return $this->operationId;
    }

    public function body(): string
    {
        return $this->redactedBody;
    }

    /**
     * 通常 API エラーボディ `{"errors":[...]}` のメッセージ一覧。
     *
     * OAuth 形式のエラーボディの場合は空配列になる。{@see self::error()} を参照。
     *
     * @return list<string>
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * OAuth エラーコード（`error` フィールド）。通常 API エラーの場合は null。
     */
    public function error(): ?string
    {
        return $this->error;
    }

    /**
     * OAuth の `error_description`。値がない場合または OAuth エラーでない場合は null。
     */
    public function errorDescription(): ?string
    {
        return $this->errorDescription;
    }

    /**
     * @return array{limit: int, remaining: int, reset: int}|null
     */
    public function rateLimit(): ?array
    {
        return $this->rateLimit;
    }

    /**
     * 値をマスクするキー（大文字小文字を無視して完全一致）。
     * `error` / `error_description` / `errors` / `scope` / `grant_type` は
     * デバッグに有用で秘密ではないため意図的に除外する。
     *
     * @var list<string>
     */
    private const REDACT_KEYS = [
        'access_token',
        'refresh_token',
        'client_secret',
        'client_id',
        'code',
        'token',
        'authorization',
        'password',
    ];

    private static function redactBody(string $body): string
    {
        if ($body === '') {
            return $body;
        }

        $decoded = json_decode($body, associative: true);
        if (is_array($decoded)) {
            $encoded = json_encode(
                self::redactArray($decoded),
                JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
            );
            if ($encoded !== false) {
                return $encoded;
            }
        }

        return self::redactNonJson($body);
    }

    /**
     * @param  array<array-key, mixed>  $data
     * @return array<array-key, mixed>
     */
    private static function redactArray(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = self::redactArray($value);

                continue;
            }

            if (is_string($key) && in_array(strtolower($key), self::REDACT_KEYS, true)) {
                $data[$key] = '***';
            }
        }

        return $data;
    }

    private static function redactNonJson(string $body): string
    {
        $keys = implode('|', array_map(
            static fn (string $k): string => preg_quote($k, '/'),
            self::REDACT_KEYS,
        ));

        $body = preg_replace(
            '/"(' . $keys . ')"\s*:\s*"[^"]*"/i',
            '"$1":"***"',
            $body,
        ) ?? $body;

        return preg_replace(
            '/(^|&)(' . $keys . ')=[^&]*/i',
            '$1$2=***',
            $body,
        ) ?? $body;
    }
}
