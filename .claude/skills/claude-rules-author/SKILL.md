---
name: claude-rules-author
description: Design high-quality .claude/rules/ files by reasoning from first principles. TRIGGER whenever the user wants to create or improve files under .claude/rules/, modularize a bloated CLAUDE.md, make Claude follow path-scoped editing conventions, enforce style/structure/naming/metadata conventions, or capture tacit editing norms into machine-loadable rules. ALSO trigger when the user describes the goal in domain terms — "rules を整備", "コード規約をルール化", "テスト規約を強制", "設定ファイル書式ルール", "API スキーマ規約", "コミット規約", "命名規約" — regardless of file type. Domain-agnostic — works for source code, configuration, infrastructure, schemas, tests, docs, or any text asset. Even when the user's request is vague (e.g. "rules を作って"), this skill begins with a mandatory requirements interview, so vague prompts are safe.
when_to_use: User input may be vague or incomplete. This skill always begins with a Phase 0 requirements interview (target file type, target paths, motivation, existing conventions, placement scope) via AskUserQuestion before any reading or writing. Use this skill rather than ad-hoc rules drafting whenever quality consistency across sessions matters.
context: fork
agent: general-purpose
effort: high
allowed-tools:
  - AskUserQuestion
  - Read
  - Write
  - Edit
  - Glob
  - Grep
  - Bash
---

# Claude Code ルールファイル設計タスク

`.claude/rules/` 配下に高品質な rules を設計するタスクを実行する。本ファイルは「タスク指示書」として読む。サブエージェントとして起動された時、以下の 6 フェーズを順に実行し、最後にユーザーへ完成物を引き渡す。

## タスク全体像

```
Phase 0 → Phase 1 → Phase 2 → Phase 3 → Phase 4 → Phase 5 → 引き渡し
要件確認   実装Read   違反引用   設計判断   構成決定   執筆
```

- Phase 0 と Phase 3 はどちらも `AskUserQuestion` を使うが、性質が異なる:
  - **Phase 0 = What**（何を作るか、要件レベル）
  - **Phase 3 = How**（どう作るか、設計判断レベル）
- 各フェーズは前フェーズの出力を入力として使う。順序を飛ばさない。
- 公式仕様: <https://code.claude.com/docs/en/memory#organize-rules-with-claude/rules/>

判断に迷ったら、本ファイル後半の「参照基準」（§7〜§12: 7 性質、書式、コンテンツ原則、検証、アンチパターン、メンテナンス）に立ち戻る。

---

## Phase 0 — 要件確認ヒアリング（必須）

ユーザーが「rules を作って」「ルール化したい」のように曖昧なプロンプトで起動した場合でも、必ず本フェーズを通す。すでに情報が与えられていても確認のために通す（揃っている項目は省略してよいが、最低 1 回の AskUserQuestion 呼び出しでスコープを固める）。

`AskUserQuestion` を使い、以下 5 項目を確認する。1 回の呼び出しに 4 件まで束ねられるので、Q1〜Q4 を 1 回目、Q5 を 2 回目（または Phase 4 に繰り延べ）に分ける。

### Q1. 何を rules 化したいか例（対象種別）

| 選択肢 | 説明 |
|---|---|
| A) ソースコード | 言語/フレームワークを併せて聞く |
| B) 設定ファイル | 何の設定か（CI、Lint、build、infra など） |
| C) ドキュメント | Markdown、ADR、README、API ドキュメントなど |
| D) テスト | フレームワーク、規約、命名 |
| E) インフラ・スキーマ | IaC、k8s manifest、OpenAPI、proto など |

### Q2. 対象ディレクトリ・グロブパターン

自由記述（推奨例として `src/api/**/*.ts` `tests/**/*.spec.py` 等を提示する）。プロジェクトルートからの相対パス。

### Q3. rules 化の動機例

| 選択肢 | 説明 |
|---|---|
| A) 同じ指摘の反復 | レビューで毎回同じ指摘をしている |
| B) 暗黙知の形式化 | チームの「いつもの書き方」が口伝で残っている |
| C) CLAUDE.md 肥大化 | 起動時ロードを分離したい |
| D) 既存規範の機械強制 | README/規約に書かれているが守られない |
| E) その他 | 自由記述 |

