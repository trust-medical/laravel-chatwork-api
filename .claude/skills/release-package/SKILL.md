---
name: release-package
description: trust-medical/laravel-chatwork-api を Composer パッケージとしてリリースする手順書。Claude が **1 ステップずつ** bash ブロックを Bash ツール実行し、各段階でユーザー承認を取って進める interactive な playbook（自動連続実行はしない）。流れは 状態スキャン → CHANGELOG 確定 → release ブランチ + PR → CI 確認 → main merge → vX.Y.Z タグ push → release workflow 発火確認 → GitHub Release 検証 + ノート自動修復 → Packagist 同期確認 → composer 解決確認。手動呼び出し専用（`/release-package <version>`）。ユーザーが「リリースして」「vX.Y.Z を出して」「タグ打って公開」「パッケージを公開」と言った時、または `[Unreleased]` セクションを確定リリースに変えたい時に呼ぶ。本パッケージ専用（Keep a Changelog + tag-triggered release.yml + Packagist webhook 同期を前提）。冪等再実行可（途中で止まっても状態スキャンが残作業を判定する）。
argument-hint: <version>（例: 1.0.1、`v` プレフィックスは付けない）
arguments: version
allowed-tools: Read, Write, Edit, Bash, Grep, Glob, AskUserQuestion
---

# Release: v$version

trust-medical/laravel-chatwork-api を **v$version** としてリリースする手続きを進める。

## 実行モデル（最重要・必ず守る）

**本ファイルは Claude が読む playbook。bash ブロックは skill ローダーが自動実行しない**（過去版は ` ```!` 形式で auto-execute されていたが、user approval を挟めない・CHANGELOG 編集ステップが欠落するなど致命的な不整合を起こすため廃止）。

Claude は以下のルールで進める:

1. **bash ブロックは Bash ツール経由で 1 ブロックずつ実行する**。複数のフェンスを連続実行したり、複数 step を 1 回の Bash 呼び出しに混ぜたりしない。
2. **各ブロック実行後、出力をユーザーに見せて続行可否を確認する**。出力が想定外（タグ位置不整合、CI fail、Packagist 未同期 など）であれば即停止し、ユーザーに状況を伝える。
3. **不可逆操作の前は必ず `AskUserQuestion` で明示承認を取る**。対象:
   - `git push origin "v$version"`（タグ push、Packagist に publish される最終操作）
   - `gh pr merge ... --merge --delete-branch`（main への書き込み + branch 削除）
   - `git push origin :refs/tags/v$version` / `git tag -d`（タグ削除 / 位置修復、force-push 相当の例外操作）
   - `gh release delete ... --cleanup-tag`（Release + tag 一括削除）
4. **CHANGELOG 編集は bash ブロックではなく Claude の Edit ツールで行う**。Skill ローダーがこのステップを飛ばすと CHANGELOG 未確定のままタグが打たれ release workflow が失敗する（v1.0.2 / v1.1.0 で実際に発生したリカバリ困難な失敗パターン）。「CHANGELOG 編集」セクションの手順を文字通り Edit ツールで実行する。
5. **bash ブロック内で `$version` 以外の `<...>` プレースホルダを書かない**。PR 番号などの動的値は **ブランチ名 / gh CLI から都度再取得** する。bash 変数は subshell をまたがず再導出する（プロセスが分かれるとシェル変数は引き継がれないため）。
6. 途中で止まった場合は **`/release-package $version` を再実行**すればよい。最初の「状態スキャン」が部分完了状態を検知し、残作業から resume する。

## 全体方針

- `git push --force` / `git reset --hard` / `--no-verify` / `--amend` は使わない（`.claude/rules/commit-style.md`）。例外はタグ位置修復のみ（後述、ユーザー承認必須）。
- リリース PR とコード修正 PR は混ぜない。CI fail があれば修正コミットを別 PR で先行マージしてから本 skill を再開する。
- リリースは「タグ push を境に不可逆になる」と心得る。タグ push 前の中断は再開しやすいが、push 後は必ず完了まで進める（中途半端な Release / Packagist 同期失敗は下流のキャッシュ問題に直結する）。

## 引数検証

`$version` は semver（例: `1.0.1`, `1.1.0`, `2.0.0-rc1`）。`v` プレフィックスを付けない。空・不正な形式なら中断する。

```bash
echo "Target version: v$version"
if ! echo "$version" | grep -Eq '^[0-9]+\.[0-9]+\.[0-9]+(-[0-9A-Za-z.-]+)?$'; then
  echo "INVALID: '$version' is not a semver"
  exit 1
