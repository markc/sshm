<x-filament-panels::page>
    <style>
        /* Ultra-Performance SSH Runner - Pure CSS (No Livewire) */
        .ssh-standalone-container {
            opacity: 0;
            transition: opacity 300ms ease-in-out;
            contain: layout style paint;
        }
        
        .ssh-standalone-container.loaded {
            opacity: 1;
        }
        
        /* Enhanced Section Containers with CSS Containment */
        .ssh-section { 
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
        
        /* Button States */
        .btn-running {
            background-color: #dc2626 !important;
            cursor: not-allowed !important;
        }
        
        .btn-running:hover {
            background-color: #dc2626 !important;
        }
        
        /* Respect reduced motion preference */
        @media (prefers-reduced-motion: reduce) {
            .ssh-standalone-container,
            .terminal-section,
            .performance-indicator,
            .fade-in {
                transition: none;
                animation: none;
            }
        }
    </style>
    
    <div class="ssh-standalone-container space-y-6" id="ssh-standalone-main">
        <!-- Section 1: Command Input Form -->
        <section class="ssh-section">
            <div class="fi-section rounded-xl bg-white shadow-lg ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="fi-section-content-ctn">
                    <div class="fi-section-content p-8">
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <!-- Left Column: Command Input -->
                            <div class="space-y-4">
                                <div>
                                    <label for="ssh-command" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                        SSH Command(s)
                                    </label>
                                    <textarea 
                                        id="ssh-command" 
                                        rows="8" 
                                        class="block w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm focus:border-orange-500 focus:ring-orange-500 resize-none"
                                        placeholder="Enter SSH command(s) to execute..."
                                    ></textarea>
                                </div>
                            </div>
                            
                            <!-- Right Column: Controls -->
                            <div class="space-y-4">
                                <!-- Host Selection and Run Button -->
                                <div class="flex space-x-3">
                                    <div class="flex-1">
                                        <label for="host-select" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                            SSH Host
                                        </label>
                                        <select id="host-select" class="block w-full rounded-lg border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-800 text-gray-900 dark:text-white shadow-sm focus:border-orange-500 focus:ring-orange-500">
                                            <option value="">Loading hosts...</option>
                                        </select>
                                    </div>
                                    <div class="flex items-end">
                                        <button 
                                            id="run-command-btn" 
                                            type="button" 
                                            class="px-4 py-2 bg-orange-600 hover:bg-orange-700 text-white font-medium rounded-lg shadow-sm transition-colors duration-200"
                                            disabled
                                        >
                                            <span class="run-text">Run Command</span>
                                            <span class="running-text hidden">Running...</span>
                                        </button>
                                    </div>
                                </div>
                                
                                <!-- Options -->
                                <div class="grid grid-cols-2 gap-4">
                                    <div class="flex items-center">
                                        <input 
                                            id="show-debug" 
                                            type="checkbox" 
                                            class="rounded border-gray-300 text-orange-600 focus:ring-orange-500"
                                        >
                                        <label for="show-debug" class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                                            Show Debug Information
                                        </label>
                                    </div>
                                    <div class="flex items-center">
                                        <input 
                                            id="use-bash" 
                                            type="checkbox" 
                                            class="rounded border-gray-300 text-orange-600 focus:ring-orange-500"
                                        >
                                        <label for="use-bash" class="ml-2 text-sm text-gray-700 dark:text-gray-300">
                                            Use Bash Mode
                                        </label>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Section 2: Terminal Output -->
        <section class="ssh-section terminal-section" id="terminal-section" style="display: none;">
            <div class="fi-section rounded-xl bg-white shadow-lg ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="fi-section-content-ctn">
                    <div class="fi-section-content p-8">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Command Output</h3>
                            <div id="performance-indicator" class="performance-indicator">
                                Connection: <span id="perf-connection">-</span> | 
                                Execution: <span id="perf-execution">-</span> | 
                                Total: <span id="perf-total">-</span>
                            </div>
                        </div>
                        
                        <!-- Terminal Container -->
                        <div class="terminal-container">
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

        <!-- Section 3: Debug Information -->
        <section class="ssh-section" id="debug-section" style="display: none;">
            <div class="fi-section rounded-xl bg-white shadow-lg ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="fi-section-content-ctn">
                    <div class="fi-section-content p-8">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Debug Information</h3>
                        <div id="debug-info" class="text-xs text-gray-500 dark:text-gray-400 space-y-3">
                            <div>Connection Method: <span id="connection-method" class="font-mono text-green-400">Pure Axios Streaming</span></div>
                            <div>Process ID: <span id="process-id" class="font-mono">None</span></div>
                            <div>Connection Status: <span id="connection-status">Ready</span></div>
                            <div>Performance Mode: <span id="performance-mode">Ultra-Fast Mode</span></div>
                            <div>Framework: <span id="framework-info" class="text-purple-400">Zero Livewire Overhead</span></div>
                            <div class="pt-2 border-t border-gray-700">
                                <div>First Byte Time: <span id="first-byte-time" class="font-mono text-blue-400">-</span></div>
                                <div>Command Execution: <span id="execution-time" class="font-mono text-green-400">-</span></div>
                                <div>Total User Time: <span id="ux-time" class="font-mono text-purple-400">-</span></div>
                            </div>
                        </div>
                        <div id="debug-log" class="text-xs mt-4 p-4 bg-gray-800 rounded border max-h-32 overflow-y-auto">
                            <div class="text-green-400">Pure Axios streaming ready. Zero framework overhead.</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <!-- Pure JavaScript Implementation (No Livewire) -->
    <script>
        console.log('🚀 SSH Standalone - Pure Axios Implementation');
        
        // Global state management (no Livewire)
        window.sshStandalone = {
            isRunning: false,
            currentReader: null,
            performance: {
                commandStartTime: null,
                connectionStartTime: null,
                firstByteTime: null,
                executionStartTime: null,
                executionEndTime: null
            },
            terminalContent: '',
            hosts: []
        };
        
        // DOM Elements
        let elements = {};
        
        // Initialize on DOM ready
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Initializing SSH Standalone Runner...');
            initializeElements();
            loadSshHosts();
            setupEventListeners();
            initializeSmoothUI();
        });
        
        function initializeElements() {
            elements = {
                container: document.getElementById('ssh-standalone-main'),
                commandTextarea: document.getElementById('ssh-command'),
                hostSelect: document.getElementById('host-select'),
                runButton: document.getElementById('run-command-btn'),
                showDebugCheckbox: document.getElementById('show-debug'),
                useBashCheckbox: document.getElementById('use-bash'),
                terminalSection: document.getElementById('terminal-section'),
                debugSection: document.getElementById('debug-section'),
                terminalOutput: document.getElementById('terminal-output'),
                terminalLoading: document.getElementById('terminal-loading'),
                performanceIndicator: document.getElementById('performance-indicator')
            };
        }
        
        function initializeSmoothUI() {
            if (elements.container) {
                requestAnimationFrame(() => {
                    elements.container.classList.add('loaded');
                });
            }
        }
        
        async function loadSshHosts() {
            try {
                console.log('Loading SSH hosts...');
                const response = await fetch('/api/ssh/hosts');
                const hosts = await response.json();
                
                window.sshStandalone.hosts = hosts;
                populateHostSelect(hosts);
                
                console.log(`Loaded ${hosts.length} SSH hosts`);
            } catch (error) {
                console.error('Failed to load SSH hosts:', error);
                elements.hostSelect.innerHTML = '<option value="">Error loading hosts</option>';
            }
        }
        
        function populateHostSelect(hosts) {
            elements.hostSelect.innerHTML = '<option value="">Select SSH Host...</option>';
            
            hosts.forEach(host => {
                const option = document.createElement('option');
                option.value = host.id;
                option.textContent = `${host.name} (${host.hostname})`;
                elements.hostSelect.appendChild(option);
            });
            
            // Enable run button when host is selected
            elements.hostSelect.addEventListener('change', function() {
                elements.runButton.disabled = !this.value || !elements.commandTextarea.value.trim();
            });
        }
        
        function setupEventListeners() {
            // Command textarea change
            elements.commandTextarea.addEventListener('input', function() {
                elements.runButton.disabled = !this.value.trim() || !elements.hostSelect.value;
            });
            
            // Run command button
            elements.runButton.addEventListener('click', handleRunCommand);
            
            // Debug toggle
            elements.showDebugCheckbox.addEventListener('change', function() {
                elements.debugSection.style.display = this.checked ? 'block' : 'none';
            });
        }
        
        async function handleRunCommand() {
            if (window.sshStandalone.isRunning) {
                console.log('Command already running, ignoring click');
                return;
            }
            
            const command = elements.commandTextarea.value.trim();
            const hostId = elements.hostSelect.value;
            const useBash = elements.useBashCheckbox.checked;
            
            if (!command || !hostId) {
                alert('Please enter a command and select an SSH host');
                return;
            }
            
            console.log('Starting SSH command execution:', { command, hostId, useBash });
            
            // Set running state
            setRunningState(true);
            
            // Show terminal section
            showTerminalSection();
            
            // Clear previous output
            clearTerminal();
            
            // Start performance tracking
            window.sshStandalone.performance.commandStartTime = performance.now();
            window.sshStandalone.performance.connectionStartTime = performance.now();
            
            try {
                await executeSSHCommand(command, hostId, useBash);
            } catch (error) {
                console.error('SSH execution error:', error);
                addTerminalOutput('error', `Error: ${error.message}`);
            } finally {
                setRunningState(false);
            }
        }
        
        async function executeSSHCommand(command, hostId, useBash) {
            console.log('🚀 Pure Axios SSH execution starting...');
            
            // Show loading state
            showLoadingState();
            
            // Prepare form data
            const formData = new FormData();
            formData.append('command', command);
            formData.append('host_id', hostId);
            formData.append('use_bash', useBash ? '1' : '0');
            formData.append('_token', document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '');
            
            // Execute streaming request
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
            
            console.log('📡 SSH Stream Response:', response.status, response.statusText);
            
            // Hide loading state
            hideLoadingState();
            
            // Track connection time
            const connectionTime = performance.now() - window.sshStandalone.performance.connectionStartTime;
            console.log(`⚡ Ultra-fast connection: ${connectionTime.toFixed(1)}ms`);
            updateDebugElement('connection-status', 'Connected');
            updatePerformanceIndicator(formatTime(connectionTime), '-', '-');
            
            // Process stream
            const reader = response.body.getReader();
            window.sshStandalone.currentReader = reader;
            
            await processStream(reader);
        }
        
        async function processStream(reader) {
            console.log('📖 Starting pure stream processing...');
            let buffer = '';
            
            try {
                while (true) {
                    const { done, value } = await reader.read();
                    
                    if (done) {
                        console.log('🏁 Stream completed - Pure implementation');
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
                                console.log('📡 Stream event:', eventData);
                                handleStreamEvent(eventData);
                            } catch (e) {
                                console.log('Non-JSON data:', line);
                            }
                        }
                    }
                }
            } catch (error) {
                console.error('Stream processing error:', error);
                addTerminalOutput('error', `Stream error: ${error.message}`);
            } finally {
                window.sshStandalone.currentReader = null;
                updateDebugElement('connection-status', 'Ready');
                console.log('✅ Stream processing complete');
            }
        }
        
        function handleStreamEvent(event) {
            const { type, data, process_id, timestamp } = event;
            
            // Track first byte time
            if (!window.sshStandalone.performance.firstByteTime && (type === 'output' || type === 'error')) {
                window.sshStandalone.performance.firstByteTime = performance.now();
                const firstByteDelay = window.sshStandalone.performance.firstByteTime - window.sshStandalone.performance.commandStartTime;
                updateDebugElement('first-byte-time', formatTime(firstByteDelay));
                console.log(`⚡ First byte: ${firstByteDelay.toFixed(1)}ms`);
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
                    handleStatusMessage(data);
                    break;
                case 'complete':
                    handleCommandComplete();
                    break;
                default:
                    console.log('Unknown event type:', type, data);
            }
        }
        
        function addTerminalOutput(type, content) {
            const output = elements.terminalOutput;
            if (!output) return;
            
            console.log(`📝 Adding terminal output [${type}]:`, content);
            
            // Create content element
            const element = document.createElement('span');
            element.textContent = content + '\n';
            
            if (type === 'error') {
                element.className = 'terminal-err';
            } else if (type === 'status') {
                element.className = 'terminal-status';
            }
            
            // Append with smooth animation
            requestAnimationFrame(() => {
                output.appendChild(element);
                
                // Auto-scroll to bottom
                output.scrollTop = output.scrollHeight;
            });
            
            // Update stored content
            window.sshStandalone.terminalContent += content + '\n';
        }
        
        function handleStatusMessage(message) {
            console.log('📡 Status:', message);
            addTerminalOutput('status', message);
            
            // Track execution completion
            if (message.includes('completed') || message.includes('failed')) {
                window.sshStandalone.performance.executionEndTime = performance.now();
                
                // Calculate performance metrics
                if (window.sshStandalone.performance.commandStartTime) {
                    const totalTime = window.sshStandalone.performance.executionEndTime - window.sshStandalone.performance.commandStartTime;
                    const connectionTime = window.sshStandalone.performance.firstByteTime - window.sshStandalone.performance.connectionStartTime;
                    const executionTime = window.sshStandalone.performance.executionEndTime - (window.sshStandalone.performance.firstByteTime || window.sshStandalone.performance.connectionStartTime);
                    
                    updatePerformanceIndicator(
                        formatTime(connectionTime),
                        formatTime(executionTime),
                        formatTime(totalTime)
                    );
                    
                    updateDebugElement('ux-time', formatTime(totalTime));
                    console.log(`🎯 Performance - Connection: ${formatTime(connectionTime)}, Execution: ${formatTime(executionTime)}, Total: ${formatTime(totalTime)}`);
                }
            }
        }
        
        function handleCommandComplete() {
            console.log('✅ Command completed - Pure implementation');
            updateDebugElement('connection-status', 'Ready');
            hidePerformanceIndicator();
        }
        
        // UI State Management
        function setRunningState(isRunning) {
            window.sshStandalone.isRunning = isRunning;
            
            const runText = elements.runButton.querySelector('.run-text');
            const runningText = elements.runButton.querySelector('.running-text');
            
            if (isRunning) {
                elements.runButton.disabled = true;
                elements.runButton.classList.add('btn-running');
                runText.classList.add('hidden');
                runningText.classList.remove('hidden');
            } else {
                elements.runButton.disabled = false;
                elements.runButton.classList.remove('btn-running');
                runText.classList.remove('hidden');
                runningText.classList.add('hidden');
            }
        }
        
        function showTerminalSection() {
            elements.terminalSection.style.display = 'block';
            requestAnimationFrame(() => {
                elements.terminalSection.classList.add('visible');
            });
        }
        
        function clearTerminal() {
            if (elements.terminalOutput) {
                elements.terminalOutput.textContent = '';
                window.sshStandalone.terminalContent = '';
            }
        }
        
        function showLoadingState() {
            if (elements.terminalLoading) {
                elements.terminalLoading.style.display = 'block';
            }
        }
        
        function hideLoadingState() {
            if (elements.terminalLoading) {
                elements.terminalLoading.style.display = 'none';
            }
        }
        
        function showPerformanceIndicator() {
            if (elements.performanceIndicator) {
                elements.performanceIndicator.classList.add('visible');
            }
        }
        
        function hidePerformanceIndicator() {
            if (elements.performanceIndicator) {
                setTimeout(() => {
                    elements.performanceIndicator.classList.remove('visible');
                }, 3000);
            }
        }
        
        function updatePerformanceIndicator(connection, execution, total) {
            const connEl = document.getElementById('perf-connection');
            const execEl = document.getElementById('perf-execution');
            const totalEl = document.getElementById('perf-total');
            
            if (connEl) connEl.textContent = connection;
            if (execEl) execEl.textContent = execution;
            if (totalEl) totalEl.textContent = total;
            
            showPerformanceIndicator();
        }
        
        function updateDebugElement(elementId, value) {
            const element = document.getElementById(elementId);
            if (element) {
                element.textContent = value;
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
</x-filament-panels::page>