<?php
/**
 * @LICENSE_TEXT
 */

namespace Che\ConsoleSignals\Tests;

use Che\ConsoleSignals\SignaledCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Class CommandStub
 *
 * @author Kirill chEbba Chebunin <iam@chebba.org>
 */
class CommandStub extends SignaledCommand
{
    protected function configure()
    {
        parent::configure();

        $this->setName('work');
        $this->addSignalCallback(SIGUSR1, function ($signo, InputInterface $input, OutputInterface $output) {
            $output->write('usr');
        });
    }

    protected function doExecute(InputInterface $input, OutputInterface $output)
    {
        $output->write('start');
        while ($this->isActive()) {
            usleep(100000);
        }
        $output->write('end');
    }
}