fi
```

## 状態スキャン（必須・冪等再実行の起点）

このスキルは途中失敗・再実行を前提とする。**最初に部分完了状態を検知して resume ポイントを決める**。

```bash
BRANCH="chore/release-v$version"
echo "=== state scan for v$version ==="

echo -n "local branch ($BRANCH): "
git rev-parse --verify --quiet "refs/heads/$BRANCH" >/dev/null && echo "exists" || echo "absent"

echo -n "remote branch ($BRANCH): "
git ls-remote --heads origin "$BRANCH" | grep -q . && echo "exists" || echo "absent"

echo -n "CHANGELOG has '## [$version]': "
grep -Eq "^## \[$version\]( |$)" CHANGELOG.md && echo "yes" || echo "no"

echo -n "open/merged PR for $BRANCH: "
gh pr list --head "$BRANCH" --state all --json number,state,mergedAt --jq 'if length == 0 then "none" else .[0] | "#\(.number) state=\(.state) mergedAt=\(.mergedAt // "null")" end'

echo -n "local tag v$version: "
git tag --list "v$version" | grep -q . && echo "exists at $(git rev-list -n 1 v$version)" || echo "absent"

echo -n "remote tag v$version: "
REMOTE_TAG=$(git ls-remote origin "refs/tags/v$version" | awk '{print $1}')
[ -n "$REMOTE_TAG" ] && echo "exists at $REMOTE_TAG" || echo "absent"

echo -n "GitHub Release v$version: "
gh release view "v$version" --json tagName,isDraft,publishedAt --jq '"tagName=\(.tagName) isDraft=\(.isDraft) publishedAt=\(.publishedAt)"' 2>/dev/null || echo "not found"

echo -n "Packagist has v$version: "
curl -s "https://repo.packagist.org/p2/trust-medical/laravel-chatwork-api.json" \
  | python3 -c "import sys,json; d=json.load(sys.stdin); v=[p for p in d.get('packages',{}).get('trust-medical/laravel-chatwork-api',[]) if p.get('version')=='v$version']; print('yes (dist '+v[0].get('dist',{}).get('reference','')[:12]+')' if v else 'no')"
```

スキャン結果と進むべき step の対応:

| 検知シグナル                                  | resume すべき step                              |
| --------------------------------------------- | ----------------------------------------------- |
| すべて absent / no / none / not found / no    | **Pre-flight チェック**（通常開始）             |
| local branch exists, but no PR / no CHANGELOG | **CHANGELOG 編集** から（ローカル branch 再利用） |
| branch + CHANGELOG OK, PR open                | **CI 通過確認** へ                              |
| PR mergedAt != null, local main 未同期        | **main 同期** へ                                |
| main 同期済み, local tag absent               | **タグ作成と push** へ                          |
| remote tag exists at "merge commit SHA"       | **release workflow 発火確認** へ                |
| remote tag exists but at non-merge SHA        | **タグ位置修復**（後述）                        |
| Release publishedAt != null, Packagist no     | **Packagist 同期確認** へ                       |
| Release + Packagist ともに OK                 | **composer 解決確認 → 完了報告** へ             |

不整合が複数ある（例: tag が remote にあるが Release が無い）場合は、`gh run list --workflow=release.yml --limit 3` でワークフロー run を確認し、ユーザーに状況提示してから判断を仰ぐ。

## Pre-flight チェック

通常開始時の前提条件。

```bash
echo -n "current branch: "; git rev-parse --abbrev-ref HEAD
echo "uncommitted changes:"; git status --porcelain
echo -n "remote main protection: "
gh api repos/trust-medical/laravel-chatwork-api/branches/main/protection 2>&1 | head -1
```

判定:

- 現在ブランチが `main` でなければ中断（`git checkout main && git pull origin main` を促す）
- 作業ツリーに未コミット変更があれば中断（**この skill の差分以外を含めない**ため重要）
- main が保護されていれば手順を読み替える（現状は保護なし想定）

## ローカルチェック（全 green 必須）

CI と重複するが、PR 提出前に local で early-fail させる。

```bash
./vendor/bin/pint --test
./vendor/bin/phpstan analyse --memory-limit=512M
./vendor/bin/pest
```

1 つでも fail したら中断し、修正コミットを別 PR で先行マージしてから再開する。**リリース PR に修正を混ぜない**。

## CHANGELOG 編集

### 編集ルール（必ず守る）

CHANGELOG.md は **Keep a Changelog** 形式。`.github/workflows/release.yml` は **タグ名から逆引きで `## [<version>]` セクションを抽出する**。よって以下を守る:

- `## [Unreleased]` 見出しは **rename しない**。空のまま最上部に残す（次の開発サイクル用）。
- その下に新しい `## [$version] - <YYYY-MM-DD>` セクションを追加し、これまで `[Unreleased]` 配下にあった本文をそちらに移す。
- 末尾のリンク参照を 2 行更新:
  - `[Unreleased]: https://github.com/trust-medical/laravel-chatwork-api/compare/v$version...HEAD`
  - `[$version]: https://github.com/trust-medical/laravel-chatwork-api/releases/tag/v$version`

### 編集手順

1. `date +%Y-%m-%d` で本日の日付を取得（下の bash ブロックを Bash ツールで実行する）。
2. Edit ツールで `CHANGELOG.md` を編集:
   - `## [Unreleased]` 直後の空行の **後ろ** に `## [$version] - <date>` 見出しを挿入
   - これまで `[Unreleased]` 配下にあった本文（Added/Changed/Fixed/Security/Documentation セクション）を `[$version]` セクション配下に移す
   - 末尾リンク参照を上記の通り 2 行更新
3. `git diff CHANGELOG.md` を表示してユーザーに目視確認させる

```bash
date +%Y-%m-%d
```

### 抽出ロジックの dry-run 検証

ユーザーに承認を求める前に、release.yml と同じ awk で `## [$version]` セクションが正しく抽出できるか必ず検証する:

```bash
awk -v ver="$version" '
  $0 ~ "^## \\[" ver "\\]( |$)" { capture=1; print; next }
  /^## \[/ && capture { exit }
  /^\[[^]]+\]:/ && capture { exit }
  capture { print }
' CHANGELOG.md | head -25
```

最初の行が `## [$version] - <date>` であること、本文が抽出されること、**バッククォート含む inline code がそのまま見えること** を確認する。空なら CHANGELOG 編集をやり直す。

## Release ブランチ作成と PR

```bash
BRANCH="chore/release-v$version"
if git rev-parse --verify --quiet "refs/heads/$BRANCH" >/dev/null; then
  echo "branch $BRANCH already exists locally; checking out"
  git checkout "$BRANCH"
else
  git checkout -b "$BRANCH"
fi
git add CHANGELOG.md
git status --short
```

commit メッセージは下記 HEREDOC で渡す（`.claude/rules/commit-style.md` の Conventional Commits + Co-Authored-By を守る）。Claude は `Bash` ツール上で次のテンプレを `$version` と本日の日付に置換して実行する:

```
chore(release): prepare <version>

CHANGELOG の [Unreleased] 本文を [<version>] - <date> に確定し、
release.yml がタグ名から抽出する `## [<version>]` セクションを正式版
リリースノートとして整備。新しい空の [Unreleased] と、末尾リンク参照
（[<version>] リリースタグ / [Unreleased] compare URL）も追加。