### Q4. 既存規範の有無

| 選択肢 | 説明 |
|---|---|
| A) 明文化済み | README / CONTRIBUTING / 規約ドキュメント等にある |
| B) 部分的に機械強制中 | lint / formatter / pre-commit hook 等 |
| C) なし | これから作る |
| D) 不明 | Phase 1 で実装を Read して確認する |

### Q5. 配置スコープ

| 選択肢 | 説明 |
|---|---|
| A) プロジェクト級（推奨） | `.claude/rules/<file>.md`、リポジトリ単位 |
| B) ユーザー級 | `~/.claude/rules/<file>.md`、別プロジェクトでも有効。判断基準: 「別プロジェクトでも有効か」が Yes なら B、No なら A |

### Phase 0 出力

Phase 0 終了時、次の項目を内部メモとして整理する:

```
- 対象種別:
- 対象パス/グロブ:
- 動機:
- 既存規範:
- 配置スコープ:
```

これを Phase 1 以降の前提として持ち越す。

---

## Phase 1 — 対象実装を Read

ユーザーから取得した対象パス配下の実ファイル群を読まずに書き始めない。実例を見ずに作った rules は抽象的になり、違反検出能力が落ちる。

1. 対象パス配下から **種類が異なる代表ファイルを 5〜10 個** 選び、全文 Read する。`Glob` でファイル一覧、必要に応じて `Grep` で代表ファイルを絞る。
2. プロジェクトに既存規範が宣言されていないか、次のいずれかを確認: `README.md` / `CONTRIBUTING.md` / 規約ドキュメント / `CLAUDE.md` / `.editorconfig` / lint 設定（`.eslintrc*`、`pyproject.toml` `[tool.ruff]` 等）/ formatter 設定。
3. 既存規範がある場合、新規 rules はそれを **機械的に強制する位置付け** にする。新方針を矛盾させない。
4. ファイル冒頭の構造、命名、章立て / 関数構造、用語の表記、参照記法を把握する。

合格基準: 対象ディレクトリに何があり、どう書かれており、既存規範がどこにあるかを口頭で説明できる状態。

---

## Phase 2 — 違反パターンを引用化

抽象的な rules だけでは執行力が弱い。具体的な違反引用を併記して初めて rules として機能する（§7 の Anchored to Reality）。

1. Phase 1 で読んだファイル群から、既存規範または一般的ベストプラクティスから逸脱する箇所を抽出する。
2. 各違反を `<ファイルパス>:<行番号> — <引用テキスト> — <違反内容>` の形式で記録する。
3. 同種違反が複数ファイルにある場合、`Grep` （`grep -rn '<パターン>' <対象>`）で網羅検出する。
4. 引用は **rules 本文末尾「違反例の代表」セクションにそのまま貼り付ける** 想定で保存する。

違反が見つからない場合、想定アンチパターンを「想定違反」として記述する。実存しない違反でも執行基準として機能する。

---

## Phase 3 — 設計判断ヒアリング

rules の粒度・厳密さ・例外の扱いは独断で決めない。プロジェクト固有のコンテキストを持つのはユーザーだけ。

`AskUserQuestion` で以下 4 項目を確認する（1 回の呼び出しで束ねる）。

| 項目 | 選択肢 |
|---|---|
| ファイル粒度 | A) コア 1 本＋種別補足（推奨） / B) 1 ファイル集約 / C) 種別ごと完全分割 |
| 既存違反の扱い | A) rules 策定と分離して別タスクで清掃（推奨） / B) 同 PR で同時清掃 / C) 違反を例外として許容 |
| 厳密さの解釈 | A) 厳密強制 / B) 推奨だが個別判断可 / C) ベストエフォート |
| 例外の扱い | A) 例外を明示列挙（推奨） / B) 例外は禁止 / C) Claude の判断に委ねる |

質問の作り方:

- 各質問は 3〜4 択。推奨案を最初に置き「（推奨）」を付ける。
- 推奨案の説明には、Phase 0〜2 で得たプロジェクト固有の根拠を 1〜2 文で書く。
- 選ぶことで何が得られるかを user が理解できる説明にする。

---

## Phase 4 — ファイル構成決定

Phase 0 配置スコープと Phase 3 ファイル粒度の回答に基づき、ファイル数とそれぞれの paths スコープを確定する。下記「構成パターン §6」を参照。

