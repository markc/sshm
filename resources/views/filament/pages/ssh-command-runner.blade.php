<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Form at the top -->
        <div class="space-y-4">
            {{ $this->form }}
        </div>

        <!-- Command output below the form -->
        @if ($isCommandRunning || $streamingOutput || $commandOutput)
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-4">
                @if ($isCommandRunning)
                    <h3 class="text-lg font-medium text-blue-600 dark:text-blue-400">
                        Command Running... 
                        <span class="inline-flex items-center ml-2">
                            <svg class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </span>
                    </h3>
                @endif
                
                @if ($streamingOutput || ($commandOutput && $commandOutput['output']))
                    <div class="{{ $isCommandRunning ? 'mt-2' : '' }}">
                        <pre id="streaming-output" class="block w-full p-2 border border-gray-300 dark:border-gray-700 rounded-md bg-gray-100 dark:bg-gray-900 text-sm font-mono overflow-x-auto whitespace-pre-wrap h-64 overflow-y-auto">{{ $streamingOutput ?: ($commandOutput['output'] ?? '') }}</pre>
                    </div>
                @endif

                @if ($commandOutput)
                    <div class="mt-4">
                        <h3 class="text-lg font-medium {{ $commandOutput['success'] ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                            {{ $commandOutput['success'] ? 'Command Completed (Exit Code: ' . $commandOutput['exit_code'] . ')' : 'Command Failed (Exit Code: ' . $commandOutput['exit_code'] . ')' }}
                        </h3>
                    </div>
                @endif
                
                @if ($commandOutput && $commandOutput['error'])
                    <div class="mt-2">
                        <div class="text-sm font-medium text-red-500">Error:</div>
                        <pre class="mt-1 block w-full p-2 border border-red-300 dark:border-red-700 rounded-md bg-red-50 dark:bg-red-900/20 text-sm font-mono overflow-x-auto whitespace-pre-wrap">{{ $commandOutput['error'] }}</pre>
                    </div>
                @endif
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
