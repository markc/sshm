#!/bin/bash

# delete-failed-runs.sh - Delete all failed GitHub Actions runs

echo "🧹 Deleting all failed GitHub Actions runs..."

# Get all failed run IDs
FAILED_RUNS=$(gh run list --status failure --json databaseId --jq '.[].databaseId')

if [ -z "$FAILED_RUNS" ]; then
    echo "✅ No failed runs found!"
    exit 0
fi

COUNT=$(echo "$FAILED_RUNS" | wc -l)
echo "Found $COUNT failed runs to delete"

# Delete each run (this will prompt for confirmation)
echo "$FAILED_RUNS" | while read run_id; do
    if [ -n "$run_id" ]; then
        echo "Deleting run $run_id..."
        echo "y" | gh run delete "$run_id" 2>/dev/null || true
        sleep 0.2  # Be nice to the API
    fi
done

echo "✅ Cleanup completed!"

# Show remaining failed runs
REMAINING=$(gh run list --status failure --json databaseId --jq '.[].databaseId' | wc -l)
echo "Remaining failed runs: $REMAINING"