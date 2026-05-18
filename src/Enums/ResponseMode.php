<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Enums;

use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkRequestException;
use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkValidationException;

/**
 * リソース呼び出しの結果をどのように返すかを選択する。
 *
 * `Array` / `Dto` / `Collection` は 4xx/5xx 時に {@see ChatworkRequestException}
 * をスローする。`Response` / `PsrResponse` はスローせず生の Laravel / PSR-7
 * レスポンスを返す。`Result` はスローせず成功・失敗を値としてラップする。
 * 送信前バリデーション失敗はモードに関わらず常にスローする。
 */
enum ResponseMode: string
{
    case Array = 'array';
    case Dto = 'dto';
    case Collection = 'collection';
    case Response = 'response';
    case PsrResponse = 'psr_response';
    case Result = 'result';

    /**
     * `config('chatwork.response.mode')` の文字列値を解決する。
     *
     * 未設定（null / 空文字）はデフォルトの {@see self::Dto} とみなす。
     * 不正な値は黙ってフォールバックせず、設定ミスを即座に顕在化させる。
     *
     * @throws ChatworkValidationException 設定値がいずれの戻り値モードにも一致しない場合。
     */
    public static function fromConfig(?string $value): self
    {
        if ($value === null || $value === '') {
            return self::Dto;
        }

        return self::tryFrom($value) ?? throw new ChatworkValidationException(
            sprintf("Invalid chatwork.response.mode '%s'.", $value),
            ['chatwork.response.mode' => ['must be one of: array, dto, collection, response, psr_response, result']],
        );
    }
}