---

## Phase 5 — 各 rules ファイルを Write

「ルールファイル書式 §8」と「コンテンツ原則 §9」に従って Write で書く。書き終わったら `Bash` で `wc -l <ファイル>` を実行し、行数を確認する。200 行を超えるなら分割を検討する。

---

## 引き渡し

ユーザーへ次を提示して完了する:

1. **作成ファイル一覧** — パスと行数の表
2. **強制される主要 rules** — 5〜10 個の箇条書きサマリ
3. **次のステップ候補**:
   - 動作確認: 対象ファイルを Read し `/memory` で該当 rules ロードを確認
   - 既存違反の一括清掃（別タスクで起票）
   - コミット（ユーザー承認後、`git add .claude/rules/`）

ユーザー承認なしにコミットしない。

---

# 参照基準（タスク実行中に都度参照する）

以下は Phase 0〜5 のタスク実行中に判断基準として参照するセクション。常時すべてを読む必要はなく、該当する判断で必要になった時に開く。

## 6. `.claude/rules/` のメカニズム

| 挙動 | 内容 |
|---|---|
| 発見 | `.claude/rules/*.md` を再帰的に発見。サブディレクトリ可。 |
| `paths` なし | 起動時にロード。全セッションに適用。 |
| `paths` あり | グロブが一致するファイルを Read した時にコンテキストへロード。 |
| サイズ目標 | 1 ファイル 200 行以下が公式推奨。 |
| 衝突時 | 矛盾は Claude が任意に選ぶ。矛盾は書き手が排除する。 |
| ユーザー級 | `~/.claude/rules/` も同様にロード（プロジェクト級より優先度低）。 |

コンテキスト効率の原則: 「すべての編集セッションで必要」と確信できる rules だけ paths なしにする。迷ったら paths を付ける。

## 7. 良い rules の本質（7 性質）

1. **Verifiable** — 検証可能性: 守られているか判定できる具体性
2. **Why-driven** — 理由駆動: 命令ではなく根拠を提示
3. **Path-scoped** — スコープ局在性: 必要なセッションだけにロード
4. **Non-conflicting** — 非矛盾性: rules 同士で矛盾しない
5. **Atomic** — 原子性: 1 ファイル 1 トピック
6. **Anchored to Reality** — 現実への係留: 実例・違反例で動かす
7. **Token-economic** — トークン経済性: 起動時ロード最小限

判断に迷ったら、この 7 性質に立ち返って優先度を決める。

## 8. 構成パターン

### パターン A: コア 1 本 + 種別補足（推奨デフォルト）

```
.claude/rules/
├── <scope>-style.md       # 共通: paths は対象ツリー全体
├── <scope>-<aspect-1>.md  # 種別補足: 種別固有のサブパス
└── <scope>-<aspect-2>.md
```

種別補足は共通 rules と重複させない。種別補足を編集すると共通 rules も同時にロードされる。

### パターン B: 1 ファイル集約

```
.claude/rules/
└── <scope>-style.md
```

総量 100 行以内、種別 1〜2 のみ、対象ツリーが小さい場合に選ぶ。

### パターン C: 種別ごと完全分割

```
.claude/rules/
├── <aspect-1>.md
└── <aspect-2>.md
```

横断項目がほぼ存在しない場合のみ。実例は稀。

### 判断フロー

```
共通の規範がある?
  ├─ Yes, 種別が複数 → パターン A
  ├─ Yes, 種別が 1-2 のみ → パターン B
  └─ No → パターン C
```

## 9. ルールファイル書式

### 9.1 Frontmatter

`paths` 付き（推奨デフォルト）:

```yaml
---
paths:
  - "<glob-pattern>"
---
```

`paths` なし（起動時ロード、必要最小限）: frontmatter を省略する。

### 9.2 標準セクション構成

```markdown
---
paths: ...
---

# <rules 名> 編集ルール

<2〜3 文のリード文>

## 1. <大項目 1>
<内容>

## 2. <大項目 2>
<内容>

...

## N. 違反例の代表

参考までに、本 rules 策定時点で <対象> に存在した代表的違反:

- `<引用 1>` — <違反内容>
- `<引用 2>` — <違反内容>

新規編集時にこれらを書き込まない。既存ファイル編集時に発見した場合は同 PR 内で削除する。
```

