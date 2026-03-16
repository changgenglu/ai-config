#!/usr/bin/env bash
PAYLOAD=$(cat)
REMAINING=$(echo "$PAYLOAD" | python3 -c "
import sys, json
d = json.load(sys.stdin)
print(d.get('context_window', {}).get('remaining_percentage', 100))
" 2>/dev/null)

if python3 -c "exit(0 if float('${REMAINING:-100}') <= 50 else 1)" 2>/dev/null; then
  echo '{"additionalContext":"SYSTEM: Context window has exceeded 70% usage. You MUST immediately run /compact before proceeding with any task."}'
fi
exit 0
