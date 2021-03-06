<?php

namespace Sobhanatar\Idempotent\Tests;

use Redis;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Sobhanatar\Idempotent\Config;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Process\Process;
use Illuminate\Contracts\Console\Kernel;

trait TestHelper
{
    protected Request $request;
    protected Config $config;
    protected Redis $redis;

    protected function seeInConsoleOutput($expectedText)
    {
        $consoleOutput = $this->app[Kernel::class]->output();
        $this->assertStringContainsString($expectedText, $consoleOutput,
            "Did not see `{$expectedText}` in console output: `$consoleOutput`");
    }

    protected function doNotSeeInConsoleOutput($unExpectedText)
    {
        $consoleOutput = $this->app[Kernel::class]->output();
        $this->assertStringNotContainsString($unExpectedText, $consoleOutput,
            "Did not expect to see `{$unExpectedText}` in console output: `$consoleOutput`");
    }

    /**
     * Create a modified copy of testbench to be used as a template.
     * Before each test, a fresh copy of the template is created.
     */
    private static function setUpLocalTestbench()
    {
        fwrite(STDOUT, "Setting up test environment for first use.\n");
        $files = new Filesystem();
        $files->makeDirectory(self::TEST_APP_TEMPLATE, 0755, true);
        $original = __DIR__ . '/../vendor/orchestra/testbench-core/laravel/';
        $files->copyDirectory($original, self::TEST_APP_TEMPLATE);
        // Modify the composer.json file
        $composer = json_decode($files->get(self::TEST_APP_TEMPLATE . '/composer.json'), true);
        // Remove "tests/TestCase.php" from autoload (it doesn't exist)
        unset($composer['autoload']['classmap'][1]);
        // Pre-install illuminate/support
        $composer['require'] = ['illuminate/support' => '~5'];
        // Install stable version
        $composer['minimum-stability'] = 'stable';
        $files->put(self::TEST_APP_TEMPLATE . '/composer.json', json_encode($composer, JSON_PRETTY_PRINT));
        // Install dependencies
        fwrite(STDOUT, "Installing test environment dependencies\n");
        (new Process(['composer', 'install', '--no-dev'], self::TEST_APP_TEMPLATE))->run(function ($type, $buffer) {
            fwrite(STDOUT, $buffer);
        });
    }

    protected function installTestApp()
    {
        $this->uninstallTestApp();
        $files = new Filesystem();
        $files->copyDirectory(self::TEST_APP_TEMPLATE, self::TEST_APP);
    }

    protected function uninstallTestApp()
    {
        $files = new Filesystem();
        if ($files->exists(self::TEST_APP)) {
            $files->deleteDirectory(self::TEST_APP);
        }
    }

    /**
     * Create an instance
     */
    protected function getRequest(
        $body = ['title' => 'title', 'summary' => 'summary'],
        $requestMethod = Request::METHOD_POST,
        $routeMethod = Request::METHOD_POST,
        $uri = 'news_post',
        $routeName = 'news_post'
    ): void
    {
        $this->request = new Request([], $body);
        $this->request->setMethod($requestMethod);

        $this->request->setRouteResolver(function () use ($uri, $routeMethod, $routeName) {
            return (new Route($routeMethod, $uri, []))->name($routeName)->bind($this->request);
        });
    }

    /**
     * Create redis instance and return the result
     *
     * @param array $config
     * @return bool
     */
    protected function getRedisConnection(array $config = []): bool
    {
        $this->redis = new Redis();
        $auth = $config['password'] ?? config('idempotent.redis.password');
        if ($auth) {
            $this->redis->auth($auth);
        }

        return $this->redis->connect(
            $config['host'] ?? config('idempotent.redis.host'),
            $config['port'] ?? config('idempotent.redis.port'),
            $config['timeout'] ?? config('idempotent.redis.timeout'),
            $config['reserved'] ?? config('idempotent.redis.reserved'),
            $config['retryInterval'] ?? config('idempotent.redis.retryInterval'),
            $config['readTimeout'] ?? config('idempotent.redis.readTimeout'),
        );
    }
}
