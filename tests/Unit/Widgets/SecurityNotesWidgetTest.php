<?php

use App\Filament\Widgets\SecurityNotesWidget;

describe('SecurityNotesWidget', function () {
    beforeEach(function () {
        $this->widget = new SecurityNotesWidget();
    });

    it('can instantiate the widget', function () {
        expect($this->widget)->toBeInstanceOf(SecurityNotesWidget::class);
    });

    it('has correct sort order', function () {
        expect($this->widget::getSort())->toBe(3);
    });

    it('is collapsible', function () {
        expect($this->widget::canView())->toBeTrue();
    });

    it('has appropriate column span', function () {
        expect($this->widget->getColumnSpan())->toBe('full');
    });

    it('renders security notes view', function () {
        $view = $this->widget->render();

        expect($view)->toBeInstanceOf(\Illuminate\Contracts\View\View::class);
        expect($view->getName())->toBe('filament.widgets.security-notes');
    });

    it('provides security warning data to view', function () {
        $view = $this->widget->render();
        $data = $view->getData();

        expect($data)->toHaveKey('warnings');
        expect($data['warnings'])->toBeArray();
        expect($data['warnings'])->not->toBeEmpty();
    });

    it('includes critical security warnings', function () {
        $view = $this->widget->render();
        $warnings = $view->getData()['warnings'];

        $warningTexts = collect($warnings)->pluck('text')->implode(' ');

        expect($warningTexts)
            ->toContain('arbitrary SSH commands')
            ->toContain('production environment')
            ->toContain('limited privileges')
            ->toContain('trusted users');
    });

    it('categorizes warnings by severity', function () {
        $view = $this->widget->render();
        $warnings = $view->getData()['warnings'];

        $severities = collect($warnings)->pluck('severity')->unique();

        expect($severities)->toContain('danger');
        expect($severities)->toContain('warning');
    });

    it('provides actionable security recommendations', function () {
        $view = $this->widget->render();
        $warnings = $view->getData()['warnings'];

        $hasActionableItems = collect($warnings)->contains(function ($warning) {
            return str_contains($warning['text'], 'should') ||
                   str_contains($warning['text'], 'must') ||
                   str_contains($warning['text'], 'ensure');
        });

        expect($hasActionableItems)->toBeTrue();
    });

    it('includes warnings about command validation', function () {
        $view = $this->widget->render();
        $warnings = $view->getData()['warnings'];

        $warningTexts = collect($warnings)->pluck('text')->implode(' ');

        expect($warningTexts)->toContain('validation');
    });

    it('warns about authentication security', function () {
        $view = $this->widget->render();
        $warnings = $view->getData()['warnings'];

        $warningTexts = collect($warnings)->pluck('text')->implode(' ');

        expect($warningTexts)->toContain('authentication');
    });

    it('includes network security considerations', function () {
        $view = $this->widget->render();
        $warnings = $view->getData()['warnings'];

        $warningTexts = collect($warnings)->pluck('text')->implode(' ');

        expect($warningTexts)->toContain('network');
    });

    it('provides warnings with proper structure', function () {
        $view = $this->widget->render();
        $warnings = $view->getData()['warnings'];

        foreach ($warnings as $warning) {
            expect($warning)->toHaveKey('text');
            expect($warning)->toHaveKey('severity');
            expect($warning['text'])->toBeString();
            expect($warning['severity'])->toBeIn(['danger', 'warning', 'info']);
        }
    });
});