Co-Authored-By: Claude Opus 4.7 (1M context) <noreply@anthropic.com>
```

push と PR 作成（**PR 番号はこの後ブランチ名から再導出するため、ここで控えなくてよい**）:

```bash
BRANCH="chore/release-v$version"
git push -u origin "$BRANCH"
gh pr create --title "chore(release): prepare $version" --body "$(cat <<EOF
## Summary

CHANGELOG の [Unreleased] 本文を [$version] - $(date +%Y-%m-%d) セクションに確定し、release.yml がタグ名から抽出する \`## [$version]\` セクションを正式版リリースノートとして整備。コードへの変更なし。

## Release readiness

- Pint / PHPStan / Pest 全 green（ローカル確認済み）
- CI matrix: Laravel 11/12 全 SUCCESS 必須、Laravel 13 は \`pestphp/pest-plugin-laravel ^3.0\` 上流対応待ちで experimental fail を許容
- CHANGELOG 抽出 dry-run: \`## [$version]\` セクションがクリーンに取れることを確認済み

## Next step

1. この PR を merge commit でマージ
2. main 同期 → \`v$version\` タグを merge commit に打って push
3. release.yml 発火を確認 → GitHub Release ノート差分検証 → Packagist 同期確認 → composer 解決確認

## Test plan

- [ ] CI green（Laravel 11/12 全 SUCCESS、Laravel 13 fail は許容）
- [ ] CHANGELOG \`## [$version]\` セクションが release.yml の awk 抽出でクリーンに取れる（dry-run 済）

🤖 Generated with [Claude Code](https://claude.com/claude-code)
EOF
)"
```

PR が既に存在する場合（再実行時）は `gh pr create` が「pull request already exists」エラーで終わるので、その出力をそのまま CI 確認ステップへ進む合図にする。

## CI 通過確認

PR 番号はブランチ名から都度引く（subshell をまたいでも安全）。

```bash
BRANCH="chore/release-v$version"
PR_NUMBER=$(gh pr list --head "$BRANCH" --state open --json number --jq '.[0].number')
if [ -z "$PR_NUMBER" ]; then
  echo "no open PR for $BRANCH"
  exit 1
fi
echo "PR_NUMBER=$PR_NUMBER"
gh pr checks "$PR_NUMBER" --watch
```

```bash
BRANCH="chore/release-v$version"
PR_NUMBER=$(gh pr list --head "$BRANCH" --state open --json number --jq '.[0].number')
gh pr view "$PR_NUMBER" --json mergeable,mergeStateStatus,statusCheckRollup \
  --jq '{
    mergeable,
    mergeStateStatus,
    failing: [.statusCheckRollup[] | select(.conclusion == "FAILURE") | .name],
    passing: [.statusCheckRollup[] | select(.conclusion == "SUCCESS") | .name] | length
  }'
```

許容ルール:

- Pint / PHPStan / Pest (PHP 8.3/8.4 × Laravel 11/12 × stable/lowest) は **全 pass 必須**
- **Laravel 13 matrix の fail は許容**（`pestphp/pest-plugin-laravel` 上流対応待ち）
- `mergeStateStatus` が `UNSTABLE` でも上記条件を満たせば `mergeable: MERGEABLE` で merge 可

`failing` 配列に Laravel 13 以外が含まれていれば中断してユーザーに報告。

> **STOP — destructive gate**: 以下の `gh pr merge` は main を書き換える不可逆操作。実行前に `AskUserQuestion` で「PR #$PR_NUMBER を `--merge --delete-branch` でマージしてよいか」を明示確認する。CI ステータス（pass 数 / fail 数 / Laravel 13 fail のみ許容）も同じ AskUserQuestion の質問文中に貼り、ユーザーが判断できる状態で問う。

```bash
BRANCH="chore/release-v$version"
PR_NUMBER=$(gh pr list --head "$BRANCH" --state open --json number --jq '.[0].number')
gh pr merge "$PR_NUMBER" --merge --delete-branch
```

これまでの PR 履歴と整合させるため `--merge`（merge commit）を使う。`--squash` は使わない。

## main 同期

