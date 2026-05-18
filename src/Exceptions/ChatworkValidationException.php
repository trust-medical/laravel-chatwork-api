<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Exceptions;

use RuntimeException;

/**
 * 送信前のリクエストバリデーションが失敗した場合にスローされる。
 *
 * HTTP リクエストのディスパッチ前に送出されるため、設定された戻り値モードに
 * 依存しない。`asResponse()` / `asResult()` 下でも呼び出し元は常にこの例外を受け取る。
 * {@see ChatworkRoutingException} は通知ルートの競合に特化した派生クラス。
 */
class ChatworkValidationException extends RuntimeException
{
    /**
     * @param  array<string, array<int, string>>  $violations  フィールド名 => バリデーション違反メッセージのリスト
     */
    public function __construct(
        string $message,
        private readonly array $violations = [],
    ) {
        parent::__construct($message);
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function violations(): array
    {
        return $this->violations;
    }
}
