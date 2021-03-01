<?php

namespace TheliaSentry\EventListener;

use Sentry\ClientBuilder;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;

class ErrorListener
{
    public function handleExceptionEvent(GetResponseForExceptionEvent $event)
    {
        if (isset($_SERVER['SENTRY_DSN']) && filter_var($_SERVER['SENTRY_DSN'], FILTER_VALIDATE_URL)) {
            $client = ClientBuilder::create([
                'dsn' => $_SERVER['SENTRY_DSN']
            ])->getClient();

            $client->captureException($event->getException());
        }
    }
}