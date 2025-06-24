<div>
    <style>
        /* Ultra-Performance Terminal Output - Pure CSS (No Livewire Interference) */
        .terminal-container {
            contain: strict;
            content-visibility: auto;
            contain-intrinsic-size: 0 384px;
            will-change: transform;
        }
        
        #terminal-output {
            transform: translateZ(0); /* GPU acceleration */
            backface-visibility: hidden;
            scroll-behavior: smooth;
            content-visibility: auto;
        }
        
        /* Terminal section always visible */
        .terminal-section {
            opacity: 1;
            transform: translateY(0);
            display: block !important;
        }
        
        .terminal-err { 
            color: #ff6b6b; 
            font-weight: 500;
        }
        .terminal-status { 
            color: #74c0fc; 
            font-style: italic;
        }
        
        
        .fade-in {
            animation: fadeIn 300ms ease-out forwards;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Optimized Scrollbar */
        #terminal-output::-webkit-scrollbar { width: 8px; }
        #terminal-output::-webkit-scrollbar-track { background: #2d3748; }
        #terminal-output::-webkit-scrollbar-thumb { 
            background: #4a5568; 
            border-radius: 4px;
            transition: background-color 150ms ease;
        }
        #terminal-output::-webkit-scrollbar-thumb:hover { background: #718096; }
        
        /* Classic Terminal Emulator Styling */
        .terminal-emulator {
            background: #1a1a1a;
            border: 2px solid #333;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.5);
            font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
            max-width: 100%;
            overflow: hidden;
        }
        
        .terminal-header {
            background: linear-gradient(to bottom, #4a4a4a, #2a2a2a);
            border-bottom: 1px solid #555;
            padding: 8px 12px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 28px;
        }
        
        .terminal-title {
            color: #fff;
            font-size: 12px;
            font-weight: 500;
            font-family: system-ui, -apple-system, sans-serif;
        }
        
        .terminal-buttons {
            display: flex;
            gap: 6px;
        }
        
        .terminal-button {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            cursor: pointer;
        }
        
        .terminal-button.close { background: #ff5f57; }
        .terminal-button.minimize { background: #ffbd2e; }
        .terminal-button.maximize { background: #28ca42; }
        
        .terminal-screen {
            background: #000;
            color: #00ff00;
            padding: 16px;
            margin: 0;
            border: none;
            width: 100%;
            height: 400px;
            overflow-y: auto;
            overflow-x: hidden;
            white-space: pre-wrap;
            word-wrap: break-word;
            font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
            font-size: 14px;
            line-height: 16px;
            resize: none;
            outline: none;
            scrollbar-width: thin;
            scrollbar-color: #00ff00 #000;
        }
        
        .terminal-screen::-webkit-scrollbar { width: 8px; }
        .terminal-screen::-webkit-scrollbar-track { background: #000; }
        .terminal-screen::-webkit-scrollbar-thumb { 
            background: #00ff00; 
            border-radius: 4px; 
        }
        .terminal-screen::-webkit-scrollbar-thumb:hover { background: #00dd00; }
        
        /* Terminal text styling */
        .terminal-error { color: #ff4444; }
        .terminal-status { color: #44ff44; }
        .terminal-prompt { color: #ffff44; }
    </style>
    
    <div class="ssh-command-runner-hybrid space-y-6">
        <!-- Section 1: Livewire Form (Keep as-is) -->
        <section class="fi-section-container">
            <div class="fi-section rounded-xl bg-white shadow-lg ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="fi-section-content-ctn">
                    <div class="fi-section-content p-8">
                        {{ $this->form }}
                    </div>
                </div>
            </div>
        </section>

        <!-- Section 2: Classic Terminal Emulator (Always Visible) -->
        <section 
            class="fi-section-container terminal-section" 
            id="terminal-section"
        >
            <div class="fi-section rounded-xl bg-white shadow-lg ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="fi-section-content-ctn">
                    <div class="fi-section-content p-8">
                        
                        <!-- Pure JS Terminal Container (Completely Protected from Livewire) -->
                        <div class="terminal-container" wire:ignore.self>
                            <!-- Classic 80x25 Terminal Emulator (Protected from Livewire) -->
                            <div class="terminal-emulator" wire:ignore>
                                <div class="terminal-header">
                                    <div class="terminal-title">SSH Manager Terminal - 80x25</div>
                                    <div class="terminal-buttons">
                                        <span class="terminal-button close"></span>
                                        <span class="terminal-button minimize"></span>
                                        <span class="terminal-button maximize"></span>
                                    </div>
                                </div>
                                <pre 
                                    id="terminal-output" 
                                    class="terminal-screen"
                                    aria-live="polite"
                                    aria-label="SSH Terminal Output"
                                ></pre>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Section 3: Debug Information (Livewire Controlled) -->
        @if ($this->showDebug)
            <section class="fi-section-container">
                <div class="fi-section rounded-xl bg-white shadow-lg ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <div class="fi-section-content-ctn">
                        <div class="fi-section-content p-8">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Debug Information</h3>
                            <div id="debug-info" class="text-xs text-gray-500 dark:text-gray-400 space-y-3" wire:ignore>
                                <div>Connection Method: <span id="connection-method" class="font-mono text-green-400">Hybrid Mode (Livewire + Pure JS)</span></div>
                                <div>Process ID: <span id="process-id" class="font-mono">None</span></div>
                                <div>Connection Status: <span id="connection-status">Ready</span></div>
                                <div>Performance Mode: <span id="performance-mode">Hybrid Ultra-Fast</span></div>
                                <div>Terminal Method: <span id="terminal-method" class="text-purple-400">Pure JavaScript (Zero Livewire)</span></div>
                                <div class="pt-2 border-t border-gray-700">
                                    <div>Connection Time: <span id="perf-connection" class="font-mono text-blue-400">-</span></div>
                                    <div>Execution Time: <span id="perf-execution" class="font-mono text-green-400">-</span></div>
                                    <div>Total Time: <span id="perf-total" class="font-mono text-purple-400">-</span></div>
                                    <div>First Byte Time: <span id="first-byte-time" class="font-mono text-yellow-400">-</span></div>
                                </div>
                            </div>
                            <div id="debug-log" class="text-xs mt-4 p-4 bg-gray-800 rounded border max-h-32 overflow-y-auto" wire:ignore>
                                <div class="text-green-400">Hybrid mode ready. Livewire forms + Pure JS terminal.</div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        @endif
    </div>

    <!-- Pure JavaScript Terminal Handler (Zero Livewire Interference) -->
    <script>
        // Debug logging helper - only logs when debug mode is enabled
        function debugLog(...args) {
            const debugToggle = document.querySelector('input[wire\\:model="showDebug"]');
            if (debugToggle && debugToggle.checked) {
                console.log(...args);
            }
        }
        
        debugLog('🚀 SSH Hybrid Mode - Livewire Forms + Pure JS Terminal');
        
        // Pure JS state management for terminal only
        window.terminalHybrid = {
            isStreaming: false,
            currentReader: null,
            terminalContent: '',
            performance: {
                commandStartTime: null,
                connectionStartTime: null,
                firstByteTime: null,
                executionEndTime: null
            }
        };
        
        // Initialize when DOM ready
        document.addEventListener('DOMContentLoaded', function() {
            debugLog('Hybrid terminal ready - Pure JS terminal output');
            
            // Update debug info
            updateDebugElement('connection-method', 'Hybrid Mode (Livewire + Pure JS)');
            updateDebugElement('terminal-method', 'Pure JavaScript (Zero Livewire)');
        });
        
        // Listen for Livewire SSH stream events (form triggers this)
        document.addEventListener('livewire:init', () => {
            Livewire.on('start-ssh-stream', (data) => {
                debugLog('🎯 Hybrid: Livewire triggered, Pure JS handling terminal:', data);
                startPureJSStream(data[0]);
            });
        });
        
        async function startPureJSStream(config) {
            const { process_id, command, host_id, use_bash } = config;
            
            // Prevent multiple streams
            if (window.terminalHybrid.isStreaming) {
                debugLog('⏸️ Stream already active, aborting');
                return;
            }
            
            window.terminalHybrid.isStreaming = true;
            window.terminalHybrid.performance.commandStartTime = performance.now();
            window.terminalHybrid.performance.connectionStartTime = performance.now();
            
            debugLog('🚀 Starting pure JS stream for terminal...');
            
            // Clear terminal and start fresh
            clearTerminal();
            addToDebugLog(`🎯 Executing SSH command: ${command}`);
            
            try {
                // Get CSRF token
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || 
                                 document.querySelector('input[name="_token"]')?.value || 
                                 @js(csrf_token());
                
                // Prepare form data
                const formData = new FormData();
                formData.append('command', command);
                formData.append('host_id', host_id);
                formData.append('use_bash', use_bash ? '1' : '0');
                formData.append('_token', csrfToken);
                
                // Pure fetch with streaming
                const response = await fetch('/api/ssh/stream', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'Accept': 'text/event-stream',
                        'Cache-Control': 'no-cache'
                    }
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                
                debugLog('📡 Pure JS Stream Response:', response.status);
                
                // Track connection time
                const connectionTime = performance.now() - window.terminalHybrid.performance.connectionStartTime;
                debugLog(`⚡ Hybrid connection: ${connectionTime.toFixed(1)}ms`);
                updateDebugElement('connection-status', 'Connected');
                updateDebugElement('perf-connection', formatTime(connectionTime));
                addToDebugLog(`🚀 Connected to SSH host - Connection time: ${formatTime(connectionTime)}`);
                
                // Process stream with pure JS
                const reader = response.body.getReader();
                window.terminalHybrid.currentReader = reader;
                
                await processPureStream(reader);
                
            } catch (error) {
                console.error('Pure JS stream error:', error);
                addTerminalOutput('error', `Connection error: ${error.message}`);
                updateDebugElement('connection-status', 'Error');
            } finally {
                window.terminalHybrid.isStreaming = false;
                window.terminalHybrid.currentReader = null;
                updateDebugElement('connection-status', 'Ready');
                
                // Always notify Livewire that command is complete (success or error) - delayed
                setTimeout(() => {
                    if (window.Livewire) {
                        debugLog('📤 Notifying Livewire: stream ended (delayed)');
                        window.Livewire.dispatch('setRunningState', { isRunning: false });
                    }
                }, 200); // 200ms delay for final cleanup
                
                debugLog('✅ Pure JS stream complete');
            }
        }
        
        async function processPureStream(reader) {
            debugLog('📖 Pure JS stream processing...');
            let buffer = '';
            
            try {
                while (true) {
                    const { done, value } = await reader.read();
                    
                    if (done) {
                        debugLog('🏁 Pure JS stream ended');
                        break;
                    }
                    
                    // Process chunk
                    const chunk = new TextDecoder().decode(value);
                    buffer += chunk;
                    
                    // Process complete lines
                    const lines = buffer.split('\n');
                    buffer = lines.pop() || '';
                    
                    for (const line of lines) {
                        if (line.startsWith('data: ')) {
                            try {
                                const eventData = JSON.parse(line.substring(6));
                                debugLog('📡 Pure JS event:', eventData);
                                handlePureStreamEvent(eventData);
                            } catch (e) {
                                debugLog('Non-JSON data:', line);
                            }
                        }
                    }
                }
            } catch (error) {
                console.error('Pure JS stream error:', error);
                addTerminalOutput('error', `Stream error: ${error.message}`);
            }
        }
        
        function handlePureStreamEvent(event) {
            const { type, data, process_id, timestamp } = event;
            
            // Track first byte time
            if (!window.terminalHybrid.performance.firstByteTime && (type === 'output' || type === 'error')) {
                window.terminalHybrid.performance.firstByteTime = performance.now();
                const firstByteDelay = window.terminalHybrid.performance.firstByteTime - window.terminalHybrid.performance.commandStartTime;
                updateDebugElement('first-byte-time', formatTime(firstByteDelay));
                debugLog(`⚡ Pure JS first byte: ${firstByteDelay.toFixed(1)}ms`);
            }
            
            // Handle different event types
            switch (type) {
                case 'output':
                    addTerminalOutput('output', data);
                    break;
                case 'error':
                    addTerminalOutput('error', data);
                    break;
                case 'status':
                    handlePureStatusMessage(data);
                    break;
                case 'complete':
                    handlePureCommandComplete();
                    break;
                default:
                    debugLog('Unknown event type:', type, data);
            }
        }
        
        function addTerminalOutput(type, content) {
            const terminalOutput = document.getElementById('terminal-output');
            if (!terminalOutput) {
                console.error('🚨 Terminal output element not found!');
                return;
            }
            
            debugLog(`💻 Terminal output [${type}]:`, content);
            
            // Only show actual command output in terminal - move status to debug
            if (type === 'output') {
                terminalOutput.textContent += content + '\n';
                // Auto-scroll to bottom
                terminalOutput.scrollTop = terminalOutput.scrollHeight;
                // Update stored content
                window.terminalHybrid.terminalContent += content + '\n';
            } else if (type === 'error') {
                const errorLine = document.createElement('span');
                errorLine.className = 'terminal-error';
                errorLine.textContent = content + '\n';
                terminalOutput.appendChild(errorLine);
                // Auto-scroll to bottom
                terminalOutput.scrollTop = terminalOutput.scrollHeight;
                // Update stored content
                window.terminalHybrid.terminalContent += content + '\n';
            } else if (type === 'status') {
                // Status messages go to debug panel only
                addToDebugLog(content);
            }
        }
        
        function handlePureStatusMessage(message) {
            debugLog('📡 Pure JS Status:', message);
            // Status messages go directly to debug log only (addTerminalOutput already calls addToDebugLog)
            addTerminalOutput('status', message);
            
            // Track execution completion
            if (message.includes('completed') || message.includes('failed')) {
                window.terminalHybrid.performance.executionEndTime = performance.now();
                
                // Calculate performance metrics
                if (window.terminalHybrid.performance.commandStartTime) {
                    const totalTime = window.terminalHybrid.performance.executionEndTime - window.terminalHybrid.performance.commandStartTime;
                    const connectionTime = window.terminalHybrid.performance.firstByteTime - window.terminalHybrid.performance.connectionStartTime;
                    const executionTime = window.terminalHybrid.performance.executionEndTime - (window.terminalHybrid.performance.firstByteTime || window.terminalHybrid.performance.connectionStartTime);
                    
                    updateDebugElement('perf-execution', formatTime(executionTime));
                    updateDebugElement('perf-total', formatTime(totalTime));
                    
                    debugLog(`🎯 Pure JS Performance - Connection: ${formatTime(connectionTime)}, Execution: ${formatTime(executionTime)}, Total: ${formatTime(totalTime)}`);
                }
            }
        }
        
        function handlePureCommandComplete() {
            debugLog('✅ Pure JS Command completed');
            updateDebugElement('connection-status', 'Ready');
            
            // Delay Livewire notification to allow terminal content to settle
            setTimeout(() => {
                if (window.Livewire) {
                    debugLog('📤 Notifying Livewire: command completed (delayed)');
                    window.Livewire.dispatch('setRunningState', { isRunning: false });
                }
            }, 100); // 100ms delay
        }
        
        // UI Helper Functions (simplified - terminal always visible)
        
        function clearTerminal() {
            const terminalOutput = document.getElementById('terminal-output');
            if (terminalOutput) {
                terminalOutput.textContent = '';
                window.terminalHybrid.terminalContent = '';
            }
            
            // Clear debug log for new command execution
            const debugLog = document.getElementById('debug-log');
            if (debugLog) {
                debugLog.innerHTML = '<div class="text-green-400">Hybrid mode ready. Livewire forms + Pure JS terminal.</div>';
            }
            
            // Reset performance stats
            updateDebugElement('perf-connection', '-');
            updateDebugElement('perf-execution', '-');
            updateDebugElement('perf-total', '-');
            updateDebugElement('first-byte-time', '-');
            updateDebugElement('connection-status', 'Connecting...');
        }
        
        function updateDebugElement(elementId, value) {
            const element = document.getElementById(elementId);
            if (element) {
                element.textContent = value;
            }
        }
        
        function addToDebugLog(message) {
            const debugLog = document.getElementById('debug-log');
            if (debugLog) {
                const timestamp = new Date().toLocaleTimeString();
                const logEntry = document.createElement('div');
                logEntry.className = 'text-blue-400 fade-in';
                logEntry.textContent = `[${timestamp}] ${message}`;
                
                requestAnimationFrame(() => {
                    debugLog.appendChild(logEntry);
                    debugLog.scrollTo({
                        top: debugLog.scrollHeight,
                        behavior: 'smooth'
                    });
                });
            }
        }
        
        function formatTime(ms) {
            if (ms < 1000) {
                return `${ms.toFixed(1)}ms`;
            } else {
                return `${(ms / 1000).toFixed(3)}s`;
            }
        }
    </script>
</div>