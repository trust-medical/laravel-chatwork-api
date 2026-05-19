<?php

declare(strict_types=1);

namespace TrustMedical\LaravelChatworkApi;

use InvalidArgumentException;
use TrustMedical\LaravelChatworkApi\Data\Contracts\MapsFromArray;
use TrustMedical\LaravelChatworkApi\Enums\ResponseMode;
use TrustMedical\LaravelChatworkApi\Exceptions\ChatworkRequestException;
use TrustMedical\LaravelChatworkApi\Http\ChatworkPendingRequestFactory;
use TrustMedical\LaravelChatworkApi\Http\ResponseMapper;
use TrustMedical\LaravelChatworkApi\Resources\ContactsResource;
use TrustMedical\LaravelChatworkApi\Resources\IncomingRequestsResource;
use TrustMedical\LaravelChatworkApi\Resources\MeResource;
use TrustMedical\LaravelChatworkApi\Resources\MyResource;
use TrustMedical\LaravelChatworkApi\Resources\RoomsResource;

/**
 * 低レベル HTTP 実行と Resource factory。{@see ChatworkManager} が解決した
 * {@see Connection} を受け取り、戻り値モードに応じて {@see ResponseMapper} で
 * レスポンスを変換する。
 *
 * 不変。connection / factory / mapper のコラボレータから再構築されるため、モード切替は
 * clone ではなく {@see self::withMode()} が新インスタンスを返す（cf. 可変な
 * {@see ChatworkManager}）。
 */
final class ChatworkClient
{
    public function __construct(
        private readonly Connection $connection,
        private readonly ChatworkPendingRequestFactory $factory,
        private readonly ResponseMapper $mapper,
        private readonly ResponseMode $mode = ResponseMode::Dto,
    ) {}

    public function connection(): Connection
    {
        return $this->connection;
    }

    public function mode(): ResponseMode
    {
        return $this->mode;
    }

    /**
     * 戻り値モード選択の公開エントリポイント（fluent chain の起点）。
     * Client は不変なコラボレータ（connection / factory / mapper）から構築されるため、
     * clone ではなく新インスタンス再構築で immutability を担保する
     * （cf. {@see ChatworkManager::withMode()} は可変 Manager のため clone+mutate）。
     */
    public function withMode(ResponseMode $mode): self
    {
        return new self($this->connection, $this->factory, $this->mapper, $mode);
    }

    public function rooms(): RoomsResource
    {
        return new RoomsResource($this);
    }

    public function me(): MeResource
    {
        return new MeResource($this);
    }

    public function my(): MyResource
    {
        return new MyResource($this);
    }

    public function contacts(): ContactsResource
    {
        return new ContactsResource($this);
    }

    public function incomingRequests(): IncomingRequestsResource
    {
        return new IncomingRequestsResource($this);
    }

    /**
     * Chatwork API を1回呼び出し、アクティブなモードに従ってレスポンスをマップする。
     *
     * 具体的な戻り値の型は {@see ResponseMode} によって決まる: readonly DTO、
     * array、Collection、Laravel/PSR-7 response、または ChatworkResult — そのため `mixed`。
     *
     * @internal 低レベル配管。公開 API は Resource 層（`Chatwork::rooms()` 等）を使うこと。
     *   このシグネチャは後方互換保証の対象外。
     *
     * @param  array<string, mixed>  $payload  POST/PUT/DELETE のフォームボディ、GET のクエリパラメータ
     * @param  class-string<MapsFromArray>|null  $dtoClass  Dto/Collection モードでハイドレートする DTO
     *
     * @throws InvalidArgumentException $method がサポートされていない HTTP メソッドの場合。
     * @throws ChatworkRequestException 投例モード（asArray/asDto/asCollection）で 4xx/5xx が返った場合。
     */
    public function send(
        string $method,
        string $path,
        array $payload = [],
        ?string $dtoClass = null,
        ?string $operationId = null,
    ): mixed {
        $verb = strtoupper($method);
        $pending = $this->factory->create($this->connection);

        $response = match ($verb) {
            'POST' => $pending->asForm()->post($path, $payload),
            'PUT' => $pending->asForm()->put($path, $payload),
            'GET' => $pending->get($path, $payload),
            // Chatwork の OpenAPI 仕様では DELETE ボディ（例: /rooms/{room_id} の action_type）が
            // application/x-www-form-urlencoded と定義されているため、payload がある場合は
            // フォームボディとして送信する。ボディなしの DELETE（deleteRoomMessage 等）は
            // Phase-6 以前の動作と同一にするため asForm() をスキップする。
            'DELETE' => $payload === []
                ? $pending->delete($path)
                : $pending->asForm()->delete($path, $payload),
            default => throw new InvalidArgumentException(sprintf('Unsupported HTTP method: %s', $verb)),
        };

        return $this->mapper->map($response, $this->mode, $dtoClass, $verb, $path, $operationId);
    }

