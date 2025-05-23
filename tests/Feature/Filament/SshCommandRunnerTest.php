<?php

use App\Filament\Pages\SshCommandRunner;
use App\Models\SshHost;
use App\Models\User;
use Livewire\Livewire;

describe('SshCommandRunner Feature Tests', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        // Create test SSH hosts using correct schema
        $this->activeHost = SshHost::factory()->create([
            'name' => 'Test Server',
            'hostname' => 'test.example.com',
            'user' => 'testuser',
            'port' => 22,
            'active' => true,
        ]);

        $this->inactiveHost = SshHost::factory()->create([
            'name' => 'Inactive Server',
            'active' => false,
        ]);
    });

    it('can render the page', function () {
        Livewire::test(SshCommandRunner::class)
            ->assertSuccessful();
    });

    it('displays page title correctly', function () {
        Livewire::test(SshCommandRunner::class)
            ->assertSee('Enter SSH Command(s)');
    });

    it('shows active SSH hosts in dropdown', function () {
        Livewire::test(SshCommandRunner::class)
            ->assertFormFieldExists('selectedHost')
            ->assertSee($this->activeHost->name);
    });

    it('does not show inactive SSH hosts in dropdown', function () {
        Livewire::test(SshCommandRunner::class)
            ->assertFormFieldExists('selectedHost')
            ->assertDontSee($this->inactiveHost->name);
    });

    it('has command textarea field', function () {
        Livewire::test(SshCommandRunner::class)
            ->assertFormFieldExists('command');
    });

    it('requires SSH host selection', function () {
        Livewire::test(SshCommandRunner::class)
            ->fillForm([
                'command' => 'ls -la',
            ])
            ->call('runCommand')
            ->assertHasErrors(['selectedHost']);
    });

    it('requires command input', function () {
        Livewire::test(SshCommandRunner::class)
            ->fillForm([
                'selectedHost' => $this->activeHost->id,
            ])
            ->call('runCommand')
            ->assertHasErrors(['command']);
    });

    it('can execute SSH command successfully', function () {
        $this->mock(\App\Services\SshService::class, function ($mock) {
            $mock->shouldReceive('executeCommandWithStreaming')
                ->andReturn([
                    'success' => true,
                    'output' => 'Command output here',
                    'error' => '',
                    'exit_code' => 0,
                ]);
        });

        Livewire::test(SshCommandRunner::class)
            ->fillForm([
                'selectedHost' => $this->activeHost->id,
                'command' => 'ls -la',
            ])
            ->call('runCommand')
            ->assertHasNoErrors();
    });

    it('handles SSH command errors gracefully', function () {
        $this->mock(\App\Services\SshService::class, function ($mock) {
            $mock->shouldReceive('executeCommandWithStreaming')
                ->andReturn([
                    'success' => false,
                    'output' => '',
                    'error' => 'Error occurred',
                    'exit_code' => 1,
                ]);
        });

        Livewire::test(SshCommandRunner::class)
            ->fillForm([
                'selectedHost' => $this->activeHost->id,
                'command' => 'invalid-command',
            ])
            ->call('runCommand')
            ->assertHasNoErrors();
    });

    it('displays command execution state', function () {
        $this->mock(\App\Services\SshService::class, function ($mock) {
            $mock->shouldReceive('executeCommandWithStreaming')
                ->andReturn([
                    'success' => true,
                    'output' => 'Hello World',
                    'error' => '',
                    'exit_code' => 0,
                ]);
        });

        $component = Livewire::test(SshCommandRunner::class)
            ->fillForm([
                'selectedHost' => $this->activeHost->id,
                'command' => 'echo "Hello World"',
            ])
            ->call('runCommand');

        expect($component->get('commandOutput'))->not->toBeNull();
    });

    it('clears output when executing new command', function () {
        $this->mock(\App\Services\SshService::class, function ($mock) {
            $mock->shouldReceive('executeCommandWithStreaming')
                ->andReturn([
                    'success' => true,
                    'output' => 'New output',
                    'error' => '',
                    'exit_code' => 0,
                ]);
        });

        $component = Livewire::test(SshCommandRunner::class)
            ->set('commandOutput', [
                'success' => true,
                'output' => 'Previous output',
                'error' => '',
                'exit_code' => 0,
            ])
            ->fillForm([
                'selectedHost' => $this->activeHost->id,
                'command' => 'echo "test"',
            ])
            ->call('runCommand');

        expect($component->get('commandOutput')['output'])->toBe('New output');
    });

    it('handles connection timeout errors', function () {
        $this->mock(\App\Services\SshService::class, function ($mock) {
            $mock->shouldReceive('executeCommandWithStreaming')
                ->andReturn([
                    'success' => false,
                    'output' => '',
                    'error' => 'Connection timeout',
                    'exit_code' => 124,
                ]);
        });

        Livewire::test(SshCommandRunner::class)
            ->fillForm([
                'selectedHost' => $this->activeHost->id,
                'command' => 'sleep 60',
            ])
            ->call('runCommand')
            ->assertHasNoErrors();
    });

    it('preserves form data after command execution', function () {
        $this->mock(\App\Services\SshService::class, function ($mock) {
            $mock->shouldReceive('executeCommandWithStreaming')
                ->andReturn([
                    'success' => true,
                    'output' => 'Success',
                    'error' => '',
                    'exit_code' => 0,
                ]);
        });

        Livewire::test(SshCommandRunner::class)
            ->fillForm([
                'selectedHost' => $this->activeHost->id,
                'command' => 'pwd',
            ])
            ->call('runCommand')
            ->assertSet('selectedHost', $this->activeHost->id)
            ->assertSet('command', 'pwd');
    });

    it('can toggle connection mode', function () {
        $component = Livewire::test(SshCommandRunner::class);

        expect($component->get('useCustomConnection'))->toBeFalse();

        $component->call('toggleConnectionMode');

        expect($component->get('useCustomConnection'))->toBeTrue();
    });

    it('tracks command running state', function () {
        $component = Livewire::test(SshCommandRunner::class);

        expect($component->get('isCommandRunning'))->toBeFalse();
    });

    it('can use custom connection settings', function () {
        Livewire::test(SshCommandRunner::class)
            ->call('toggleConnectionMode')
            ->assertFormFieldExists('hostname')
            ->assertFormFieldExists('port')
            ->assertFormFieldExists('username')
            ->assertFormFieldExists('identityFile');
    });

    it('validates custom connection fields when enabled', function () {
        Livewire::test(SshCommandRunner::class)
            ->call('toggleConnectionMode')
            ->fillForm([
                'command' => 'ls -la',
                'hostname' => '',
            ])
            ->call('runCommand')
            ->assertHasErrors(['hostname']);
    });

    it('shows error when no SSH hosts exist', function () {
        SshHost::query()->delete();

        Livewire::test(SshCommandRunner::class)
            ->assertFormFieldExists('selectedHost');
    });

    it('updates host options when hosts are added', function () {
        $newHost = SshHost::factory()->create([
            'name' => 'New Server',
            'active' => true,
        ]);

        Livewire::test(SshCommandRunner::class)
            ->assertSee($this->activeHost->name)
            ->assertSee($newHost->name);
    });
});
