#!/usr/bin/env bash
# PAM - initialize Git version control (run on a normal filesystem, project root)
#
# The .gitignore is already created. This removes any partial .git folder left
# by the sandbox, then initializes a clean repo and makes the baseline commit.
set -euo pipefail
cd "$(dirname "$0")/.."

if [ -d .git ]; then
  echo "Removing existing .git folder..."
  rm -rf .git
fi

git init
git add -A
git commit -m "Baseline: legacy PAM (PHP/MySQLi) before modernization"

echo
echo "Done. Verify with: git log --oneline && git status"
