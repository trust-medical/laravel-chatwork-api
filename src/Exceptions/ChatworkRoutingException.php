<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Exceptions;

/**
 * Chatwork 通知のルートを一意に解決できない場合にスローされる。
 *
 * {@see ChatworkValidationException} の特化クラスで、通知チャネルが競合するルートを
 * 受け取った場合（例: 明示的な `toRoom()` と `routeNotificationForChatwork()` の併用）、
 * またはルートを決定できない場合に送出される。親クラスと同様に送信前の失敗であり、
 * 設定された戻り値モードに関わらず常にスローされる。
 */
final class ChatworkRoutingException extends ChatworkValidationException {}
