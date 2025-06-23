<div>
    <style>
        .fi-section-container { margin-bottom: 1.5rem !important; }
        .terminal-output { color: #ffffff; }
        .terminal-err { color: #ff6b6b; }
        .terminal-status { color: #74c0fc; }
        #terminal-output::-webkit-scrollbar { width: 8px; }
        #terminal-output::-webkit-scrollbar-track { background: #2d3748; }
        #terminal-output::-webkit-scrollbar-thumb { background: #4a5568; border-radius: 4px; }
        #terminal-output::-webkit-scrollbar-thumb:hover { background: #718096; }
    </style>
    
    <div class="space-y-6">
            <!-- Section 1: Command Input -->
            <section class="fi-section-container">
                <div class="fi-section rounded-xl bg-white shadow-lg ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <div class="fi-section-content-ctn">
                        <div class="fi-section-content p-8">
                            {{ $this->form }}
                        </div>
                    </div>
                </div>
            </section>

            <!-- Section 2: Terminal Output (conditionally shown) -->
            <section class="fi-section-container" id="terminal-section" style="display: {{ $this->hasTerminalOutput ? 'block' : 'none' }};">
                <div class="fi-section rounded-xl bg-white shadow-lg ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <div class="fi-section-content-ctn">
                        <div class="fi-section-content p-8">
                            <pre id="terminal-output" class="block w-full p-6 border border-gray-300 dark:border-gray-700 rounded-lg bg-gray-900 text-green-400 text-sm font-mono overflow-x-auto whitespace-pre-wrap h-96 overflow-y-auto"></pre>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Section 3: Debug Information (conditionally shown) -->
            @if ($this->showDebug)
                <section class="fi-section-container">
                    <div class="fi-section rounded-xl bg-white shadow-lg ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                        <div class="fi-section-content-ctn">
                            <div class="fi-section-content p-8">
                                <div id="debug-info" class="text-xs text-gray-500 dark:text-gray-400 space-y-3">
                                    <div>Status: <span id="connection-status">Checking JavaScript...</span></div>
                                    <div>Process ID: <span id="process-id">None</span></div>
                                    <div>Echo: <span id="echo-status">Unknown</span></div>
                                    <div id="command-status-debug" class="text-sm mt-6"></div>
                                </div>
                                <!-- Hidden host selector for JavaScript access -->
                                <input type="hidden" id="host-select" value="{{ $this->selectedHost }}">
                            </div>
                        </div>
                    </div>
                </section>
            @else
                <!-- Hidden host selector for JavaScript access when debug is off -->
                <input type="hidden" id="host-select" value="{{ $this->selectedHost }}">
            @endif
            
            <!-- Load Echo and Pusher from CDN for testing -->
            <script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
            <script src="https://cdn.jsdelivr.net/npm/laravel-echo@1.16.0/dist/echo.iife.js"></script>
        
            <!-- Simple JavaScript Test -->
            <script>
        // Simple JavaScript test first
        console.log('=== SSH Terminal JavaScript Test ===');
        console.log('1. Basic JavaScript is working');
        
        // Initialize Echo manually
        console.log('2. Initializing Echo from CDN...');
        try {
            window.Echo = new Echo({
                broadcaster: 'reverb',
                key: 'mzm0mamkrxpsxp8qssar',
                wsHost: 'localhost',
                wsPort: 8080,
                wssPort: 8080,
                forceTLS: false,
                enabledTransports: ['ws', 'wss'],
            });
            console.log('3. Echo initialized from CDN successfully!');
        } catch (error) {
            console.error('3. Failed to initialize Echo from CDN:', error);
        }
        
        // Test if elements exist
        document.addEventListener('DOMContentLoaded', function() {
            console.log('4. DOM loaded');
            
            const terminalOutput = document.getElementById('terminal-output');
            const connectionStatus = document.getElementById('connection-status');
            const echoStatus = document.getElementById('echo-status');
            
            if (terminalOutput) {
                console.log('5. Terminal output element found');
                if (connectionStatus) {
                    connectionStatus.textContent = 'JavaScript Working';
                }
            } else {
                console.error('5. ERROR: Terminal output element not found');
                if (connectionStatus) {
                    connectionStatus.textContent = 'ERROR: Elements missing';
                }
            }
            
            // Check if Echo is available
            if (typeof window.Echo !== 'undefined' && window.Echo) {
                console.log('6. Laravel Echo is available!');
                if (echoStatus) {
                    echoStatus.textContent = 'Available';
                    echoStatus.style.color = 'green';
                }
            } else {
                console.log('6. Laravel Echo is NOT available');
                if (echoStatus) {
                    echoStatus.textContent = 'Missing';
                    echoStatus.style.color = 'red';
                }
                
                // Check what's actually available
                console.log('Available global objects:');
                console.log('- window.axios:', typeof window.axios);
                console.log('- window.Pusher:', typeof window.Pusher);
                console.log('- window.Echo:', typeof window.Echo);
                console.log('- window.Livewire:', typeof window.Livewire);
            }
        });
        
        // Store terminal content globally to persist across Livewire re-renders
        window.terminalContent = window.terminalContent || '';
        
        // Global helper function to add terminal output (define early)
        window.addTerminalOutput = function(type, content) {
            console.log(`Adding terminal output: ${type} - ${content}`);
            
            // Add only actual command output to persistent storage
            if (type === 'out' || type === 'err') {
                if (type === 'out') {
                    window.terminalContent += content + '\n';
                } else if (type === 'err') {
                    window.terminalContent += content + '\n'; // Store as plain text, we'll style on display
                }
                
                // Update the DOM element if it exists
                const terminalOutput = document.getElementById('terminal-output');
                if (terminalOutput) {
                    if (type === 'out') {
                        terminalOutput.textContent += content + '\n';
                    } else if (type === 'err') {
                        terminalOutput.innerHTML += `<span class="terminal-err">${content}</span>\n`;
                    }
                }
            }
            
            // Move all status messages to debug section only
            if (type === 'status') {
                const debugStatus = document.getElementById('command-status-debug');
                if (debugStatus) {
                    const timestamp = new Date().toLocaleTimeString();
                    debugStatus.innerHTML += `<div class="text-blue-400">[${timestamp}] ${content}</div>`;
                }
                
                // Update connection status for completed commands
                if (content.includes('Command completed') || content.includes('Command failed')) {
                    const connectionStatus = document.getElementById('connection-status');
                    if (connectionStatus) {
                        connectionStatus.textContent = 'Ready';
                    }
                    
                    // Update button state directly without Livewire to prevent re-render
                    const commandButton = document.querySelector('[data-livewire-action="startTerminalCommand"], [data-livewire-action="stopTerminalCommand"]');
                    if (commandButton) {
                        // Find the button text and icon elements
                        const buttonText = commandButton.querySelector('span:not([data-slot])');
                        const buttonIcon = commandButton.querySelector('[data-slot="icon"]');
                        
                        if (buttonText) buttonText.textContent = 'Run Command';
                        if (buttonIcon) {
                            buttonIcon.innerHTML = '<svg fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" aria-hidden="true" data-slot="icon"><path stroke-linecap="round" stroke-linejoin="round" d="M5.25 5.653c0-.856.917-1.398 1.667-.986l11.54 6.347a1.125 1.125 0 0 1 0 1.972l-11.54 6.347a1.125 1.125 0 0 1-1.667-.986V5.653Z"></path></svg>';
                        }
                        
                        // Reset button color classes
                        commandButton.className = commandButton.className.replace(/bg-custom-\d+-500|bg-danger-\d+/g, 'bg-primary-600');
                    }
                    
                    // Also notify Livewire but without forcing re-render
                    if (window.Livewire) {
                        window.Livewire.dispatch('setRunningState', { isRunning: false });
                    }
                }
            }
            
            terminalOutput.scrollTop = terminalOutput.scrollHeight;
        };
        
        // Function to restore terminal content after Livewire re-renders
        window.restoreTerminalContent = function() {
            const terminalOutput = document.getElementById('terminal-output');
            if (terminalOutput && window.terminalContent) {
                terminalOutput.textContent = window.terminalContent;
                terminalOutput.scrollTop = terminalOutput.scrollHeight;
            }
        };


        // Listen for Livewire events to subscribe to WebSocket process
        document.addEventListener('livewire:init', () => {
            console.log('7. Livewire initialized');
            
            // Restore terminal content after any Livewire update
            window.Livewire.hook('morph.updated', () => {
                setTimeout(window.restoreTerminalContent, 10);
            });
            
            Livewire.on('subscribe-to-process', (data) => {
                console.log('8. Subscribe to process event received:', data);
                const processId = data[0].process_id;
                
                // Update debug info (only if debug section is visible)
                const processIdElement = document.getElementById('process-id');
                const connectionStatusElement = document.getElementById('connection-status');
                
                if (processIdElement) processIdElement.textContent = processId;
                if (connectionStatusElement) connectionStatusElement.textContent = 'Connecting...';
                
                const terminalOutput = document.getElementById('terminal-output');
                const terminalSection = document.getElementById('terminal-section');
                
                if (terminalOutput) {
                    // Clear terminal for new command
                    terminalOutput.textContent = '';
                    window.terminalContent = ''; // Also clear stored content
                    
                    // Clear debug status for new command
                    const debugStatus = document.getElementById('command-status-debug');
                    if (debugStatus) {
                        debugStatus.innerHTML = '';
                    }
                    
                    // Subscribe to WebSocket channel if Echo is available
                    if (window.Echo) {
                        try {
                            console.log('9. Subscribing to channel: ssh-process.' + processId);
                            
                            // Disconnect any existing channel
                            if (window.currentSSHChannel) {
                                window.Echo.leave(window.currentSSHChannel.name);
                            }
                            
                            // Subscribe to private channel  
                            window.currentSSHChannel = window.Echo.private(`ssh-process.${processId}`)
                                .listen('SshOutputReceived', (event) => {
                                    console.log('10. SSH output received:', event);
                                    // Call the global addTerminalOutput function
                                    window.addTerminalOutput(event.type, event.line);
                                })
                                .error((error) => {
                                    console.error('WebSocket channel error:', error);
                                    window.addTerminalOutput('error', `WebSocket error: ${error.message || 'Connection failed'}`);
                                });
                                
                            if (connectionStatusElement) connectionStatusElement.textContent = 'Connected';
                            
                        } catch (error) {
                            console.error('9. Failed to subscribe to channel:', error);
                            if (connectionStatusElement) connectionStatusElement.textContent = 'Connection Failed';
                        }
                    } else {
                        console.error('9. Echo not available for WebSocket subscription');
                        if (connectionStatusElement) connectionStatusElement.textContent = 'Echo Missing';
                    }
                }
            });
        });
        </script>
    </div>
</div>