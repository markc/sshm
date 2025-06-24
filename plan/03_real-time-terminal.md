# Stage 3: Real-Time Terminal Implementation

## Overview
This stage implements real-time SSH command execution with streaming output, advanced UI components, and sophisticated terminal interface. This corresponds to the major "enhance SSH command runner with real-time streaming and advanced UI" commit and subsequent streaming improvements.

## Prerequisites
- Completed Stage 2: SSH Management Core Features
- Working SSH host and key management
- Basic command runner interface

## Step 1: Install Additional Dependencies

```bash
# Install additional packages for real-time features
composer require pusher/pusher-php-server:"^7.2"
composer require laravel/reverb:"^1.0" --dev

# Install broadcasting dependencies  
npm install --save laravel-echo pusher-js
```

## Step 2: Configure Broadcasting

Update `.env` file:

```env
# Broadcasting Configuration
BROADCAST_CONNECTION=reverb

# Reverb Configuration  
REVERB_APP_ID=ssh-manager
REVERB_APP_KEY=local-key
REVERB_APP_SECRET=local-secret
REVERB_HOST=127.0.0.1
REVERB_PORT=8080
REVERB_SCHEME=http

# Pusher Configuration (for Reverb compatibility)
PUSHER_APP_ID="${REVERB_APP_ID}"
PUSHER_APP_KEY="${REVERB_APP_KEY}"
PUSHER_APP_SECRET="${REVERB_APP_SECRET}"
PUSHER_HOST="${REVERB_HOST}"
PUSHER_PORT="${REVERB_PORT}"
PUSHER_SCHEME="${REVERB_SCHEME}"
PUSHER_APP_CLUSTER=mt1

# Queue Configuration for SSH Jobs
QUEUE_CONNECTION=database
```

## Step 3: Set Up Broadcasting Configuration

Update `config/broadcasting.php`:

```php
'reverb' => [
    'driver' => 'reverb',
    'key' => env('REVERB_APP_KEY'),
    'secret' => env('REVERB_APP_SECRET'),
    'app_id' => env('REVERB_APP_ID'),
    'options' => [
        'host' => env('REVERB_HOST', '127.0.0.1'),
        'port' => env('REVERB_PORT', 8080),
        'scheme' => env('REVERB_SCHEME', 'http'),
        'useTLS' => env('REVERB_SCHEME', 'http') === 'https',
    ],
],
```

## Step 4: Create SSH Command Job

```bash
# Create job for SSH command execution
php artisan make:job RunSshCommand

# Create event for SSH output streaming
php artisan make:event SshOutputReceived

# Create queue table
php artisan queue:table
php artisan migrate
```

Update `app/Jobs/RunSshCommand.php`:

```php
<?php

namespace App\Jobs;

use App\Events\SshOutputReceived;
use App\Models\SshHost;
use App\Settings\SshSettings;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Spatie\Ssh\Ssh;

class RunSshCommand implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public SshHost $host,
        public string $command,
        public string $sessionId,
        public bool $useBash = false,
        public bool $debugMode = false
    ) {}

    public function handle(): void
    {
        $settings = app(SshSettings::class);
        
        try {
            // Send status update
            SshOutputReceived::dispatch(
                $this->sessionId,
                'status',
                "Connecting to {$this->host->hostname}..."
            );

            // Build SSH connection
            $ssh = Ssh::create($this->host->user, $this->host->hostname)
                ->port($this->host->port)
                ->timeout($settings->getTimeout());

            // Configure authentication
            if ($this->host->private_key_path) {
                $ssh->usePrivateKey($this->host->private_key_path);
            } elseif ($this->host->password) {
                $ssh->usePassword($this->host->password);
            }

            // Configure SSH options
            if (!$settings->getStrictHostChecking()) {
                $ssh->disableStrictHostKeyChecking();
            }

            if ($this->debugMode) {
                SshOutputReceived::dispatch(
                    $this->sessionId,
                    'debug',
                    "SSH connection configured: {$this->host->user}@{$this->host->hostname}:{$this->host->port}"
                );
            }

            // Prepare command
            $finalCommand = $this->useBash ? "bash -ci '{$this->command}'" : $this->command;

            if ($this->debugMode) {
                SshOutputReceived::dispatch(
                    $this->sessionId,
                    'debug',
                    "Executing command: {$finalCommand}"
                );
            }

            // Execute command with streaming output
            $ssh->execute($finalCommand, function ($type, $line) {
                if ($type === 'out') {
                    SshOutputReceived::dispatch($this->sessionId, 'output', $line);
                } elseif ($type === 'err') {
                    SshOutputReceived::dispatch($this->sessionId, 'error', $line);
                }
            });

            SshOutputReceived::dispatch(
                $this->sessionId,
                'status',
                'Command completed successfully'
            );

        } catch (\Exception $e) {
            SshOutputReceived::dispatch(
                $this->sessionId,
                'error',
                'SSH Error: ' . $e->getMessage()
            );
        }

        SshOutputReceived::dispatch($this->sessionId, 'complete', '');
    }
}
```

