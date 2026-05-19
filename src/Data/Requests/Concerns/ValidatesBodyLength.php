<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Data\Requests\Concerns;

use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkValidationException;

/**
 * Request DTO 共通の本文長検証。
 *
 * Chatwork の本文フィールド（メッセージ本文・タスク本文等）は最小1〜
 * 上限長の制約を持つ。検証ロジックのみ共有し、上限値はフィールドごとに
 * 異なりうるため呼び出し側が min/max/label を渡す（定数は共有しない）。
 */
trait ValidatesBodyLength
{
    /**
     * @throws ChatworkValidationException 本文が空、または上限を超える場合。
     */
    private static function assertBodyLength(string $body, int $min, int $max, string $label): void
    {
        $length = mb_strlen($body);

        if ($length < $min) {
            throw new ChatworkValidationException(
                sprintf('%s body must not be empty.', $label),
                ['body' => ['must not be empty']],
            );
        }

        if ($length > $max) {
            throw new ChatworkValidationException(
                sprintf('%s body must be %d characters or less.', $label, $max),
                ['body' => [sprintf('must be %d characters or less', $max)]],
            );
        }
    }
}
