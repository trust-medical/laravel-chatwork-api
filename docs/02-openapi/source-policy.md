# API仕様ソース方針

## 仕様ソースの優先順位

1. Chatwork公式Reference: https://developer.chatwork.com/reference
2. Chatworkエンドポイント説明: https://developer.chatwork.com/docs/endpoints
3. 補完済みOpenAPI: `docs/02-openapi/chatwork-api-v2-complemented.openapi.json`
4. 実装順序とLaravel API設計用の正規化仕様: `docs/02-openapi/normalized-chatwork-api-v2.yaml`

## 補完済みOpenAPI

`docs/02-openapi/chatwork-api-v2-complemented.openapi.json` は、公式Reference、公式PDF、公式OAuth文書、公式Changelogをもとに補完した実装参照用OpenAPIである。

補完済み範囲:

- Chatwork API v2の公開Reference上の全エンドポイント。
- OAuth2 token endpoint。
- API TokenとOAuth2 Bearer Tokenのsecurity scheme。
- POST/PUTの `application/x-www-form-urlencoded` request body。
- ファイルアップロードの multipart request body。
- 主要response schema。
- 204、400、401、403、404、429 response。

補完済みOpenAPIを実装時の正とする。

## 正規化仕様の役割

`normalized-chatwork-api-v2.yaml` は、実装順序やLaravel package APIを検討するための内部仕様である。
公式Referenceをもとに次を管理する。

- HTTP method
- path
- resource分類
- operationId
- request形式
- 主な入力制約
- response DTO候補
- 破壊的操作かどうか
- 初期実装優先度

## 補完ルール

- 公式Referenceに存在しないoperationは補完済みOpenAPIと正規化仕様に追加する。
- 公式Referenceを優先し、矛盾がある場合は補完済みOpenAPIを修正する。
- 公式Reference本文でresponse body例が確認できないoperationは公式PDFで補完する。

## 公式仕様から確認済みの前提

Chatwork公式のエンドポイント説明では、base URIは `https://api.chatwork.com/v2` とされている。
また、API TokenはHTTP headerの `x-chatworktoken` で送信し、POST/PUTの通常リクエストボディは `application/x-www-form-urlencoded` とされている。

参照: https://developer.chatwork.com/docs/endpoints

2025-07-03以降、POST/PUT操作ではquery parameterではなく `application/x-www-form-urlencoded` のbody parameterを利用する必要がある。

参照: https://developer.chatwork.com/changelog/202501-notice
