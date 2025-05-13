<x-filament-panels::page>
    <div class="filament-page-card filament-resources-list-records-page mb-6">
        <div class="overflow-hidden">
            <div class="p-6">
                <h2 class="text-xl font-bold mb-6">SSH Server Status</h2>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="border rounded-lg p-4">
                        <h3 class="text-lg font-semibold mb-2">SSH Daemon Status</h3>
                        @if ($sshStatus === "active")
                            <div class="text-success-500 flex items-center">
                                <span class="h-3 w-3 rounded-full bg-success-500 mr-2"></span>
                                SSH Server is running
                            </div>
                        @elseif ($sshStatus === "inactive")
                            <div class="text-danger-500 flex items-center">
                                <span class="h-3 w-3 rounded-full bg-danger-500 mr-2"></span>
                                SSH Server is stopped
                            </div>
                        @else
                            <div class="text-warning-500 flex items-center">
                                <span class="h-3 w-3 rounded-full bg-warning-500 mr-2"></span>
                                SSH Server status unknown
                            </div>
                        @endif
                    </div>
                    
                    <div class="border rounded-lg p-4">
                        <h3 class="text-lg font-semibold mb-2">SSH Directory</h3>
                        <div class="text-sm">
                            <pre>{{ $sshDirInfo }}</pre>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <div class="filament-page-card filament-resources-list-records-page">
        <div class="overflow-hidden">
            <div class="p-6">
                <h2 class="text-xl font-bold mb-6">SSH Management Quick Guide</h2>
                
                <div class="prose max-w-none">
                    <h3>SSH Configuration Structure</h3>
                    <p>
                        This application integrates with the standard SSH directory structure in your home folder (<code>~/.ssh</code>).
                        The system follows OpenSSH conventions:
                    </p>
                    
                    <ul>
                        <li><code>~/.ssh/config</code> - Main configuration file</li>
                        <li><code>~/.ssh/config.d/</code> - Directory containing individual host configurations</li>
                        <li><code>~/.ssh/authorized_keys</code> - Public keys that are allowed to connect to this machine</li>
                        <li><code>~/.ssh/[keyname]</code> - Private keys</li>
                        <li><code>~/.ssh/[keyname].pub</code> - Public keys</li>
                    </ul>
                    
                    <h3>Working with SSH Keys</h3>
                    <p>
                        The SSH Keys page allows you to:
                    </p>
                    
                    <ol>
                        <li>Create new SSH keys (uses modern Ed25519 algorithm by default)</li>
                        <li>View existing keys from your <code>~/.ssh</code> directory</li>
                        <li>Copy keys to remote servers authorized_keys files</li>
                        <li>Delete keys when no longer needed</li>
                    </ol>
                    
                    <h3>Working with SSH Connections</h3>
                    <p>
                        The SSH Connections page lets you:
                    </p>
                    
                    <ol>
                        <li>Create and edit SSH connection profiles</li>
                        <li>Save configurations to the standard <code>~/.ssh/config.d/</code> directory</li>
                        <li>Test connections</li>
                        <li>Set a default connection for the command runner</li>
                    </ol>
                    
                    <h3>SSH Command Runner</h3>
                    <p>
                        The SSH Command Runner page allows you to execute commands on remote servers using saved connections.
                    </p>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
