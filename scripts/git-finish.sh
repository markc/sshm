#!/bin/bash
# Git finish script - format, commit, PR, and merge with cleanup

echo "🚀 Starting git finish workflow..."

# Check if we're on a feature branch
CURRENT_BRANCH=$(git branch --show-current)
if [ "$CURRENT_BRANCH" = "main" ]; then
    echo "❌ Error: Cannot run git finish on main branch"
    exit 1
fi

echo "📝 Running Laravel Pint code formatter..."
vendor/bin/pint

# Check for changes after formatting
if [ -z "$(git status --porcelain)" ]; then
    echo "ℹ️  No changes to commit. Repository is clean."
    exit 0
fi

# Get list of changed files for smart commit message generation
CHANGED_FILES=$(git diff --name-only HEAD)
echo "📋 Changed files: $(echo "$CHANGED_FILES" | tr '\n' ' ')"

# Smart commit message generation based on changed files
if [ -n "$1" ]; then
    COMMIT_MSG="$1"
    echo "💬 Using provided commit message: $COMMIT_MSG"
else
    echo "🤖 Generating smart commit message..."
    if echo "$CHANGED_FILES" | grep -q "CLAUDE.md"; then
        COMMIT_MSG="docs: update project documentation"
    elif echo "$CHANGED_FILES" | grep -q "Settings.php"; then
        COMMIT_MSG="feat: update Settings page functionality"
    elif echo "$CHANGED_FILES" | grep -q "PasswordHelper.php"; then
        COMMIT_MSG="fix: improve password hashing compatibility"
    elif echo "$CHANGED_FILES" | grep -q "\.php$"; then
        FIRST_PHP_FILE=$(echo "$CHANGED_FILES" | grep "\.php$" | head -1 | sed 's/.*\///')
        COMMIT_MSG="feat: update $FIRST_PHP_FILE"
    elif echo "$CHANGED_FILES" | grep -q "\.sh$"; then
        COMMIT_MSG="feat: update workflow scripts"
    elif echo "$CHANGED_FILES" | grep -q "\.md$"; then
        COMMIT_MSG="docs: update documentation"
    elif echo "$CHANGED_FILES" | grep -q "\.js\|\.css\|\.vue"; then
        COMMIT_MSG="feat: update frontend assets"
    else
        FIRST_FILE=$(echo "$CHANGED_FILES" | head -1 | sed 's/.*\///')
        COMMIT_MSG="feat: update $FIRST_FILE"
    fi
    echo "💬 Generated commit message: $COMMIT_MSG"
fi

# Stage and commit changes
echo "📦 Staging and committing changes..."
git add .
git commit -m "$COMMIT_MSG

🤖 Generated with [Claude Code](https://claude.ai/code)

Co-Authored-By: Claude <noreply@anthropic.com>"

# Push to remote
echo "⬆️  Pushing to remote..."
git push -u origin "$CURRENT_BRANCH"

# Generate PR title from commit message
PR_TITLE=$(echo "$COMMIT_MSG" | sed 's/^[^:]*: *//' | sed 's/.*/\u&/')

# Generate PR body with file list
PR_BODY="## Summary
$(echo "$COMMIT_MSG" | sed 's/^[^:]*: *//' | sed 's/.*/\u&/')

## Changes
$(echo "$CHANGED_FILES" | head -5 | sed 's/^/- /')

🤖 Generated with [Claude Code](https://claude.ai/code)"

# Create PR
echo "🔄 Creating pull request..."
PR_URL=$(gh pr create --title "$PR_TITLE" --body "$PR_BODY")

# Get PR number from URL
PR_NUMBER=$(echo "$PR_URL" | grep -o '[0-9]*$')

# Enable auto-merge with better error handling
echo "🤝 Enabling auto-merge..."
if gh pr merge "$PR_NUMBER" --auto --squash --delete-branch; then
    echo "✅ Auto-merge enabled successfully"
else
    echo "⚠️  Auto-merge couldn't be enabled. This might be due to:"
    echo "   - Branch protection rules requiring reviews"
    echo "   - No status checks configured"
    echo "   - Repository settings preventing auto-merge"
    echo "   You may need to merge manually at: $PR_URL"
fi

# Wait briefly for GitHub to process
echo "⏳ Waiting for GitHub to process..."
sleep 5

# Switch back to main and pull
echo "🔄 Switching to main and pulling latest..."
git checkout main
git pull

# Clean up the feature branch
echo "🧹 Cleaning up feature branch..."
git branch -d "$CURRENT_BRANCH" 2>/dev/null || echo "ℹ️  Branch cleanup will happen after PR merge"

echo ""
echo "✅ Git finish workflow completed successfully!"
echo "🎉 Your changes have been committed, pushed, and a PR has been created with auto-merge enabled."
echo ""
echo "Next steps:"
echo "  - The PR will auto-merge once checks pass"
echo "  - The feature branch will be automatically deleted"
echo "  - You're now back on main with the latest changes"