```bash
git checkout main
git pull origin main
git log --oneline -5
```

直近 commit が `Merge pull request #<PR_NUMBER> from trust-medical/chore/release-v$version` であることを目視確認。`chore(release): prepare $version` も一つ下に見えるはず。

## タグ pre-check（push 直前に必ず実行）

タグ位置のミス（fix commit に打つ等）を防ぐため、push 直前に **ローカル / リモート両方** を再検証する。

```bash
echo -n "target merge SHA: "
MERGE_SHA=$(git rev-parse HEAD)
echo "$MERGE_SHA"

echo -n "local tag v$version: "
git tag --list "v$version" | grep -q . && echo "exists at $(git rev-list -n 1 v$version)" || echo "absent"

echo -n "remote tag v$version: "
REMOTE_TAG=$(git ls-remote origin "refs/tags/v$version" | awk '{print $1}')
[ -n "$REMOTE_TAG" ] && echo "exists at $REMOTE_TAG" || echo "absent"

echo -n "HEAD is merge commit (has 2 parents): "
git cat-file -p HEAD | grep -c '^parent ' | grep -q '^2$' && echo "yes" || echo "no (likely a fix commit; do not tag here)"
```

判定:

- `local tag` も `remote tag` も absent、かつ `HEAD is merge commit: yes` → **タグ作成と push** へ進む
- どちらかの tag が exists かつ **同じ SHA** = `MERGE_SHA` → タグは既に正位置。**release workflow 発火確認** へスキップ
- どちらかの tag が exists かつ **異なる SHA** → **タグ位置修復**（次節）が必要

## タグ作成と push（不可逆操作）

> **STOP — destructive gate**: タグ push は **Packagist へ publish される最終操作**。push 後はリトラクト不可（タグ削除は force-push 相当の例外操作で、Packagist キャッシュにも残る）。実行前に `AskUserQuestion` で次を確認する:
> - HEAD merge commit SHA は意図したものか（`MERGE_SHA` を質問文に貼る）
> - CHANGELOG に `## [$version] - <date>` セクションが入っているか（`grep -q "^## \[$version\]" CHANGELOG.md` 結果を貼る）
> - main は origin/main と同期されているか
> ユーザーが Yes と答えてから初めて実行する。`$MERGE_SHA` は **同じ Bash 呼び出し内** で再取得する（subshell をまたぐとシェル変数は引き継がれないため）。

```bash
MERGE_SHA=$(git rev-parse HEAD)
echo "tagging v$version at $MERGE_SHA"
git tag -a "v$version" -m "Release v$version" "$MERGE_SHA"
git push origin "v$version"
```

タグ push 後、`.github/workflows/release.yml` が自動発火する。

### タグ位置修復（remote tag が wrong SHA の場合）

> **STOP — destructive gate (force-push 例外)**: タグ削除は `commit-style.md` の force-push 禁則の **唯一の例外**。実行前に `AskUserQuestion` で必ず承認を取る。質問文に下記を含める:
> - 現在の wrong SHA と移したい正しい SHA（`git ls-remote origin "refs/tags/v$version"` 結果 vs `git rev-parse HEAD`）
> - Release が既に存在するか（`gh release view "v$version"` の結果）
> - 既存 Release を残すか / `gh release delete --cleanup-tag` で同時に消すか
> ユーザーが Yes と答えてから 1 ブロックずつ実行する（下記コマンドを一気に走らせない）:

```bash
# Step 1: Release 存在チェック（informational）
gh release view "v$version" --json isDraft 2>/dev/null \
  && echo "Release exists; consider 'gh release delete v$version --cleanup-tag' before retagging" \
  || echo "no Release; tag deletion is safe"
```

```bash
# Step 2: local + remote tag 削除（不可逆）
git tag -d "v$version"
git push origin ":refs/tags/v$version"
```

```bash
# Step 3: 正位置に retag + push（不可逆、Packagist 再 publish）
MERGE_SHA=$(git rev-parse HEAD)
git tag -a "v$version" -m "Release v$version" "$MERGE_SHA"
git push origin "v$version"
```

