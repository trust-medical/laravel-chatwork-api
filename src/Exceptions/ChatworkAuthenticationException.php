<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Exceptions;

use RuntimeException;
use TrustMedical\LaravelChatworkApi\Auth\TokenProvider;

/**
 * リクエストの認証ができない場合にスローされる。
 *
 * HTTP ディスパッチ前、有効な認証情報を解決できないとき（空トークンや
 * 値を返さない {@see TokenProvider} など）に送出される。
 * Chatwork 自体からの HTTP 401 レスポンスは本例外ではなく
 * {@see ChatworkRequestException} として表面化する。
 */
final class ChatworkAuthenticationException extends RuntimeException implements ChatworkException {}
