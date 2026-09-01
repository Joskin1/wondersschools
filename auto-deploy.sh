#!/bin/bash
set -euo pipefail

APP_DIR=/var/www/Wonder
LOG_FILE=/home/ubuntu/wonder-auto-deploy.log
LOCK_FILE=/tmp/wonder-auto-deploy.lock
BRANCH=main

exec 9>"$LOCK_FILE"
flock -n 9 || exit 0

cd "$APP_DIR"

git fetch origin "$BRANCH"
LOCAL_COMMIT=$(git rev-parse HEAD)
REMOTE_COMMIT=$(git rev-parse "origin/$BRANCH")

if [ "$LOCAL_COMMIT" = "$REMOTE_COMMIT" ]; then
    exit 0
fi

{
    echo "=== Auto deploy started at $(date -Is) ==="
    echo "Deploying $LOCAL_COMMIT -> $REMOTE_COMMIT"
} >> "$LOG_FILE"

git reset --hard "origin/$BRANCH" >> "$LOG_FILE" 2>&1
chmod +x production-deploy.sh
./production-deploy.sh >> "$LOG_FILE" 2>&1

echo "=== Auto deploy finished at $(date -Is) ===" >> "$LOG_FILE"