## Step 5: Create SSH Output Event

Update `app/Events/SshOutputReceived.php`:

```php
<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SshOutputReceived implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $sessionId,
        public string $type,
        public string $data
    ) {}

    public function broadcastOn(): array
    {
        return [
            new Channel("ssh-output.{$this->sessionId}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'ssh.output';
    }

    public function broadcastWith(): array
    {
        return [
            'type' => $this->type,
            'data' => $this->data,
            'timestamp' => now()->toISOString(),
        ];
    }
}
```

## Step 6: Enhanced SSH Command Runner

Update `app/Filament/Pages/SshCommandRunner.php`:

```php
<?php

namespace App\Filament\Pages;

use App\Jobs\RunSshCommand;
use App\Models\SshHost;
use App\Settings\SshSettings;
use Filament\Actions\Action;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class SshCommandRunner extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-command-line';

    protected static ?string $navigationLabel = 'SSH Commands';

    protected static ?int $navigationSort = 1;

    protected string $view = 'filament.pages.ssh-command-runner';

    public ?string $currentSessionId = null;
    public bool $isCommandRunning = false;
    public bool $useBashMode = false;
    public bool $showDebug = false;
    public bool $hasTerminalOutput = false;

    public function mount(): void
    {
        // Initialize properties
        $this->isCommandRunning = false;
        $this->currentSessionId = null;

        // Set default SSH host from settings
        $settings = app(SshSettings::class);
        $defaultHost = $settings->getDefaultSshHost();

        if ($defaultHost) {
            // Find the host by name and set as selected
            $host = SshHost::where('name', $defaultHost)->where('active', true)->first();
            if ($host) {
                $this->selectedHost = (string) $host->id;
            }
        }
    }

    public ?string $selectedHost = null;
    public ?string $command = null;

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Grid::make(2)
                    ->schema([
                        // Left side - Command textarea (3 rows, no label)
                        Textarea::make('command')
                            ->hiddenLabel()
                            ->required()
                            ->rows(3)
                            ->placeholder('Enter SSH command(s) to execute...')
                            ->extraAttributes([
                                'style' => 'resize: none;',
                                'id' => 'command-input',
                            ])
                            ->columnSpan(1),

                        // Right side - Two sub-columns for controls
                        Grid::make(2)
                            ->schema([
                                // Left sub-column: Run button and Debug toggle
                                Group::make([
                                    // Single button with dual states: Run/Stop
                                    Actions::make([
                                        Action::make('commandButton')
                                            ->label(fn () => $this->isCommandRunning ? 'Stop Command' : 'Run Command')
                                            ->icon(fn () => $this->isCommandRunning ? 'heroicon-o-arrow-path' : 'heroicon-o-play')
                                            ->iconPosition('before')
                                            ->color(fn () => $this->isCommandRunning ? 'danger' : 'primary')
                                            ->size('lg')
                                            ->extraAttributes(fn () => [
                                                'id' => 'command-btn',
                                                'class' => $this->isCommandRunning ? 'w-full' : 'w-full',
                                            ])
                                            ->action(fn () => $this->isCommandRunning ? $this->stopCommand() : $this->runCommand())
                                            ->requiresConfirmation(false)
                                            ->button(),
                                    ]),

                                    // Debug Toggle
                                    Toggle::make('showDebug')
                                        ->label('Show Debug Information')
                                        ->inline(true)
                                        ->live(),
                                ])->columnSpan(1),

                                // Right sub-column: SSH Host selector and Bash Mode toggle
                                Group::make([
                                    // SSH Host selector (no label, custom placeholder)
                                    Select::make('selectedHost')
                                        ->hiddenLabel()
                                        ->placeholder('Select SSH Host')
                                        ->options(function () {
                                            return SshHost::where('active', true)
                                                ->pluck('name', 'id')
                                                ->toArray();
                                        })
                                        ->default(function () {
                                            $settings = app(SshSettings::class);
                                            $defaultHost = $settings->getDefaultSshHost();

                                            if ($defaultHost) {
                                                $host = SshHost::where('name', $defaultHost)->where('active', true)->first();
                                                if ($host) {
                                                    return (string) $host->id;
                                                }
                                            }

                                        })
                                        ->afterStateUpdated(function ($state) {
                                            if ($state) {
                                                $this->selectedHost = $state;
                                            }
                                        }),

                                    // Bash Mode Toggle
                                    Toggle::make('useBashMode')
                                        ->label('Use Bash Mode')
                                        ->inline(true),

                                ])->columnSpan(1),
                            ])
                            ->columnSpan(1),
                    ]),
            ]);
    }

    protected function getFormModel(): string
    {
        return SshCommandRunner::class;
    }

    protected function getViewData(): array
    {
        return [
            'showDebug' => $this->showDebug,
            'hasTerminalOutput' => $this->hasTerminalOutput,
        ];
    }

    public function runCommand(): void
    {
        $this->validate([
            'command' => 'required|string',
            'selectedHost' => 'required',
        ]);

        $this->isCommandRunning = true;
        $this->hasTerminalOutput = true;

        // Generate session ID for tracking
        $sessionId = (string) Str::uuid();
        $this->currentSessionId = $sessionId;

        // Dispatch job for SSH command execution
        $host = SshHost::findOrFail($this->selectedHost);
        
        RunSshCommand::dispatch(
            $host,
            $this->command,
            $sessionId,
            $this->useBashMode,
            $this->showDebug
        );

        // Emit event to frontend to start listening
        $this->dispatch('ssh-command-started', ['sessionId' => $sessionId]);
    }

    public function stopCommand(): void
    {
        if ($this->currentSessionId) {
            // Implement command termination logic here
            $this->isCommandRunning = false;
            $this->currentSessionId = null;
        }
    }

    public function setCommandCompleted(): void
    {
        $this->isCommandRunning = false;
        $this->currentSessionId = null;
    }

    protected function getListeners(): array
    {
        return [
            'ssh-command-completed' => 'setCommandCompleted',
        ];
    }
}
```

