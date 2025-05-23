<x-filament-panels::page>
    <div class="space-y-6">
        @if ($isCommandRunning || $streamingOutput || $commandOutput)
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg p-4 mb-4">
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
                @elseif ($commandOutput)
                    <h3 class="text-lg font-medium {{ $commandOutput['success'] ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' }}">
                        {{ $commandOutput['success'] ? 'Command Completed (Exit Code: ' . $commandOutput['exit_code'] . ')' : 'Command Failed (Exit Code: ' . $commandOutput['exit_code'] . ')' }}
                    </h3>
                @endif
                
                @if ($streamingOutput || ($commandOutput && $commandOutput['output']))
                    <div class="mt-2">
                        <div class="text-sm font-medium text-gray-500 dark:text-gray-400">Output:</div>
                        <pre id="streaming-output" class="mt-1 block w-full p-2 border border-gray-300 dark:border-gray-700 rounded-md bg-gray-100 dark:bg-gray-900 text-sm font-mono overflow-x-auto whitespace-pre-wrap h-64 overflow-y-auto">{{ $isCommandRunning ? $streamingOutput : ($commandOutput['output'] ?? '') }}</pre>
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

        <form wire:submit="runCommand" class="space-y-4">
            {{ $this->form }}

            <div class="flex justify-end">
                <x-filament::button type="submit" wire:loading.attr="disabled" :disabled="$isCommandRunning">
                    <span wire:loading.remove>{{ $isCommandRunning ? 'Command Running...' : 'Run Command' }}</span>
                    <span wire:loading>Starting Command...</span>
                </x-filament::button>
            </div>
        </form>

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
        });
    </script>
</x-filament-panels::page>
