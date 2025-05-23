Please create a Laravel 12 project integrated with Filament 3.2.

The core functionality should be to execute SSH commands on a remote server from within the Filament admin panel.

Here are the specific requirements:

1.  **Project Setup:**

    - Initialize a standard Laravel 12 project.
    - Install Filament 3.2 and configure it for basic usage (e.g., create an admin user).
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