#!/usr/bin/env bash
# PreToolUse hook: warn (do not block) when src/*.php is edited without a corresponding test.
# Reads tool input JSON on stdin, looks for tool_input.file_path, and checks if
# tests/Feature/<relative>Test.php or tests/Unit/<relative>Test.php exists.

set -u

if ! command -v jq >/dev/null 2>&1; then
    exit 0
fi

INPUT=$(cat)
FILE_PATH=$(echo "$INPUT" | jq -r '.tool_input.file_path // empty')

# No file path → nothing to check.
[ -z "$FILE_PATH" ] && exit 0

# Only inspect src/*.php.
case "$FILE_PATH" in
    *src/*.php) ;;
    *) exit 0 ;;
esac

PROJECT_DIR="${CLAUDE_PROJECT_DIR:-$(pwd)}"

# Compute path relative to src/ (e.g. src/Resources/Foo.php → Resources/Foo.php).
REL="${FILE_PATH#"$PROJECT_DIR"/}"
REL="${REL#src/}"
REL_BASE="${REL%.php}"

FEATURE_TEST="$PROJECT_DIR/tests/Feature/${REL_BASE}Test.php"
UNIT_TEST="$PROJECT_DIR/tests/Unit/${REL_BASE}Test.php"

# Facades / generated DTOs may not need direct tests — relax for Facades/.
case "$REL" in
    Facades/*) exit 0 ;;
esac

if [ ! -f "$FEATURE_TEST" ] && [ ! -f "$UNIT_TEST" ]; then
    echo "[TDD-WARN] No test file for ${FILE_PATH}." >&2
    echo "[TDD-WARN]   Expected: tests/Feature/${REL_BASE}Test.php or tests/Unit/${REL_BASE}Test.php" >&2
    echo "[TDD-WARN]   Write a failing test first (Red), then implement (Green)." >&2
fi

exit 0
