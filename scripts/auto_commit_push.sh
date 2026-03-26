#!/usr/bin/env bash
set -euo pipefail

REPO_ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$REPO_ROOT"

INTERVAL_SECONDS="${INTERVAL_SECONDS:-10}"
MODE="${1:-loop}"
HEARTBEAT_FILE="${HEARTBEAT_FILE:-scripts/.auto_commit_heartbeat.txt}"

SCOPES=(
  auth
  admin
  players
  coaches
  teams
  matches
  standings
  news
  profile
  ui
)

build_commit_message() {
  local scope="$1"
  echo "feat(${scope}): update ${scope} functionality"
}

scope_for_path() {
  local path="$1"

  case "$path" in
    auth/*) echo "auth" ;;
    admin/*) echo "admin" ;;
    players/*|player/*) echo "players" ;;
    coaches/*|coach/*) echo "coaches" ;;
    teams/*) echo "teams" ;;
    matches/*) echo "matches" ;;
    standings/*) echo "standings" ;;
    news/*) echo "news" ;;
    assets/*|app/views/*) echo "ui" ;;
    profile/*) echo "profile" ;;
    weather/*) echo "weather" ;;
    scripts/*) echo "scripts" ;;
    *) echo "core" ;;
  esac
}

collect_changed_files() {
  git status --porcelain | awk '{print substr($0,4)}'
}

commit_scope_changes() {
  local scope="$1"
  local branch="$2"
  shift 2

  local files=("$@")
  local scope_files=()
  local file current_scope message

  for file in "${files[@]}"; do
    current_scope="$(scope_for_path "$file")"
    if [[ "$current_scope" == "$scope" ]]; then
      scope_files+=("$file")
    fi
  done

  if [[ ${#scope_files[@]} -eq 0 ]]; then
    return 0
  fi

  git add -A -- "${scope_files[@]}"

  if git diff --cached --quiet; then
    return 0
  fi

  message="$(build_commit_message "$scope")"
  git commit -m "$message"
  git push origin "$branch"

  echo "[$(date '+%Y-%m-%d %H:%M:%S')] Pushed [$scope]: $message"
}

ensure_git_repo() {
  if ! git rev-parse --git-dir >/dev/null 2>&1; then
    echo "This script must run inside a git repository." >&2
    exit 1
  fi

  if ! git remote get-url origin >/dev/null 2>&1; then
    echo "Remote origin is not configured." >&2
    exit 1
  fi
  
}

  toggle_heartbeat_change() {
    mkdir -p "$(dirname "$HEARTBEAT_FILE")"

    if [[ ! -f "$HEARTBEAT_FILE" ]]; then
      printf 'auto-commit-heartbeat' > "$HEARTBEAT_FILE"
    fi

    if [[ -s "$HEARTBEAT_FILE" ]] && [[ "$(tail -c 1 "$HEARTBEAT_FILE" 2>/dev/null || true)" == $'\n' ]]; then
      # Remove trailing newline.
      local content
      content="$(cat "$HEARTBEAT_FILE")"
      printf '%s' "$content" > "$HEARTBEAT_FILE"
    else
      # Add trailing newline.
      printf '\n' >> "$HEARTBEAT_FILE"
    fi
  }

commit_once() {
  local branch
  branch="$(git rev-parse --abbrev-ref HEAD)"

  toggle_heartbeat_change
  git reset >/dev/null

  local changed_files=()
  mapfile -t changed_files < <(collect_changed_files)

  if [[ ${#changed_files[@]} -eq 0 ]]; then
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] No changes to commit."
    return 0
  fi

  local scope
  local -A seen_scopes=()
  local ordered_scopes=("${SCOPES[@]}" scripts weather core)

  for file in "${changed_files[@]}"; do
    scope="$(scope_for_path "$file")"
    seen_scopes["$scope"]=1
  done

  for scope in "${ordered_scopes[@]}"; do
    if [[ -n "${seen_scopes[$scope]:-}" ]]; then
      commit_scope_changes "$scope" "$branch" "${changed_files[@]}"
      unset 'seen_scopes[$scope]'
    fi
  done

  for scope in "${!seen_scopes[@]}"; do
    commit_scope_changes "$scope" "$branch" "${changed_files[@]}"
  done

  if ! git diff --cached --quiet; then
    git reset >/dev/null
  fi
}

run_loop() {
  echo "Starting auto commit loop with interval ${INTERVAL_SECONDS}s"
  while true; do
    if ! commit_once; then
      echo "[$(date '+%Y-%m-%d %H:%M:%S')] Commit/push failed. Retrying after interval." >&2
    fi
    sleep "$INTERVAL_SECONDS"
  done
}

ensure_git_repo

case "$MODE" in
  once)
    commit_once
    ;;
  loop)
    run_loop
    ;;
  *)
    echo "Usage: scripts/auto_commit_push.sh [once|loop]" >&2
    exit 1
    ;;
esac