## release workflow 発火確認

タグ push の数秒後に workflow run が現れる。`--watch` で完了を待つ。

```bash
RUN_ID=""
while [ -z "$RUN_ID" ]; do
  RUN_ID=$(gh run list --workflow=release.yml --limit 5 --json databaseId,headBranch \
    --jq ".[] | select(.headBranch == \"v$version\") | .databaseId" | head -1)
  [ -z "$RUN_ID" ] && sleep 3
done
echo "watching run $RUN_ID"
gh run watch "$RUN_ID" --exit-status
gh run view "$RUN_ID" --json conclusion,headSha,headBranch --jq '{conclusion, headSha, headBranch}'
```

`conclusion: success` でなければ次の `gh run view --log-failed` で原因を見る。よくある failure: CHANGELOG セクション欠落（`[Unreleased]` を rename してしまった等） / 抽出結果が空。

## GitHub Release 検証 + ノート自動修復（重要）

`release.yml` は `gh release create --notes "${{ ... }}"` で notes を bash の double-quote 内に展開しているため、**バッククォート（inline code）がコマンド置換として消費されて全消失する**既存バグがある（PR で修正予定）。Skill 側で次の auto-repair を行い、リリース発生時点で確実にノートが正しい状態にする。

```bash
echo "=== release metadata ==="
gh release view "v$version" --json url,name,publishedAt,tagName,isPrerelease,isDraft

echo "=== first 5 lines of release body ==="
gh api "repos/trust-medical/laravel-chatwork-api/releases/tags/v$version" --jq '.body' | head -5

echo "=== expected notes (from local CHANGELOG) ==="
awk -v ver="$version" '
  $0 ~ "^## \\[" ver "\\]( |$)" { capture=1; print; next }
  /^## \[/ && capture { exit }
  /^\[[^]]+\]:/ && capture { exit }
  capture { print }
' CHANGELOG.md > "/tmp/v$version-notes.md"
head -5 "/tmp/v$version-notes.md"
```

検証項目:

- `isDraft: false` / `isPrerelease: false`
- リリースノート本文の最初の行が `## [$version] - <date>` であること
- `/tmp/v$version-notes.md` と release body が **inline code（バッククォート）含めて一致** すること

ノートに **バッククォートが消失している、または最初の行が一致しない** 場合は auto-repair:

```bash
ACTUAL=$(gh api "repos/trust-medical/laravel-chatwork-api/releases/tags/v$version" --jq '.body')
EXPECTED=$(cat "/tmp/v$version-notes.md")
if [ "$ACTUAL" != "$EXPECTED" ]; then
  echo "notes differ; repairing via --notes-file"
  gh release edit "v$version" --notes-file "/tmp/v$version-notes.md"
  echo "after repair:"
  gh api "repos/trust-medical/laravel-chatwork-api/releases/tags/v$version" --jq '.body' | head -5
else
  echo "notes match expected; no repair needed"
fi
```

## Packagist 同期確認

Packagist 初回登録は本パッケージで完了済み（v1.0.0 時点）。以降は GitHub Webhook で自動同期されるはず。dist ref が `MERGE_SHA` と一致することも確認する。

```bash
MERGE_SHA=$(git rev-list -n 1 "v$version")
curl -s "https://repo.packagist.org/p2/trust-medical/laravel-chatwork-api.json" | python3 -c "
import sys, json
d = json.load(sys.stdin)
pkgs = d.get('packages', {}).get('trust-medical/laravel-chatwork-api', [])
target = 'v$version'
hit = [p for p in pkgs if p.get('version') == target]
if hit:
    dist_ref = hit[0].get('dist', {}).get('reference','')
    print(f'OK: {target} synced (dist ref {dist_ref[:12]}, time {hit[0].get(\"time\")})')
    if not '$MERGE_SHA'.startswith(dist_ref):
        print(f'WARN: dist ref does not match MERGE_SHA {\"$MERGE_SHA\"[:12]}')
else:
    print(f'NOT YET: {target} not in Packagist (have {[p.get(\"version\") for p in pkgs[:5]]})')
    sys.exit(1)
"
```

