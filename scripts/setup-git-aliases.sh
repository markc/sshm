#!/bin/bash

# Git Workflow Aliases Setup Script
# Run this script once to configure the streamlined feature branch workflow

echo "Setting up Git workflow aliases..."

# PRE-EDIT alias: git start <branch-name>
git config alias.start '!bash scripts/git-start.sh'
echo "✅ git start - configured"

# POST-EDIT alias: git finish [commit-msg] (optional)
git config alias.finish '!bash scripts/git-finish.sh'
echo "✅ git finish - configured"

# Status check alias: git check
git config alias.check '!git branch -a && echo "--- Merged branches ---" && git branch -r --merged main'
echo "✅ git check - configured"

# Weekly cleanup alias: git cleanup  
git config alias.cleanup '!git branch -r --merged main | grep -v main | cut -d/ -f2- | xargs -r git push origin --delete && git remote prune origin'
echo "✅ git cleanup - configured"

echo ""
echo "🎉 Git workflow aliases successfully configured!"
echo ""
echo "Usage:"
echo "  git start [branch-name]         # Start new feature branch (auto-generates if not provided)"
echo "  # ... make your changes ..."
echo "  git finish [msg]                # Auto-commit, PR, and merge (smart message generation)"
echo "  git check                       # Check repository status"
echo "  git cleanup                     # Clean up old branches (weekly)"
echo ""
echo "For more details, see CLAUDE.md in the project root."