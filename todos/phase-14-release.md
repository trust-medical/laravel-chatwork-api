# Phase 14: Release Preparation

## 目的

OSS / Packagist 公開とドキュメント整備。全 endpoint 実装と全テスト緑を前提に、利用者がインストールして使える状態にする。

## 前提

- Phase 0〜13 すべて完了。
- すべてのテストが緑、PHPStan / Pint クリーン。

## DoD

- README で利用者がインストール〜送信までのチュートリアルを完走できる。
- CHANGELOG / LICENSE 整備済み。
- CI（GitHub Actions）が main / PR で緑。
- Packagist に公開される（または公開可能な状態）。

## TODO

### 14-1. README.md（ルート）

- [ ] 概要 / バッジ（Packagist version, Tests, License）
- [ ] Requirements（PHP / Laravel）
- [ ] Installation（composer require）
- [ ] Quickstart（API Token / Bearer / Notification の最短サンプル）
- [ ] Configuration（`config/chatwork.php` の主要キー）
- [ ] Authentication
  - API Token
  - OAuth2（authorization URL → callback → refresh）
  - `TokenProvider` の自前実装方法
- [ ] Resources（メソッド一覧と OpenAPI operationId 対応表）
- [ ] Return Modes（6 モード）
- [ ] Notifications（Channel / Message / Route / 配列route / 衝突検知）
- [ ] Error Handling
  - `ChatworkValidationException`
  - `ChatworkRequestException`（`errors()` / `error()` / `errorDescription()` / `rateLimit()`）
  - `ChatworkAuthenticationException`
  - `ChatworkRoutingException`
- [ ] Testing（利用者側で `Http::fake()` で検証する方法）
- [ ] Security
  - HTTP Client log middleware の token redact 注意書き
  - `state` 検証必須
  - callback route デフォルト無効
- [ ] Roadmap / Limitations
  - retry / rate limit 制御は実装しない（利用者が `rateLimit()` を使って実装）
  - 配列 route の partial success aggregation は将来対応
  - Chatwork記法 advanced（rp, 引用, 絵文字）は将来対応
- [ ] Contributing
- [ ] License

### 14-2. CHANGELOG.md

- [ ] `CHANGELOG.md` 作成（Keep a Changelog 形式）
- [ ] `[Unreleased]` セクション
- [ ] `[1.0.0] - YYYY-MM-DD` セクションに Phase 0〜13 の主要機能を整理

### 14-3. LICENSE

- [ ] `LICENSE` 確認（既存なければ MIT を採用、`trust-medical` 名義）

### 14-4. CI（GitHub Actions）

- [ ] `.github/workflows/ci.yml` 完成版
  - matrix:
    - PHP: `8.3`, `8.4`
    - Laravel: `^11.0`, `^12.0`, `^13.0`
    - dependency-version: `prefer-lowest`, `prefer-stable`
  - steps:
    - checkout
    - setup-php
    - composer install
    - `composer lint`（Pint --test）
    - `composer analyse`（PHPStan）
    - `composer test`（Pest with coverage）
    - coverage upload to Codecov（optional）
- [ ] `.github/workflows/release.yml`（タグ push でリリース自動化、optional）
- [ ] `.github/dependabot.yml`（composer 依存の自動更新）

### 14-5. Packagist 公開準備

- [ ] `composer.json` の最終確認
  - `version` は付けない（Packagist はタグから取る）
  - `keywords`: `chatwork`, `laravel`, `notification`, `api`, `api-v2`
  - `support.issues` / `source` URL
  - `funding`（optional）
- [ ] `composer validate --strict` 緑
- [ ] GitHub repo を public にする
- [ ] Packagist に submit
- [ ] GitHub と Packagist の auto-update webhook 設定

### 14-6. ドキュメント補完（任意）

- [ ] `docs/` に利用者向けの追加 page（高度な OAuth2 設定、複数 connection 切替の例、Notification queue tuning）
- [ ] GitHub Pages または VitePress でドキュメントサイト（optional、後続）

### 14-7. リリース直前の最終 QA

- [ ] `composer audit` で脆弱な依存がないこと
- [ ] `security-reviewer` agent で全 src/ をスキャン
- [ ] OpenAPI で定義された全 32 operations が Resource にマップされていること（手動チェック or `openapi-diff-analyzer` agent）
- [ ] sample app（または `tests/Integration/`）で実 API を叩けることをローカル手動確認（**コミットしない**）
- [ ] `CLAUDE.md` の依存方向図と src/ 構成が最終実装と一致していること
- [ ] `docs/` 全体を読み直して obsolete な記述を更新
- [ ] `review/round1/00_summary.md` の CRITICAL/HIGH がすべてクローズされていること

### 14-8. リリース

- [ ] git tag `v1.0.0` 作成（annotated tag）
- [ ] GitHub Release ノート作成（CHANGELOG をコピー）
- [ ] Packagist に最新版が反映されることを確認
- [ ] アナウンス（社内 Slack / Chatwork など、任意）

## 完了後

→ パッケージ MVP リリース完了。バグ報告対応や次バージョン（v1.1 / v2.0）に向けたバックログへ移行。
