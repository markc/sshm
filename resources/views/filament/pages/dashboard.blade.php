<x-filament-panels::page>
    {{-- Full width README section --}}
    <div class="w-full">
        <x-filament::section>
            <x-slot name="heading">
                <div class="flex items-center justify-between w-full">
                    <span>README.md</span>
                    <x-filament::button 
                        tag="a" 
                        href="{{ \App\Filament\Pages\ReadmeEditor::getUrl() }}" 
                        icon="heroicon-o-pencil-square" 
                        size="sm">
                        Edit README
                    </x-filament::button>
                </div>
            </x-slot>
            
            <div class="prose dark:prose-invert max-w-none">
                {!! $this->getReadmeContent() !!}
            </div>
        </x-filament::section>
    </div>
</x-filament-panels::page>