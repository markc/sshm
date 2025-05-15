<x-filament-panels::page>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        {{-- Left column: Markdown Preview --}}
        <x-filament::section>
            <x-slot name="heading">README Preview</x-slot>
            
            <div class="prose prose-sm dark:prose-invert max-w-none" wire:poll.2s="$refresh">
                {!! $this->getReadmeContent() !!}
            </div>
        </x-filament::section>
        
        {{-- Right column: Markdown Editor --}}
        <div>
            {{ $this->form }}
        </div>
    </div>
</x-filament-panels::page>
