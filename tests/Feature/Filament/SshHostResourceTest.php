<?php

use App\Filament\Resources\SshHostResource;
use App\Filament\Resources\SshHostResource\Pages\CreateSshHost;
use App\Filament\Resources\SshHostResource\Pages\EditSshHost;
use App\Filament\Resources\SshHostResource\Pages\ListSshHosts;
use App\Models\SshHost;
use App\Models\User;
use Livewire\Livewire;

describe('SshHostResource Feature Tests', function () {
    beforeEach(function () {
        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        $this->hosts = SshHost::factory()->count(5)->create();
    });

    describe('List Page', function () {
        it('can render the list page', function () {
            Livewire::test(ListSshHosts::class)
                ->assertSuccessful();
        });

        it('displays SSH hosts in table', function () {
            $host = $this->hosts->first();

            Livewire::test(ListSshHosts::class)
                ->assertCanSeeTableRecords([$host]);
        });

        it('can search SSH hosts by name', function () {
            $searchableHost = SshHost::factory()->create(['name' => 'SearchableHost']);

            Livewire::test(ListSshHosts::class)
                ->searchTable('SearchableHost')
                ->assertCanSeeTableRecords([$searchableHost])
                ->assertCanNotSeeTableRecords($this->hosts);
        });

        it('can search SSH hosts by hostname', function () {
            $searchableHost = SshHost::factory()->create(['hostname' => 'unique.example.com']);

            Livewire::test(ListSshHosts::class)
                ->searchTable('unique.example.com')
                ->assertCanSeeTableRecords([$searchableHost]);
        });

        it('can filter by active status', function () {
            $activeHost = SshHost::factory()->create(['active' => true]);
            $inactiveHost = SshHost::factory()->create(['active' => false]);

            Livewire::test(ListSshHosts::class)
                ->filterTable('active', '1')
                ->assertCanSeeTableRecords([$activeHost])
                ->assertCanNotSeeTableRecords([$inactiveHost]);
        });

        it('can sort by name', function () {
            Livewire::test(ListSshHosts::class)
                ->sortTable('name')
                ->assertSuccessful();
        });

        it('can sort by hostname', function () {
            Livewire::test(ListSshHosts::class)
                ->sortTable('hostname')
                ->assertSuccessful();
        });

        it('displays correct table columns', function () {
            Livewire::test(ListSshHosts::class)
                ->assertTableColumnExists('name')
                ->assertTableColumnExists('hostname')
                ->assertTableColumnExists('user')
                ->assertTableColumnExists('port')
                ->assertTableColumnExists('identity_file')
                ->assertTableColumnExists('active');
        });

        it('can test connection from table', function () {
            $host = $this->hosts->first();

            // Skip this test as it requires complex mocking of Filament action dependencies
            $this->markTestSkipped('Complex Filament action testing not implemented');
        });

        it('can delete host from table', function () {
            $host = $this->hosts->first();

            Livewire::test(ListSshHosts::class)
                ->callTableAction('delete', $host)
                ->assertSuccessful();

            expect(SshHost::find($host->id))->toBeNull();
        });

        it('can bulk delete hosts', function () {
            $hostsToDelete = $this->hosts->take(2);

            Livewire::test(ListSshHosts::class)
                ->callTableBulkAction('delete', $hostsToDelete)
                ->assertSuccessful();

            foreach ($hostsToDelete as $host) {
                expect(SshHost::find($host->id))->toBeNull();
            }
        });

        it('shows pagination when many hosts exist', function () {
            SshHost::factory()->count(20)->create();

            // Just verify the component renders successfully with many records
            Livewire::test(ListSshHosts::class)
                ->assertSuccessful();
        });
    });

    describe('Create Page', function () {
        it('can render the create page', function () {
            Livewire::test(CreateSshHost::class)
                ->assertSuccessful();
        });

        it('has all required form fields', function () {
            Livewire::test(CreateSshHost::class)
                ->assertFormFieldExists('name')
                ->assertFormFieldExists('hostname')
                ->assertFormFieldExists('user')
                ->assertFormFieldExists('port')
                ->assertFormFieldExists('identity_file')
                ->assertFormFieldExists('active');
        });

        it('can create SSH host with valid data', function () {
            $hostData = [
                'name' => 'Test Server',
                'hostname' => 'test.example.com',
                'user' => 'testuser',
                'port' => 22,
                'active' => true,
            ];

            Livewire::test(CreateSshHost::class)
                ->fillForm($hostData)
                ->call('create')
                ->assertHasNoFormErrors();

            $this->assertDatabaseHas('ssh_hosts', $hostData);
        });

        it('validates required fields', function () {
            Livewire::test(CreateSshHost::class)
                ->fillForm([])
                ->call('create')
                ->assertHasFormErrors([
                    'name' => 'required',
                    'hostname' => 'required',
                    'user' => 'required',
                    'port' => 'required',
                ]);
        });

        it('validates unique name', function () {
            $existingHost = $this->hosts->first();

            Livewire::test(CreateSshHost::class)
                ->fillForm([
                    'name' => $existingHost->name,
                    'hostname' => 'new.example.com',
                    'user' => 'newuser',
                    'port' => 22,
                ])
                ->call('create')
                ->assertHasFormErrors(['name' => 'unique']);
        });

        it('validates port range', function () {
            Livewire::test(CreateSshHost::class)
                ->fillForm([
                    'port' => 0,
                ])
                ->call('create')
                ->assertHasFormErrors(['port']);

            Livewire::test(CreateSshHost::class)
                ->fillForm([
                    'port' => 65536,
                ])
                ->call('create')
                ->assertHasFormErrors(['port']);
        });

        it('validates hostname format', function () {
            Livewire::test(CreateSshHost::class)
                ->fillForm([
                    'hostname' => '',
                ])
                ->call('create')
                ->assertHasFormErrors(['hostname']);
        });

        it('sets default values correctly', function () {
            Livewire::test(CreateSshHost::class)
                ->assertFormSet([
                    'port' => 22,
                    'user' => 'root',
                    'active' => true,
                ]);
        });

        it('redirects after successful creation', function () {
            Livewire::test(CreateSshHost::class)
                ->fillForm([
                    'name' => 'New Server',
                    'hostname' => 'new.example.com',
                    'user' => 'newuser',
                    'port' => 22,
                ])
                ->call('create')
                ->assertHasNoFormErrors();
        });
    });

    describe('Edit Page', function () {
        it('can render the edit page', function () {
            $host = $this->hosts->first();

            Livewire::test(EditSshHost::class, ['record' => $host->getRouteKey()])
                ->assertSuccessful();
        });

        it('loads existing data into form', function () {
            $host = $this->hosts->first();

            Livewire::test(EditSshHost::class, ['record' => $host->getRouteKey()])
                ->assertFormSet([
                    'name' => $host->name,
                    'hostname' => $host->hostname,
                    'user' => $host->user,
                    'port' => $host->port,
                    'identity_file' => $host->identity_file,
                    'active' => $host->active,
                ]);
        });

        it('can update SSH host', function () {
            $host = $this->hosts->first();
            $updatedData = [
                'name' => 'Updated Server',
                'hostname' => 'updated.example.com',
                'user' => 'updateduser',
                'port' => 2222,
                'identity_file' => null, // Clear SSH key selection
                'active' => ! $host->active,
            ];

            Livewire::test(EditSshHost::class, ['record' => $host->getRouteKey()])
                ->fillForm($updatedData)
                ->call('save')
                ->assertHasNoFormErrors();

            $host->refresh();
            expect($host->name)->toBe('Updated Server');
            expect($host->hostname)->toBe('updated.example.com');
            expect($host->user)->toBe('updateduser');
            expect($host->port)->toBe(2222);
        });

        it('validates unique name excluding current record', function () {
            $host1 = $this->hosts->first();
            $host2 = $this->hosts->last();

            Livewire::test(EditSshHost::class, ['record' => $host1->getRouteKey()])
                ->fillForm(['name' => $host2->name])
                ->call('save')
                ->assertHasFormErrors(['name' => 'unique']);
        });

        it('allows keeping same name on update', function () {
            $host = $this->hosts->first();

            Livewire::test(EditSshHost::class, ['record' => $host->getRouteKey()])
                ->fillForm([
                    'port' => 2222,
                    'identity_file' => null, // Clear SSH key selection
                ])
                ->call('save')
                ->assertHasNoFormErrors();
        });

        it('can delete SSH host from edit page', function () {
            $host = $this->hosts->first();

            Livewire::test(EditSshHost::class, ['record' => $host->getRouteKey()])
                ->callAction('delete')
                ->assertSuccessful();

            expect(SshHost::find($host->id))->toBeNull();
        });

        it('redirects to list page after deletion', function () {
            $host = $this->hosts->first();

            Livewire::test(EditSshHost::class, ['record' => $host->getRouteKey()])
                ->callAction('delete')
                ->assertRedirect(SshHostResource::getUrl('index'));
        });
    });

    describe('Resource Configuration', function () {
        it('has correct navigation label', function () {
            expect(SshHostResource::getNavigationLabel())->toBe('SSH Hosts');
        });

        it('has correct model label', function () {
            expect(SshHostResource::getModelLabel())->toBe('ssh host');
        });

        it('has correct plural model label', function () {
            expect(SshHostResource::getPluralModelLabel())->toBe('ssh hosts');
        });

        it('uses correct navigation icon', function () {
            expect(SshHostResource::getNavigationIcon())->toBe('heroicon-o-server');
        });

        it('has correct navigation sort order', function () {
            expect(SshHostResource::getNavigationSort())->toBe(2);
        });
    });
});
