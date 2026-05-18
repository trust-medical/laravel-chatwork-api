<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Data\Responses;

/**
 * `204 No Content` レスポンスの空ボディを表す DTO。
 *
 * `ResponseMapper` がボディを持たないレスポンスを、ペイロードを持つ Response DTO と
 * 同一のファクトリ契約で hydrate できるようにするためのセンチネルとして機能する。
 */
final readonly class NoContentData
{
    public function __construct() {}

    /**
     * @param  array<string, mixed>  $_data  無視される。204 No Content はペイロードを持たないが、
     *                                       ResponseMapper が一様に hydrate できるよう他の
     *                                       Response DTO とシグネチャを揃えている。
     */
    public static function fromArray(array $_data): self
    {
        return new self();
    }
}
