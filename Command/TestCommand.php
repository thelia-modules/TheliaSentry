<?php

namespace TheliaSentry\Command;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Thelia\Command\ContainerAwareCommand;

class TestCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName("sentry:test");
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        throw new \Exception('Test Sentry catch exception.');
    }
}