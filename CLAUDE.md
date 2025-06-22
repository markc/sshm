# SSH Manager (SSHM) - Claude AI Development Instructions

## Documentation Structure

**IMPORTANT**: All documentation must follow this structure:

- **End-user documentation**: Add all how-to-use guides, tutorials, and user manuals to the `docs/` folder
- **Developer documentation**: Add all how-to-build guides, technical specifications, and development plans to the `plan/` folder

## Git Workflow Requirements

**⚠️ CRITICAL: NEVER USE DIRECT GIT COMMANDS ⚠️**

**MANDATORY**: All commits to this repository MUST go through the git aliases workflow. **NEVER** use direct git commands like `git add`, `git commit`, `git push`, etc.

### Required Workflow:

1. **Setup aliases** (run once): `@scripts/setup-git-aliases.sh`
2. **Before making any changes**: `git start [branch-name]`
3. **After completing changes**: `git finish [commit-message]`
4. **After PR merge**: Always merge to main using `gh` CLI, then checkout main locally

### Why This Workflow is Mandatory:

- **Automatic code formatting**: `git finish` runs Laravel Pint before committing
- **Consistent branching**: Ensures proper feature branch creation from latest main
- **Automated PR creation**: Creates pull requests with proper titles and descriptions
- **Auto-merge setup**: Enables automatic merging when CI passes
- **Branch cleanup**: Automatically cleans up feature branches after merge
- **Consistent commit messages**: Uses standardized commit message format with Claude Code attribution

### Git Aliases Available
- `git start [branch-name]` - Start new feature branch (auto-generates if not provided)
- `git finish [msg]` - Auto-commit, create PR, and prepare for merge (smart message generation)
- `git check` - Check repository status and merged branches
- `git cleanup` - Clean up old merged branches (run weekly)

### What `git finish` Does Automatically:
1. Runs `./vendor/bin/pint` to fix code formatting
2. Stages all changes with `git add .`
3. Creates commit with proper message format and Claude Code attribution
4. Pushes to feature branch with `git push -u origin [branch]`
5. Creates GitHub PR with auto-generated title and description
6. Enables auto-merge with squash and branch deletion
7. Switches back to main and pulls latest changes
8. Cleans up local feature branch

**NEVER bypass this workflow** - it ensures code quality, consistent formatting, and proper CI integration.

### CI/CD Pipeline
The repository includes a single CI runner (`.github/workflows/ci.yml`) that:
- Runs on PHP 8.4 only
- Executes Laravel Pint code formatting checks
- Runs the complete Pest test suite (149 tests, 482 assertions)
- Triggers only on merge to main branch
- Uploads artifacts on failure

**CI runs once per merge to main to ensure production quality.**

## Original Project Requirements

Please create a Laravel 12 project integrated with Filament 4.0.

The core functionality should be to execute SSH commands on a remote server from within the Filament admin panel.

Here are the specific requirements:

1.  **Project Setup:**

    - Initialize a standard Laravel 12 project.
    - Install Filament 4.0 and configure it for basic usage (e.g., create an admin user).
    - Install the `spatie/ssh` package.

2.  **Filament Resource or Page:**

    - Create a new Filament Page (not a Resource) named `SshCommandRunner`. This page will contain the UI for running commands.
    - This page should be accessible within the Filament admin panel's navigation.

3.  **User Interface on the `SshCommandRunner` Page:**

    - Include a `<textarea>` where the user can input the SSH command(s) to be executed. This textarea should have a label like "Enter SSH Command(s)".
    - Include a `<button type="submit">` with the text "Run Command".
    - Include a hidden `<div id="command-output-alert">` above the textarea. This `div` will be used to display the output of the executed command. It should be initially hidden.

4.  **Server Configuration:**

    - Provide a mechanism to configure the remote server details (host, user, private key path or password, port).
    - Suggest using environment variables (`.env`) for storing sensitive information like the private key path or password.
    - The page should ideally read these configuration values from the environment variables.

5.  **Backend Logic (in the Filament Page class):**

    - Implement a method (e.g., `runSshCommand`) that is triggered when the submit button is clicked.
    - Inside this method:
        - Get the command(s) from the textarea input.
        - Use `spatie/ssh` to connect to the remote server using the configured credentials.
        - Execute the command(s) entered by the user.
        - Capture the output and any errors from the SSH execution.
        - Handle potential SSH connection errors or command execution failures.

