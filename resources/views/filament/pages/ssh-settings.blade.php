<x-filament-panels::page>
    <form wire:submit="save">
        {{ $this->form }}
        
        <div class="mt-6">
            <x-filament::button type="submit" size="lg">
                Save Settings
            </x-filament::button>
        </div>
    </form>
    
    <div class="mt-8 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
        <h3 class="text-lg font-medium mb-2">SSH Settings Information</h3>
        <ul class="list-disc list-inside space-y-1 text-sm text-gray-600 dark:text-gray-400">
            <li>The <strong>SSH Home Directory</strong> is used as the base directory for all SSH operations.</li>
            <li>This should be the user's home directory where the <code>.ssh</code> folder is located.</li>
            <li>For most users, this will be <code>/home/username</code> (e.g., <code>/home/markc</code>).</li>
            <li>Changes to these settings will apply system-wide for all users of the application.</li>
            <li>After changing the home directory, you may need to reinitialize the SSH directory structure.</li>
        </ul>
    </div>
</x-filament-panels::page>
