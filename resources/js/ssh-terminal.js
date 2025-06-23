/**
 * SSH Terminal Real-Time WebSocket Integration
 * 
 * This module handles real-time SSH command execution with live output streaming
 * through Laravel Echo and WebSocket broadcasting.
 */

class SshTerminal {
    constructor() {
        this.currentProcessId = null;
        this.currentChannel = null;
        this.terminalOutput = null;
        this.commandInput = null;
        this.runButton = null;
        this.stopButton = null;
        this.statusDisplay = null;
        
        this.init();
    }

    init() {
        // Get DOM elements
        this.terminalOutput = document.getElementById('terminal-output');
        this.commandInput = document.getElementById('command-input');
        this.runButton = document.getElementById('run-command-btn');
        this.stopButton = document.getElementById('stop-command-btn');
        this.statusDisplay = document.getElementById('command-status');
        
        if (!this.terminalOutput || !this.commandInput || !this.runButton) {
            console.log('SSH Terminal: Required DOM elements not found');
            return;
        }

        this.setupEventListeners();
        this.clearTerminal();
    }

    setupEventListeners() {
        // Run command button
        this.runButton.addEventListener('click', () => this.startCommand());
        
        // Stop command button
        if (this.stopButton) {
            this.stopButton.addEventListener('click', () => this.stopCommand());
        }
        
        // Enter key in command input
        this.commandInput.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' && (e.ctrlKey || e.metaKey)) {
                e.preventDefault();
                this.startCommand();
            }
        });
    }

    async startCommand() {
        const command = this.commandInput.value.trim();
        const hostId = document.getElementById('host-select')?.value;
        
        if (!command) {
            this.addOutput('error', 'Please enter a command to execute.');
            return;
        }
        
        if (!hostId) {
            this.addOutput('error', 'Please select an SSH host.');
            return;
        }

        this.setRunning(true);
        this.clearTerminal();
        
        try {
            // Start the SSH command execution
            const response = await this.apiRequest('/api/ssh/start', {
                method: 'POST',
                body: JSON.stringify({
                    command: command,
                    host_id: hostId
                })
            });

            if (response.process_id) {
                this.currentProcessId = response.process_id;
                this.addOutput('status', `🚀 Starting SSH session on ${response.host}...`);
                this.addOutput('status', `Process ID: ${response.process_id}`);
                this.subscribeToProcess(response.process_id);
            }
        } catch (error) {
            this.addOutput('error', `Failed to start command: ${error.message}`);
            this.setRunning(false);
        }
    }

    async stopCommand() {
        if (!this.currentProcessId) {
            this.addOutput('error', 'No active process to stop.');
            return;
        }

        try {
            await this.apiRequest('/api/ssh/stop', {
                method: 'POST',
                body: JSON.stringify({
                    process_id: this.currentProcessId
                })
            });
            
            this.addOutput('status', '🛑 Stop signal sent...');
        } catch (error) {
            this.addOutput('error', `Failed to stop process: ${error.message}`);
        }
    }

    subscribeToProcess(processId) {
        // Disconnect any existing channel
        if (this.currentChannel) {
            window.Echo.leave(this.currentChannel.name);
        }

        // Subscribe to the process-specific private channel
        this.currentChannel = window.Echo.private(`ssh-process.${processId}`)
            .listen('SshOutputReceived', (event) => {
                this.handleOutput(event);
            })
            .error((error) => {
                console.error('WebSocket error:', error);
                this.addOutput('error', `WebSocket connection error: ${error.message || 'Unknown error'}`);
                this.setRunning(false);
            });
    }

    handleOutput(event) {
        const { type, line } = event;
        
        switch (type) {
            case 'out':
                this.addOutput('output', line);
                break;
            case 'err':
                this.addOutput('error', line);
                break;
            case 'status':
                this.addOutput('status', line);
                // Check if this is a completion status
                if (line.includes('Command completed') || line.includes('Command failed') || line.includes('Session ended')) {
                    this.setRunning(false);
                }
                break;
        }
    }

    addOutput(type, content) {
        if (!this.terminalOutput) return;

        const line = document.createElement('div');
        line.className = `terminal-line terminal-${type}`;
        
        // Add timestamp
        const timestamp = new Date().toLocaleTimeString();
        const timestampSpan = document.createElement('span');
        timestampSpan.className = 'terminal-timestamp';
        timestampSpan.textContent = `[${timestamp}] `;
        
        // Add content
        const contentSpan = document.createElement('span');
        contentSpan.textContent = content;
        
        line.appendChild(timestampSpan);
        line.appendChild(contentSpan);
        
        this.terminalOutput.appendChild(line);
        
        // Auto-scroll to bottom
        this.terminalOutput.scrollTop = this.terminalOutput.scrollHeight;
        
        // Update status display
        if (this.statusDisplay && type === 'status') {
            this.statusDisplay.textContent = content;
        }
    }

    clearTerminal() {
        if (this.terminalOutput) {
            this.terminalOutput.innerHTML = '';
        }
        if (this.statusDisplay) {
            this.statusDisplay.textContent = 'Ready';
        }
    }

    setRunning(isRunning) {
        if (this.runButton) {
            this.runButton.disabled = isRunning;
            this.runButton.textContent = isRunning ? 'Running...' : 'Run Command';
        }
        
        if (this.stopButton) {
            this.stopButton.disabled = !isRunning;
        }
        
        if (this.commandInput) {
            this.commandInput.disabled = isRunning;
        }
        
        if (!isRunning) {
            this.currentProcessId = null;
            if (this.currentChannel) {
                window.Echo.leave(this.currentChannel.name);
                this.currentChannel = null;
            }
        }
    }

    async apiRequest(url, options = {}) {
        const defaultOptions = {
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            credentials: 'same-origin'
        };

        const response = await fetch(url, { ...defaultOptions, ...options });
        
        if (!response.ok) {
            const errorData = await response.json().catch(() => ({ error: 'Unknown error' }));
            throw new Error(errorData.error || `HTTP ${response.status}`);
        }

        return response.json();
    }
}

// Initialize SSH Terminal when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    if (document.getElementById('terminal-output')) {
        window.sshTerminal = new SshTerminal();
    }
});

// Export for manual initialization if needed
window.SshTerminal = SshTerminal;