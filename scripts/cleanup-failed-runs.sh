#!/bin/bash

# cleanup-failed-runs.sh - Delete failed GitHub Actions runs

set -e

echo "🧹 Cleaning up failed GitHub Actions runs..."

# Check if gh CLI is available
if ! command -v gh &> /dev/null; then
    echo "❌ GitHub CLI (gh) is not installed. Please install it first."
    exit 1
fi

# Check if authenticated
if ! gh auth status &> /dev/null; then
    echo "❌ Not authenticated with GitHub. Run 'gh auth login' first."
    exit 1
fi

echo "📊 Current failed runs:"
FAILED_COUNT=$(gh run list --status failure --limit 100 --json databaseId | jq '. | length')
echo "Found $FAILED_COUNT failed runs"

if [ "$FAILED_COUNT" -eq 0 ]; then
    echo "✅ No failed runs to delete!"
    exit 0
fi

# Show recent failed runs
echo ""
echo "Recent failed runs:"
gh run list --status failure --limit 10

echo ""
read -p "Do you want to delete ALL failed runs? (y/N): " -n 1 -r
echo ""

if [[ $REPLY =~ ^[Yy]$ ]]; then
    echo "🗑️  Deleting failed runs..."
    
    # Get all failed run IDs and delete them
    gh run list --status failure --limit 100 --json databaseId --jq '.[].databaseId' | while read run_id; do
        echo "Deleting run $run_id..."
        gh run delete "$run_id" --yes
        sleep 0.5  # Be nice to the API
    done
    
    echo "✅ Cleanup completed!"
    
    # Show remaining runs
    echo ""
    echo "📊 Remaining failed runs:"
    REMAINING_COUNT=$(gh run list --status failure --limit 100 --json databaseId | jq '. | length')
    echo "Remaining failed runs: $REMAINING_COUNT"
    
else
    echo "❌ Cleanup cancelled."
fi