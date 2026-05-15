#!/usr/bin/env bash
# SessionStart hook: inject project state and current TDD phase into Claude's context.
# Output a JSON object on stdout with hookSpecificOutput.additionalContext.

set -u

PROJECT_DIR="${CLAUDE_PROJECT_DIR:-$(pwd)}"
cd "$PROJECT_DIR" 2>/dev/null || exit 0

BRANCH=$(git branch --show-current 2>/dev/null || echo "?")
RECENT_COMMITS=$(git log -5 --oneline 2>/dev/null || echo "(no commits yet)")

# Estimate current phase from filesystem state.
PHASE="A (skeleton not started)"
if [ -f "$PROJECT_DIR/composer.json" ]; then
    PHASE="A done — composer.json present"
fi
if [ -d "$PROJECT_DIR/vendor" ]; then
    PHASE="B done — vendor/ installed"
fi
if [ -f "$PROJECT_DIR/src/ChatworkServiceProvider.php" ]; then
    PHASE="Implementation phase — ServiceProvider present"
fi

# Detect TDD focus from CLAUDE.md (next Phase target).
NEXT_TDD="POST /rooms/{room_id}/messages (Phase 2 in docs/06-testing/tdd-roadmap.md)"

CTX=$(cat <<EOF
## Project Snapshot

- Branch: ${BRANCH}
- Phase: ${PHASE}
- Next TDD target: ${NEXT_TDD}

### Recent commits
${RECENT_COMMITS}

### Rules to follow
- @.claude/rules/coding-style.md
- @.claude/rules/testing.md
- @.claude/rules/architecture.md
- @.claude/rules/commit-style.md
EOF
)

if command -v jq >/dev/null 2>&1; then
    jq -n --arg ctx "$CTX" \
        '{hookSpecificOutput: {hookEventName: "SessionStart", additionalContext: $ctx}}'
else
    # Fallback: stdout becomes context.
    echo "$CTX"
fi

exit 0
