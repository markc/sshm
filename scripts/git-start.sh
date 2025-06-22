#!/bin/bash
# Git start script - creates new feature branch from latest main

if [ -z "$1" ]; then
    # Auto-generate branch name using username
    BRANCH_NAME="feature/$(whoami)"
    echo "No branch name provided, using default: $BRANCH_NAME"
else
    BRANCH_NAME="$1"
fi

echo "Starting new feature branch: $BRANCH_NAME"
git checkout main
git pull origin main
git checkout -b "$BRANCH_NAME"
echo "✅ Ready to work on branch: $BRANCH_NAME"