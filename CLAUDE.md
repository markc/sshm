Please create a Laravel 12 project integrated with Filament 3.2.

The core functionality should be to execute SSH commands on a remote server from within the Filament admin panel.

Here are the specific requirements:

1.  **Project Setup:**
    *   Initialize a standard Laravel 12 project.
    *   Install Filament 3.2 and configure it for basic usage (e.g., create an admin user).
    *   Install the `spatie/ssh` package.

2.  **Filament Resource or Page:**
    *   Create a new Filament Page (not a Resource) named `SshCommandRunner`. This page will contain the UI for running commands.
    *   This page should be accessible within the Filament admin panel's navigation.

3.  **User Interface on the `SshCommandRunner` Page:**
    *   Include a `<textarea>` where the user can input the SSH command(s) to be executed. This textarea should have a label like "Enter SSH Command(s)".
    *   Include a `<button type="submit">` with the text "Run Command".
    *   Include a hidden `<div id="command-output-alert">` above the textarea. This `div` will be used to display the output of the executed command. It should be initially hidden.

4.  **Server Configuration:**
    *   Provide a mechanism to configure the remote server details (host, user, private key path or password, port).
    *   Suggest using environment variables (`.env`) for storing sensitive information like the private key path or password.
    *   The page should ideally read these configuration values from the environment variables.

5.  **Backend Logic (in the Filament Page class):**
    *   Implement a method (e.g., `runSshCommand`) that is triggered when the submit button is clicked.
    *   Inside this method:
        *   Get the command(s) from the textarea input.
        *   Use `spatie/ssh` to connect to the remote server using the configured credentials.
        *   Execute the command(s) entered by the user.
        *   Capture the output and any errors from the SSH execution.
        *   Handle potential SSH connection errors or command execution failures.

6.  **Frontend (JavaScript or Livewire) Interaction:**
    *   When the submit button is clicked, send the command(s) to the backend method.
    *   Upon receiving the results (output or error) from the backend, update the content of the hidden `div` (`#command-output-alert`) with the results.
    *   Make the `div` visible to display the output.
    *   Consider styling the alert `div` to visually distinguish between success and error output (e.g., using Tailwind classes if Filament uses them).

7.  **Security Considerations:**
    *   Emphasize the security risks of allowing arbitrary command execution.
    *   Suggest potential mitigations (though the prompt doesn't require implementing complex security: this is a basic example):
        *   Strict authorization: Only allow specific users access to this page.
        *   Input sanitization (though for SSH commands, this is tricky and often involves limiting allowed commands rather than sanitizing user input directly).
        *   Running commands with a user with limited privileges on the remote server.

8.  **Code Structure:**
    *   Organize the code logically within the Laravel and Filament structure.
    *   Provide clear comments explaining the different parts of the code.

**Expected Output from Claude:**

The expected output is the necessary code files and instructions to set up and run the project:

*   Composer commands for installation.
*   Instructions for setting up the `.env` file with SSH credentials.
*   The code for the Filament Page class (`SshCommandRunner.php`).
*   The Blade view file (if a separate view is needed for the form and output display within the Filament Page).
*   Any necessary JavaScript or Livewire code for frontend interaction (if not handled purely by Livewire's form submission).
*   Basic instructions on how to access the page in Filament.

This prompt should give the code-generating AI a clear understanding of what you need to build a functional example project. Remember to review and adapt the generated code as needed for your specific environment and security requirements.
