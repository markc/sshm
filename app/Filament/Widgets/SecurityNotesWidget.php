<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;

class SecurityNotesWidget extends Widget
{
    protected static string $view = 'filament.widgets.security-notes';

    protected static ?int $sort = 3;

    protected int|string|array $columnSpan = 'full';

    protected function getViewData(): array
    {
        return [
            'warnings' => $this->getSecurityWarnings(),
        ];
    }

    private function getSecurityWarnings(): array
    {
        return [
            [
                'text' => 'This application allows execution of arbitrary SSH commands on remote servers. Exercise extreme caution when deploying in a production environment.',
                'severity' => 'danger',
            ],
            [
                'text' => 'Ensure that SSH connections are made with limited privileges user accounts on remote servers.',
                'severity' => 'danger',
            ],
            [
                'text' => 'Only trusted users should have access to the SSH command runner functionality.',
                'severity' => 'warning',
            ],
            [
                'text' => 'Implement proper command validation and logging for security auditing purposes.',
                'severity' => 'warning',
            ],
            [
                'text' => 'Use SSH key-based authentication instead of passwords for enhanced security.',
                'severity' => 'warning',
            ],
            [
                'text' => 'Ensure network connections are properly secured and encrypted.',
                'severity' => 'info',
            ],
            [
                'text' => 'Regular security audits should be performed on all SSH configurations.',
                'severity' => 'info',
            ],
        ];
    }
}