6.  **Frontend (JavaScript or Livewire) Interaction:**

    - When the submit button is clicked, send the command(s) to the backend method.
    - Upon receiving the results (output or error) from the backend, update the content of the hidden `div` (`#command-output-alert`) with the results.
    - Make the `div` visible to display the output.
    - Consider styling the alert `div` to visually distinguish between success and error output (e.g., using Tailwind classes if Filament uses them).

7.  **Security Considerations:**

    - Emphasize the security risks of allowing arbitrary command execution.
    - Suggest potential mitigations (though the prompt doesn't require implementing complex security: this is a basic example):
        - Strict authorization: Only allow specific users access to this page.
        - Input sanitization (though for SSH commands, this is tricky and often involves limiting allowed commands rather than sanitizing user input directly).
        - Running commands with a user with limited privileges on the remote server.

8.  **Code Structure:**
    - Organize the code logically within the Laravel and Filament structure.
    - Provide clear comments explaining the different parts of the code.

**Expected Output from Claude:**

The expected output is the necessary code files and instructions to set up and run the project:

- Composer commands for installation.
- Instructions for setting up the `.env` file with SSH credentials.
- The code for the Filament Page class (`SshCommandRunner.php`).
- The Blade view file (if a separate view is needed for the form and output display within the Filament Page).
- Any necessary JavaScript or Livewire code for frontend interaction (if not handled purely by Livewire's form submission).
- Basic instructions on how to access the page in Filament.

** Use the sshm.sh bash script as a guide to create the sshm Laravel+Filament project**

- Implement all bash functions in Filament
- Provide an admin with tables and views to manage the SSH contents of the ~/.ssh folder

This prompt should give the code-generating AI a clear understanding of what you need to build a functional example project. Remember to review and adapt the generated code as needed for your specific environment and security requirements.

## Implementation Summary

**IMPORTANT: This project uses Filament v4.0** - The latest major version with breaking changes from v3.x. Key differences include:
- Form methods use `Form` instead of `Schema` 
- Required PHP 8.2+ and Laravel v11.28+
- Files are private by default
- Table filters are deferred by default
- Layout components consume one grid column by default

The SSH Manager (SSHM) project has been successfully implemented with the following components:

1. **Models and Migrations**:
   - Created SshHost model for storing SSH host configurations
   - Created SshKey model for managing SSH keys
   - Implemented migrations for both models

2. **Filament Pages and Resources**:
   - Created SshCommandRunner page for executing SSH commands
   - Created SshHostResource for managing SSH hosts
   - Created SshKeyResource for managing SSH keys

3. **SSH Service**:
   - Implemented a service class for handling SSH operations
   - Added methods for initializing SSH directory structure
   - Added methods for managing permissions
   - Added methods for controlling the SSH service
   - Added methods for syncing database records to filesystem

4. **Environment Configuration**:
   - Added configuration options for SSH settings in services.php
   - Updated .env.example with SSH configuration options

5. **Documentation**:
   - Created comprehensive README.md with setup and usage instructions

The application provides a web interface for managing SSH hosts and keys, as well as executing SSH commands on remote servers. It also includes features for initializing the SSH directory structure, managing permissions, and controlling the SSH service.

To start using the application, follow the setup instructions in the README.md file. Once installed, you can access the Filament admin panel at http://localhost:8000/admin.

## Home Directory Customization

The SSHM project supports the following ways to customize the home directory for different users:

1. **Using the Settings Page (Recommended)**:
   - A dedicated "SSH Settings" page in the Filament admin panel
   - Allows administrators to configure:
     - SSH Home Directory (e.g., `/home/markc`)
     - Default SSH User
     - Default SSH Port
     - Default SSH Key Type
     - Strict Host Key Checking option
   - Changes made here are persistent and stored in the database
   - These settings override the environment variables

2. **Using Environment Variables**:
   - The `.env` file can be used to set default values
   - These act as fallbacks if no settings are saved in the database
   - Format:
     ```
     SSH_HOME_DIR="/home/markc"
     SSH_DEFAULT_USER="root"
     SSH_DEFAULT_PORT=22
     SSH_DEFAULT_KEY_TYPE="ed25519"
     SSH_STRICT_HOST_CHECKING=false
     ```

3. **For Per-User Customization**:
   - The current implementation uses one set of settings for all application users
   - For true multi-user support, you would need to:
     1. Associate settings with specific application users
     2. Create a settings table with a user_id column
     3. Retrieve settings based on the authenticated user
     4. Add user management if needed

Using the settings page is the most user-friendly approach, as it doesn't require editing files or restarting the application.

After changing the home directory, you may need to reinitialize the SSH directory structure from the SSH Hosts page.

## UI Improvements

The following UI improvements have been implemented:

1. **Collapsible Sidebar**: The sidebar is configured to be collapsible on desktop using `sidebarCollapsibleOnDesktop()` in the AdminPanelProvider. This provides more horizontal space for the content area, especially useful when running SSH commands or viewing command output.

2. **Compact Navigation Labels**: Navigation labels have been shortened (e.g., "SSH Commands" instead of "SSH Command Runner") to improve sidebar readability and usability.

3. **Optimized Page Actions**: On list pages, the "Create" button is positioned on the right side, while all other actions are grouped into a dropdown menu to its left. This provides a clean, organized interface that prioritizes the most common action (creating new items) while keeping other actions accessible but uncluttered.

These UI improvements should be maintained in future rebuilds of the project to ensure consistent user experience.

## Important Filament API Notes

1. **Table Refreshing**: Use `$this->resetTable()` method to refresh tables after data changes, not `refresh()` or `refreshTable()` which do not exist in Filament v3.

## Dashboard Customizations

The following dashboard improvements have been implemented:

1. **Security Notes Widget**: Security warnings have been moved from the SSH Commands page to the dashboard as a dedicated widget. The widget displays color-coded security notes with icons:
   - Amber alert for command execution warnings
   - Blue note for SSH security best practices
   - Green tip for user privilege recommendations
   - Purple info about logging considerations

2. **SSH Statistics Widget**: A new stats overview widget shows:
   - Total SSH hosts count with active/inactive status badges
   - Total SSH keys count with active/inactive status badges
   - Mini charts and appropriate icons for visual appeal

3. **Combined Stats Widget**: The SshStatsWidget has been enhanced to include all three stats in a single row:
   - System Versions (left): Laravel, Filament, and Spatie SSH versions
   - SSH Hosts (center): Total hosts with active/inactive breakdown and chart
   - SSH Keys (right): Total keys with active/inactive breakdown and chart

4. **Widget Organization**: Widgets are ordered as:
   - Account Widget (position 1)
   - SSH Stats Widget (position 2, contains all three stats in one row)
   - Security Notes Widget (position 3, full width)
   - Filament Info Widget (position 4)

   All three stats (System Versions, SSH Hosts, SSH Keys) are now displayed in a single widget row with equal 1/3 width each.

These widgets provide immediate visibility into system status and security considerations when users access the dashboard.

## SSH Command Runner Enhancements

The following major enhancements have been implemented for the SSH Command Runner page:

### 1. **Real-Time Command Output Streaming**
- SSH commands now stream output in real-time instead of waiting for completion
- Live output display with auto-scrolling in a fixed-height terminal-style area
- Visual indicators showing "Command Running..." with spinning animation
- Immediate feedback as commands execute on remote servers

### 2. **Advanced Layout Redesign**
The SSH Command Runner page has been completely redesigned with a sophisticated horizontal layout:

**Left Side (50% width):**
- Large command textarea for entering SSH commands
- 8 rows height with disabled resize for consistent layout
- Clean, spacious input area for complex multi-line commands

**Right Side (50% width):**
- **Top Row**: SSH Host selector and Run Command button side-by-side
- **Middle Section**: Custom connection fields (when using custom connection mode)
- **Bottom Section**: Debug and execution options

### 3. **Verbose Debug System**
- **Debug Toggle**: Enable/disable detailed SSH connection debugging
- **Hidden Debug Area**: Appears only when debug mode is enabled
- **Real-Time Debug Output**: Terminal-style display with green text on dark background
- **Comprehensive Debug Information**:
  - Connection details (host, port, user, identity file)
  - Setup progress and configuration steps
  - Command execution status and timing
  - Results summary with exit codes and output lengths
  - Full exception details for troubleshooting

### 4. **Bash Execution Mode**
- **"Use bash" Toggle**: Option to wrap commands in interactive bash
- **Command Wrapping**: Uses `bash -ci 'command'` for enhanced compatibility
- **Environment Access**: Loads user's `.bashrc`, aliases, and functions
- **Smart Integration**: Works with both normal and debug modes

### 5. **UI/UX Improvements**
- **Balanced 50/50 Layout**: Equal space for command input and controls
- **Inline Controls**: SSH host selector and run button on same line
- **Compact Toggles**: Verbose Debug and Use bash toggles with inline labels
- **Visual States**: Button shows different states (normal, running, disabled)
- **Clean Separation**: Logical grouping of related functionality

### 6. **Technical Implementation**
- **Spatie SSH Integration**: Enhanced with streaming callbacks and verbose modes
- **Filament Grid System**: Custom 2-column layout using pure Filament components
- **Livewire Events**: Real-time updates via `outputUpdated` and `debugUpdated` events
- **JavaScript Integration**: Auto-scrolling output areas and event handling
- **Error Handling**: Comprehensive exception catching and user feedback

These enhancements transform the SSH Command Runner from a basic command execution tool into a professional-grade SSH management interface with real-time feedback, comprehensive debugging, and flexible execution options.

## Documentation Enhancements

The project documentation has been significantly enhanced to provide comprehensive guidance for users and developers:

### **README.md Complete Rewrite**
The README.md has been completely rewritten to provide a thorough understanding of the SSH Manager application:

**Security-First Approach:**
- Prominent security warning about localhost-only usage at the top of the document
- Detailed security considerations section covering network security, access control, command auditing, key security, user privileges, and input validation
- Clear emphasis on the risks of arbitrary command execution capabilities

**Project Understanding:**
- Deep explanation of what SSHM actually does and its purpose
- Comprehensive breakdown of core functionality including command execution engine, connection management, key management system, advanced debugging, and dashboard overview
- Focus on real-time capabilities and modern web interface benefits

**Installation Excellence:**
- Single consolidated `<pre>` block containing all installation commands
- Inclusion of php.new reference for quick PHP development environment setup
- Detailed step-by-step installation explanations covering repository setup, PHP dependencies, environment configuration, database initialization, admin user creation, frontend assets, and development server
- Complete prerequisites list with specific version requirements
- Post-installation configuration guidance for SSH settings

**Usage Documentation:**
- **Dashboard Overview**: System status visibility, version tracking, and statistics
- **SSH Command Runner**: Split-screen layout explanation, execution options (verbose debug, bash mode), connection modes (saved hosts vs custom connections)
- **Host Management**: Configuration storage, bulk operations, connectivity testing
- **Key Management**: Lifecycle management, generation, import, deployment, security features
- **System Administration**: Directory initialization, permission management, service control, configuration synchronization

**Professional Presentation:**
- Structured sections with clear headings and logical flow
- Technical accuracy combined with accessibility for different user levels
- Emphasis on both powerful capabilities and responsible usage
- Integration of all recent feature enhancements and UI improvements

This documentation approach ensures users understand both the potential and responsibilities that come with using a web-based SSH management tool.

## Desktop Mode Implementation

The SSH Manager now includes a "Desktop Mode" feature that allows the application to run without authentication requirements, suitable for trusted desktop environments.

### Implementation Details:

1. **Environment Configuration**:
   - Created `.env.desktop` file with `DESKTOP_MODE=true` and desktop user settings
   - Added desktop mode configuration to `config/app.php`

2. **Custom Authentication**:
   - Created `DesktopAuthenticate` middleware that auto-creates and logs in the desktop user
   - Modified `AdminPanelProvider` to conditionally apply authentication based on mode
   - When desktop mode is enabled, the login page is completely bypassed

3. **Mode Management**:
   - Created `desktop-mode.sh` script for easy mode switching
   - Script preserves important settings like `APP_KEY` and `SSH_HOME_DIR` when switching
   - Automatic backup/restore of `.env` files during mode changes

4. **Console Command**:
   - Added `sshm:create-desktop-user` artisan command to create the desktop user
   - Desktop user is automatically created when enabling desktop mode

5. **Usage**:
   ```bash
   # Enable desktop mode
   ./desktop-mode.sh enable
   
   # Disable desktop mode  
   ./desktop-mode.sh disable
   
   # Check current mode
   ./desktop-mode.sh status
   ```

### Desktop File Configuration

Created a desktop entry for SSHM application with the following configuration:
```
[Desktop Entry]
Categories=Development;
Comment=SSH Manager
Exec=sh -c 'cd /home/markc/Dev/sshm && php artisan serve --host=127.0.0.1 --port=8888 & sleep 2 && chromium --app=http://localhost:8888/admin --window-size=1024,680 --window-position=center --user-data-dir="$HOME/.config/sshm-app"'
Icon=alienarena
Name=SSHM
NoDisplay=false
Path=
StartupNotify=true
StartupWMClass=chromium-browser
Terminal=false
TerminalOptions=
Type=Application
Version=1.0
X-KDE-SubstituteUID=false
X-KDE-Username=
```

Key aspects of the desktop file:
- Launches Laravel development server on localhost:8888
- Opens Chromium in app mode without browser chrome
- Sets specific window size (1024x680) and centers it
- Uses a dedicated user data directory for the app (`~/.config/sshm-app`)
- Uses alienarena icon for visual identification
- Can be launched from application menu or desktop

Location: `~/.local/share/applications/sshm.desktop`

This feature is ideal for single-user desktop installations where authentication overhead is unnecessary, while maintaining all SSH management functionality.

## Recent UI/UX Updates

1. **Default Table Pagination**: Changed from 10 to 5 rows per page for all tables to provide a more compact view
2. **Collapsible Sidebar**: Changed to `sidebarFullyCollapsibleOnDesktop()` so sidebar defaults to closed state
3. **Modal Creation**: "New SSH Host" and "New SSH Key" now use popup modals instead of separate pages for a smoother workflow

## Development Environment

### Prerequisites
- **PHP 8.2+** with extensions: OpenSSL, PDO, Mbstring, Tokenizer, XML, Ctype, JSON, BCMath
- **Composer** for PHP dependency management
- **Node.js 18+** and **NPM** for frontend asset compilation
- **SQLite** database (included in most PHP installations)

### Step-by-Step Installation

1. **Repository Setup**: Clone the SSHM repository and navigate to the project directory. This downloads all source code and configuration files.

2. **PHP Dependencies**: Run `composer install` to download and install all PHP packages including Laravel framework, Filament admin panel, and the Spatie SSH library for remote command execution.

3. **Environment Configuration**: Copy the example environment file and generate a unique application key for encryption and security. This key protects session data and other sensitive information.

4. **Database Initialization**: Create an SQLite database file and run migrations to set up tables for SSH hosts, keys, settings, and user management.

5. **Admin User Creation**: Use the Filament command to create your first administrator account. You'll be prompted for name, email, and password.

6. **Frontend Assets**: Install Node.js dependencies and compile CSS/JavaScript assets using Vite. This builds the modern admin interface.

7. **Development Server**: Start Laravel's built-in development server on `localhost:8000`. The admin panel is accessible at `/admin`.

## Continuous Integration

This project includes comprehensive GitHub Actions workflows for automated testing and deployment:

### Test Workflow (`.github/workflows/tests.yml`)
- Runs on push to `main`/`develop` branches and pull requests
- Tests against PHP 8.2 and 8.3
- Executes full Pest test suite (149 tests, 482 assertions)
- Includes Laravel Pint code formatting checks

### Build Workflow (`.github/workflows/build.yml`)
- Builds and compiles frontend assets
- Runs database migrations
- Runs Laravel Pint formatting checks
- Executes complete test suite
- Creates deployment artifacts

### Code Quality Workflow (`.github/workflows/code-quality.yml`)
- Dedicated workflow for code quality assurance
- Laravel Pint formatting validation
- PHP syntax checking
- Composer validation and security audits

**Test Coverage**: The application includes comprehensive test coverage:
- **Unit Tests**: Models, Services, and Widgets (56 tests)
- **Feature Tests**: Filament pages and resources (93 tests)
- **Test Categories**: Database operations, UI interactions, form validation, security features

All tests must pass before merge. The test suite covers:
- SSH host and key management
- Command execution functionality
- Filament admin panel features
- Widget functionality
- Form validation and security
- Database operations and migrations

### Code Formatting

This project uses **Laravel Pint** for consistent code formatting:

```bash
# Check code formatting
./vendor/bin/pint --test

# Apply code formatting fixes
./vendor/bin/pint
```

The project includes a custom `pint.json` configuration with enhanced rules for:
- Consistent spacing and concatenation
- Import organization
- Method chaining indentation
- Trait management
- Operator spacing

### Pre-commit Hook

To ensure code quality before commits, you can install the provided pre-commit hook:

```bash
# Install the pre-commit hook
cp scripts/pre-commit-hook.sh .git/hooks/pre-commit
chmod +x .git/hooks/pre-commit
```

The hook automatically runs:
- Laravel Pint formatting checks
- Full Pest test suite

This prevents commits with formatting issues or failing tests.

## Development Setup and Practices

### Prerequisites
- **PHP 8.2+** with extensions: OpenSSL, PDO, Mbstring, Tokenizer, XML, Ctype, JSON, BCMath
- **Composer** for PHP dependency management
- **Node.js 18+** and **NPM** for frontend asset compilation
- **SQLite** database (included in most PHP installations)

### Step-by-Step Development Installation

1. **Repository Setup**: Clone the SSHM repository and navigate to the project directory. This downloads all source code and configuration files.

2. **PHP Dependencies**: Run `composer install` to download and install all PHP packages including Laravel framework, Filament admin panel, and the Spatie SSH library for remote command execution.

3. **Environment Configuration**: Copy the example environment file and generate a unique application key for encryption and security. This key protects session data and other sensitive information.

4. **Database Initialization**: Create an SQLite database file and run migrations to set up tables for SSH hosts, keys, settings, and user management.

5. **Admin User Creation**: Use the Filament command to create your first administrator account. You'll be prompted for name, email, and password.

6. **Frontend Assets**: Install Node.js dependencies and compile CSS/JavaScript assets using Vite. This builds the modern admin interface.

7. **Development Server**: Start Laravel's built-in development server on `localhost:8000`. The admin panel is accessible at `/admin`.

### Continuous Integration

This project includes comprehensive GitHub Actions workflows for automated testing and deployment:

#### Test Workflow (`.github/workflows/tests.yml`)
- Runs on push to `main`/`develop` branches and pull requests
- Tests against PHP 8.2 and 8.3
- Executes full Pest test suite (149 tests, 482 assertions)
- Includes Laravel Pint code formatting checks

#### Build Workflow (`.github/workflows/build.yml`)
- Builds and compiles frontend assets
- Runs database migrations
- Runs Laravel Pint formatting checks
- Executes complete test suite
- Creates deployment artifacts

#### Code Quality Workflow (`.github/workflows/code-quality.yml`)
- Dedicated workflow for code quality assurance
- Laravel Pint formatting validation
- PHP syntax checking
- Composer validation and security audits

### Test Coverage

The application includes comprehensive test coverage:
- **Unit Tests**: Models, Services, and Widgets (56 tests)
- **Feature Tests**: Filament pages and resources (93 tests)
- **Test Categories**: Database operations, UI interactions, form validation, security features

All tests must pass before merge. The test suite covers:
- SSH host and key management
- Command execution functionality
- Filament admin panel features
- Widget functionality
- Form validation and security
- Database operations and migrations

### Code Formatting

This project uses **Laravel Pint** for consistent code formatting:

```bash
# Check code formatting
./vendor/bin/pint --test

# Apply code formatting fixes
./vendor/bin/pint
```

The project includes a custom `pint.json` configuration with enhanced rules for:
- Consistent spacing and concatenation
- Import organization
- Method chaining indentation
- Trait management
- Operator spacing

### Pre-commit Hook

To ensure code quality before commits, you can install the provided pre-commit hook:

```bash
# Install the pre-commit hook
cp scripts/pre-commit-hook.sh .git/hooks/pre-commit
chmod +x .git/hooks/pre-commit
```

The hook automatically runs:
- Laravel Pint formatting checks
- Full Pest test suite

This prevents commits with formatting issues or failing tests.

# important-instruction-reminders
Do what has been asked; nothing more, nothing less.
NEVER create files unless they're absolutely necessary for achieving your goal.
ALWAYS prefer editing an existing file to creating a new one.
NEVER proactively create documentation files (*.md) or README files. Only create documentation files if explicitly requested by the User.