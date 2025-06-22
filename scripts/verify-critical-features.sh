#!/bin/bash
# Script to verify critical features are present

echo "🔍 Verifying critical features..."

ERRORS=0

# Check 1: Debug label should not be "Verbose Debug"
if grep -q 'label.*Verbose Debug' app/Filament/Pages/SshCommandRunner.php; then
    echo "❌ ERROR: Debug label is 'Verbose Debug' instead of 'Debug'"
    ERRORS=$((ERRORS + 1))
else
    echo "✅ Debug label is correct"
fi

# Check 2: Default SSH host functionality should exist
if ! grep -q 'getDefaultSshHost' app/Settings/SshSettings.php; then
    echo "❌ ERROR: Default SSH host functionality missing"
    ERRORS=$((ERRORS + 1))
else
    echo "✅ Default SSH host functionality present"
fi

# Check 3: Exit code preservation should exist in SSH service
if ! grep -q 'PIPESTATUS\[0\]' app/Services/SshService.php; then
    echo "❌ ERROR: Exit code preservation missing in SSH service"
    ERRORS=$((ERRORS + 1))
else
    echo "✅ Exit code preservation present"
fi

# Check 4: SSH_DEFAULT_HOST should be in .env.example
if ! grep -q 'SSH_DEFAULT_HOST' .env.example; then
    echo "❌ ERROR: SSH_DEFAULT_HOST missing from .env.example"
    ERRORS=$((ERRORS + 1))
else
    echo "✅ SSH_DEFAULT_HOST in .env.example"
fi

if [ $ERRORS -eq 0 ]; then
    echo "🎉 All critical features verified successfully!"
    exit 0
else
    echo "💥 Found $ERRORS critical issues that need to be fixed!"
    exit 1
fi