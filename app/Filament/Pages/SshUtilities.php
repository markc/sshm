<?php

namespace App\Filament\Pages;

use App\Services\SshManagerService;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Process;

class SshUtilities extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-wrench';
    
    protected static ?string $navigationLabel = 'SSH Utilities';
    
    protected static ?string $navigationGroup = 'SSH Management';
    
    protected static ?int $navigationSort = 30;

    protected static string $view = 'filament.pages.ssh-utilities';
    
    public ?string $sshStatus = null;
    
    public ?string $sshDirInfo = null;
    
    public function mount(): void
    {
        $this->checkSshStatus();
        $this->getSshDirInfo();
    }
    
    protected function getActions(): array
    {
        return [
            Action::make('initSshDir')
                ->label('Initialize SSH Directory')
                ->icon('heroicon-o-folder-plus')
                ->color('success')
                ->action(function () {
                    $sshManager = new SshManagerService();
                    
                    try {
                        $result = $sshManager->initializeSshDirectory();
                        
                        Notification::make()
                            ->title('SSH Directory Initialized')
                            ->body(implode(', ', $result))
                            ->success()
                            ->send();
                            
                        $this->getSshDirInfo();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Initialization Failed')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
            
            Action::make('fixPermissions')
                ->label('Fix SSH Permissions')
                ->icon('heroicon-o-shield-check')
                ->color('warning')
                ->action(function () {
                    $sshManager = new SshManagerService();
                    
                    try {
                        $sshManager->setPermissions();
                        
                        Notification::make()
                            ->title('SSH Permissions Fixed')
                            ->body('All SSH files and directories have been set to the correct permissions.')
                            ->success()
                            ->send();
                            
                        $this->getSshDirInfo();
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Permission Fix Failed')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
                
            Action::make('startSshd')
                ->label('Start SSH Server')
                ->icon('heroicon-o-play')
                ->color('success')
                ->visible(fn () => $this->sshStatus === 'inactive')
                ->action(function () {
                    try {
                        $process = Process::timeout(60)->run('sudo systemctl start sshd && sudo systemctl enable sshd');
                        
                        if ($process->successful()) {
                            Notification::make()
                                ->title('SSH Server Started')
                                ->body('The SSH server has been started and enabled.')
                                ->success()
                                ->send();
                                
                            $this->checkSshStatus();
                        } else {
                            throw new \Exception("Failed to start SSH server: " . $process->errorOutput());
                        }
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Failed to Start SSH Server')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
                
            Action::make('stopSshd')
                ->label('Stop SSH Server')
                ->icon('heroicon-o-stop')
                ->color('danger')
                ->visible(fn () => $this->sshStatus === 'active')
                ->action(function () {
                    try {
                        $process = Process::timeout(60)->run('sudo systemctl stop sshd && sudo systemctl disable sshd');
                        
                        if ($process->successful()) {
                            Notification::make()
                                ->title('SSH Server Stopped')
                                ->body('The SSH server has been stopped and disabled.')
                                ->success()
                                ->send();
                                
                            $this->checkSshStatus();
                        } else {
                            throw new \Exception("Failed to stop SSH server: " . $process->errorOutput());
                        }
                    } catch (\Exception $e) {
                        Notification::make()
                            ->title('Failed to Stop SSH Server')
                            ->body($e->getMessage())
                            ->danger()
                            ->send();
                    }
                }),
                
            Action::make('refreshStatus')
                ->label('Refresh Status')
                ->icon('heroicon-o-arrow-path')
                ->action(function () {
                    $this->checkSshStatus();
                    $this->getSshDirInfo();
                    
                    Notification::make()
                        ->title('Status Refreshed')
                        ->success()
                        ->send();
                }),
        ];
    }
    
    private function checkSshStatus(): void
    {
        try {
            $process = Process::timeout(10)->run('systemctl is-active sshd');
            $this->sshStatus = trim($process->output());
        } catch (\Exception $e) {
            $this->sshStatus = 'unknown';
        }
    }
    
    private function getSshDirInfo(): void
    {
        try {
            $sshManager = new SshManagerService();
            $sshDir = $sshManager->getSshDir();
            
            $dirInfo = [];
            
            // Check if directory exists
            if (!is_dir($sshDir)) {
                $dirInfo[] = "SSH directory does not exist";
            } else {
                $dirInfo[] = "SSH directory: {$sshDir}";
                
                // Check config file
                if (file_exists("{$sshDir}/config")) {
                    $dirInfo[] = "Config file: exists";
                } else {
                    $dirInfo[] = "Config file: missing";
                }
                
                // Check config.d directory
                if (is_dir("{$sshDir}/config.d")) {
                    $configCount = count(glob("{$sshDir}/config.d/*"));
                    $dirInfo[] = "Config.d directory: exists with {$configCount} configs";
                } else {
                    $dirInfo[] = "Config.d directory: missing";
                }
                
                // Check authorized_keys
                if (file_exists("{$sshDir}/authorized_keys")) {
                    $keyCount = count(file("{$sshDir}/authorized_keys", FILE_SKIP_EMPTY_LINES));
                    $dirInfo[] = "Authorized keys: {$keyCount} keys";
                } else {
                    $dirInfo[] = "Authorized keys: missing";
                }
                
                // Check SSH keys
                $keyFiles = glob("{$sshDir}/*.pub");
                $dirInfo[] = "SSH keys: " . count($keyFiles);
            }
            
            $this->sshDirInfo = implode("\n", $dirInfo);
        } catch (\Exception $e) {
            $this->sshDirInfo = "Error retrieving SSH directory information: " . $e->getMessage();
        }
    }
}
