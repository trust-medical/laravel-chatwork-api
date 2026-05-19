<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi\Exceptions;

/**
 * パッケージが送出する全例外が実装するマーカー interface。
 *
 * 利用側は `catch (ChatworkException $e)` で本パッケージ由来の例外を
 * 一括捕捉できる。`violations()` / `status()` / `errors()` 等は具象例外
 * 固有のため、共通契約には含めない（具象型で分岐すること）。
 *
 * `\Throwable` を継承するため、静的解析は `catch (ChatworkException $e)` を
 * 正当な catch 節として扱い、`$e->getMessage()` 等の `Throwable` API を
 * 型安全に利用できる（全実装が `RuntimeException` 継承のため BC セーフ）。
 */
interface ChatworkException extends \Throwable {}
