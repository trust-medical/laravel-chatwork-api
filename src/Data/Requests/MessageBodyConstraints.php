<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Data\Requests;

/**
 * メッセージ作成・更新リクエストで共有する Chatwork メッセージ本文の文字数制約を保持する。
 *
 * CreateMessageRequest と UpdateMessageRequest が同一の送信前バリデーション上限を
 * 適用できるよう一元管理する。
 */
final class MessageBodyConstraints
{
    public const int BODY_MIN = 1;

    public const int BODY_MAX = 65535;

    private function __construct() {}
}
