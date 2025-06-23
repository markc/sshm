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
        .performance-indicator { font-family: monospace; font-size: 0.75rem; padding: 4px 8px; border-radius: 4px; }
        .performance-fast { background-color: #065f46; color: #10b981; }
        .performance-medium { background-color: #92400e; color: #f59e0b; }
        .performance-slow { background-color: #7f1d1d; color: #ef4444; }
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
                        <div class="flex justify-between items-center mb-4">
                            <div id="performance-indicator" class="performance-indicator" style="display: none;">
                                Connection: <span id="perf-connection">-</span> | 
                                Execution: <span id="perf-execution">-</span> | 
                                Total: <span id="perf-total">-</span>
                            </div>
                        </div>
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
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Debug Information</h3>
                            <div id="debug-info" class="text-xs text-gray-500 dark:text-gray-400 space-y-3">
                                <div>Connection Method: <span id="connection-method" class="font-mono text-green-400">Server-Sent Events</span></div>
                                <div>Process ID: <span id="process-id" class="font-mono">None</span></div>
                                <div>Connection Status: <span id="connection-status">Ready</span></div>
                                <div>Performance Mode: <span id="performance-mode">Fast Mode</span></div>
                                <div>SSH Multiplexing: <span id="ssh-multiplexing" class="text-green-400">Enabled</span></div>
                                <div class="pt-2 border-t border-gray-700">
                                    <div>First Byte Time: <span id="first-byte-time" class="font-mono text-blue-400">-</span></div>
                                    <div>Command Execution: <span id="execution-time" class="font-mono text-green-400">-</span></div>
                                    <div>Total User Time: <span id="ux-time" class="font-mono text-purple-400">-</span></div>
                                </div>
                            </div>
                            <div id="debug-log" class="text-xs mt-4 p-4 bg-gray-800 rounded border max-h-32 overflow-y-auto">
                                <div class="text-green-400">Server-Sent Events ready. No WebSocket overhead.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        @endif
    </div>

    <!-- Optimized JavaScript using Server-Sent Events -->
    <script>
        console.log('🚀 SSHM Optimized - Server-Sent Events Mode');
        
        // Performance tracking variables
        window.sshPerformance = {
            commandStartTime: null,
            connectionStartTime: null,
            firstByteTime: null,
            executionStartTime: null,
            executionEndTime: null
        };
        
        // Terminal content storage (persists across Livewire updates)
        window.terminalContent = window.terminalContent || '';
        window.currentEventSource = null;
        
        // Initialize on DOM ready
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM ready - Optimized SSH Runner initialized');
            
            // Update debug info to show optimized mode
            updateDebugElement('connection-method', 'Server-Sent Events (Optimized)');
            updateDebugElement('connection-status', 'Ready');
        });
        
        // Listen for Livewire events
        document.addEventListener('livewire:init', () => {
            console.log('Livewire initialized');
            
            // Restore terminal content after Livewire updates
            window.Livewire.hook('morph.updated', () => {
                setTimeout(restoreTerminalContent, 10);
            });
            
            // Listen for optimized SSH stream start
            Livewire.on('start-ssh-stream', (data) => {
                console.log('Starting optimized SSH stream:', data);
                startSshStream(data[0]);
            });
        });
        
        // Start SSH command streaming via Server-Sent Events
        function startSshStream(config) {
            const { process_id, command, host_id, use_bash } = config;
            
            // Performance tracking
            window.sshPerformance.commandStartTime = performance.now();
            window.sshPerformance.connectionStartTime = performance.now();
            
            // Clear terminal for new command
            clearTerminal();
            
            // Update debug info
            updateDebugElement('process-id', process_id);
            updateDebugElement('connection-status', 'Connecting...');
            updateDebugElement('performance-mode', 'Optimized Mode');
            
            // Show performance indicator
            const perfIndicator = document.getElementById('performance-indicator');
            if (perfIndicator) {
                perfIndicator.style.display = 'block';
                updatePerformanceIndicator('Connecting...', '-', '-');
            }
            
            // Close existing EventSource if any
            if (window.currentEventSource) {
                window.currentEventSource.close();
            }
            
            // Create Server-Sent Events connection
            const eventSourceUrl = new URL('/api/ssh/stream', window.location.origin);
            
            // Create form data for POST request simulation
            const formData = new FormData();
            formData.append('command', command);
            formData.append('host_id', host_id);
            formData.append('use_bash', use_bash ? '1' : '0');
            formData.append('_token', document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '');
            
            // Use fetch to POST data and get streaming response
            fetch('/api/ssh/stream', {
                method: 'POST',
                body: formData,
                headers: {
                    'Accept': 'text/event-stream',
                    'Cache-Control': 'no-cache',
                },
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                
                // Track connection establishment time
                const connectionTime = performance.now() - window.sshPerformance.connectionStartTime;
                console.log(`Connection established in ${connectionTime.toFixed(1)}ms`);
                
                updateDebugElement('connection-status', 'Connected');
                updatePerformanceIndicator(formatTime(connectionTime), '-', '-');
                
                // Read streaming response
                const reader = response.body.getReader();
                return readStream(reader, process_id);
            })
            .catch(error => {
                console.error('SSH stream error:', error);
                addTerminalOutput('error', `Connection error: ${error.message}`);
                updateDebugElement('connection-status', 'Error');
                resetPerformanceTracking();
            });
        }
        
        // Read Server-Sent Events stream
        async function readStream(reader, processId) {
            let buffer = '';
            
            try {
                while (true) {
                    const { done, value } = await reader.read();
                    
                    if (done) {
                        console.log('Stream ended');
                        break;
                    }
                    
                    // Convert bytes to text
                    const chunk = new TextDecoder().decode(value);
                    buffer += chunk;
                    
                    // Process complete lines
                    const lines = buffer.split('\n');
                    buffer = lines.pop() || ''; // Keep incomplete line in buffer
                    
                    for (const line of lines) {
                        if (line.startsWith('data: ')) {
                            try {
                                const eventData = JSON.parse(line.substring(6));
                                handleStreamEvent(eventData);
                            } catch (e) {
                                console.log('Non-JSON data:', line);
                            }
                        }
                    }
                }
            } catch (error) {
                console.error('Stream reading error:', error);
                addTerminalOutput('error', `Stream error: ${error.message}`);
            } finally {
                resetPerformanceTracking();
                updateDebugElement('connection-status', 'Ready');
                
                // Update Livewire state
                if (window.Livewire) {
                    window.Livewire.dispatch('setRunningState', { isRunning: false });
                }
            }
        }
        
        // Handle streaming events
        function handleStreamEvent(event) {
            const { type, data, process_id, timestamp } = event;
            
            // Track first byte time
            if (!window.sshPerformance.firstByteTime && (type === 'output' || type === 'error')) {
                window.sshPerformance.firstByteTime = performance.now();
                const firstByteDelay = window.sshPerformance.firstByteTime - window.sshPerformance.commandStartTime;
                updateDebugElement('first-byte-time', formatTime(firstByteDelay));
                console.log(`First byte received in ${firstByteDelay.toFixed(1)}ms`);
            }
            
            // Track execution start
            if (!window.sshPerformance.executionStartTime && type === 'status' && data.includes('Executing')) {
                window.sshPerformance.executionStartTime = performance.now();
            }
            
            // Handle different event types
            switch (type) {
                case 'output':
                    addTerminalOutput('out', data);
                    break;
                case 'error':
                    addTerminalOutput('err', data);
                    break;
                case 'status':
                    handleStatusMessage(data);
                    break;
                case 'complete':
                    handleCommandComplete();
                    break;
                default:
                    console.log('Unknown event type:', type, data);
            }
        }
        
        // Handle status messages
        function handleStatusMessage(message) {
            console.log('Status:', message);
            
            // Track execution completion and extract timing
            if (message.includes('completed') || message.includes('failed')) {
                window.sshPerformance.executionEndTime = performance.now();
                
                // Extract execution time from message
                const executionTimeMatch = message.match(/Execution time: ([^)]+)/);
                if (executionTimeMatch) {
                    updateDebugElement('execution-time', executionTimeMatch[1]);
                }
                
                // Calculate total user experience time
                if (window.sshPerformance.commandStartTime) {
                    const totalTime = window.sshPerformance.executionEndTime - window.sshPerformance.commandStartTime;
                    updateDebugElement('ux-time', formatTime(totalTime));
                    
                    // Update performance indicator
                    const connectionTime = window.sshPerformance.firstByteTime - window.sshPerformance.connectionStartTime;
                    const executionTime = window.sshPerformance.executionEndTime - (window.sshPerformance.executionStartTime || window.sshPerformance.firstByteTime);
                    
                    updatePerformanceIndicator(
                        formatTime(connectionTime),
                        formatTime(executionTime),
                        formatTime(totalTime)
                    );
                    
                    console.log(`Performance - Connection: ${formatTime(connectionTime)}, Execution: ${formatTime(executionTime)}, Total: ${formatTime(totalTime)}`);
                }
            }
            
            // Show status in debug log
            const debugLog = document.getElementById('debug-log');
            if (debugLog) {
                const timestamp = new Date().toLocaleTimeString();
                const logEntry = document.createElement('div');
                logEntry.className = 'text-blue-400';
                logEntry.textContent = `[${timestamp}] ${message}`;
                debugLog.appendChild(logEntry);
                debugLog.scrollTop = debugLog.scrollHeight;
            }
        }
        
        // Handle command completion
        function handleCommandComplete() {
            console.log('Command completed');
            updateDebugElement('connection-status', 'Ready');
            
            // Hide performance indicator after delay
            setTimeout(() => {
                const perfIndicator = document.getElementById('performance-indicator');
                if (perfIndicator) {
                    perfIndicator.style.display = 'none';
                }
            }, 5000);
        }
        
        // Add terminal output
        function addTerminalOutput(type, content) {
            const terminalOutput = document.getElementById('terminal-output');
            
            if (type === 'out') {
                window.terminalContent += content + '\n';
                if (terminalOutput) {
                    terminalOutput.textContent += content + '\n';
                }
            } else if (type === 'err') {
                window.terminalContent += content + '\n';
                if (terminalOutput) {
                    const errorSpan = document.createElement('span');
                    errorSpan.className = 'terminal-err';
                    errorSpan.textContent = content + '\n';
                    terminalOutput.appendChild(errorSpan);
                }
            }
            
            // Auto-scroll
            if (terminalOutput) {
                terminalOutput.scrollTop = terminalOutput.scrollHeight;
            }
        }
        
        // Clear terminal
        function clearTerminal() {
            window.terminalContent = '';
            const terminalOutput = document.getElementById('terminal-output');
            if (terminalOutput) {
                terminalOutput.textContent = '';
            }
            
            // Clear debug log
            const debugLog = document.getElementById('debug-log');
            if (debugLog) {
                debugLog.innerHTML = '<div class="text-green-400">Server-Sent Events ready. No WebSocket overhead.</div>';
            }
            
            // Reset performance tracking
            resetPerformanceTracking();
        }
        
        // Restore terminal content after Livewire updates
        function restoreTerminalContent() {
            const terminalOutput = document.getElementById('terminal-output');
            if (terminalOutput && window.terminalContent) {
                terminalOutput.textContent = window.terminalContent;
                terminalOutput.scrollTop = terminalOutput.scrollHeight;
            }
        }
        
        // Update debug elements
        function updateDebugElement(elementId, value, color = null) {
            const element = document.getElementById(elementId);
            if (element) {
                element.textContent = value;
                if (color) element.style.color = color;
            }
        }
        
        // Update performance indicator
        function updatePerformanceIndicator(connection, execution, total) {
            document.getElementById('perf-connection').textContent = connection;
            document.getElementById('perf-execution').textContent = execution;
            document.getElementById('perf-total').textContent = total;
            
            // Color code based on total time
            const perfIndicator = document.getElementById('performance-indicator');
            if (perfIndicator && total !== '-') {
                const totalMs = parseFloat(total);
                if (totalMs < 500) {
                    perfIndicator.className = 'performance-indicator performance-fast';
                } else if (totalMs < 2000) {
                    perfIndicator.className = 'performance-indicator performance-medium';
                } else {
                    perfIndicator.className = 'performance-indicator performance-slow';
                }
            }
        }
        
        // Format time for display
        function formatTime(ms) {
            if (ms < 1000) {
                return `${ms.toFixed(1)}ms`;
            } else {
                return `${(ms / 1000).toFixed(3)}s`;
            }
        }
        
        // Reset performance tracking
        function resetPerformanceTracking() {
            window.sshPerformance = {
                commandStartTime: null,
                connectionStartTime: null,
                firstByteTime: null,
                executionStartTime: null,
                executionEndTime: null
            };
            
            updateDebugElement('first-byte-time', '-');
            updateDebugElement('execution-time', '-');
            updateDebugElement('ux-time', '-');
        }
    </script>
</div>