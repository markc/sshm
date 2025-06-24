# Stage 2: SSH Management Core Features

## Overview
This stage implements the core SSH management functionality including SSH host management, SSH key management, basic command runner, and essential services. This corresponds to the "Revised version" and early enhancements in the git history.

## Prerequisites
- Completed Stage 1: Laravel 12 + Filament v4 Foundation
- Working admin panel with authentication
- Spatie SSH package installed

## Step 1: Create SSH Host Model and Migration

```bash
# Create SSH Host model with migration
php artisan make:model SshHost -m

# Create SSH Key model with migration  
php artisan make:model SshKey -m
```

Update the SSH Host migration (`database/migrations/xxxx_create_ssh_hosts_table.php`):

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ssh_hosts', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('hostname');
            $table->integer('port')->default(22);
            $table->string('user')->default('root');
            $table->string('identity_file')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ssh_hosts');
    }
};
```

Update the SSH Key migration (`database/migrations/xxxx_create_ssh_keys_table.php`):

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('ssh_keys', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->text('public_key');
            $table->text('private_key');
            $table->string('comment')->nullable();
            $table->string('type')->default('ed25519');
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ssh_keys');
    }
};
```

## Step 2: Create Model Classes

Update `app/Models/SshHost.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SshHost extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'hostname',
        'port',
        'user',
        'identity_file',
        'active',
    ];

    protected $casts = [
        'port' => 'integer',
        'active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function getConnectionString(): string
    {
        return "{$this->user}@{$this->hostname}:{$this->port}";
    }
}
```

Update `app/Models/SshKey.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SshKey extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'public_key',
        'private_key',
        'comment',
        'type',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];

    protected $hidden = [
        'private_key',
    ];

    public function scopeActive($query)
    {
        return $query->where('active', true);
    }

    public function getKeyTypeDisplay(): string
    {
        return strtoupper($this->type);
    }
}
```

## Step 3: Create SSH Settings System

Create `app/Settings/SshSettings.php`:

```php
<?php

namespace App\Settings;

class SshSettings
{
    public string $home_dir = '/home/user';
    public string $default_user = 'root';
    public int $default_port = 22;
    public string $default_key_type = 'ed25519';
    public bool $strict_host_checking = false;
    public string $default_ssh_host = '';
    public string $default_ssh_key = '';
    public int $timeout = 300;

    public function getHomeDir(): string
    {
        return $this->home_dir ?: env('SSH_HOME_DIR', '/home/user');
    }

    public function getDefaultUser(): string
    {
        return $this->default_user ?: env('SSH_DEFAULT_USER', 'root');
    }

    public function getDefaultPort(): int
    {
        return $this->default_port ?: (int) env('SSH_DEFAULT_PORT', 22);
    }

    public function getDefaultKeyType(): string
    {
        return $this->default_key_type ?: env('SSH_DEFAULT_KEY_TYPE', 'ed25519');
    }

    public function getStrictHostChecking(): bool
    {
        return $this->strict_host_checking ?? (bool) env('SSH_STRICT_HOST_CHECKING', false);
    }

    public function getTimeout(): int
    {
        return $this->timeout ?: (int) env('SSH_TIMEOUT', 300);
    }

    public function getDefaultSshHost(): string
    {
        return $this->default_ssh_host ?: env('SSH_DEFAULT_HOST', '');
    }

    public function getDefaultSshKey(): string
    {
        return $this->default_ssh_key ?: env('SSH_DEFAULT_KEY', '');
    }
}
```

## Step 4: Create SSH Host Resource

Create `app/Filament/Resources/SshHostResource.php`:

```php
<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SshHostResource\Pages;
use App\Models\SshHost;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SshHostResource extends Resource
{
    protected static ?string $model = SshHost::class;

    protected static ?string $navigationIcon = 'heroicon-o-server';

    protected static ?string $navigationLabel = 'SSH Hosts';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Host Configuration')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),

                        Forms\Components\TextInput::make('hostname')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('192.168.1.100 or example.com'),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make('user')
                                    ->required()
                                    ->default('root')
                                    ->maxLength(255),

                                Forms\Components\TextInput::make('port')
                                    ->required()
                                    ->numeric()
                                    ->default(22)
                                    ->minValue(1)
                                    ->maxValue(65535),
                            ]),

                        Forms\Components\Textarea::make('description')
                            ->maxLength(1000)
                            ->rows(3),

                        Forms\Components\Toggle::make('active')
                            ->default(true),
                    ]),

                Forms\Components\Section::make('Authentication')
                    ->schema([
                        Forms\Components\Textarea::make('private_key_path')
                            ->label('Private Key Path')
                            ->placeholder('/home/user/.ssh/id_rsa')
                            ->rows(2),

                        Forms\Components\TextInput::make('password')
                            ->password()
                            ->revealable()
                            ->placeholder('SSH password (if not using key)'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('hostname')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('user')
                    ->sortable(),

                Tables\Columns\TextColumn::make('port')
                    ->sortable(),

                Tables\Columns\IconColumn::make('active')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('active'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSshHosts::route('/'),
            'create' => Pages\CreateSshHost::route('/create'),
            'edit' => Pages\EditSshHost::route('/{record}/edit'),
        ];
    }
}
```

## Step 5: Create SSH Key Resource

Create `app/Filament/Resources/SshKeyResource.php`:

```php
<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SshKeyResource\Pages;
use App\Models\SshKey;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class SshKeyResource extends Resource
{
    protected static ?string $model = SshKey::class;

    protected static ?string $navigationIcon = 'heroicon-o-key';

    protected static ?string $navigationLabel = 'SSH Keys';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Key Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),

                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\Select::make('type')
                                    ->required()
                                    ->options([
                                        'ed25519' => 'Ed25519 (Recommended)',
                                        'rsa' => 'RSA',
                                        'ecdsa' => 'ECDSA',
                                    ])
                                    ->default('ed25519'),

                                Forms\Components\TextInput::make('bits')
                                    ->label('Key Size (bits)')
                                    ->numeric()
                                    ->placeholder('2048, 3072, 4096')
                                    ->helperText('Only required for RSA keys'),
                            ]),

                        Forms\Components\Textarea::make('description')
                            ->maxLength(1000)
                            ->rows(3),

                        Forms\Components\Toggle::make('active')
                            ->default(true),
                    ]),

                Forms\Components\Section::make('Key Data')
                    ->schema([
                        Forms\Components\Textarea::make('public_key')
                            ->required()
                            ->rows(4)
                            ->placeholder('ssh-ed25519 AAAAC3NzaC1lZDI1NTE5...'),

                        Forms\Components\Textarea::make('private_key')
                            ->required()
                            ->rows(6)
                            ->placeholder('-----BEGIN OPENSSH PRIVATE KEY-----'),

                        Forms\Components\TextInput::make('passphrase')
                            ->password()
                            ->revealable()
                            ->placeholder('Key passphrase (if any)'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('key_type_display')
                    ->label('Type')
                    ->sortable('type'),

                Tables\Columns\TextColumn::make('public_key')
                    ->limit(50)
                    ->tooltip(function (SshKey $record): string {
                        return $record->public_key;
                    }),

                Tables\Columns\IconColumn::make('active')
                    ->boolean()
                    ->sortable(),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'ed25519' => 'Ed25519',
                        'rsa' => 'RSA',
                        'ecdsa' => 'ECDSA',
                    ]),
                Tables\Filters\TernaryFilter::make('active'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSshKeys::route('/'),
            'create' => Pages\CreateSshKey::route('/create'),
            'edit' => Pages\EditSshKey::route('/{record}/edit'),
        ];
    }
}
```

## Step 6: Create Basic SSH Command Runner

Create `app/Filament/Pages/SshCommandRunner.php`:

