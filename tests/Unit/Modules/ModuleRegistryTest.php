<?php

namespace Tests\Unit\Modules;

use App\Modules\Billing\BillingModule;
use App\Modules\ModuleManifest;
use App\Modules\ModuleRegistry;
use RuntimeException;
use Tests\TestCase;

class ModuleRegistryTest extends TestCase
{
    public function test_is_enabled_reflects_config(): void
    {
        config(['modules.modules' => [], 'modules.enabled' => ['billing' => true]]);

        $registry = new ModuleRegistry;

        $this->assertTrue($registry->isEnabled('billing'));
        $this->assertFalse($registry->isEnabled('mobile'));
    }

    public function test_manifest_returns_the_registered_manifest_instance(): void
    {
        config(['modules.modules' => [BillingModule::class], 'modules.enabled' => ['billing' => true]]);

        $registry = new ModuleRegistry;

        $this->assertSame('billing', $registry->manifest('billing')?->name());
        $this->assertNull($registry->manifest('nonexistent'));
    }

    public function test_throws_when_an_enabled_module_requires_a_disabled_dependency(): void
    {
        config([
            'modules.modules' => [FakeModuleWithDependency::class],
            'modules.enabled' => ['fake' => true, 'required' => false],
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Module [fake] requires [required], which is not enabled.');

        new ModuleRegistry;
    }

    public function test_throws_when_two_conflicting_modules_are_both_enabled(): void
    {
        config([
            'modules.modules' => [FakeModuleWithConflict::class],
            'modules.enabled' => ['fake' => true, 'rival' => true],
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Module [fake] conflicts with [rival], which is also enabled.');

        new ModuleRegistry;
    }

    public function test_does_not_validate_dependencies_for_a_disabled_module(): void
    {
        config([
            'modules.modules' => [FakeModuleWithDependency::class],
            'modules.enabled' => ['fake' => false, 'required' => false],
        ]);

        $registry = new ModuleRegistry;

        $this->assertFalse($registry->isEnabled('fake'));
    }
}

class FakeModuleWithDependency implements ModuleManifest
{
    public function name(): string
    {
        return 'fake';
    }

    public function permissions(): array
    {
        return [];
    }

    public function dependencies(): array
    {
        return ['required'];
    }

    public function conflicts(): array
    {
        return [];
    }
}

class FakeModuleWithConflict implements ModuleManifest
{
    public function name(): string
    {
        return 'fake';
    }

    public function permissions(): array
    {
        return [];
    }

    public function dependencies(): array
    {
        return [];
    }

    public function conflicts(): array
    {
        return ['rival'];
    }
}