    /**
     * リスト系エンドポイント共通の戻り値処理。Dto モードのときだけ
     * Collection モードで取得して配列に展開し、それ以外は素の send に委譲する。
     *
     * 各リソースの list メソッドに散在していた同一の Dto アンラップを集約する。
     *
     * @internal 低レベル配管。公開 API は Resource 層（`Chatwork::rooms()` 等）を使うこと。
     *   このシグネチャは後方互換保証の対象外。
     *
     * @param  array<string, mixed>  $payload
     * @param  class-string<MapsFromArray>  $dtoClass
     *
     * @throws InvalidArgumentException $method がサポートされていない HTTP メソッドの場合。
     * @throws ChatworkRequestException 投例モード（asArray/asDto/asCollection）で 4xx/5xx が返った場合。
     */
    public function sendList(
        string $method,
        string $path,
        array $payload,
        string $dtoClass,
        ?string $operationId = null,
    ): mixed {
        if ($this->mode === ResponseMode::Dto) {
            return $this->withMode(ResponseMode::Collection)
                ->send($method, $path, $payload, $dtoClass, $operationId)
                ->all();
        }

        return $this->send($method, $path, $payload, $dtoClass, $operationId);
    }

    /**
     * マルチパートファイルアップロード。attach() がマルチパートボディ形式を強制するのに対して、
     * 他の書き込みはすべて asForm() を経由するため、send() と分離している。
     *
     * 具体的な戻り値の型は {@see ResponseMode} によって決まるため `mixed`。
     *
     * @internal 低レベル配管。公開 API は Resource 層（`Chatwork::rooms()` 等）を使うこと。
     *   このシグネチャは後方互換保証の対象外。
     *
     * @param  array<string, scalar>  $fields  ファイル以外のマルチパートパーツ
     * @param  class-string<MapsFromArray>|null  $dtoClass  Dto/Collection モードでハイドレートする DTO
     *
     * @throws ChatworkRequestException 投例モード（asArray/asDto/asCollection）で 4xx/5xx が返った場合。
     */
    public function upload(
        string $path,
        string $fileField,
        string $fileContents,
        string $filename,
        array $fields = [],
        ?string $dtoClass = null,
        ?string $operationId = null,
    ): mixed {
        $response = $this->factory->create($this->connection)
            ->attach($fileField, $fileContents, $filename)
            ->post($path, $fields);

        return $this->mapper->map($response, $this->mode, $dtoClass, 'POST', $path, $operationId);
    }

    /**
     * rooms()->messages()->create() の簡易ショートカット。戻り値はアクティブなモードに従う。
     *
     * @throws ChatworkRequestException 投例モードで 4xx/5xx が返った場合。
     */
    public function createRoomMessage(int $roomId, string $body, ?bool $selfUnread = null): mixed
    {
        return $this->rooms()->messages()->create($roomId, $body, $selfUnread);
    }
}
