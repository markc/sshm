<div>
    <style>
        .fi-section-container { margin-bottom: 1.5rem !important; margin-top: 1.5rem !important; }
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
                            <pre id="terminal-output" class="block w-full p-6 border border-gray-300 dark:border-gray-700 rounded-lg bg-gray-900 text-green-400 text-sm font-mono overflow-x-auto whitespace-pre h-96 overflow-y-auto"></pre>
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
                                    <div>Last Execution Time: <span id="execution-time" class="font-mono text-green-400">-</span></div>
                                    <div>User Experience Time: <span id="ux-time" class="font-mono text-blue-400">-</span></div>
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
            
            console.log('Debug elements found:', {
                terminalOutput: !!terminalOutput,
                connectionStatus: !!connectionStatus,
                echoStatus: !!echoStatus,
                processId: !!document.getElementById('process-id'),
                executionTime: !!document.getElementById('execution-time'),
                uxTime: !!document.getElementById('ux-time')
            });
            
            if (terminalOutput) {
                console.log('5. Terminal output element found');
            } else {
                console.error('5. ERROR: Terminal output element not found');
            }
            
            // Initialize debug values immediately if elements exist
            window.updateDebugElement('connection-status', 'Ready');
            
            // Check if Echo is available and update stored value
            if (typeof window.Echo !== 'undefined' && window.Echo) {
                console.log('6. Laravel Echo is available!');
                window.debugValues.echoStatus = 'Available';
                window.updateDebugElement('echo-status', 'Available', '#10b981');
            } else {
                console.log('6. Laravel Echo is NOT available');
                window.debugValues.echoStatus = 'Missing';
                window.updateDebugElement('echo-status', 'Missing', '#ef4444');
            }
            
            // Also try to restore any existing debug values if debug panel is visible
            setTimeout(window.restoreAllDebugValues, 50);
            
            // Check what's actually available
            console.log('Available global objects:');
            console.log('- window.axios:', typeof window.axios);
            console.log('- window.Pusher:', typeof window.Pusher);
            console.log('- window.Echo:', typeof window.Echo);
            console.log('- window.Livewire:', typeof window.Livewire);
        });
        
        // Store terminal content globally to persist across Livewire re-renders
        window.terminalContent = window.terminalContent || '';
        window.debugContent = window.debugContent || '';
        
        // Store timing information for user experience measurement
        window.commandStartTime = null;
        window.firstOutputTime = null;
        
        // Store current debug values to persist across Livewire re-renders
        window.debugValues = {
            connectionStatus: 'Ready',
            processId: 'None',
            echoStatus: 'Unknown',
            executionTime: '-',
            uxTime: '-'
        };
        
        // Global helper function to add terminal output (define early)
        window.addTerminalOutput = function(type, content) {
            console.log(`Adding terminal output: ${type} - ${content}`);
            
            // Track first output time for UX measurement
            if ((type === 'out' || type === 'err') && window.commandStartTime && !window.firstOutputTime) {
                window.firstOutputTime = performance.now();
                const firstOutputDelay = window.firstOutputTime - window.commandStartTime;
                console.log(`First output received after ${firstOutputDelay.toFixed(1)}ms`);
            }
            
            // Get terminal output element once at function level
            const terminalOutput = document.getElementById('terminal-output');
            
            // Add only actual command output to persistent storage
            if (type === 'out' || type === 'err') {
                if (type === 'out') {
                    window.terminalContent += content + '\n';
                } else if (type === 'err') {
                    window.terminalContent += content + '\n'; // Store as plain text, we'll style on display
                }
                
                // Update the DOM element if it exists
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
                    // Skip showing execution time in debug log since it's shown in debug area
                    if (!content.includes('Execution time:')) {
                        const debugLine = `<div class="text-blue-400">[${timestamp}] ${content}</div>`;
                        debugStatus.innerHTML += debugLine;
                        // Store debug content for persistence
                        window.debugContent += debugLine;
                    }
                }
                
                // Update connection status for completed commands and extract execution time
                if (content.includes('Command completed') || content.includes('Command failed')) {
                    window.updateDebugElement('connection-status', 'Ready');
                    
                    // Extract and display execution time
                    const executionTimeMatch = content.match(/Execution time: ([^)]+)/);
                    if (executionTimeMatch) {
                        window.updateDebugElement('execution-time', executionTimeMatch[1], '#10b981');
                    }
                    
                    // Calculate and display total user experience time
                    if (window.commandStartTime) {
                        const commandEndTime = performance.now();
                        const totalUxTime = commandEndTime - window.commandStartTime;
                        
                        let uxTimeFormatted;
                        if (totalUxTime < 1000) {
                            uxTimeFormatted = totalUxTime.toFixed(1) + 'ms';
                        } else {
                            uxTimeFormatted = (totalUxTime / 1000).toFixed(3) + 's';
                        }
                        
                        window.updateDebugElement('ux-time', uxTimeFormatted, '#3b82f6');
                        
                        // Also display first output time if available
                        if (window.firstOutputTime) {
                            const firstOutputDelay = window.firstOutputTime - window.commandStartTime;
                            const firstOutputFormatted = firstOutputDelay < 1000 ? 
                                firstOutputDelay.toFixed(1) + 'ms' : 
                                (firstOutputDelay / 1000).toFixed(3) + 's';
                            console.log('UX Timing - Total: ' + uxTimeFormatted + ', First Output: ' + firstOutputFormatted);
                        }
                        
                        // Reset timing variables for next command
                        window.commandStartTime = null;
                        window.firstOutputTime = null;
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
            
            // Auto-scroll terminal output if element exists
            if (terminalOutput) {
                terminalOutput.scrollTop = terminalOutput.scrollHeight;
            }
        };
        
        // Function to restore terminal content after Livewire re-renders
        window.restoreTerminalContent = function() {
            const terminalOutput = document.getElementById('terminal-output');
            if (terminalOutput && window.terminalContent) {
                terminalOutput.textContent = window.terminalContent;
                terminalOutput.scrollTop = terminalOutput.scrollHeight;
            }
        };
        
        // Function to restore debug content after Livewire re-renders
        window.restoreDebugContent = function() {
            const debugStatus = document.getElementById('command-status-debug');
            if (debugStatus && window.debugContent) {
                debugStatus.innerHTML = window.debugContent;
            }
        };
        
        // Helper function to safely update debug elements
        window.updateDebugElement = function(elementId, value, color = null) {
            // Store the value persistently with proper key mapping
            const keyMap = {
                'connection-status': 'connectionStatus',
                'process-id': 'processId',
                'echo-status': 'echoStatus',
                'execution-time': 'executionTime',
                'ux-time': 'uxTime'
            };
            const key = keyMap[elementId];
            if (window.debugValues && key && window.debugValues.hasOwnProperty(key)) {
                window.debugValues[key] = value;
            }
            
            const element = document.getElementById(elementId);
            if (element) {
                element.textContent = value;
                if (color) element.style.color = color;
                console.log(`Updated ${elementId} to: ${value}`);
                return true;
            } else {
                console.log(`Debug element ${elementId} not found (debug panel may be hidden) - stored value: ${value}`);
                return false;
            }
        };
        
        // Function to restore all debug values after Livewire re-renders
        window.restoreAllDebugValues = function() {
            if (window.debugValues) {
                console.log('Restoring debug values:', window.debugValues);
                window.updateDebugElement('connection-status', window.debugValues.connectionStatus);
                window.updateDebugElement('process-id', window.debugValues.processId);
                window.updateDebugElement('echo-status', window.debugValues.echoStatus);
                window.updateDebugElement('execution-time', window.debugValues.executionTime);
                window.updateDebugElement('ux-time', window.debugValues.uxTime);
            }
        };


        // Listen for Livewire events to subscribe to WebSocket process
        document.addEventListener('livewire:init', () => {
            console.log('7. Livewire initialized');
            
            // Restore terminal content after any Livewire update
            window.Livewire.hook('morph.updated', () => {
                setTimeout(() => {
                    window.restoreTerminalContent();
                    window.restoreDebugContent();
                    window.restoreAllDebugValues();
                }, 10);
            });
            
            Livewire.on('subscribe-to-process', (data) => {
                console.log('8. Subscribe to process event received:', data);
                const processId = data[0].process_id;
                
                // Start timing for user experience measurement
                window.commandStartTime = performance.now();
                window.firstOutputTime = null;
                console.log('Started UX timing measurement');
                
                // Update debug info (always update, regardless of visibility)
                const processIdElement = document.getElementById('process-id');
                const connectionStatusElement = document.getElementById('connection-status');
                
                console.log('Updating debug info for process:', processId);
                
                // Update debug elements using helper function
                window.updateDebugElement('process-id', processId);
                window.updateDebugElement('connection-status', 'Connecting...');
                
                const terminalOutput = document.getElementById('terminal-output');
                const terminalSection = document.getElementById('terminal-section');
                
                if (terminalOutput) {
                    // Clear terminal for new command
                    terminalOutput.textContent = '';
                    window.terminalContent = ''; // Also clear stored content
                    
                    // Clear debug status for new command instead of accumulating
                    const debugStatus = document.getElementById('command-status-debug');
                    if (debugStatus) {
                        debugStatus.innerHTML = '';
                        window.debugContent = ''; // Clear stored debug content for fresh start
                    }
                    
                    // Reset debug values for new command
                    window.updateDebugElement('execution-time', '-');
                    window.updateDebugElement('ux-time', '-');
                    
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
                                    window.addTerminalOutput('error', 'WebSocket error: ' + (error.message || 'Connection failed'));
                                });
                                
                            window.updateDebugElement('connection-status', 'Connected');
                            window.updateDebugElement('echo-status', 'Connected', '#10b981');
                            
                        } catch (error) {
                            console.error('9. Failed to subscribe to channel:', error);
                            window.updateDebugElement('connection-status', 'Connection Failed');
                        }
                    } else {
                        console.error('9. Echo not available for WebSocket subscription');
                        window.updateDebugElement('connection-status', 'Echo Missing');
                    }
                }
            });
        });
        </script>
    </div>
</div>