5 分待っても同期されない場合、Packagist の package settings から手動 update を促す。

## composer 解決確認

実際の利用者と同じ手順で解決可能か検証する。

```bash
TMPDIR=$(mktemp -d)
cd "$TMPDIR"
MAJOR=$(echo "$version" | cut -d. -f1)
cat > composer.json <<EOF
{
  "name": "tmp/install-test",
  "require": {
    "php": "^8.3",
    "laravel/framework": "^12.0",
    "trust-medical/laravel-chatwork-api": "^${MAJOR}.0"
  },
  "minimum-stability": "stable",
  "prefer-stable": true
}
EOF
composer install --dry-run --no-interaction 2>&1 | grep -E "trust-medical|laravel/framework" | head -5
cd - >/dev/null && rm -rf "$TMPDIR"
```

`trust-medical/laravel-chatwork-api (v$version)` が解決されることを確認。Laravel 11 でも同じ手順で dry-run しておくと安心（`"laravel/framework": "^11.0"` に差し替え）。

## 完了報告

下記の URL とメトリクスを 1 つのメッセージにまとめてユーザーに提示する:

- GitHub Release URL: `https://github.com/trust-medical/laravel-chatwork-api/releases/tag/v$version`
- Packagist URL: `https://packagist.org/packages/trust-medical/laravel-chatwork-api#v$version`
- リリース時の merge commit SHA
- Pest 件数 / Pint / PHPStan の green 確認
- インストールコマンド: `composer require trust-medical/laravel-chatwork-api:^<major>`

## 中断状態からのリカバリ指針

| 失敗段階                                    | 対応                                                                                                                                                                                                                                            |
| ------------------------------------------- | ----------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| ローカルチェック失敗                        | 修正を別 PR で先行マージしてから再開                                                                                                                                                                                                            |
| CI 失敗（Laravel 13 以外）                  | PR を修正 commit で更新（force push 禁止）。CHANGELOG 編集に手戻りがあれば該当ブランチ上で normal commit                                                                                                                                        |
| `gh pr create` で "already exists"          | 状態スキャンが PR 検知するはず。CI 確認ステップへ進む                                                                                                                                                                                           |
| ローカル `chore/release-vX.Y.Z` 既存        | 状態スキャンで検知 → 同名ブランチを `git checkout` で再利用、または手動削除（`git branch -D`）                                                                                                                                                  |
| **タグが wrong SHA を指して remote に存在** | **「タグ位置修復」** へ。`git push origin :refs/tags/v$version` で削除 → 正位置で再作成 → push。Release が既にあれば `gh release delete v$version --cleanup-tag` を先行（ユーザー承認必須・不可逆）                                              |
| release workflow 失敗（抽出結果空など）     | `gh run view --log-failed` で原因確認 → CHANGELOG を修正してメインに別 PR でマージ → タグを wrong-SHA 修復手順で打ち直し or `gh release edit --notes-file` でノート上書き                                                                        |
| **release notes のバッククォート消失**      | **GitHub Release 検証ステップで auto-repair される**。万一 skill 外で気づいた場合は `awk -v ver=X ... CHANGELOG.md > /tmp/notes.md && gh release edit vX --notes-file /tmp/notes.md`                                                             |
| Packagist 同期失敗                          | packagist.org の package 画面で "Update" を押す or webhook 設定確認                                                                                                                                                                             |
| composer 解決失敗                           | composer.json の制約・stability 設定の不整合を疑う。タグは残して fix 用 PR でメタデータ修正 → `$version` +0.0.1 を別途リリース                                                                                                                  |

## 参考

- 前例: v1.0.0 リリース（commit `e46f9ee`, PR #7、release workflow fix PR #8）、v1.0.1（PR #11）、v1.0.2（PR #13）
- CHANGELOG 編集ルール: `CHANGELOG.md` 冒頭の注釈
- release workflow: `.github/workflows/release.yml`
- コミット規約: `.claude/rules/commit-style.md`
