#!/bin/bash
# Worker auto-restart daemon

cd /var/www/html

while true; do
    echo "[$(date)] Starting worker.php..." >> tmp/worker.log
    php worker.php 2>&1 | tee -a tmp/worker.log
    EXIT_CODE=$?
    echo "[$(date)] Worker exited with code $EXIT_CODE. Restarting in 5 seconds..." >> tmp/worker.log
    sleep 5
done