セクションは数字で振り（`## 1.` `## 2.`）、他 rules から `§3` のように参照可能にする。大項目は 5〜12 個程度。

### 9.3 「違反例の代表」セクション

rules の執行力を倍増させる最重要セクション。Phase 2 で収集した違反引用を末尾に貼る。

含める情報:
1. 違反テキストの直接引用（バッククォートで囲む）
2. 何が違反なのかを 1 行で説明
3. 修正方針（削除 / リライト / 別ファイルへ移動）
4. 締めの文「新規編集時にこれらを書き込まない。既存ファイル編集時に発見した場合は同 PR 内で削除する。」

## 10. コンテンツ原則

§7 の本質 7 性質を実務に落としたもの。

1. **「なぜ」を必ず書く** — 命令ではなく理由を提示すれば edge case でも判断できる
2. **違反引用を必ず併記** — Phase 2 の引用を本文または末尾に
3. **SSOT を明示** — 閾値・仕様の単一典拠を 1 か所に。重複定義禁止
4. **ALWAYS / NEVER の濫用回避** — 本当に絶対のものだけ強調
5. **主観表現を避ける** — 「と思われる」は書かない、断定または事実記述
6. **例外を明示** — 「<X> 禁止。ただし <条件> は対象外」と明示
7. **テンプレート断片を埋め込む** — 具体的な構造を fenced code block で

## 11. 検証チェックリスト

rules 作成後、次を実行する。

```bash
wc -l .claude/rules/*.md
ls -la .claude/rules/
```

確認項目:

- [ ] 各ファイル 200 行以下
- [ ] paths frontmatter のグロブが正しい
- [ ] 既存規範と矛盾しない
- [ ] rules 同士で矛盾しない
- [ ] 違反例の代表セクションが各ファイルにある
- [ ] paths なしのファイルが本当に必要最小限
- [ ] Why-driven、SSOT 明示が守られている

## 12. アンチパターン

- **AP-1**: 全ファイルに paths を付ける / 1 つも付けない
- **AP-2**: 複数トピックを 1 ファイルに詰め込む
- **AP-3**: 本文に過去経緯・将来計画を書く（git で追跡する）
- **AP-4**: 既存違反清掃と rules 策定を同 PR に混ぜる
- **AP-5**: 個人名・固有の組織内ロール名・固有の連絡先を本文に直書き
- **AP-6**: 公式仕様を確認せずに書く
- **AP-7**: rules を CLAUDE.md に重複させる（CLAUDE.md は参照リンクのみ）
- **AP-8**: 抽象論だけで違反引用を省く

## 13. メンテナンスサイクル

rules は書いたら終わりではない。

- **違反検出 → 更新**: 新しい違反パターンを発見したら違反例セクションに追記
- **例外増加時の原則再評価**: 例外が 3 件を超えたら原則そのものを見直す
- **矛盾解決順序**: ①既存 rules 更新で整合 → ②paths でスコープ分離 → ③より具体的な側を優先と明記
- **削除基準**: 対象ファイル群消滅 / 違反例が 1 件も該当しない / 前提仕様が変更
- **定期レビュー**: 四半期に 1 回、`.claude/rules/*.md` を通読

## 14. プロジェクト固有 vs ユーザー固有

判断基準: 「この rule が別プロジェクトでも有効か」が Yes ならユーザー級（`~/.claude/rules/`）、No ならプロジェクト級（`.claude/rules/`）。迷ったらプロジェクト級。

ユーザー級では、プロジェクト固有の SSOT パス、ロール名、lint 設定を含めない。

---

## 自己適用テスト

本ファイル自身が本ファイルで定めた基準を満たしているかを書き手は常に意識する。

- §7 の 7 性質: 本 SKILL.md は Verifiable / Why-driven / Atomic / Non-conflicting を満たしているか
- §10 コンテンツ原則: 「なぜ」を書き、SSOT を意識しているか
- §12 アンチパターン: 自分が書いた rules が AP-1〜AP-8 のどれかに該当していないか

「rules を書くための rules」が自分の基準で自分自身を評価できる状態が、本スキルが本質駆動である証になる。