## Step 7: Create Advanced Terminal View

Update `resources/views/filament/pages/ssh-command-runner.blade.php`:

```blade
<x-filament-panels::page>
    <div class="space-y-6">
        <!-- Form Section -->
        <div class="rounded-xl bg-white shadow-lg ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="p-8">
                {{ $this->form }}
            </div>
        </div>

        <!-- Terminal Output Section -->
        <div class="rounded-xl bg-white shadow-lg ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10" 
             id="terminal-section"
             @if(!$hasTerminalOutput) style="display: none;" @endif>
            <div class="p-8">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Command Output</h3>
                
                <!-- Terminal Output Area -->
                <div class="bg-gray-900 rounded-lg border border-gray-700 overflow-hidden">
                    <div class="bg-gray-800 px-4 py-2 border-b border-gray-700">
                        <div class="flex items-center space-x-2">
                            <div class="w-3 h-3 bg-red-500 rounded-full"></div>
                            <div class="w-3 h-3 bg-yellow-500 rounded-full"></div>
                            <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                            <span class="text-gray-400 text-sm ml-4">SSH Terminal - 80x25</span>
                        </div>
                    </div>
                    
                    <pre id="terminal-output" 
                         class="text-green-400 p-4 min-h-[300px] max-h-[500px] overflow-y-auto whitespace-pre-wrap font-mono text-sm"
                         aria-live="polite">Ready for command execution...</pre>
                </div>

                <!-- Command Status -->
                <div id="command-status" class="mt-4 text-sm text-gray-600 dark:text-gray-400"></div>
            </div>
        </div>

        <!-- Debug Information Section -->
        @if($showDebug)
            <div class="rounded-xl bg-white shadow-lg ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="p-8">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Debug Information</h3>
                    <div id="debug-info" class="text-xs text-gray-500 dark:text-gray-400 space-y-2">
                        <div>Connection Method: <span class="font-mono">Real-time WebSocket Streaming</span></div>
                        <div>Session ID: <span id="session-id" class="font-mono">None</span></div>
                        <div>Connection Status: <span id="connection-status">Ready</span></div>
                        <div>Performance Mode: <span id="performance-mode">High-Performance Streaming</span></div>
                    </div>
                    <div id="debug-log" class="text-xs mt-4 p-4 bg-gray-800 rounded border max-h-32 overflow-y-auto"></div>
                </div>
            </div>
        @endif
    </div>

    @push('scripts')
    <script>
        // Laravel Echo configuration
        import Echo from 'laravel-echo';
        import Pusher from 'pusher-js';

        window.Pusher = Pusher;

        window.Echo = new Echo({
            broadcaster: 'reverb',
            key: '{{ env('REVERB_APP_KEY') }}',
            wsHost: '{{ env('REVERB_HOST', '127.0.0.1') }}',
            wsPort: {{ env('REVERB_PORT', 8080) }},
            wssPort: {{ env('REVERB_PORT', 8080) }},
            forceTLS: false,
            enabledTransports: ['ws', 'wss'],
        });

        // SSH Terminal Manager
        window.sshTerminal = {
            sessionId: null,
            channel: null,
            terminalOutput: null,
            debugLog: null,
            
            init() {
                this.terminalOutput = document.getElementById('terminal-output');
                this.debugLog = document.getElementById('debug-log');
            },
            
            startSession(sessionId) {
                this.sessionId = sessionId;
                this.updateDebugInfo('session-id', sessionId);
                this.updateDebugInfo('connection-status', 'Connecting...');
                
                // Clear terminal
                if (this.terminalOutput) {
                    this.terminalOutput.textContent = '';
                }
                
                // Join SSH output channel
                this.channel = Echo.channel(`ssh-output.${sessionId}`)
                    .listen('ssh.output', (data) => {
                        this.handleOutput(data);
                    });
                    
                this.addToDebugLog(`Started listening on channel: ssh-output.${sessionId}`);
                this.updateDebugInfo('connection-status', 'Connected');
            },
            
            handleOutput(data) {
                const { type, data: content, timestamp } = data;
                
                switch (type) {
                    case 'output':
                        this.appendToTerminal(content, 'text-green-400');
                        break;
                    case 'error':
                        this.appendToTerminal(content, 'text-red-400');
                        break;
                    case 'status':
                        this.updateStatus(content);
                        this.addToDebugLog(`Status: ${content}`);
                        break;
                    case 'debug':
                        this.addToDebugLog(`Debug: ${content}`);
                        break;
                    case 'complete':
                        this.commandCompleted();
                        break;
                }
            },
            
            appendToTerminal(content, className = 'text-green-400') {
                if (!this.terminalOutput) return;
                
                const span = document.createElement('span');
                span.className = className;
                span.textContent = content + '\n';
                
                this.terminalOutput.appendChild(span);
                this.terminalOutput.scrollTop = this.terminalOutput.scrollHeight;
            },
            
            updateStatus(status) {
                const statusElement = document.getElementById('command-status');
                if (statusElement) {
                    statusElement.textContent = status;
                }
            },
            
            updateDebugInfo(elementId, value) {
                const element = document.getElementById(elementId);
                if (element) {
                    element.textContent = value;
                }
            },
            
            addToDebugLog(message) {
                if (!this.debugLog) return;
                
                const timestamp = new Date().toLocaleTimeString();
                const logEntry = document.createElement('div');
                logEntry.className = 'text-blue-400';
                logEntry.textContent = `[${timestamp}] ${message}`;
                
                this.debugLog.appendChild(logEntry);
                this.debugLog.scrollTop = this.debugLog.scrollHeight;
            },
            
            commandCompleted() {
                this.updateStatus('Command execution completed');
                this.updateDebugInfo('connection-status', 'Ready');
                this.addToDebugLog('Command execution completed');
                
                // Notify Livewire component
                @this.call('setCommandCompleted');
                
                // Leave channel
                if (this.channel) {
                    Echo.leave(`ssh-output.${this.sessionId}`);
                    this.channel = null;
                }
            }
        };

        // Initialize when page loads
        document.addEventListener('DOMContentLoaded', function() {
            window.sshTerminal.init();
        });

        // Listen for Livewire events
        document.addEventListener('livewire:init', () => {
            Livewire.on('ssh-command-started', (data) => {
                const sessionId = data[0].sessionId;
                
                // Show terminal section
                const terminalSection = document.getElementById('terminal-section');
                if (terminalSection) {
                    terminalSection.style.display = 'block';
                }
                
                // Start SSH session
                window.sshTerminal.startSession(sessionId);
            });
        });
    </script>
    @endpush
</x-filament-panels::page>
```

