---
name: release-package
description: trust-medical/laravel-chatwork-api を Composer パッケージとしてリリースする一連の手続き（CHANGELOG 確定 → release ブランチ + PR → CI 確認 → main merge → vX.Y.Z タグ push → release workflow 発火確認 → GitHub Release 検証 → Packagist 同期確認 → composer 解決確認）を、ステップごとにユーザー承認を取りながら遂行するスキル。手動呼び出し専用（`/release-package <version>`）。ユーザーが「リリースして」「vX.Y.Z を出して」「タグ打って公開」「パッケージを公開」と言った時、または `[Unreleased]` セクションを確定リリースに変えたい時に呼ぶ。本パッケージ専用（Keep a Changelog + tag-triggered release.yml + Packagist webhook 同期を前提）。
argument-hint: <version>（例: 1.0.1、`v` プレフィックスは付けない）
arguments: version
disable-model-invocation: true
allowed-tools: Read, Write, Edit, Bash, Grep, Glob
---

# Release: v$version

trust-medical/laravel-chatwork-api を **v$version** としてリリースする手続きを進める。

各 step の末尾でユーザーに**続行確認を取り**、失敗したら即停止する。途中で `git push --force` / `git reset --hard` / `--no-verify` / `--amend` を使ってはならない（`.claude/rules/commit-style.md` に反する）。

## 引数検証

`$version` は semver（例: `1.0.1`, `1.1.0`, `2.0.0-rc1`）。`v` プレフィックスを付けない。空・不正な形式なら中断する。

```!
echo "Target version: v$version"
echo "$version" | grep -Eq '^[0-9]+\.[0-9]+\.[0-9]+(-[0-9A-Za-z.-]+)?$' || echo "INVALID: not a semver"
```

## Pre-flight チェック

下記がすべて OK でないと進めない。

```!
git -C . rev-parse --abbrev-ref HEAD
git -C . status --porcelain
git tag --list "v$version"
gh api repos/trust-medical/laravel-chatwork-api/branches/main/protection 2>&1 | head -1
```

判定:
- 現在ブランチが `main` でなければ中断（`git checkout main && git pull` を促す）
- 作業ツリーに未コミット変更があれば中断
- `v$version` タグが既存なら中断（リリース済み）
- main は保護なし想定。protection があれば手順を読み替える

## ローカルチェック（全 green 必須）

CI と重複するが、PR 提出前に local で early-fail させる。

```!
./vendor/bin/pint --test
./vendor/bin/phpstan analyse --memory-limit=512M
./vendor/bin/pest
```

1 つでも fail したら中断し、修正コミットを別 PR で先行マージしてから再開する。リリース PR に修正を混ぜない。

## CHANGELOG 編集

### 編集ルール（必ず守る）

CHANGELOG.md は **Keep a Changelog** 形式。`.github/workflows/release.yml` は **タグ名から逆引きで `## [<version>]` セクションを抽出する**（PR #8 で fix 済み）。よって以下を守る:

- `## [Unreleased]` 見出しは **rename しない**。空のまま最上部に残す（次の開発サイクル用）
- その下に新しい `## [$version] - <YYYY-MM-DD>` セクションを追加し、これまで `[Unreleased]` に書かれていた本文をこちらに移す
- 末尾のリンク参照を 2 行更新:
  - `[Unreleased]: https://github.com/trust-medical/laravel-chatwork-api/compare/v$version...HEAD`
  - `[$version]: https://github.com/trust-medical/laravel-chatwork-api/releases/tag/v$version`

### 編集手順

1. `date +%Y-%m-%d` で本日の日付を取得
2. Edit ツールで CHANGELOG.md を編集:
   - `## [Unreleased]` 直後の空行の **後ろ** に `## [$version] - <date>` 見出しを挿入
   - これまで `[Unreleased]` の下にあった本文（Added/Changed/Fixed/Security/Documentation セクション）をそのまま下の `[$version]` セクション配下に置く（実質、`[Unreleased]` セクションの内容を `[$version]` セクションに移す）
   - 末尾リンク参照を上記の通り 2 行更新
