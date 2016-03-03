<?php
/*
 * Copyright (c)
 * Kirill chEbba Chebunin <iam@chebba.org>
 *
 * This source file is subject to the MIT license that is bundled
 * with this package in the file LICENSE.
 */

namespace Che\ConsoleSignals;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Console command with signal support
 *
 * @author Kirill chEbba Chebunin <iam@chebba.org>
 * @license http://opensource.org/licenses/mit-license.php MIT
 */
abstract class SignaledCommand extends Command
{
    private $executed = false;
    private $signalsEnabled = false;
    private $signalCallbacks = [];

    public static final function getDefaultStopSignals()
    {
        return [
            defined('SIGTERM') ? SIGTERM : 'SIGTERM',
            defined('SIGINT') ? SIGINT : 'SIGINT'
        ];
    }

    public function getStopSignals()
    {
        return self::getDefaultStopSignals();
    }

    abstract protected function doExecute(InputInterface $input, OutputInterface $output);

    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        $this->addOption('no-signals', null, InputOption::VALUE_NONE, 'Disable signal handling');

        $stopCallback = $this->createStopSignalCallback();
        foreach ($this->getStopSignals() as $signal) {
            $this->addSignalCallback($signal, $stopCallback);
        }
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->start();

        if ($this->isSignalSupportEnabled($input)) {
            if ($output->getVerbosity() > OutputInterface::VERBOSITY_NORMAL) {
                $output->writeln('Signal support is enabled');
            }

            $this->registerSignals($input, $output);
        }

        $this->doExecute($input, $output);

        $this->stop();
    }

    protected function start()
    {
        $this->executed = true;
    }

    protected function stop()
    {
        $this->executed = false;
    }

    protected function isExecuted()
    {
        return $this->executed;
    }

    protected function isActive()
    {
        if (!$this->isExecuted()) {
            return false;
        }

        if ($this->signalsEnabled) {
            pcntl_signal_dispatch();

            return $this->isExecuted();
        }

        return true;
    }

    protected function addSignalCallback($signal, callable $callback)
    {
        $this->signalCallbacks[$signal][] = $callback;
    }

    protected function isSignalSupportEnabled(InputInterface $input)
    {
        return extension_loaded('pcntl') && (!$input->hasOption('no-signals') || !$input->getOption('no-signals'));
    }

    private function registerSignals(InputInterface $input, OutputInterface $output)
    {
        if ($this->signalsEnabled) {
            return;
        }

        foreach ($this->signalCallbacks as $signal => $callbacks) {
            foreach ($callbacks as $callback) {
                if ($output->getVerbosity() > OutputInterface::VERBOSITY_NORMAL) {
                    $output->writeln(sprintf('Register signal %s', $signal));
                }
                pcntl_signal($signal, function($signo) use ($callback, $input, $output) {
                    $callback($signo, $input, $output);
                });
            }
        }

        $this->signalsEnabled = true;
    }

    private function createStopSignalCallback()
    {
        return function ($signo, InputInterface $input, OutputInterface $output) {
            if ($output->getVerbosity() > OutputInterface::VERBOSITY_NORMAL) {
                $output->writeln(sprintf('Got stop signal "%s". Stopping...', $signo));
            }
            $this->stop();
        };
    }
}