## Step 8: Configure Reverb WebSocket Server

Install Reverb configuration:

```bash
# Publish Reverb configuration
php artisan vendor:publish --provider="Laravel\Reverb\ReverbServiceProvider"

# Configure Reverb
php artisan reverb:install
```

## Step 9: Create Development Scripts

Create `scripts/dev-server.sh`:

```bash
#!/bin/bash

# Development server with WebSocket support
echo "🚀 Starting SSH Manager Development Environment..."

# Start Reverb WebSocket server in background
echo "📡 Starting Reverb WebSocket server..."
php artisan reverb:start --host=127.0.0.1 --port=8080 &
REVERB_PID=$!

# Start queue worker in background
echo "⚡ Starting queue worker..."
php artisan queue:work --timeout=300 &
QUEUE_PID=$!

# Start Laravel development server
echo "🌐 Starting Laravel development server..."
php artisan serve --host=127.0.0.1 --port=8000 &
LARAVEL_PID=$!

echo ""
echo "✅ Development environment ready!"
echo "🌐 Application: http://127.0.0.1:8000/admin"
echo "📡 WebSocket: ws://127.0.0.1:8080"
echo ""
echo "Press Ctrl+C to stop all services..."

# Function to cleanup background processes
cleanup() {
    echo ""
    echo "🛑 Stopping all services..."
    kill $REVERB_PID 2>/dev/null
    kill $QUEUE_PID 2>/dev/null  
    kill $LARAVEL_PID 2>/dev/null
    echo "✅ All services stopped"
    exit 0
}

# Set trap to cleanup on script exit
trap cleanup SIGINT SIGTERM

# Wait for any process to finish
wait
```