3. `git diff CHANGELOG.md` を表示してユーザーに目視確認させる

### 抽出ロジックの dry-run 検証

ユーザーに承認を求める前に、release.yml と同じ awk で `## [$version]` セクションが正しく抽出できるか必ず検証する:

```!
awk -v ver="$version" '
  $0 ~ "^## \\[" ver "\\]( |$)" { capture=1; print; next }
  /^## \[/ && capture { exit }
  /^\[[^]]+\]:/ && capture { exit }
  capture { print }
' CHANGELOG.md | head -20
```

最初の行が `## [$version] - <date>` であること、本文が抽出されることを確認する。空なら CHANGELOG 編集をやり直す。

## Release ブランチ作成と PR

```!
git checkout -b "chore/release-v$version"
git add CHANGELOG.md
```

commit メッセージは下記 HEREDOC で渡す（`.claude/rules/commit-style.md` の Conventional Commits + Co-Authored-By を守る）:

```
chore(release): prepare $version

CHANGELOG の [Unreleased] 本文を [$version] - <date> に確定し、
release.yml がタグ名から抽出する `## [$version]` セクションを正式版
リリースノートとして整備。新しい空の [Unreleased] と、末尾リンク参照
（[$version] リリースタグ / [Unreleased] compare URL）も追加。

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>
```

push と PR 作成:

```!
git push -u origin "chore/release-v$version"
gh pr create --title "chore(release): prepare $version" --body "..."
```

PR 本文には以下を含める:
- Summary（CHANGELOG 確定の意図）
- Release readiness（OpenAPI カバレッジ / Pest 件数 / Pint / PHPStan / 前回 merge 以降の変更要約）
- Next step（タグ push → Packagist 同期確認）
- Test plan checklist（CI green、CHANGELOG 抽出確認）

## CI 通過確認

```!
gh pr checks <PR番号> --watch
gh pr view <PR番号> --json mergeable,mergeStateStatus,statusCheckRollup --jq '{mergeable, mergeStateStatus, failing: [.statusCheckRollup[] | select(.conclusion == "FAILURE") | .name]}'
```

許容ルール:
- Pint / PHPStan / Pest (PHP 8.3/8.4 × Laravel 11/12 × stable/lowest) は **全 pass 必須**
- **Laravel 13 matrix の fail は許容**（`ci.yml` で `continue-on-error: true` の experimental。`pestphp/pest-plugin-laravel` 上流対応待ち）
- `mergeStateStatus` が `UNSTABLE` でも上記条件を満たせば `mergeable: MERGEABLE` で merge 可

ユーザー承認を取って merge:

```!
gh pr merge <PR番号> --merge --delete-branch
```

これまでの PR 履歴と整合させるため `--merge`（merge commit）を使う。`--squash` は使わない。

## main 同期とタグ push

```!
git checkout main
git pull origin main
git log --oneline -3
```

merge commit が手元に来たことを確認したら、ユーザー承認を取ってタグを切る。

```!
MERGE_SHA=$(git rev-parse HEAD)
git tag -a "v$version" -m "Release v$version" "$MERGE_SHA"
git push origin "v$version"
```

タグ push は **不可逆操作（GitHub Release が一般公開される）** なので、push の直前に必ず再確認する。

## release workflow 発火確認

タグ push 後、`.github/workflows/release.yml` が自動発火する。

```!
sleep 5
gh run list --workflow=release.yml --limit 3
```

直近 run が `status: completed, conclusion: success` であることを確認。failed なら次節の「修復」へ。

## GitHub Release 検証

```!
gh release view "v$version" --json url,name,publishedAt,tagName,isPrerelease,isDraft
gh release view "v$version" --json body --jq '.body' | head -3
```

検証項目:
- `isDraft: false` / `isPrerelease: false`
- リリースノート本文の **最初の行が `## [$version] - <date>`** であること

万一最初の行が `## [Unreleased]` だった場合は release.yml の awk バグ（v1.0.0 で発生、PR #8 で fix 済み）が再発した可能性がある。修復:

