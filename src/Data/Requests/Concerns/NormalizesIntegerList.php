<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Data\Requests\Concerns;

use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkValidationException;

/**
 * Request DTO 共通の整数 ID リスト検証・CSV 変換。
 *
 * Chatwork の `*_ids` フィールド（members_admin_ids 等）は正の整数の
 * カンマ区切り文字列で送るため、送信前検証と文字列化を一元化する。
 */
trait NormalizesIntegerList
{
    /**
     * @param  array<int, mixed>  $ids
     *
     * @throws ChatworkValidationException 正の整数以外を含む場合。
     */
    private static function assertIntegerList(array $ids, string $field): void
    {
        foreach ($ids as $id) {
            if (! is_int($id) || $id <= 0) {
                throw new ChatworkValidationException(
                    sprintf('%s must contain positive integers only.', $field),
                    [$field => ['must contain positive integers only']],
                );
            }
        }
    }

    /**
     * @param  array<int, int>  $ids
     */
    private static function idsToCsv(array $ids): string
    {
        return implode(',', $ids);
    }
}
