<?php

namespace TheliaSentry\EventListener;

use Sentry\ClientBuilder;;

use Sentry\State\Scope;
use Symfony\Component\Console\Event\ConsoleExceptionEvent;
use TheliaSentry\TheliaSentry;

final class ConsoleCommandListener
{
    /** @var string */
    private $env;

    public function __construct(string $env)
    {
        $this->env = $env;
    }

    public function handleConsoleErrorEvent(ConsoleExceptionEvent $event)
    {
        if (null !== $client = TheliaSentry::getClient()) {
            $scope = new Scope();

            $scope->setTags([
                'command' => $event->getCommand()->getName(),
                'status_code' => $event->getExitCode(),
            ]);

            $scope->setTag('environment', $this->env);

            $client->captureException($event->getException(), $scope);
        }
    }
}
