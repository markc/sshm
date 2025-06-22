<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Form at the top -->
        <div class="space-y-4">
            {{ $this->form }}
        </div>

        <!-- Command output section with Filament styling -->
        @if ($isCommandRunning || $streamingOutput || $commandOutput)
            <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="fi-section-header flex flex-col gap-3 px-6 py-4">
                    <div class="flex items-center gap-3">
                        <div class="grid flex-1 gap-1">
                            <h3 class="fi-section-header-heading text-base font-semibold text-gray-950 dark:text-white">
                                @if ($isCommandRunning)
                                    <span class="text-blue-600 dark:text-blue-400">
                                        Command Running...
                                        <svg class="inline ml-2 animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                                        </svg>
                                    </span>
                                @else
                                    Command Output
                                @endif
                            </h3>
                            <p class="fi-section-header-description text-sm text-gray-500 dark:text-gray-400">
                                Results from SSH command execution
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="fi-section-content-ctn border-t border-gray-200 dark:border-white/10">
                    <div class="fi-section-content p-6">
                        @if ($streamingOutput || ($commandOutput && $commandOutput['output']))
                            <pre id="streaming-output" class="block w-full p-4 border border-gray-300 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-800 text-sm font-mono overflow-x-auto whitespace-pre-wrap h-64 overflow-y-auto">{{ $streamingOutput ?: ($commandOutput['output'] ?? '') }}</pre>
                        @endif

                        @if ($commandOutput)
                            <div class="mt-4">
                                <div class="inline-flex items-center gap-1.5 rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset {{ $commandOutput['success'] ? 'bg-green-50 text-green-700 ring-green-600/20 dark:bg-green-400/10 dark:text-green-400 dark:ring-green-400/30' : 'bg-red-50 text-red-700 ring-red-600/20 dark:bg-red-400/10 dark:text-red-400 dark:ring-red-400/30' }}">
                                    {{ $commandOutput['success'] ? 'Command Completed (Exit Code: ' . $commandOutput['exit_code'] . ')' : 'Command Failed (Exit Code: ' . $commandOutput['exit_code'] . ')' }}
                                </div>
                            </div>
                        @endif
                        
                        @if ($commandOutput && $commandOutput['error'])
                            <div class="mt-4">
                                <div class="text-sm font-medium text-red-600 dark:text-red-400 mb-2">Error:</div>
                                <pre class="block w-full p-4 border border-red-300 dark:border-red-700 rounded-lg bg-red-50 dark:bg-red-900/20 text-sm font-mono overflow-x-auto whitespace-pre-wrap">{{ $commandOutput['error'] }}</pre>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        <!-- Debug output at the bottom -->
        @if ($verboseDebug && ($debugOutput || $isCommandRunning))
            <div class="bg-gray-50 dark:bg-gray-900 overflow-hidden shadow-sm sm:rounded-lg p-4 border border-gray-300 dark:border-gray-600">
                <h4 class="text-md font-medium text-gray-800 dark:text-gray-200 mb-2">
                    🐛 Verbose Debug Output
                </h4>
                <pre id="debug-output" class="block w-full p-3 border border-gray-300 dark:border-gray-700 rounded-md bg-gray-800 dark:bg-black text-green-400 text-xs font-mono overflow-x-auto whitespace-pre-wrap h-40 overflow-y-auto">{{ $debugOutput }}</pre>
            </div>
        @endif

    </div>

    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('outputUpdated', (output) => {
                const outputElement = document.getElementById('streaming-output');
                if (outputElement) {
                    outputElement.textContent = output[0];
                    outputElement.scrollTop = outputElement.scrollHeight;
                }
            });

            Livewire.on('debugUpdated', (debugOutput) => {
                const debugElement = document.getElementById('debug-output');
                if (debugElement) {
                    debugElement.textContent = debugOutput[0];
                    debugElement.scrollTop = debugElement.scrollHeight;
                }
            });
        });
    </script>
</x-filament-panels::page>
