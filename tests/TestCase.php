<?php

namespace Sobhanatar\Idempotent\Tests;

use Orchestra\Testbench\TestCase as TestBench;
use Sobhanatar\Idempotent\IdempotentServiceProvider;

abstract class TestCase extends TestBench
{
    use TestHelper;

    protected const TEST_APP_TEMPLATE = __DIR__ . '/../testbench/template';
    protected const TEST_APP = __DIR__ . '/../testbench/laravel';
    protected const MIGRATION_PATH = __DIR__ . '/../database/migrations';

    //.env should be in testbench/template
    protected bool $loadEnvironmentVariables = true;

    public static function setUpBeforeClass(): void
    {
        if (!file_exists(self::TEST_APP_TEMPLATE)) {
            self::setUpLocalTestbench();
        }
        parent::setUpBeforeClass();
    }

    /**
     * @return string
     */
    protected function getBasePath(): string
    {
        return self::TEST_APP;
    }

    /**
     * Setup before each test.
     */
    public function setUp(): void
    {
        $this->installTestApp();
        parent::setUp();
    }

    /**
     * Tear down after each test.
     */
    public function tearDown(): void
    {
        $this->uninstallTestApp();
        parent::tearDown();
    }

    /**
     * Tell Testbench to use this package.
     *
     * @param $app
     * @return array
     */
    protected function getPackageProviders($app): array
    {
        return [
            IdempotentServiceProvider::class
        ];
    }
}
