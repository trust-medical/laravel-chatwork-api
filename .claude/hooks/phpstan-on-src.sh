#!/usr/bin/env bash
# PostToolUse hook: run phpstan analyse on the single edited src/*.php file.
# Emits findings to Claude via JSON additionalContext. No-op without vendor.

set -u

if ! command -v jq >/dev/null 2>&1; then
    exit 0
fi

INPUT=$(cat)
FILE_PATH=$(echo "$INPUT" | jq -r '.tool_input.file_path // empty')

[ -z "$FILE_PATH" ] && exit 0

case "$FILE_PATH" in
    *src/*.php) ;;
    *) exit 0 ;;
esac

PROJECT_DIR="${CLAUDE_PROJECT_DIR:-$(pwd)}"
PHPSTAN="$PROJECT_DIR/vendor/bin/phpstan"

[ -x "$PHPSTAN" ] || exit 0

# Run phpstan, capture combined output.
RESULT=$("$PHPSTAN" analyse "$FILE_PATH" \
    --configuration="$PROJECT_DIR/phpstan.neon" \
    --no-progress \
    --error-format=raw \
    --memory-limit=512M 2>&1) || true

# If there is non-trivial output, ship it back to Claude as additionalContext.
if [ -n "$RESULT" ] && echo "$RESULT" | grep -q ':'; then
    jq -n --arg ctx "PHPStan findings for ${FILE_PATH}:\n${RESULT}" \
        '{hookSpecificOutput: {hookEventName: "PostToolUse", additionalContext: $ctx}}'
fi

exit 0
