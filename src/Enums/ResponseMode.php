<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Enums;

use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkRequestException;

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
}
