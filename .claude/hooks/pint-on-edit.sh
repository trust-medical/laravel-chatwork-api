#!/usr/bin/env bash
# PostToolUse hook: run laravel/pint on the single edited PHP file.
# No-op when vendor/bin/pint does not exist yet (e.g. before composer install).

set -u

if ! command -v jq >/dev/null 2>&1; then
    exit 0
fi

INPUT=$(cat)
FILE_PATH=$(echo "$INPUT" | jq -r '.tool_input.file_path // empty')

[ -z "$FILE_PATH" ] && exit 0

case "$FILE_PATH" in
    *.php) ;;
    *) exit 0 ;;
esac

PROJECT_DIR="${CLAUDE_PROJECT_DIR:-$(pwd)}"
PINT="$PROJECT_DIR/vendor/bin/pint"

[ -x "$PINT" ] || exit 0

"$PINT" "$FILE_PATH" --no-interaction 2>&1 | tail -20

exit 0
