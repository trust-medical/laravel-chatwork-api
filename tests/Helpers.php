<?php

declare(strict_types=1);
use Illuminate\Contracts\Encryption\Encrypter;
use TrustMedical\LaravelChatworkApi\Notifications\ChatworkMessage;
use TrustMedical\LaravelChatworkApi\Notifications\ChatworkNotification;

/**
 * composer.json の `autoload-dev.files` 経由で読み込まれるテストヘルパー。
 *
 * PHPStan が Pest クロージャ内（`$this` が動的にバインドされ TestCase として型付けできない）
 * で解決できるよう、ファイルスコープで定義している。
 */
if (! function_exists('fixtureJson')) {
    /**
     * tests/Fixtures/chatwork/ 配下のフィクスチャ JSON ファイルを読み込み、配列として返す。
     *
     * @return array<int|string, mixed>
     */
    function fixtureJson(string $relativePath): array
    {
        $path = __DIR__ . '/Fixtures/chatwork/' . ltrim($relativePath, '/');

        if (! is_file($path)) {
            throw new RuntimeException("フィクスチャが見つかりません: {$path}");
        }

        /** @var array<int|string, mixed> $decoded */
        $decoded = json_decode((string) file_get_contents($path), associative: true, flags: JSON_THROW_ON_ERROR);

        return $decoded;
    }
}

if (! function_exists('tempUploadFile')) {
    /**
     * アップロードテスト用の一時ファイルを作成し、シャットダウン時の削除をスケジュールする。
     *
     * $truncateTo が指定された場合は実 IO を書き込まずに正確にそのバイト数のスパースファイルを作成する
     * （5 MiB 境界のテストケースで使用）。指定がない場合は $contents をそのまま書き込む。
     */
    function tempUploadFile(string $contents = 'hello world', ?int $truncateTo = null): string
    {
        $path = (string) tempnam(sys_get_temp_dir(), 'cwfile');
        register_shutdown_function(static fn () => @unlink($path));

        if ($truncateTo !== null) {
            $fp = fopen($path, 'w');
            assert($fp !== false);
            ftruncate($fp, $truncateTo);
            fclose($fp);

            return $path;
        }

        file_put_contents($path, $contents);

        return $path;
    }
}

if (! function_exists('chatworkNotification')) {
    /**
     * ChatworkMessage を ChatworkNotification でラップして notify() に渡せるようにする。
     *
     * ChatworkMessage は Notification ではなく純粋なビルダーのため、テストで
     * 単発メッセージを送る際にこのラッパーで通知化する。
     */
    function chatworkNotification(
        ChatworkMessage $message
    ): ChatworkNotification {
        return new class($message) extends ChatworkNotification
        {
            public function __construct(
                private readonly ChatworkMessage $message
            ) {}

            public function toChatwork(object $notifiable): ChatworkMessage
            {
                return $this->message;
            }
        };
    }
}

if (! function_exists('testEncrypter')) {
    /**
     * CacheTokenRepository など Encrypter を要求するクラス用の決定的なテスト用 Encrypter。
     * アプリの APP_KEY 設定に依存せず、save / find が同一インスタンスを使う限り
     * ラウンドトリップが成立する。
     */
    function testEncrypter(): Encrypter
    {
        return new Illuminate\Encryption\Encrypter(str_repeat('k', 32), 'aes-256-cbc');
    }
}

if (! function_exists('fixture')) {
    /**
     * tests/Fixtures/chatwork/ 配下のフィクスチャファイルを読み込み、生の文字列として返す。
     */
    function fixture(string $relativePath): string
    {
        $path = __DIR__ . '/Fixtures/chatwork/' . ltrim($relativePath, '/');

        if (! is_file($path)) {
            throw new RuntimeException("フィクスチャが見つかりません: {$path}");
        }

        return (string) file_get_contents($path);
    }
}
