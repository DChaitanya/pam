@echo off
REM PAM - initialize Git version control (run on Windows, in the project root)
REM
REM The .gitignore is already created. This script removes any partial .git
REM folder, then initializes a clean repo and makes the baseline commit.

cd /d "%~dp0.."

REM Remove the half-created .git folder left by the sandbox (if present).
if exist ".git" (
  echo Removing existing .git folder...
  rmdir /s /q ".git"
)

git init
git add -A
git commit -m "Baseline: legacy PAM (PHP/MySQLi) before modernization"

echo.
echo Done. Verify with:  git log --oneline  and  git status
