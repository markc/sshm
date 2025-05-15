<?php

namespace App\Filament\Resources\SshKeyResource\Pages;

use App\Filament\Resources\SshKeyResource;
use App\Services\SshManagerService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateSshKey extends CreateRecord
{
    protected static string $resource = SshKeyResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        try {
            // Initialize SSH directory if it doesn't exist
            $sshManager = new SshManagerService;
            $sshManager->initializeSshDirectory();

            // Create the SSH key
            $keyDetails = $sshManager->createKey(
                $data['name'],
                $data['comment'] ?? '',
                $data['has_password'] ? $this->data['password'] ?? '' : '',
            );

            // Create the database record
            return static::getModel()::create([
                'name' => $data['name'],
                'comment' => $keyDetails['comment'],
                'algorithm' => $keyDetails['algorithm'],
                'bits' => $keyDetails['bits'],
                'fingerprint' => $keyDetails['fingerprint'],
                'has_password' => $keyDetails['has_password'],
                'path' => $keyDetails['path'],
            ]);
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error creating SSH key')
                ->body($e->getMessage())
                ->danger()
                ->send();

            $this->halt();
        }
    }
}
