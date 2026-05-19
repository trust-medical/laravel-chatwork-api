# Security Policy

## Supported Versions

最新の `1.x` リリースに対してセキュリティ修正を提供します。

## Reporting a Vulnerability

脆弱性を見つけた場合は **公開 issue を作成しないでください**。

GitHub の [Security Advisories](https://github.com/trust-medical/laravel-chatwork-api/security/advisories/new)
から非公開で報告してください。可能であれば以下を含めてください:

- 影響を受けるバージョン / コンポーネント
- 再現手順または PoC
- 想定される影響

報告受領後、できるだけ速やかに確認し、修正版のリリースと
（必要に応じて）GitHub Security Advisory の公開を行います。

## 留意事項

- API トークン / client secret / refresh token は例外メッセージ・ログ・
  デバッグ出力に出力されません（マスク済み）。万一漏えいが疑われる場合は
  ただちにトークンをローテーションしてください。
- OAuth2 callback ルートはデフォルト無効、`state` 検証必須、callback には
  既定で throttle を適用しています。
