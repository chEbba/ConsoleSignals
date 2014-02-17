<?php
/**
 * @LICENSE_TEXT
 */

namespace Che\ConsoleSignals\Tests;

use Che\ConsoleSignals\SignaledCommand;
use PHPUnit_Framework_TestCase as TestCase;
use Symfony\Component\Process\Process;

/**
 * Class SignaledCommandTest
 *
 * @author Kirill chEbba Chebunin <iam@chebba.org>
 */
class SignaledCommandTest extends TestCase
{
    private $output;

    protected function setUp()
    {
        if (!extension_loaded('pcntl')) {
            $this->markTestSkipped('no pcntl');
        }

        $this->output = '';
    }

    private function startProcess($command, $arguments = '')
    {
        $process = new Process(sprintf('php %s %s %s', __DIR__ . '/cli.php', $command, $arguments));
        $process->start(function ($type, $output) {
            if ($type !== Process::ERR) {
                $this->output .= $output;
            }
        });
        usleep(100000);

        return $process;
    }

    /**
     * @test process is stopped by default stop signals
     * @dataProvider stopSignals
     */
    public function stopSignal($signal)
    {
        $process = $this->startProcess('work');
        $process->signal($signal);

        // 0.2 seconds should be enough to stop
        usleep(200000);

        $this->assertTrue($process->isTerminated());
        $this->assertEquals('startend', $this->output);
    }

    public function stopSignals()
    {
        return array_map(function ($signal) {return [$signal];}, SignaledCommand::getDefaultStopSignals());
    }

    /**
     * @test disable signals with option
     */
    public function noSignals()
    {
        $process = $this->startProcess('work', '--no-signals');
        $process->signal(SIGTERM);

        usleep(200000);

        $this->assertTrue($process->isTerminated());
        $this->assertEquals('start', $this->output);
    }

    /**
     * @test register custom signal handler
     */
    public function customSignal()
    {
        $process = $this->startProcess('work');
        $process->signal(30);

        $process->stop();

        $this->assertEquals('startusrend', $this->output);
    }
}
