<?php

namespace App\Observers;

use App\Models\SshConfig;

class SshConfigObserver
{
    /**
     * Handle the SshConfig "created" event.
     */
    public function created(SshConfig $sshConfig): void
    {
        if ($sshConfig->is_default) {
            $this->resetOtherDefaultConfigs($sshConfig);
        }
    }

    /**
     * Handle the SshConfig "updated" event.
     */
    public function updated(SshConfig $sshConfig): void
    {
        if ($sshConfig->is_default) {
            $this->resetOtherDefaultConfigs($sshConfig);
        }
    }

    /**
     * Reset default flag on other configs
     */
    private function resetOtherDefaultConfigs(SshConfig $currentConfig): void
    {
        SshConfig::where('id', '!=', $currentConfig->id)
            ->where('is_default', true)
            ->update(['is_default' => false]);
    }

    /**
     * Handle the SshConfig "deleted" event.
     */
    public function deleted(SshConfig $sshConfig): void
    {
        //
    }

    /**
     * Handle the SshConfig "restored" event.
     */
    public function restored(SshConfig $sshConfig): void
    {
        //
    }

    /**
     * Handle the SshConfig "force deleted" event.
     */
    public function forceDeleted(SshConfig $sshConfig): void
    {
        //
    }
}