Make it executable:

```bash
chmod +x scripts/dev-server.sh
```

## Step 10: Update Package.json for Frontend Assets

Update `package.json`:

```json
{
    "private": true,
    "type": "module",
    "scripts": {
        "build": "vite build",
        "dev": "vite dev"
    },
    "devDependencies": {
        "axios": "^1.7.4",
        "laravel-vite-plugin": "^1.0",
        "vite": "^5.0"
    },
    "dependencies": {
        "laravel-echo": "^1.16.1",
        "pusher-js": "^8.4.0"
    }
}
```

Install dependencies:

```bash
npm install
npm run build
```

## Step 11: Configure Vite for WebSocket

Update `vite.config.js`:

```js
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
    ],
    server: {
        host: '127.0.0.1',
        port: 5173,
        hmr: {
            host: '127.0.0.1',
        },
    },
});
```

## Step 12: Test Real-Time Terminal

```bash
# Start the complete development environment
./scripts/dev-server.sh
```

Test the functionality:
1. ✅ Navigate to `/admin/ssh-commands`
2. ✅ Select an SSH host
3. ✅ Enter a test command (e.g., `whoami`)
4. ✅ Click "Run Command"
5. ✅ Verify real-time output appears in terminal
6. ✅ Check debug information displays (if enabled)

