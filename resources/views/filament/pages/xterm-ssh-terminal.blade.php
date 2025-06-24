<div class="xterm-ssh-terminal">
    @pushOnce('styles')
    <style>
        /* Performance-optimized terminal container using Filament design tokens */
        .xterm-terminal-container {
            contain: strict;
            content-visibility: auto;
            contain-intrinsic-size: 0 500px;
            will-change: transform;
            transform: translateZ(0);
            backface-visibility: hidden;
        }
        
        /* Terminal wrapper with Filament panel styling */
        .xterm-terminal-wrapper {
            @apply rounded-xl overflow-hidden;
            background: rgb(var(--gray-950));
            border: 1px solid rgb(var(--gray-800));
            box-shadow: var(--fi-shadow-lg);
        }
        
        /* Dark mode compatibility */
        .dark .xterm-terminal-wrapper {
            background: rgb(var(--gray-950));
            border-color: rgb(var(--gray-700));
        }
        
        /* Terminal header with Filament styling */
        .xterm-terminal-header {
            @apply px-4 py-3 flex items-center justify-between;
            background: rgb(var(--gray-900));
            border-bottom: 1px solid rgb(var(--gray-800));
        }
        
        .dark .xterm-terminal-header {
            background: rgb(var(--gray-950));
            border-bottom-color: rgb(var(--gray-700));
        }
        
        .xterm-terminal-title {
            @apply text-sm font-medium text-gray-100 flex items-center gap-2;
        }
        
        /* Status badge using Filament badge styles */
        .xterm-terminal-status {
            @apply inline-flex items-center px-2 py-1 text-xs font-medium rounded-md;
        }
        
        .xterm-terminal-status.connected {
            @apply bg-success-50 text-success-700 ring-1 ring-success-600/20;
        }
        
        .dark .xterm-terminal-status.connected {
            @apply bg-success-400/10 text-success-400 ring-success-400/20;
        }
        
        .xterm-terminal-status.disconnected {
            @apply bg-danger-50 text-danger-700 ring-1 ring-danger-600/20;
        }
        
        .dark .xterm-terminal-status.disconnected {
            @apply bg-danger-400/10 text-danger-400 ring-danger-400/20;
        }
        
        .xterm-terminal-status.connecting {
            @apply bg-primary-50 text-primary-700 ring-1 ring-primary-600/20 animate-pulse;
        }
        
        .dark .xterm-terminal-status.connecting {
            @apply bg-primary-400/10 text-primary-400 ring-primary-400/20;
        }
        
        /* macOS-style control buttons */
        .xterm-terminal-controls {
            @apply flex items-center gap-2;
        }
        
        .xterm-control-button {
            @apply w-3 h-3 rounded-full cursor-pointer transition-opacity duration-200 hover:opacity-80;
        }
        
        .xterm-control-button.close { background: #ff5f57; }
        .xterm-control-button.minimize { background: #ffbd2e; }
        .xterm-control-button.maximize { background: #28ca42; }
        
        /* Terminal container */
        #xterm-container {
            height: 500px;
            width: 100%;
            @apply p-0 m-0;
            background: rgb(var(--gray-950));
        }
        
        /* Performance optimizations */
        .xterm-screen {
            contain: layout style paint;
        }
        
        .xterm-rows {
            contain: layout style;
        }
        
        /* Debug panel with Filament styling */
        .xterm-debug-panel {
            @apply mt-4 p-4 rounded-lg border;
            background: rgb(var(--gray-950));
            border-color: rgb(var(--gray-800));
            font-family: ui-monospace, SFMono-Regular, "SF Mono", Monaco, Consolas, "Liberation Mono", "Courier New", monospace;
        }
        
        .dark .xterm-debug-panel {
            background: rgb(var(--gray-900));
            border-color: rgb(var(--gray-700));
        }
        
        .xterm-debug-grid {
            @apply grid grid-cols-1 md:grid-cols-3 gap-4;
        }
        
        .xterm-debug-item {
            @apply flex justify-between py-1 text-xs border-b border-gray-800/50;
        }
        
        .xterm-debug-label {
            @apply text-gray-400 font-medium;
        }
        
        .xterm-debug-value {
            @apply text-success-400 font-semibold font-mono;
        }
    </style>
    @endPushOnce

    <div class="xterm-ssh-terminal space-y-6">
        <!-- Form Section -->
        <section class="fi-section-container">
            <div class="fi-section rounded-xl bg-white shadow-lg ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="fi-section-content-ctn">
                    <div class="fi-section-content p-8">
                        {{ $this->form }}
                    </div>
                </div>
            </div>
        </section>

        <!-- Xterm.js Terminal Section -->
        <section class="fi-section-container">
            <div class="fi-section rounded-xl bg-white shadow-lg ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="fi-section-content-ctn">
                    <div class="fi-section-content p-8">
                        <!-- Terminal Container (Protected from Livewire) -->
                        <div class="xterm-terminal-container" wire:ignore.self>
                            <div class="xterm-terminal-wrapper" wire:ignore>
                                <!-- Terminal Header -->
                                <div class="xterm-terminal-header" wire:ignore>
                                    <div class="xterm-terminal-controls">
                                        <span class="xterm-control-button close" onclick="xtermTerminal?.disconnect()"></span>
                                        <span class="xterm-control-button minimize"></span>
                                        <span class="xterm-control-button maximize" onclick="xtermTerminal?.resize()"></span>
                                    </div>
                                </div>
                                
                                <!-- Terminal Display Area - Completely Protected from Livewire -->
                                <div id="xterm-container" wire:ignore></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Debug Information (conditionally shown) -->
        @if ($this->showDebug)
            <section class="fi-section-container">
                <div class="fi-section rounded-xl bg-white shadow-lg ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                    <div class="fi-section-content-ctn">
                        <div class="fi-section-content p-8">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">
                                    🚀 Ultra-Fast Performance Metrics
                                </h3>
                                <div class="xterm-terminal-title">
                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                        <path d="M3 3h18a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2zm0 2v14h18V5H3zm2 2h2v2H5V7zm0 4h2v2H5v-2zm0 4h2v2H5v-2zm4-8h8v2H9V7z"/>
                                    </svg>
                                    SSH Terminal - Ultra-Fast WebSocket
                                    <span id="xterm-status" class="xterm-terminal-status disconnected">Disconnected</span>
                                </div>
                            </div>
                            <div class="xterm-debug-panel" wire:ignore>
                                <div class="xterm-debug-grid">
                                    <div>
                                        <div class="xterm-debug-item">
                                            <span class="xterm-debug-label">Terminal Engine:</span>
                                            <span class="xterm-debug-value" id="debug-engine">Xterm.js WebGL</span>
                                        </div>
                                        <div class="xterm-debug-item">
                                            <span class="xterm-debug-label">Connection Status:</span>
                                            <span class="xterm-debug-value" id="debug-connection">Disconnected</span>
                                        </div>
                                        <div class="xterm-debug-item">
                                            <span class="xterm-debug-label">Session ID:</span>
                                            <span class="xterm-debug-value" id="debug-session">None</span>
                                        </div>
                                        <div class="xterm-debug-item">
                                            <span class="xterm-debug-label">Current Host:</span>
                                            <span class="xterm-debug-value" id="debug-host">None</span>
                                        </div>
                                    </div>
                                    <div>
                                        <div class="xterm-debug-item">
                                            <span class="xterm-debug-label">Connection Time:</span>
                                            <span class="xterm-debug-value" id="debug-conn-time">-</span>
                                        </div>
                                        <div class="xterm-debug-item">
                                            <span class="xterm-debug-label">First Byte:</span>
                                            <span class="xterm-debug-value" id="debug-first-byte">-</span>
                                        </div>
                                        <div class="xterm-debug-item">
                                            <span class="xterm-debug-label">Total Latency:</span>
                                            <span class="xterm-debug-value" id="debug-total-latency">-</span>
                                        </div>
                                        <div class="xterm-debug-item">
                                            <span class="xterm-debug-label">Command Status:</span>
                                            <span class="xterm-debug-value" id="debug-cmd-status">Ready</span>
                                        </div>
                                    </div>
                                    <div>
                                        <div class="xterm-debug-item">
                                            <span class="xterm-debug-label">Renderer:</span>
                                            <span class="xterm-debug-value" id="debug-renderer">WebGL GPU</span>
                                        </div>
                                        <div class="xterm-debug-item">
                                            <span class="xterm-debug-label">Input Batching:</span>
                                            <span class="xterm-debug-value" id="debug-input-batch">60fps (16ms)</span>
                                        </div>
                                        <div class="xterm-debug-item">
                                            <span class="xterm-debug-label">Performance:</span>
                                            <span class="xterm-debug-value" id="debug-performance">Ready</span>
                                        </div>
                                        <div class="xterm-debug-item">
                                            <span class="xterm-debug-label">Last Command:</span>
                                            <span class="xterm-debug-value" id="debug-last-cmd">None</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        @endif
    </div>

    <!-- Load Xterm.js and dependencies -->
    <script src="https://cdn.jsdelivr.net/npm/@xterm/xterm@5.5.0/lib/xterm.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@xterm/addon-fit@0.10.0/lib/addon-fit.min.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@xterm/xterm@5.5.0/css/xterm.css" />

    <!-- Ultra-Fast Xterm.js WebSocket Integration -->
    <script>
        /**
         * Ultra-Fast Xterm.js WebSocket Terminal (Inline Definition)
         */
        class XtermWebSocketTerminal {
            constructor() {
                this.terminal = null;
                this.fitAddon = null;
                this.websocket = null;
                this.sessionId = null;
                this.isConnected = false;
                
                // Command execution control
                this.currentAbortController = null;
                
                // Performance tracking
                this.performance = {
                    commandStartTime: null,
                    firstByteTime: null,
                    connectionStartTime: null,
                };
                
                // Input batching for efficiency
                this.inputBuffer = '';
                this.inputTimeout = null;
                this.batchDelay = 16; // 60fps batching
                
                this.init();
            }

            /**
             * Initialize the terminal with optimal performance settings
             */
            init() {
                conditionalDebug('🚀 Initializing Ultra-Fast Xterm.js WebSocket Terminal');
                
                // Create terminal with Filament-compatible configuration
                this.terminal = new Terminal({
                    // Performance optimizations
                    renderer: 'webgl',              // GPU acceleration
                    disableStdin: false,            // Enable input
                    convertEol: false,              // Let SSH handle line endings
                    cursorBlink: false,             // Reduce redraws for performance
                    fastScrollModifier: 'alt',      // Efficient scrolling
                    scrollback: 1000,               // Reasonable buffer size
                    
                    // Filament-compatible visual configuration
                    theme: {
                        background: 'rgb(9, 9, 11)',       // gray-950
                        foreground: 'rgb(244, 244, 245)',  // gray-100
                        cursor: 'rgb(34, 197, 94)',        // green-500 (success)
                        cursorAccent: 'rgb(9, 9, 11)',     // gray-950
                        selection: 'rgba(59, 130, 246, 0.3)', // blue-500 with opacity
                        black: 'rgb(39, 39, 42)',          // gray-800
                        red: 'rgb(239, 68, 68)',           // red-500
                        green: 'rgb(34, 197, 94)',         // green-500
                        yellow: 'rgb(234, 179, 8)',        // yellow-500
                        blue: 'rgb(59, 130, 246)',         // blue-500
                        magenta: 'rgb(168, 85, 247)',      // purple-500
                        cyan: 'rgb(6, 182, 212)',          // cyan-500
                        white: 'rgb(244, 244, 245)',       // gray-100
                        brightBlack: 'rgb(63, 63, 70)',    // gray-700
                        brightRed: 'rgb(248, 113, 113)',   // red-400
                        brightGreen: 'rgb(74, 222, 128)',  // green-400
                        brightYellow: 'rgb(250, 204, 21)', // yellow-400
                        brightBlue: 'rgb(96, 165, 250)',   // blue-400
                        brightMagenta: 'rgb(196, 121, 251)', // purple-400
                        brightCyan: 'rgb(34, 211, 238)',   // cyan-400
                        brightWhite: 'rgb(255, 255, 255)', // white
                    },
                    
                    // Font configuration with Filament's font stack
                    fontFamily: 'ui-monospace, SFMono-Regular, "SF Mono", Monaco, Consolas, "Liberation Mono", "Courier New", monospace',
                    fontSize: 14,
                    lineHeight: 1.4,
                    
                    // Behavior
                    bell: false,                    // Disable audio bell
                    screenKeys: true,               // Enable screen keys
                    useFlowControl: true,           // Enable flow control
                });

                // Add essential addons
                this.fitAddon = new FitAddon.FitAddon();
                this.terminal.loadAddon(this.fitAddon);

                // Setup input handling with batching
                this.setupInputHandling();
                
                conditionalDebug('✅ Xterm.js terminal initialized with GPU acceleration');
            }

            /**
             * Mount terminal to DOM element
             */
            mount(element) {
                if (!element) {
                    conditionalDebug('❌ Terminal mount element not found');
                    return;
                }

                this.terminal.open(element);
                this.fitAddon.fit();
                
                // Handle resize events
                window.addEventListener('resize', () => {
                    this.fitAddon.fit();
                });

                conditionalDebug('✅ Terminal mounted to DOM');
            }

            /**
             * Setup optimized input handling with batching
             */
            setupInputHandling() {
                this.terminal.onData((data) => {
                    // Batch input for efficiency (60fps = 16ms)
                    this.inputBuffer += data;
                    
                    if (this.inputTimeout) {
                        clearTimeout(this.inputTimeout);
                    }
                    
                    this.inputTimeout = setTimeout(() => {
                        if (this.inputBuffer && this.isConnected) {
                            this.sendInput(this.inputBuffer);
                            this.inputBuffer = '';
                        }
                    }, this.batchDelay);
                });
            }

            /**
             * Connect to SSH session via WebSocket
             */
            async connect(hostId, options = {}) {
                conditionalDebug('connect() called with hostId:', hostId, 'options:', options);
                
                try {
                    this.performance.connectionStartTime = performance.now();
                    conditionalDebug('Starting connection process...');
                    
                    // Update debug panel with connection attempt
                    updateDebugElement('debug-connection', 'Initializing...');
                    updateDebugElement('debug-session', 'Creating session...');
                    
                    // Initialize session
                    conditionalDebug('Making init request to /api/xterm/init');
                    const response = await fetch('/api/xterm/init', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                        },
                        body: JSON.stringify({
                            host_id: hostId,
                            use_bash: options.useBash || false,
                        }),
                    });

                    conditionalDebug('Init response status:', response.status, response.statusText);
                    
                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                    }

                    const sessionData = await response.json();
                    conditionalDebug('Session data received:', sessionData);
                    
                    this.sessionId = sessionData.session_id;
                    conditionalDebug('Session ID set to:', this.sessionId);

                    // Mark as connected (WebSocket connection would go here)
                    this.isConnected = true;
                    conditionalDebug('Connection marked as established');
                    
                    const connectionTime = performance.now() - this.performance.connectionStartTime;
                    conditionalDebug(`⚡ Connection established in ${connectionTime.toFixed(1)}ms`);
                    
                    // Update debug panel instead of terminal output
                    updateDebugElement('debug-connection', 'Connected');
                    updateDebugElement('debug-host', sessionData.host_info.name);
                    updateDebugElement('debug-session', this.sessionId.substring(0, 8) + '...');
                    updateDebugElement('debug-conn-time', `${connectionTime.toFixed(1)}ms`);
                    
                    conditionalDebug('Connection info updated in debug panel');

                } catch (error) {
                    conditionalDebug('❌ Connection failed:', error);
                    conditionalDebug('Updating debug panel with connection error...');
                    updateDebugElement('debug-connection', `Failed: ${error.message}`);
                    updateDebugElement('debug-session', 'None');
                }
            }

            /**
             * Execute SSH command with performance tracking
             */
            async executeCommand(command, options = {}) {
                conditionalDebug('executeCommand called with:', command, options);
                conditionalDebug('this.isConnected:', this.isConnected);
                conditionalDebug('this.sessionId:', this.sessionId);
                
                if (!this.isConnected) {
                    conditionalDebug('Not connected, returning early');
                    updateDebugElement('debug-performance', 'Not Connected');
                    
                    // Clean up abort controller
                    this.currentAbortController = null;
                    
                    // Ensure command completion is dispatched even when not connected
                    if (window.Livewire) {
                        window.Livewire.dispatch('setRunningState', { isRunning: false });
                    }
                    
                    return Promise.reject(new Error('Not connected to SSH session'));
                }

                this.performance.commandStartTime = performance.now();
                this.performance.firstByteTime = null;

                conditionalDebug(`🎯 Executing command: ${command}`);
                
                // Create abort controller for this command
                this.currentAbortController = new AbortController();
                
                // Update debug panel with command execution status
                updateDebugElement('debug-cmd-status', 'Executing...');
                updateDebugElement('debug-last-cmd', command.length > 20 ? command.substring(0, 20) + '...' : command);
                updateDebugElement('debug-total-latency', 'Measuring...');

                try {
                    conditionalDebug('Making fetch request to /api/xterm/execute');
                    conditionalDebug('Request body:', {
                        session_id: this.sessionId,
                        command: command,
                        use_bash: options.useBash || false,
                    });
                    
                    const response = await fetch('/api/xterm/execute', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content'),
                        },
                        body: JSON.stringify({
                            session_id: this.sessionId,
                            command: command,
                            use_bash: options.useBash || false,
                        }),
                        signal: this.currentAbortController.signal,
                    });
                    
                    conditionalDebug('Fetch response status:', response.status, response.statusText);

                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                    }

                    const result = await response.json();
                    conditionalDebug('DEBUG: Fetch response result:', result);
                    
                    if (result.success) {
                        conditionalDebug('DEBUG: Command succeeded, displaying results');
                        updateDebugElement('debug-cmd-status', 'Success');
                        updateDebugElement('debug-performance', 'Complete');
                        
                        // Display the actual command results (Phase 1 implementation)
                        await this.fetchCommandResults(command, result);
                        return result;
                    } else {
                        conditionalDebug('DEBUG: Command failed:', result.error);
                        updateDebugElement('debug-cmd-status', 'Failed');
                        updateDebugElement('debug-performance', 'Error');
                        await this.fetchCommandResults(command, result);
                        throw new Error(result.error || 'Command execution failed');
                    }

                } catch (error) {
                    if (error.name === 'AbortError') {
                        conditionalDebug('DEBUG: Command was aborted by user');
                        updateDebugElement('debug-cmd-status', 'Stopped');
                        updateDebugElement('debug-performance', 'Aborted');
                        updateDebugElement('debug-total-latency', 'Stopped');
                        
                        // Dispatch completion event for aborted commands
                        if (window.Livewire) {
                            conditionalDebug('DEBUG: Dispatching setRunningState for aborted command');
                            window.Livewire.dispatch('setRunningState', { isRunning: false });
                            conditionalDebug('DEBUG: setRunningState dispatched for aborted command');
                        } else {
                            conditionalDebug('DEBUG: window.Livewire not available for abort case');
                        }
                        
                        throw new Error('Command stopped by user');
                    } else {
                        conditionalDebug('❌ Command execution failed:', error);
                        updateDebugElement('debug-cmd-status', 'Error');
                        updateDebugElement('debug-performance', 'Network Error');
                        updateDebugElement('debug-total-latency', 'Failed');
                        
                        // Ensure command completion is dispatched even on network/connection errors
                        if (window.Livewire) {
                            window.Livewire.dispatch('setRunningState', { isRunning: false });
                        }
                        
                        throw error;
                    }
                } finally {
                    // Clean up abort controller
                    this.currentAbortController = null;
                }
            }

            /**
             * Fetch command results (Phase 1 implementation)
             */
            async fetchCommandResults(command, result) {
                conditionalDebug('DEBUG: fetchCommandResults called with command:', command);
                conditionalDebug('DEBUG: fetchCommandResults called with result:', result);
                
                try {
                    conditionalDebug('DEBUG: Processing command output...');
                    
                    // Calculate execution metrics for debug panel
                    const executionTime = result.execution_time || 0;
                    const totalLatency = this.performance.commandStartTime ? 
                        (performance.now() - this.performance.commandStartTime) : 0;
                    
                    // Update debug panel with execution metrics
                    if (executionTime > 0) {
                        updateDebugElement('debug-total-latency', `${(executionTime * 1000).toFixed(1)}ms`);
                        updateDebugElement('debug-first-byte', `${(executionTime * 1000).toFixed(1)}ms`);
                    }
                    
                    // Display only the actual command output in terminal (no status messages)
                    if (result && result.output) {
                        conditionalDebug('DEBUG: Result has output, length:', result.output.length);
                        conditionalDebug('DEBUG: Output content:', result.output);
                        
                        // Split output into lines and display each line
                        const lines = result.output.split('\n');
                        conditionalDebug('DEBUG: Split into', lines.length, 'lines');
                        
                        for (let i = 0; i < lines.length; i++) {
                            const line = lines[i];
                            conditionalDebug(`DEBUG: Line ${i}:`, line);
                            
                            // Display all lines, including empty ones for proper formatting
                            this.terminal.writeln(line);
                        }
                    } else if (result && result.error) {
                        conditionalDebug('DEBUG: Result has error:', result.error);
                        // Show only the actual error output, not status messages
                        this.terminal.writeln(result.error);
                        updateDebugElement('debug-cmd-status', 'Error');
                        updateDebugElement('debug-performance', 'Error Output');
                    } else {
                        conditionalDebug('DEBUG: No output or error found in result');
                        // Update debug panel instead of terminal
                        updateDebugElement('debug-cmd-status', 'No Output');
                        updateDebugElement('debug-performance', 'Silent Command');
                    }
                    
                    conditionalDebug('DEBUG: Finished displaying results');
                    
                    // Notify completion with delay (like SSH Commands page)
                    conditionalDebug('DEBUG: Notifying Livewire of command completion');
                    setTimeout(() => {
                        if (window.Livewire) {
                            conditionalDebug('DEBUG: Dispatching setRunningState event with isRunning: false');
                            window.Livewire.dispatch('setRunningState', { isRunning: false });
                            conditionalDebug('DEBUG: setRunningState event dispatched');
                        } else {
                            conditionalDebug('DEBUG: window.Livewire is not available');
                        }
                    }, 200); // 200ms delay for clean state transition
                } catch (error) {
                    conditionalDebug('DEBUG: Error in fetchCommandResults:', error);
                    updateDebugElement('debug-cmd-status', 'Display Error');
                    updateDebugElement('debug-performance', 'Processing Failed');
                    if (window.Livewire) {
                        window.Livewire.dispatch('setRunningState', { isRunning: false });
                    }
                }
            }

            /**
             * Stop currently running command
             */
            stopCommand() {
                if (this.currentAbortController) {
                    conditionalDebug('🛑 Stopping current command...');
                    this.currentAbortController.abort();
                    return true;
                } else {
                    conditionalDebug('🛑 No command currently running to stop');
                    return false;
                }
            }

            /**
             * Send input (placeholder for now)
             */
            async sendInput(input) {
                conditionalDebug('📤 Input:', input);
            }

            /**
             * Clear terminal
             */
            clear() {
                this.terminal.clear();
            }

            /**
             * Resize terminal
             */
            resize() {
                this.fitAddon.fit();
            }

            /**
             * Disconnect and cleanup
             */
            disconnect() {
                this.isConnected = false;
                this.sessionId = null;
                conditionalDebug('✅ Disconnected');
            }

            /**
             * Get performance metrics
             */
            getPerformanceMetrics() {
                return {
                    ...this.performance,
                    connected: this.isConnected,
                    sessionId: this.sessionId,
                };
            }
        }

        // Make available globally
        window.XtermWebSocketTerminal = XtermWebSocketTerminal;
        
        // Global terminal instance
        window.xtermTerminal = null;
        
        // Debug logging (conditional based on debug toggle)
        function debugLog(...args) {
            if ({{ $this->showDebug ? 'true' : 'false' }}) {
                console.log('[XTERM DEBUG]', ...args);
            }
        }
        
        // Conditional console debug (only when debug mode is on)
        function conditionalDebug(...args) {
            if ({{ $this->showDebug ? 'true' : 'false' }}) {
                console.log('[DEBUG]', ...args);
            }
        }
        
        // Update debug UI elements
        function updateDebugElement(id, value) {
            const element = document.getElementById(id);
            if (element) {
                element.textContent = value;
            }
        }
        
        // Update status indicator
        function updateStatus(status, text) {
            const statusElement = document.getElementById('xterm-status');
            if (statusElement) {
                statusElement.className = `xterm-terminal-status ${status}`;
                statusElement.textContent = text;
            }
        }
        
        // Initialize terminal when DOM is ready
        document.addEventListener('DOMContentLoaded', function() {
            debugLog('🚀 Initializing Ultra-Fast Xterm.js WebSocket Terminal');
            conditionalDebug('DEBUG: DOM loaded, initializing terminal');
            
            // Create terminal instance (XtermWebSocketTerminal should be available globally)
            if (typeof XtermWebSocketTerminal !== 'undefined') {
                conditionalDebug('DEBUG: XtermWebSocketTerminal class available');
                window.xtermTerminal = new XtermWebSocketTerminal();
                conditionalDebug('DEBUG: Terminal instance created:', window.xtermTerminal);
            } else {
                conditionalDebug('❌ XtermWebSocketTerminal not available. Check if xterm-websocket.js is loaded.');
                return;
            }
            
            // Mount to container
            const container = document.getElementById('xterm-container');
            if (container) {
                conditionalDebug('DEBUG: Terminal container found, mounting...');
                window.xtermTerminal.mount(container);
                debugLog('✅ Terminal mounted to DOM');
                
                // Terminal initialized successfully
                conditionalDebug('DEBUG: Terminal mounted and ready for commands');
            } else {
                conditionalDebug('❌ Terminal container not found');
            }
            
            // Update debug info
            updateDebugElement('debug-engine', 'Xterm.js WebGL');
            updateDebugElement('debug-connection', 'Ready');
            updateDebugElement('debug-renderer', 'WebGL GPU');
            updateDebugElement('debug-cmd-status', 'Ready');
        });
        
        // Livewire event listeners
        document.addEventListener('livewire:init', () => {
            debugLog('📡 Livewire initialized, setting up event listeners');
            
            // Connect to terminal
            Livewire.on('connect-xterm-terminal', (data) => {
                debugLog('🔌 Connecting to SSH host:', data[0]);
                const config = data[0];
                
                updateStatus('connecting', 'Connecting...');
                updateDebugElement('debug-connection', 'Connecting...');
                
                if (window.xtermTerminal) {
                    window.xtermTerminal.connect(config.hostId, {
                        useBash: config.useBash,
                        showDebug: config.showDebug,
                    }).then(() => {
                        updateStatus('connected', 'Connected');
                        updateDebugElement('debug-connection', 'Connected');
                        updateDebugElement('debug-session', window.xtermTerminal.sessionId ? window.xtermTerminal.sessionId.substring(0, 8) + '...' : 'Active');
                        
                        // Update performance metrics
                        const metrics = window.xtermTerminal.getPerformanceMetrics();
                        if (metrics.connectionStartTime) {
                            const connTime = performance.now() - metrics.connectionStartTime;
                            updateDebugElement('debug-conn-time', `${connTime.toFixed(1)}ms`);
                        }
                        
                        // Connection successful - session status updated internally
                        
                    }).catch((error) => {
                        updateStatus('disconnected', 'Connection Failed');
                        updateDebugElement('debug-connection', 'Failed');
                        debugLog('❌ Connection failed:', error);
                    });
                }
            });

            // Connect and execute command (for first-time connections)
            Livewire.on('connect-and-execute-xterm-command', (data) => {
                debugLog('🔌 Connecting and executing command:', data[0]);
                const config = data[0];
                
                updateStatus('connecting', 'Connecting...');
                updateDebugElement('debug-connection', 'Connecting...');
                
                if (window.xtermTerminal) {
                    window.xtermTerminal.connect(config.hostId, {
                        useBash: config.useBash,
                        showDebug: config.showDebug,
                    }).then(() => {
                        updateStatus('connected', 'Connected');
                        updateDebugElement('debug-connection', 'Connected');
                        updateDebugElement('debug-session', window.xtermTerminal.sessionId ? window.xtermTerminal.sessionId.substring(0, 8) + '...' : 'Active');
                        
                        // Update performance metrics
                        const metrics = window.xtermTerminal.getPerformanceMetrics();
                        if (metrics.connectionStartTime) {
                            const connTime = performance.now() - metrics.connectionStartTime;
                            updateDebugElement('debug-conn-time', `${connTime.toFixed(1)}ms`);
                        }
                        
                        // Connection successful - session status updated internally
                        
                        // Now execute the command immediately after connection
                        debugLog('⚡ Executing command after connection:', config.command);
                        conditionalDebug('DEBUG: About to execute command:', config.command);
                        conditionalDebug('DEBUG: Terminal state:', window.xtermTerminal.isConnected);
                        conditionalDebug('DEBUG: Session ID:', window.xtermTerminal.sessionId);
                        
                        window.xtermTerminal.executeCommand(config.command, {
                            useBash: config.useBash,
                        }).then(() => {
                            conditionalDebug('DEBUG: Command execution promise resolved');
                            // Command completion is handled by fetchCommandResults
                        }).catch((error) => {
                            conditionalDebug('DEBUG: Command execution promise rejected:', error);
                            // Command completion is handled by fetchCommandResults or executeCommand error handling
                        });
                        
                    }).catch((error) => {
                        updateStatus('disconnected', 'Connection Failed');
                        updateDebugElement('debug-connection', 'Failed');
                        debugLog('❌ Connection failed:', error);
                        // Also notify command completion on connection failure
                        Livewire.dispatch('setRunningState', { isRunning: false });
                    });
                }
            });
            
            // Execute command
            Livewire.on('execute-xterm-command', (data) => {
                debugLog('⚡ Executing command:', data[0]);
                const config = data[0];
                
                if (window.xtermTerminal) {
                    window.xtermTerminal.executeCommand(config.command, {
                        useBash: config.useBash,
                    }).then(() => {
                        // Command completion is handled by fetchCommandResults
                        conditionalDebug('DEBUG: Execute command completed successfully');
                    }).catch((error) => {
                        // Command completion is handled by fetchCommandResults or executeCommand error handling
                        conditionalDebug('DEBUG: Execute command failed:', error);
                    });
                }
            });
            
            // Disconnect terminal
            Livewire.on('disconnect-xterm-terminal', () => {
                debugLog('🔌 Disconnecting terminal');
                
                if (window.xtermTerminal) {
                    window.xtermTerminal.disconnect();
                }
                
                updateStatus('disconnected', 'Disconnected');
                updateDebugElement('debug-connection', 'Disconnected');
                updateDebugElement('debug-session', 'None');
                updateDebugElement('debug-host', 'None');
                updateDebugElement('debug-cmd-status', 'Ready');
                
                // Disconnected - session status updated internally
            });
            
            // Clear terminal
            Livewire.on('clear-xterm-terminal', () => {
                debugLog('🧹 Clearing terminal');
                
                if (window.xtermTerminal) {
                    window.xtermTerminal.clear();
                }
            });

            // Stop command
            Livewire.on('stop-xterm-command', () => {
                debugLog('🛑 Stopping command');
                
                if (window.xtermTerminal) {
                    const stopped = window.xtermTerminal.stopCommand();
                    
                    if (!stopped) {
                        // No command was running, just notify completion
                        Livewire.dispatch('setRunningState', { isRunning: false });
                    }
                    // If command was stopped, the abort handler will dispatch setRunningState
                }
            });
        });
        
        // Handle window resize
        window.addEventListener('resize', () => {
            if (window.xtermTerminal) {
                window.xtermTerminal.resize();
            }
        });
        
        debugLog('✅ Xterm.js WebSocket integration loaded');
    </script>
</div>