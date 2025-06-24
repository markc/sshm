<div>
    <style>
        /* FOUC Prevention & Base Optimizations */
        .ssh-runner-container {
            opacity: 0;
            transition: opacity 300ms ease-in-out;
            contain: layout style paint;
        }
        
        .ssh-runner-container.loaded {
            opacity: 1;
        }
        
        /* Enhanced Section Containers with CSS Containment */
        .fi-section-container { 
            margin-bottom: 1.5rem !important; 
            margin-top: 1.5rem !important;
            contain: layout style;
        }
        
        /* Terminal Output Optimizations */
        .terminal-container {
            contain: strict;
            content-visibility: auto;
            contain-intrinsic-size: 0 384px; /* h-96 equivalent */
            will-change: transform;
        }
        
        #terminal-output {
            transform: translateZ(0); /* GPU acceleration */
            backface-visibility: hidden;
            scroll-behavior: smooth;
            content-visibility: auto;
        }
        
        /* Smooth Transitions for State Changes */
        .terminal-section {
            opacity: 0;
            transform: translateY(10px);
            transition: opacity 300ms ease-out, transform 300ms ease-out;
        }
        
        .terminal-section.visible {
            opacity: 1;
            transform: translateY(0);
        }
        
        /* Terminal Output Styling with Performance */
        .terminal-output { 
            color: #ffffff; 
            line-height: 1.4;
            text-rendering: optimizeSpeed;
        }
        .terminal-err { 
            color: #ff6b6b; 
            font-weight: 500;
        }
        .terminal-status { 
            color: #74c0fc; 
            font-style: italic;
        }
        
        /* Optimized Scrollbar */
        #terminal-output::-webkit-scrollbar { 
            width: 8px; 
        }
        #terminal-output::-webkit-scrollbar-track { 
            background: #2d3748; 
        }
        #terminal-output::-webkit-scrollbar-thumb { 
            background: #4a5568; 
            border-radius: 4px;
            transition: background-color 150ms ease;
        }
        #terminal-output::-webkit-scrollbar-thumb:hover { 
            background: #718096; 
        }
        
        /* Performance Indicator with Smooth Transitions */
        .performance-indicator { 
            font-family: monospace; 
            font-size: 0.75rem; 
            padding: 4px 8px; 
            border-radius: 4px;
            opacity: 0;
            transform: translateY(-5px);
            transition: opacity 250ms ease, transform 250ms ease;
        }
        
        .performance-indicator.visible {
            opacity: 1;
            transform: translateY(0);
        }
        
        .performance-fast { 
            background-color: #065f46; 
            color: #10b981; 
        }
        .performance-medium { 
            background-color: #92400e; 
            color: #f59e0b; 
        }
        .performance-slow { 
            background-color: #7f1d1d; 
            color: #ef4444; 
        }
        
        /* Loading Skeleton */
        .terminal-loading {
            background: linear-gradient(90deg, #374151 25%, #4b5563 50%, #374151 75%);
            background-size: 200% 100%;
            animation: skeleton-loading 2s infinite;
            border-radius: 0.375rem;
            height: 2rem;
            margin: 0.5rem 0;
        }
        
        @keyframes skeleton-loading {
            0% { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }
        
        /* Fade-in animations for content */
        .fade-in {
            animation: fadeIn 300ms ease-out forwards;
        }
        
        @keyframes fadeIn {
            from { 
                opacity: 0; 
                transform: translateY(10px); 
            }
            to { 
                opacity: 1; 
                transform: translateY(0); 
            }
        }
        
        /* Respect reduced motion preference */
        @media (prefers-reduced-motion: reduce) {
            .ssh-runner-container,
            .terminal-section,
            .performance-indicator,
            .fade-in {
                transition: none;
                animation: none;
            }
        }
    </style>
    
    <div class="ssh-runner-container space-y-6" id="ssh-runner-main">
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
        <section 
            class="fi-section-container terminal-section" 
            id="terminal-section" 
            style="display: block;"
            wire:ignore.self
            x-data="{ preserveContent: true }"
            x-init="$el._morphIgnore = true"
        >
            <div class="fi-section rounded-xl bg-white shadow-lg ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="fi-section-content-ctn">
                    <div class="fi-section-content p-8">
                        <div class="flex justify-between items-center mb-4">
                            <div id="performance-indicator" class="performance-indicator">
                                Connection: <span id="perf-connection">-</span> | 
                                Execution: <span id="perf-execution">-</span> | 
                                Total: <span id="perf-total">-</span>
                            </div>
                        </div>
                        
                        <!-- Terminal Container with Optimizations -->
                        <div 
                            class="terminal-container" 
                            wire:ignore.self
                            x-data="{ terminalProtected: true }"
                            x-init="$el._morphIgnore = true; $el.setAttribute('data-livewire-ignore', 'true')"
                        >
                            <div id="terminal-loading" class="terminal-loading" style="display: none;">
                                <div class="flex items-center space-x-2">
                                    <div class="w-2 h-2 bg-blue-400 rounded-full animate-pulse"></div>
                                    <span class="text-gray-400 text-sm">Connecting to SSH host...</span>
                                </div>
                            </div>
                            
                            <pre 
                                id="terminal-output" 
                                class="block w-full p-6 border border-gray-300 dark:border-gray-700 rounded-lg bg-gray-900 text-green-400 text-sm font-mono overflow-x-auto whitespace-pre h-96 overflow-y-auto"
                                aria-live="polite"
                                aria-label="SSH Command Output"
                            ></pre>
                        </div>
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

    <!-- Ultra-Optimized JavaScript with FOUC Prevention & Smooth Animations -->
    <script>
        console.log('🚀 SSHM Ultra-Optimized - Production Mode');
        
        // Performance tracking and optimization variables
        window.sshPerformance = {
            commandStartTime: null,
            connectionStartTime: null,
            firstByteTime: null,
            executionStartTime: null,
            executionEndTime: null,
            animationFrameId: null,
            updateQueue: []
        };
        
        // Terminal content storage with optimizations
        window.terminalContent = window.terminalContent || '';
        window.currentEventSource = null;
        window.currentStreamReader = null;
        window.isStreamActive = false;
        window.intersectionObserver = null;
        
        // FOUC Prevention & Smooth Loading
        function initializeSmoothUI() {
            const container = document.getElementById('ssh-runner-main');
            const terminalSection = document.querySelector('.terminal-section');
            
            if (container) {
                // Remove FOUC by showing content smoothly
                requestAnimationFrame(() => {
                    container.classList.add('loaded');
                });
            }
            
            // Initialize Intersection Observer for performance
            if ('IntersectionObserver' in window) {
                window.intersectionObserver = new IntersectionObserver(
                    (entries) => {
                        entries.forEach(entry => {
                            if (entry.isIntersecting) {
                                entry.target.classList.add('visible');
                            }
                        });
                    },
                    { threshold: 0.1, rootMargin: '20px' }
                );
                
                // Observe terminal section for smooth reveal
                if (terminalSection) {
                    window.intersectionObserver.observe(terminalSection);
                }
            }
        }
        
        // Optimized DOM manipulation with batching
        function batchDOMUpdates(callback) {
            if (window.sshPerformance.animationFrameId) {
                cancelAnimationFrame(window.sshPerformance.animationFrameId);
            }
            
            window.sshPerformance.animationFrameId = requestAnimationFrame(() => {
                callback();
                window.sshPerformance.animationFrameId = null;
            });
        }
        
        // Enhanced performance indicator with smooth transitions
        function showPerformanceIndicator() {
            const indicator = document.getElementById('performance-indicator');
            if (indicator) {
                batchDOMUpdates(() => {
                    indicator.classList.add('visible');
                });
            }
        }
        
        function hidePerformanceIndicator(delay = 3000) {
            setTimeout(() => {
                const indicator = document.getElementById('performance-indicator');
                if (indicator) {
                    batchDOMUpdates(() => {
                        indicator.classList.remove('visible');
                    });
                }
            }, delay);
        }
        
        // Initialize on DOM ready with FOUC prevention
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM ready - Ultra-optimized SSH Runner initializing...');
            
            // Initialize smooth UI immediately to prevent FOUC
            initializeSmoothUI();
            
            // Update debug info with smooth transition
            setTimeout(() => {
                updateDebugElement('connection-method', 'Server-Sent Events (Ultra-Optimized)');
                updateDebugElement('connection-status', 'Ready');
            }, 100);
        });
        
        // Enhanced Livewire integration with optimizations
        document.addEventListener('livewire:init', () => {
            console.log('Livewire initialized - Enhanced performance mode active');
            
            // Aggressive DOM protection against Livewire morphing
            window.Livewire.hook('morph.updating', ({ el, toEl, childrenOnly, skip }) => {
                // Protect terminal elements from morphing
                const terminalSection = el.querySelector('#terminal-section');
                const terminalContainer = el.querySelector('.terminal-container');
                const terminalOutput = el.querySelector('#terminal-output');
                
                if (terminalSection) {
                    console.log('🛡️ Protecting terminal section from Livewire morph');
                    skip();
                    return false;
                }
                
                if (terminalContainer || terminalOutput) {
                    console.log('🛡️ Protecting terminal container from Livewire morph');
                    skip();
                    return false;
                }
            });
            
            window.Livewire.hook('morph.updated', ({ el, component }) => {
                console.log('🔄 Livewire morph detected, preserving terminal content');
                
                // Ensure critical elements remain protected
                batchDOMUpdates(() => {
                    const terminalSection = document.getElementById('terminal-section');
                    const terminalContainer = document.querySelector('.terminal-container');
                    const terminalOutput = document.getElementById('terminal-output');
                    
                    // Force visibility and protection
                    if (terminalSection) {
                        terminalSection.style.display = 'block';
                        terminalSection.classList.add('visible');
                        terminalSection.setAttribute('data-livewire-ignore', 'true');
                        terminalSection._morphIgnore = true;
                    }
                    
                    if (terminalContainer) {
                        terminalContainer.setAttribute('data-livewire-ignore', 'true');
                        terminalContainer._morphIgnore = true;
                    }
                    
                    if (terminalOutput) {
                        terminalOutput.setAttribute('data-livewire-ignore', 'true');
                        terminalOutput._morphIgnore = true;
                        restoreTerminalContent();
                    }
                });
            });
            
            // Enhanced SSH stream handling with smooth animations
            Livewire.on('start-ssh-stream', (data) => {
                console.log('Starting ultra-optimized SSH stream:', data);
                
                // Show terminal section with smooth animation
                const terminalSection = document.getElementById('terminal-section');
                if (terminalSection) {
                    batchDOMUpdates(() => {
                        terminalSection.style.display = 'block';
                        setTimeout(() => {
                            terminalSection.classList.add('visible');
                        }, 50);
                    });
                }
                
                // Update Livewire component to show terminal output
                if (window.Livewire) {
                    window.Livewire.dispatch('setTerminalVisibility', { visible: true });
                }
                
                startSshStream(data[0]);
            });
        });
        
        // Ultra-optimized SSH streaming with smooth animations
        function startSshStream(config) {
            const { process_id, command, host_id, use_bash } = config;
            
            // Force reset stream state if stuck
            if (window.isStreamActive) {
                console.log('⚠️ Stream appears stuck, forcing reset...');
                forceStreamReset();
            }
            
            // Stop any existing stream
            if (window.currentStreamReader) {
                console.log('🛑 Stopping previous stream...');
                try {
                    window.currentStreamReader.cancel();
                } catch (e) {
                    console.log('Stream reader already closed');
                }
                window.currentStreamReader = null;
            }
            
            window.isStreamActive = true;
            
            // Performance tracking
            window.sshPerformance.commandStartTime = performance.now();
            window.sshPerformance.connectionStartTime = performance.now();
            
            // Show loading state with smooth animation
            showLoadingState();
            
            // Clear terminal with fade animation
            clearTerminalSmooth();
            
            // Update debug info with smooth transitions
            batchDOMUpdates(() => {
                updateDebugElement('process-id', process_id);
                updateDebugElement('connection-status', 'Connecting...');
                updateDebugElement('performance-mode', 'Ultra-Optimized Mode');
            });
            
            // Show performance indicator with animation
            showPerformanceIndicator();
            updatePerformanceIndicator('Connecting...', '-', '-');
            
            // Close existing EventSource if any
            if (window.currentEventSource) {
                window.currentEventSource.close();
            }
            
            // Get CSRF token from Livewire
            const csrfToken = document.querySelector('input[name="_token"]')?.value || 
                             document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || 
                             @js(csrf_token());
            
            // Create optimized fetch request
            const formData = new FormData();
            formData.append('command', command);
            formData.append('host_id', host_id);
            formData.append('use_bash', use_bash ? '1' : '0');
            formData.append('_token', csrfToken);
            
            // Enhanced fetch with optimizations
            fetch('/api/ssh/stream', {
                method: 'POST',
                body: formData,
                headers: {
                    'Accept': 'text/event-stream',
                    'Cache-Control': 'no-cache',
                    'X-CSRF-TOKEN': csrfToken,
                },
                keepalive: true // Optimize connection handling
            })
            .then(response => {
                console.log('📡 SSH Stream Response:', response.status, response.statusText);
                
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                
                // Hide loading and show terminal with smooth transition
                hideLoadingState();
                
                // Track connection establishment time
                const connectionTime = performance.now() - window.sshPerformance.connectionStartTime;
                console.log(`🚀 Ultra-fast connection: ${connectionTime.toFixed(1)}ms`);
                
                batchDOMUpdates(() => {
                    updateDebugElement('connection-status', 'Connected');
                    updatePerformanceIndicator(formatTime(connectionTime), '-', '-');
                });
                
                // Start optimized stream reading
                const reader = response.body.getReader();
                window.currentStreamReader = reader;
                console.log('📖 Starting stream reader...');
                return readStreamOptimized(reader, process_id);
            })
            .catch(error => {
                console.error('SSH stream error:', error);
                hideLoadingState();
                addTerminalOutputSmooth('error', `Connection error: ${error.message}`);
                updateDebugElement('connection-status', 'Error');
                resetPerformanceTracking();
                
                // Clean up stream state on error
                window.isStreamActive = false;
                window.currentStreamReader = null;
            });
        }
        
        // Enhanced loading state management
        function showLoadingState() {
            const loadingEl = document.getElementById('terminal-loading');
            const terminalEl = document.getElementById('terminal-output');
            
            if (loadingEl && terminalEl) {
                batchDOMUpdates(() => {
                    loadingEl.style.display = 'block';
                    loadingEl.classList.add('fade-in');
                    terminalEl.style.opacity = '0.3';
                });
            }
        }
        
        function hideLoadingState() {
            const loadingEl = document.getElementById('terminal-loading');
            const terminalEl = document.getElementById('terminal-output');
            
            if (loadingEl && terminalEl) {
                batchDOMUpdates(() => {
                    loadingEl.style.display = 'none';
                    loadingEl.classList.remove('fade-in');
                    terminalEl.style.opacity = '1';
                });
            }
        }
        
        // Ultra-optimized stream reading with batching and performance
        async function readStreamOptimized(reader, processId) {
            let buffer = '';
            let updateQueue = [];
            let lastUpdateTime = performance.now();
            const batchInterval = 16; // ~60fps updates
            const streamTimeout = 30000; // 30 second timeout
            const startTime = performance.now();
            
            // Start update batching loop
            const updateLoop = () => {
                if (updateQueue.length > 0) {
                    console.log(`⚡ Processing ${updateQueue.length} queued updates`);
                    const currentBatch = updateQueue.splice(0, updateQueue.length);
                    
                    batchDOMUpdates(() => {
                        currentBatch.forEach((update, index) => {
                            console.log(`🔧 Executing queued update ${index + 1}`);
                            update();
                        });
                    });
                }
                
                if (!window.sshPerformance.streamEnded) {
                    requestAnimationFrame(updateLoop);
                }
            };
            
            window.sshPerformance.streamEnded = false;
            requestAnimationFrame(updateLoop);
            
            try {
                while (true) {
                    // Check for timeout
                    if (performance.now() - startTime > streamTimeout) {
                        console.log('⏰ Stream timeout reached, terminating...');
                        addTerminalOutputSmooth('error', 'Stream timeout after 30 seconds');
                        break;
                    }
                    
                    const { done, value } = await reader.read();
                    
                    if (done) {
                        console.log('🏁 Stream ended - Ultra-optimized mode');
                        window.sshPerformance.streamEnded = true;
                        break;
                    }
                    
                    // Convert bytes to text with optimization
                    const chunk = new TextDecoder().decode(value);
                    buffer += chunk;
                    
                    // Process complete lines with batching
                    const lines = buffer.split('\n');
                    buffer = lines.pop() || '';
                    
                    for (const line of lines) {
                        if (line.startsWith('data: ')) {
                            try {
                                const eventData = JSON.parse(line.substring(6));
                                console.log('📡 Stream event received:', eventData);
                                
                                // Queue update for smooth batching
                                console.log(`📥 Queuing ${eventData.type} event for processing`);
                                updateQueue.push(() => handleStreamEventOptimized(eventData));
                                
                            } catch (e) {
                                console.log('❌ Non-JSON data:', line);
                            }
                        } else if (line.trim()) {
                            console.log('📦 Raw stream data:', line);
                        }
                    }
                    
                    // Process updates immediately if queue is getting large
                    const now = performance.now();
                    if (updateQueue.length > 3 || now - lastUpdateTime > batchInterval) {
                        lastUpdateTime = now;
                        
                        // Process current queue immediately
                        if (updateQueue.length > 0) {
                            console.log(`🚀 Immediate processing ${updateQueue.length} updates`);
                            const immediateBatch = updateQueue.splice(0, updateQueue.length);
                            immediateBatch.forEach((update, index) => {
                                console.log(`⚡ Immediate update ${index + 1}`);
                                update();
                            });
                        }
                        
                        await new Promise(resolve => requestAnimationFrame(resolve));
                    }
                }
            } catch (error) {
                console.error('Stream reading error:', error);
                addTerminalOutputSmooth('error', `Stream error: ${error.message}`);
            } finally {
                // Process any remaining queued updates before ending
                if (updateQueue.length > 0) {
                    console.log(`🔄 Processing final ${updateQueue.length} queued updates`);
                    const finalBatch = updateQueue.splice(0, updateQueue.length);
                    finalBatch.forEach((update, index) => {
                        console.log(`🔧 Final update ${index + 1}`);
                        update();
                    });
                }
                
                // Clean up stream state
                window.sshPerformance.streamEnded = true;
                window.isStreamActive = false;
                window.currentStreamReader = null;
                
                batchDOMUpdates(() => {
                    resetPerformanceTracking();
                    updateDebugElement('connection-status', 'Ready');
                    hidePerformanceIndicator();
                });
                
                console.log('🏁 Stream cleanup complete');
            }
        }
        
        // Ultra-optimized event handling with smooth animations
        function handleStreamEventOptimized(event) {
            const { type, data, process_id, timestamp } = event;
            console.log(`🎯 Processing event [${type}]:`, data);
            
            // Track first byte time with precision
            if (!window.sshPerformance.firstByteTime && (type === 'output' || type === 'error')) {
                window.sshPerformance.firstByteTime = performance.now();
                const firstByteDelay = window.sshPerformance.firstByteTime - window.sshPerformance.commandStartTime;
                updateDebugElement('first-byte-time', formatTime(firstByteDelay));
                console.log(`⚡ First byte: ${firstByteDelay.toFixed(1)}ms`);
            }
            
            // Track execution start
            if (!window.sshPerformance.executionStartTime && type === 'status' && data.includes('Executing')) {
                window.sshPerformance.executionStartTime = performance.now();
            }
            
            // Handle different event types with smooth transitions
            switch (type) {
                case 'output':
                    addTerminalOutputSmooth('out', data);
                    break;
                case 'error':
                    addTerminalOutputSmooth('err', data);
                    break;
                case 'status':
                    handleStatusMessageOptimized(data);
                    break;
                case 'complete':
                    handleCommandCompleteOptimized();
                    break;
                default:
                    console.log('Unknown event type:', type, data);
            }
        }
        
        // Optimized terminal output with smooth animations and virtual scrolling
        function addTerminalOutputSmooth(type, content) {
            const terminalOutput = document.getElementById('terminal-output');
            if (!terminalOutput) {
                console.error('🚨 Terminal output element not found!');
                return;
            }
            
            console.log(`📝 Adding terminal output [${type}]:`, content);
            
            // Check for virtual scrolling threshold (1000+ lines)
            const lineCount = (window.terminalContent.match(/\n/g) || []).length;
            if (lineCount > 1000) {
                implementVirtualScrolling(terminalOutput);
            }
            
            // Create smooth content addition
            const fragment = document.createDocumentFragment();
            
            if (type === 'out') {
                window.terminalContent += content + '\n';
                const textNode = document.createTextNode(content + '\n');
                fragment.appendChild(textNode);
            } else if (type === 'err') {
                window.terminalContent += content + '\n';
                const errorSpan = document.createElement('span');
                errorSpan.className = 'terminal-err fade-in';
                errorSpan.textContent = content + '\n';
                fragment.appendChild(errorSpan);
            }
            
            // Smooth append with RAF for performance
            requestAnimationFrame(() => {
                terminalOutput.appendChild(fragment);
                
                // Smooth auto-scroll with easing
                if (terminalOutput.scrollHeight > terminalOutput.clientHeight) {
                    terminalOutput.scrollTo({
                        top: terminalOutput.scrollHeight,
                        behavior: 'smooth'
                    });
                }
            });
        }
        
        // Virtual scrolling implementation for large outputs
        function implementVirtualScrolling(terminalEl) {
            if (terminalEl.dataset.virtualScrolling === 'true') return;
            
            terminalEl.dataset.virtualScrolling = 'true';
            console.log('📜 Virtual scrolling activated for performance');
            
            // Keep only last 500 visible lines for performance
            const lines = terminalEl.textContent.split('\n');
            if (lines.length > 1000) {
                const keepLines = lines.slice(-500);
                terminalEl.textContent = keepLines.join('\n');
                window.terminalContent = keepLines.join('\n');
            }
        }
        
        // Enhanced status message handling with smooth animations
        function handleStatusMessageOptimized(message) {
            console.log('📡 Status:', message);
            
            // Track execution completion with enhanced timing
            if (message.includes('completed') || message.includes('failed')) {
                window.sshPerformance.executionEndTime = performance.now();
                
                // Extract and display execution time
                const executionTimeMatch = message.match(/Execution time: ([^)]+)/);
                if (executionTimeMatch) {
                    updateDebugElement('execution-time', executionTimeMatch[1]);
                }
                
                // Calculate comprehensive performance metrics
                if (window.sshPerformance.commandStartTime) {
                    const totalTime = window.sshPerformance.executionEndTime - window.sshPerformance.commandStartTime;
                    updateDebugElement('ux-time', formatTime(totalTime));
                    
                    // Update performance indicator with smooth animation
                    const connectionTime = window.sshPerformance.firstByteTime - window.sshPerformance.connectionStartTime;
                    const executionTime = window.sshPerformance.executionEndTime - (window.sshPerformance.executionStartTime || window.sshPerformance.firstByteTime);
                    
                    updatePerformanceIndicatorSmooth(
                        formatTime(connectionTime),
                        formatTime(executionTime),
                        formatTime(totalTime)
                    );
                    
                    console.log(`🎯 Performance - Connection: ${formatTime(connectionTime)}, Execution: ${formatTime(executionTime)}, Total: ${formatTime(totalTime)}`);
                }
            }
            
            // Show status in debug log with smooth scrolling
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
        
        // Enhanced command completion with smooth transitions
        function handleCommandCompleteOptimized() {
            console.log('✅ Command completed - Ultra-optimized mode');
            
            // Force stream cleanup immediately
            window.sshPerformance.streamEnded = true;
            window.isStreamActive = false;
            window.currentStreamReader = null;
            
            batchDOMUpdates(() => {
                updateDebugElement('connection-status', 'Ready');
            });
            
            // Hide performance indicator with delay and smooth animation
            hidePerformanceIndicator(5000);
            
            console.log('🏁 Command complete cleanup finished');
        }
        
        // Smooth terminal clearing with fade animation
        function clearTerminalSmooth() {
            const terminalOutput = document.getElementById('terminal-output');
            if (!terminalOutput) return;
            
            // Fade out current content
            terminalOutput.style.transition = 'opacity 150ms ease-out';
            terminalOutput.style.opacity = '0.3';
            
            setTimeout(() => {
                window.terminalContent = '';
                terminalOutput.textContent = '';
                terminalOutput.dataset.virtualScrolling = 'false';
                
                // Fade back in
                terminalOutput.style.opacity = '1';
                setTimeout(() => {
                    terminalOutput.style.transition = '';
                }, 150);
            }, 150);
            
            // Clear debug log smoothly
            const debugLog = document.getElementById('debug-log');
            if (debugLog) {
                debugLog.innerHTML = '<div class="text-green-400">Server-Sent Events ready. Ultra-optimized mode active.</div>';
            }
        }
        
        // Enhanced performance indicator updates with smooth animations
        function updatePerformanceIndicatorSmooth(connection, execution, total) {
            requestAnimationFrame(() => {
                const connEl = document.getElementById('perf-connection');
                const execEl = document.getElementById('perf-execution');
                const totalEl = document.getElementById('perf-total');
                
                if (connEl) connEl.textContent = connection;
                if (execEl) execEl.textContent = execution;
                if (totalEl) totalEl.textContent = total;
                
                // Color-code based on performance
                const perfIndicator = document.getElementById('performance-indicator');
                if (perfIndicator && total !== '-') {
                    const totalMs = parseFloat(total);
                    let newClass = 'performance-indicator visible ';
                    
                    if (totalMs < 100) {
                        newClass += 'performance-fast';
                    } else if (totalMs < 500) {
                        newClass += 'performance-medium';
                    } else {
                        newClass += 'performance-slow';
                    }
                    
                    perfIndicator.className = newClass;
                }
            });
        }
        
        // Legacy compatibility functions (optimized)
        function clearTerminal() { clearTerminalSmooth(); }
        function addTerminalOutput(type, content) { addTerminalOutputSmooth(type, content); }
        function updatePerformanceIndicator(conn, exec, total) { updatePerformanceIndicatorSmooth(conn, exec, total); }
        function handleStreamEvent(event) { handleStreamEventOptimized(event); }
        function handleStatusMessage(message) { handleStatusMessageOptimized(message); }
        function handleCommandComplete() { handleCommandCompleteOptimized(); }
        
        // Force reset stream state when stuck
        function forceStreamReset() {
            console.log('🔄 Force resetting stream state...');
            window.isStreamActive = false;
            window.currentStreamReader = null;
            window.sshPerformance.streamEnded = true;
            
            // Clear any pending updates
            if (window.sshPerformance.animationFrameId) {
                cancelAnimationFrame(window.sshPerformance.animationFrameId);
                window.sshPerformance.animationFrameId = null;
            }
            
            updateDebugElement('connection-status', 'Ready');
            console.log('✅ Stream state reset complete');
        }
        
        // Optimized utility functions
        function restoreTerminalContent() {
            const terminalOutput = document.getElementById('terminal-output');
            if (terminalOutput && window.terminalContent) {
                requestAnimationFrame(() => {
                    terminalOutput.textContent = window.terminalContent;
                    terminalOutput.scrollTop = terminalOutput.scrollHeight;
                });
            }
        }
        
        function updateDebugElement(elementId, value, color = null) {
            const element = document.getElementById(elementId);
            if (element) {
                requestAnimationFrame(() => {
                    element.textContent = value;
                    if (color) element.style.color = color;
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
        
        function resetPerformanceTracking() {
            window.sshPerformance = {
                commandStartTime: null,
                connectionStartTime: null,
                firstByteTime: null,
                executionStartTime: null,
                executionEndTime: null,
                animationFrameId: null,
                updateQueue: [],
                streamEnded: false
            };
            
            updateDebugElement('first-byte-time', '-');
            updateDebugElement('execution-time', '-');
            updateDebugElement('ux-time', '-');
        }
    </script>
</div>