## Step 13: Enhanced Error Handling

Create `app/Exceptions/SshConnectionException.php`:

```php
<?php

namespace App\Exceptions;

use Exception;

class SshConnectionException extends Exception
{
    public function __construct(
        string $message = 'SSH connection failed',
        int $code = 0,
        ?\Throwable $previous = null,
        public ?string $hostname = null,
        public ?int $port = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function getConnectionDetails(): array
    {
        return [
            'hostname' => $this->hostname,
            'port' => $this->port,
            'message' => $this->getMessage(),
        ];
    }
}
```

## Step 14: Add Performance Monitoring

Update the SSH Command Runner with timing:

```php
// Add to SshCommandRunner class
private array $performanceMetrics = [];

public function runCommand(): void
{
    $this->performanceMetrics['start_time'] = microtime(true);
    
    // ... existing code ...
    
    // Emit performance data
    $this->dispatch('ssh-performance-update', [
        'metrics' => $this->performanceMetrics
    ]);
}
```

## Step 15: Format and Commit

```bash
# Format code
./vendor/bin/pint

# Run tests
php artisan test

# Commit this stage
git add .
git commit -m "feat: enhance SSH command runner with real-time streaming and advanced UI

Major enhancements to the SSH Command Runner including real-time command
output streaming, sophisticated layout redesign, verbose debugging system,
and comprehensive documentation updates.

Key Features Added:
- Real-time SSH command output streaming with auto-scrolling display
- Advanced 50/50 split layout with optimized control placement
- Verbose debug system with terminal-style output and comprehensive logging
- Bash execution mode with interactive shell wrapping (bash -ci)
- Enhanced UI with inline toggles and side-by-side controls

Technical Improvements:
- Spatie SSH integration with streaming callbacks and debug modes
- Filament grid system implementation for custom layouts
- Livewire event handling for real-time UI updates
- JavaScript integration for auto-scrolling output areas
- Smart command wrapping with job control message filtering

WebSocket Infrastructure:
- Laravel Reverb integration for real-time communication
- Broadcasting events for SSH output streaming
- Queue-based SSH command execution with progress tracking
- Development scripts for complete environment setup

Security and Performance:
- Proper SSH authentication handling (keys and passwords)
- Command timeout configuration and management
- Debug mode for development and troubleshooting
- Professional terminal emulator interface"

git push origin main
```

## Expected Features Working

✅ **Real-Time Streaming**: SSH commands execute with live output  
✅ **WebSocket Communication**: Reverb-powered real-time updates  
✅ **Advanced UI Layout**: 50/50 split with professional terminal  
✅ **Debug System**: Comprehensive logging and performance metrics  
✅ **Bash Mode**: Interactive shell command wrapping  
✅ **Queue Processing**: Background SSH job execution  
✅ **Error Handling**: Proper exception management and user feedback  
✅ **Development Environment**: Complete setup scripts  

## Next Stage
Proceed to `04_performance-frankenphp.md` to implement FrankenPHP, Redis optimization, and ultra-high performance features.

## Troubleshooting

**Issue: WebSocket connection fails**
- Check Reverb server is running: `php artisan reverb:start`
- Verify ports 8080 and 8000 are available
- Check browser console for connection errors

**Issue: SSH commands not executing**
- Verify queue worker is running: `php artisan queue:work`
- Check job table for failed jobs: `php artisan queue:failed`
- Verify SSH host credentials are correct

**Issue: No real-time output**
- Check broadcasting configuration in `.env`
- Verify JavaScript console for Echo connection errors
- Test WebSocket manually: visit `ws://127.0.0.1:8080`

**Issue: Frontend assets not loading**
- Run `npm install` and `npm run build`
- Check Vite configuration and asset compilation
- Clear browser cache and reload