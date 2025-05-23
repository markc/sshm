<div>
    <div class="text-sm text-gray-600 dark:text-gray-400 mb-4">
        <p class="mb-2">Copy and paste this public key to the remote server's <code>~/.ssh/authorized_keys</code> file to enable key-based authentication.</p>
    </div>
    
    <pre class="p-3 bg-gray-100 dark:bg-gray-800 rounded-md text-xs font-mono overflow-x-auto whitespace-pre-wrap border border-gray-300 dark:border-gray-700">{{ $record->public_key }}</pre>
    
    <div x-data="{ copied: false }" class="mt-4">
        <button 
            type="button"
            x-on:click="
                navigator.clipboard.writeText('{{ $record->public_key }}');
                copied = true;
                setTimeout(() => copied = false, 2000);
            "
            class="inline-flex items-center justify-center py-1 gap-1 font-medium rounded-lg border transition-colors focus:outline-none focus:ring-offset-2 focus:ring-2 focus:ring-inset filament-button dark:focus:ring-offset-0 h-9 px-4 text-sm text-white shadow focus:ring-white border-transparent bg-primary-600 hover:bg-primary-500 focus:bg-primary-700 focus:ring-offset-primary-700 filament-page-button-action"
        >
            <span x-show="!copied">
                <svg class="w-4 h-4 mr-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="9" y="9" width="13" height="13" rx="2" ry="2"></rect>
                    <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"></path>
                </svg>
                Copy to Clipboard
            </span>
            <span x-show="copied">
                <svg class="w-4 h-4 mr-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="20 6 9 17 4 12"></polyline>
                </svg>
                Copied!
            </span>
        </button>
    </div>
</div>