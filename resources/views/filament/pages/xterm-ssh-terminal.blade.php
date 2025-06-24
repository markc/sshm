<div>
    <style>
        /* Ultra-Fast Xterm.js Styling - Optimized for Performance */
        .xterm-terminal-container {
            contain: strict;
            content-visibility: auto;
            contain-intrinsic-size: 0 600px;
            will-change: transform;
            transform: translateZ(0); /* GPU acceleration */
            backface-visibility: hidden;
        }
        
        .xterm-terminal-wrapper {
            background: #000000;
            border: 2px solid #333;
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.6);
            overflow: hidden;
            position: relative;
        }
        
        .xterm-terminal-header {
            background: linear-gradient(135deg, #2d3748, #1a202c);
            border-bottom: 1px solid #4a5568;
            padding: 12px 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            height: 48px;
        }
        
        .xterm-terminal-title {
            color: #ffffff;
            font-size: 14px;
            font-weight: 600;
            font-family: system-ui, -apple-system, sans-serif;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        
        .xterm-terminal-status {
            font-size: 12px;
            padding: 4px 8px;
            border-radius: 6px;
            font-weight: 500;
        }
        
        .xterm-terminal-status.connected {
            background: rgba(34, 197, 94, 0.2);
            color: #22c55e;
        }
        
        .xterm-terminal-status.disconnected {
            background: rgba(239, 68, 68, 0.2);
            color: #ef4444;
        }
        
        .xterm-terminal-status.connecting {
            background: rgba(59, 130, 246, 0.2);
            color: #3b82f6;
        }
        
        .xterm-terminal-controls {
            display: flex;
            gap: 8px;
            align-items: center;
        }
        
        .xterm-control-button {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            cursor: pointer;
            transition: opacity 0.2s ease;
        }
        
        .xterm-control-button:hover {
            opacity: 0.8;
        }
        
        .xterm-control-button.close { background: #ff5f57; }
        .xterm-control-button.minimize { background: #ffbd2e; }
        .xterm-control-button.maximize { background: #28ca42; }
        
        #xterm-container {
            height: 600px;
            width: 100%;
            padding: 0;
            margin: 0;
        }
        
        /* Performance optimizations for xterm.js */
        .xterm-screen {
            contain: layout style paint;
        }
        
        .xterm-rows {
            contain: layout style;
        }
        
        /* Debug panel styling */
        .xterm-debug-panel {
            background: rgba(0, 0, 0, 0.9);
            border: 1px solid #333;
            border-radius: 8px;
            padding: 16px;
            margin-top: 16px;
            font-family: 'Monaco', 'Menlo', 'Ubuntu Mono', monospace;
            font-size: 12px;
            color: #00ff00;
        }
        
        .xterm-debug-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
        }
        
        .xterm-debug-item {
            display: flex;
            justify-content: space-between;
            padding: 4px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .xterm-debug-label {
            color: #74c0fc;
            font-weight: 500;
        }
        
        .xterm-debug-value {
            color: #00ff00;
            font-weight: 600;
        }
        
        /* Pulse animation for connecting status */
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }
        
        .xterm-terminal-status.connecting {
            animation: pulse 2s infinite;
        }
    </style>

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
                                <div class="xterm-terminal-header">
                                    <div class="xterm-terminal-title">
                                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
                                            <path d="M3 3h18a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2zm0 2v14h18V5H3zm2 2h2v2H5V7zm0 4h2v2H5v-2zm0 4h2v2H5v-2zm4-8h8v2H9V7z"/>
                                        </svg>
                                        SSH Terminal - Ultra-Fast WebSocket
                                        <span id="xterm-status" class="xterm-terminal-status disconnected">Disconnected</span>
                                    </div>
                                    <div class="xterm-terminal-controls">
                                        <span class="xterm-control-button close" onclick="xtermTerminal?.disconnect()"></span>
                                        <span class="xterm-control-button minimize"></span>
                                        <span class="xterm-control-button maximize" onclick="xtermTerminal?.resize()"></span>
                                    </div>
                                </div>
                                
                                <!-- Terminal Display Area -->
                                <div id="xterm-container"></div>
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
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">
                                🚀 Ultra-Fast Performance Metrics
                            </h3>
                            <div class="xterm-debug-panel" wire:ignore>
                                <div class="xterm-debug-grid">
                                    <div>
                                        <div class="xterm-debug-item">
                                            <span class="xterm-debug-label">Terminal Engine:</span>
                                            <span class="xterm-debug-value" id="debug-engine">Xterm.js WebGL</span>
                                        </div>
                                        <div class="xterm-debug-item">
                                            <span class="xterm-debug-label">Connection:</span>
                                            <span class="xterm-debug-value" id="debug-connection">WebSocket</span>
                                        </div>
                                        <div class="xterm-debug-item">
                                            <span class="xterm-debug-label">Session ID:</span>
                                            <span class="xterm-debug-value" id="debug-session">None</span>
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
                                            <span class="xterm-debug-value" id="debug-performance">Ultra-Fast</span>
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

    <!-- Ultra-Fast Xterm.js WebSocket Integration -->
    <script>
        // Since we're including xterm-websocket.js in app.js, it should be available globally
        
        // Global terminal instance
        window.xtermTerminal = null;
        
        // Debug logging
        function debugLog(...args) {
            if ({{ $this->showDebug ? 'true' : 'false' }}) {
                console.log('[XTERM DEBUG]', ...args);
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
            
            // Create terminal instance (XtermWebSocketTerminal should be available globally)
            if (typeof XtermWebSocketTerminal !== 'undefined') {
                window.xtermTerminal = new XtermWebSocketTerminal();
            } else {
                console.error('❌ XtermWebSocketTerminal not available. Check if xterm-websocket.js is loaded.');
                return;
            }
            
            // Mount to container
            const container = document.getElementById('xterm-container');
            if (container) {
                window.xtermTerminal.mount(container);
                debugLog('✅ Terminal mounted to DOM');
            } else {
                console.error('❌ Terminal container not found');
            }
            
            // Update debug info
            updateDebugElement('debug-engine', 'Xterm.js WebGL');
            updateDebugElement('debug-connection', 'WebSocket Ready');
            updateDebugElement('debug-renderer', 'WebGL GPU');
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
                        updateDebugElement('debug-session', window.xtermTerminal.sessionId || 'Active');
                        
                        // Update performance metrics
                        const metrics = window.xtermTerminal.getPerformanceMetrics();
                        if (metrics.connectionStartTime) {
                            const connTime = performance.now() - metrics.connectionStartTime;
                            updateDebugElement('debug-conn-time', `${connTime.toFixed(1)}ms`);
                        }
                        
                        // Notify Livewire of successful connection
                        Livewire.dispatch('updateSessionStatus', {
                            sessionId: window.xtermTerminal.sessionId,
                            connected: true,
                        });
                        
                    }).catch((error) => {
                        updateStatus('disconnected', 'Connection Failed');
                        updateDebugElement('debug-connection', 'Failed');
                        debugLog('❌ Connection failed:', error);
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
                
                // Notify Livewire
                Livewire.dispatch('updateSessionStatus', {
                    sessionId: null,
                    connected: false,
                });
            });
            
            // Clear terminal
            Livewire.on('clear-xterm-terminal', () => {
                debugLog('🧹 Clearing terminal');
                
                if (window.xtermTerminal) {
                    window.xtermTerminal.clear();
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