<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Data\Responses\Concerns;

/**
 * Response DTO 共通の「mixed を int のリストへ正規化」ヘルパ。
 *
 * Chatwork が返す ID 配列を堅牢に list<int> へ変換する（非配列は空リスト）。
 */
trait ConvertsToIntList
{
    /**
     * @return list<int>
     */
    private static function toIntList(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return array_values(array_map(static fn (mixed $id): int => (int) $id, $value));
    }
}