```!
awk -v ver="$version" '
  $0 ~ "^## \\[" ver "\\]( |$)" { capture=1; print; next }
  /^## \[/ && capture { exit }
  /^\[[^]]+\]:/ && capture { exit }
  capture { print }
' CHANGELOG.md > /tmp/v$version-notes.md
gh release edit "v$version" --notes-file /tmp/v$version-notes.md
```

## Packagist 同期確認

Packagist 初回登録は本パッケージで完了済み（v1.0.0 時点）。以降は GitHub Webhook で自動同期されるはず。

```!
curl -s "https://repo.packagist.org/p2/trust-medical/laravel-chatwork-api.json" | python3 -c "
import sys, json
d = json.load(sys.stdin)
pkgs = d.get('packages', {}).get('trust-medical/laravel-chatwork-api', [])
target = 'v$version'
hit = [p for p in pkgs if p.get('version') == target]
if hit:
    print(f'OK: {target} synced')
    print(f\"  dist ref: {hit[0].get('dist', {}).get('reference','')[:12]}\")
    print(f\"  time: {hit[0].get('time')}\")
else:
    print(f'NOT YET: {target} not in Packagist (have {[p.get(\"version\") for p in pkgs[:5]]})')
"
```

5 分待っても同期されない場合、Packagist の package settings から手動 update を促す。dist ref が `git rev-parse v$version^{commit}` と一致することも確認。

## composer 解決確認

実際の利用者と同じ手順で解決可能か検証する。

```!
TMPDIR=$(mktemp -d)
cd "$TMPDIR"
cat > composer.json <<EOF
{
  "name": "tmp/install-test",
  "require": {
    "php": "^8.3",
    "laravel/framework": "^12.0",
    "trust-medical/laravel-chatwork-api": "^$(echo $version | cut -d. -f1).0"
  },
  "minimum-stability": "stable",
  "prefer-stable": true
}
EOF
composer install --dry-run --no-interaction 2>&1 | grep -E "trust-medical|laravel/framework" | head -5
cd - && rm -rf "$TMPDIR"
```

Laravel 11 でも同じ手順で dry-run しておくと安心（`"laravel/framework": "^11.0"` に差し替え）。

`trust-medical/laravel-chatwork-api (v$version)` が解決されることを確認。

## 完了報告

下記の URL とメトリクスを 1 つのメッセージにまとめてユーザーに提示する:

- GitHub Release URL: `https://github.com/trust-medical/laravel-chatwork-api/releases/tag/v$version`
- Packagist URL: `https://packagist.org/packages/trust-medical/laravel-chatwork-api#v$version`
- リリース時の merge commit SHA
- Pest 件数 / Pint / PHPStan の green 確認
- インストールコマンド: `composer require trust-medical/laravel-chatwork-api:^<major>`

## 失敗時のロールバック指針

| 失敗段階 | 対応 |
|---|---|
| ローカルチェック失敗 | 修正を別 PR で先行マージしてから再開 |
| CI 失敗 | PR を修正 commit で更新（force push 禁止）。CHANGELOG 編集に手戻りがあれば該当ブランチ上で normal commit |
| タグ push 後の release workflow 失敗 | run のログを確認 → `gh release edit --notes-file` でノート上書き or `gh release delete v$version --cleanup-tag` で完全取り消し（**ユーザー承認必須**、タグ削除は不可逆） |
| Packagist 同期失敗 | packagist.org の package 画面で "Update" を押す or webhook 設定確認 |
| composer 解決失敗 | composer.json の制約・stability 設定の不整合を疑う。タグは残して fix 用 PR でメタデータ修正 → v$version+0.0.1 を別途リリース |

## 参考

- 前例: v1.0.0 リリース（commit `e46f9ee`, PR #7、release workflow fix PR #8）
- CHANGELOG 編集ルール: `CHANGELOG.md` 冒頭の注釈
- release workflow: `.github/workflows/release.yml`
- コミット規約: `.claude/rules/commit-style.md`
