<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Exceptions;

use RuntimeException;

/**
 * パッケージの設定・配線が不正なときに送出される例外。
 *
 * 送信前の入力値エラー（{@see ChatworkValidationException}）とは別系統で、
 * config 値・サービスバインディング・接続定義など「開発者/デプロイ起因」の
 * 誤りを fail-loud に顕在化させるために使う。
 */
final class ChatworkConfigurationException extends RuntimeException implements ChatworkException {}