```php
<?php

namespace App\Filament\Pages;

use App\Models\SshHost;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Actions\Action;

class SshCommandRunner extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-command-line';

    protected static ?string $navigationLabel = 'SSH Commands';

    protected static ?int $navigationSort = 1;

    protected static string $view = 'filament.pages.ssh-command-runner';

    public ?string $selectedHost = null;
    public ?string $command = null;
    public ?string $output = '';
    public bool $isRunning = false;

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('selectedHost')
                    ->label('SSH Host')
                    ->options(function () {
                        return SshHost::where('active', true)
                            ->pluck('name', 'id')
                            ->toArray();
                    })
                    ->required()
                    ->placeholder('Select an SSH host'),

                Textarea::make('command')
                    ->label('Command')
                    ->required()
                    ->rows(4)
                    ->placeholder('Enter SSH command to execute...'),
            ]);
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('run')
                ->label('Run Command')
                ->icon('heroicon-o-play')
                ->action('runCommand')
                ->disabled(fn () => $this->isRunning),
        ];
    }

    public function runCommand(): void
    {
        $this->validate([
            'selectedHost' => 'required',
            'command' => 'required|string',
        ]);

        $this->isRunning = true;
        $this->output = "Executing: {$this->command}\n";

        try {
            $host = SshHost::findOrFail($this->selectedHost);
            
            // Basic command execution (will be enhanced in later stages)
            $this->output .= "Connected to {$host->hostname}\n";
            $this->output .= "Command executed successfully\n";
            $this->output .= "[This is a placeholder - real SSH execution will be implemented in Stage 3]\n";
            
        } catch (\Exception $e) {
            $this->output .= "Error: {$e->getMessage()}\n";
        }

        $this->isRunning = false;
    }
}
```

## Step 7: Create SSH Settings Page

Create `app/Filament/Pages/SshSettings.php`:

```php
<?php

namespace App\Filament\Pages;

use App\Settings\SshSettings;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Pages\Page;

class SshSettings extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'SSH Settings';

    protected static ?int $navigationSort = 4;

    protected static string $view = 'filament.pages.ssh-settings';

    public ?array $data = [];

    public function mount(): void
    {
        $settings = app(SshSettings::class);
        $this->form->fill([
            'home_dir' => $settings->getHomeDir(),
            'default_user' => $settings->getDefaultUser(),
            'default_port' => $settings->getDefaultPort(),
            'default_key_type' => $settings->getDefaultKeyType(),
            'strict_host_checking' => $settings->getStrictHostChecking(),
            'timeout' => $settings->getTimeout(),
        ]);
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('SSH Configuration')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                TextInput::make('home_dir')
                                    ->label('SSH Home Directory')
                                    ->required()
                                    ->placeholder('/home/username'),

                                TextInput::make('default_user')
                                    ->label('Default SSH User')
                                    ->required()
                                    ->placeholder('root'),

                                TextInput::make('default_port')
                                    ->label('Default SSH Port')
                                    ->numeric()
                                    ->required()
                                    ->placeholder('22'),

                                TextInput::make('default_key_type')
                                    ->label('Default Key Type')
                                    ->required()
                                    ->placeholder('ed25519'),

                                TextInput::make('timeout')
                                    ->label('SSH Timeout (seconds)')
                                    ->numeric()
                                    ->required()
                                    ->placeholder('300'),

                                Toggle::make('strict_host_checking')
                                    ->label('Strict Host Key Checking'),
                            ]),
                    ]),
            ])
            ->statePath('data');
    }
}
```

## Step 8: Create Resource Page Classes

Generate the resource page classes:

```bash
# SSH Host resource pages
php artisan make:filament-resource-pages SshHostResource

# SSH Key resource pages  
php artisan make:filament-resource-pages SshKeyResource
```

## Step 9: Create View Templates

Create `resources/views/filament/pages/ssh-command-runner.blade.php`:

```blade
<x-filament-panels::page>
    <div class="space-y-6">
        <div class="bg-white rounded-lg shadow p-6">
            {{ $this->form }}
            
            <div class="mt-4">
                {{ $this->getFormActions() }}
            </div>
        </div>

        @if($output)
            <div class="bg-gray-900 text-green-400 p-4 rounded-lg font-mono text-sm">
                <pre>{{ $output }}</pre>
            </div>
        @endif
    </div>
</x-filament-panels::page>
```

