<?php

declare(strict_types=1);

namespace TheliaSentry\EventListener;

use Sentry\ClientBuilder;;
use Symfony\Component\Console\Event\ConsoleExceptionEvent;

final class ConsoleCommandListener
{
    public function handleConsoleErrorEvent(ConsoleExceptionEvent $event)
    {
        if (class_exists('\Sentry\ClientBuilder')
            && isset($_SERVER['SENTRY_DSN'])
            && filter_var($_SERVER['SENTRY_DSN'], FILTER_VALIDATE_URL)
        ) {
            $client = ClientBuilder::create([
                'dsn' => $_SERVER['SENTRY_DSN']
            ])->getClient();

            $client->captureException($event->getException());
        }
    }
}
