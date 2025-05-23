<?php

namespace App\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static string getHomeDir()
 * @method static string getDefaultUser()
 * @method static int getDefaultPort()
 * @method static string getDefaultKeyType()
 * @method static bool getStrictHostChecking()
 *
 * @see \App\Settings\SshSettings
 */
class SshSettings extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return \App\Settings\SshSettings::class;
    }
}
