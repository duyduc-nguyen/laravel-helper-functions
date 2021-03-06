<?php

namespace Illuminated\Helpers\Artisan;

use Illuminated\Helpers\HelperFunctions\Tests\TestCase;
use Mockery;

class CommandTest extends TestCase
{
    protected function setUp()
    {
        parent::setUp();

        $phpBinaryMock = Mockery::mock('overload:Symfony\Component\Process\PhpExecutableFinder');
        $phpBinaryMock->shouldReceive('find')->withNoArgs()->once()->andReturn('php');
    }

    /** @test */
    public function only_one_constructor_argument_is_required()
    {
        $command = new Command('test');
        $this->assertInstanceOf(Command::class, $command);
    }

    /** @test */
    public function it_has_static_constructor_named_factory()
    {
        $command = Command::factory('test');
        $this->assertInstanceOf(Command::class, $command);
    }

    /** @test */
    public function it_can_run_command_in_background()
    {
        $this->shouldReceiveExecCallOnceWith('(php artisan test:command) > /dev/null 2>&1 &');

        $command = Command::factory('test:command');
        $command->runInBackground();
    }

    /** @test */
    public function run_in_background_supports_before_subcommand()
    {
        $this->shouldReceiveExecCallOnceWith('(before command && php artisan test:command) > /dev/null 2>&1 &');

        $command = Command::factory('test:command', 'before command');
        $command->runInBackground();
    }

    /** @test */
    public function run_in_background_supports_after_subcommand()
    {
        $this->shouldReceiveExecCallOnceWith('(php artisan test:command && after command) > /dev/null 2>&1 &');

        $command = Command::factory('test:command', null, 'after command');
        $command->runInBackground();
    }

    /** @test */
    public function run_in_background_supports_before_and_after_subcommands_together()
    {
        $this->shouldReceiveExecCallOnceWith('(before && php artisan test:command && after) > /dev/null 2>&1 &');

        $command = Command::factory('test:command', 'before', 'after');
        $command->runInBackground();
    }

    /**
     * @test
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function it_supports_overriding_of_artisan_binary_through_constant()
    {
        $this->shouldReceiveExecCallOnceWith('(before && php custom-artisan test:command && after) > /dev/null 2>&1 &');

        define('ARTISAN_BINARY', 'custom-artisan');
        $command = Command::factory('test:command', 'before', 'after');
        $command->runInBackground();
    }
}

if (!function_exists(__NAMESPACE__ . '\exec')) {
    function exec($command)
    {
        return TestCase::$functions->exec($command);
    }
}
