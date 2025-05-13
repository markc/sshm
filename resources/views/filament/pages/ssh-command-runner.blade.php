<x-filament-panels::page>
    <div class="space-y-6">
        @if($hasOutput)
            <div id="command-output-alert" class="mb-6">
                @if($commandOutput)
                    <div class="p-3 bg-white text-gray-800 font-mono text-sm rounded border border-gray-200 overflow-x-auto">
                        <pre>{{ $commandOutput }}</pre>
                    </div>
                @endif
                
                @if($commandError)
                    <div class="mt-2 p-3 bg-white text-red-800 font-mono text-sm rounded border border-red-200 overflow-x-auto">
                        <pre>{{ $commandError }}</pre>
                    </div>
                @endif
            </div>
        @endif

        <form wire:submit="runCommand" class="relative">
            {{ $this->form }}

            <div class="absolute" style="top: 92px; right: 2.5%; width: 20%; max-width: 200px;">
                <x-filament::button type="submit" color="primary" class="w-full justify-center">
                    Run Command
                </x-filament::button>
            </div>
        </form>
    </div>
</x-filament-panels::page>