<x-filament-panels::page>
    {{ $this->form }}

    <!-- Command Output Section -->
    @if ($isCommandRunning || $streamingOutput || $commandOutput)
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="fi-section-content-ctn">
                <div class="fi-section-content p-6">
                    @if ($streamingOutput || ($commandOutput && $commandOutput['output']))
                        <pre id="streaming-output" class="block w-full p-4 border border-gray-300 dark:border-gray-700 rounded-lg bg-gray-50 dark:bg-gray-800 text-sm font-mono overflow-x-auto whitespace-pre-wrap h-64 overflow-y-auto">{{ $streamingOutput ?: ($commandOutput['output'] ?? '') }}</pre>
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

    <!-- Debug Output Section -->
    @if ($verboseDebug && ($debugOutput || $isCommandRunning))
        <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="fi-section-content-ctn">
                <div class="fi-section-content p-6">
                    <pre id="debug-output" class="block w-full p-3 border border-gray-300 dark:border-gray-700 rounded-md bg-gray-800 dark:bg-black text-green-400 text-xs font-mono overflow-x-auto whitespace-pre-wrap h-40 overflow-y-auto">{{ $debugOutput }}</pre>
                </div>
            </div>
        </div>
    @endif

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