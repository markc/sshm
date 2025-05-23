<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            <div class="flex items-center gap-2">
                <x-heroicon-o-shield-exclamation class="h-5 w-5 text-amber-500" />
                Security Notes
            </div>
        </x-slot>
        
        <div class="space-y-3">
            <div class="text-sm text-gray-600 dark:text-gray-300">
                <span class="text-primary-600 dark:text-primary-400 font-medium">Command Execution:</span> This tool allows for arbitrary command execution on remote servers. Use with extreme caution.
            </div>
        
            <div class="text-sm text-gray-600 dark:text-gray-300">
                <span class="text-primary-600 dark:text-primary-400 font-medium">SSH Security:</span> Ensure that SSH connections are properly secured with strong keys and appropriate permissions.
            </div>
        
            <div class="text-sm text-gray-600 dark:text-gray-300">
                <span class="text-primary-600 dark:text-primary-400 font-medium">User Privileges:</span> Consider using a server user with limited privileges for these connections.
            </div>
        
            <div class="text-sm text-gray-600 dark:text-gray-300">
                <span class="text-primary-600 dark:text-primary-400 font-medium">Logging:</span> All commands executed through this interface might be logged in your server logs.
            </div>
        </div>
    </x-filament::section>
</x-filament-widgets::widget>