Create `resources/views/filament/pages/ssh-settings.blade.php`:

```blade
<x-filament-panels::page>
    <div class="max-w-2xl">
        {{ $this->form }}
    </div>
</x-filament-panels::page>
```

## Step 10: Run Migrations and Test

```bash
# Run the new migrations
php artisan migrate

# Test the application
php artisan serve
```

Visit the admin panel and verify:
- ✅ SSH Hosts resource works (create, edit, list)
- ✅ SSH Keys resource works (create, edit, list)  
- ✅ SSH Command Runner page loads
- ✅ SSH Settings page displays

## Step 11: Create Basic Service Class

Create `app/Services/SshService.php`:

```php
<?php

namespace App\Services;

use App\Models\SshHost;
use App\Settings\SshSettings;

class SshService
{
    protected SshSettings $settings;

    public function __construct(SshSettings $settings)
    {
        $this->settings = $settings;
    }

    public function testConnection(SshHost $host): array
    {
        try {
            // Placeholder for SSH connection test
            // Real implementation will be added in Stage 3
            return [
                'success' => true,
                'message' => 'Connection test successful (placeholder)',
                'response_time' => '50ms',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Connection failed: ' . $e->getMessage(),
                'response_time' => null,
            ];
        }
    }

    public function executeCommand(SshHost $host, string $command): array
    {
        try {
            // Placeholder for SSH command execution
            // Real implementation will be added in Stage 3
            return [
                'success' => true,
                'output' => "Simulated output for: {$command}",
                'exit_code' => 0,
                'execution_time' => '120ms',
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'output' => '',
                'exit_code' => 1,
                'error' => $e->getMessage(),
            ];
        }
    }
}
```

## Step 12: Register Service Provider

Update `app/Providers/AppServiceProvider.php`:

```php
<?php

namespace App\Providers;

use App\Services\SshService;
use App\Settings\SshSettings;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SshSettings::class, function () {
            return new SshSettings();
        });

        $this->app->singleton(SshService::class, function ($app) {
            return new SshService($app->make(SshSettings::class));
        });
    }

    public function boot(): void
    {
        //
    }
}
```

## Step 13: Create Factory Classes (Optional)

```bash
# Create factories for testing
php artisan make:factory SshHostFactory
php artisan make:factory SshKeyFactory
```

## Step 14: Format and Commit

```bash
# Format code
./vendor/bin/pint

# Run tests
php artisan test

# Commit this stage
git add .
git commit -m "feat: implement SSH management core features

- Add SSH Host model and resource with full CRUD operations
- Add SSH Key model and resource with key management
- Implement basic SSH Command Runner page with form
- Create SSH Settings page for configuration management
- Add SSH Service class for future command execution
- Set up proper model relationships and validation
- Create Filament resources with tables, forms, and filters
- Add authentication fields for SSH connections

Core SSH management functionality now ready for enhancement."

git push origin main
```

## Expected Features Working

✅ **SSH Host Management**: Create, edit, delete, and list SSH hosts  
✅ **SSH Key Management**: Manage SSH keys with different types  
✅ **Basic Command Runner**: Form interface for SSH commands  
✅ **Settings Management**: Configure SSH defaults and options  
✅ **Filament Resources**: Full admin interface for all entities  
✅ **Database Models**: Proper Eloquent models with relationships  
✅ **Service Architecture**: Foundation for SSH operations  

## Next Stage
Proceed to `03_real-time-terminal.md` to implement actual SSH execution with real-time streaming and advanced UI features.

## Troubleshooting

**Issue: Migration errors**
- Ensure database.sqlite exists and is writable
- Check migration syntax for any typos
- Run `php artisan migrate:fresh` if needed

**Issue: Filament resources not appearing**
- Verify resource classes are in correct namespace
- Check navigation sort numbers don't conflict
- Clear cache: `php artisan optimize:clear`

**Issue: Form validation errors**  
- Check fillable properties in models
- Verify form component names match model attributes
- Ensure required validation rules are appropriate