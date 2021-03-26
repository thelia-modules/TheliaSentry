<?php

namespace TheliaSentry\EventListener;

use Sentry\ClientBuilder;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;

class ErrorListener
{
    public function handleExceptionEvent(GetResponseForExceptionEvent $event)
    {
        $ignoreExceptions = [
            \Thelia\Core\HttpKernel\Exception\RedirectException::class,
            \Symfony\Component\HttpKernel\Exception\HttpException::class,
            \Thelia\Core\Security\Exception\AuthenticationException::class,
            \Thelia\Exception\AdminAccessDenied::class,
            \Thelia\Exception\TheliaProcessException::class
        ];

        foreach ($ignoreExceptions as $exceptionClass) {
            if (is_a(get_class($event->getException()), $exceptionClass, true)) {
                return;
            }
        }

        if (class_exists('\Sentry\ClientBuilder')
            && isset($_SERVER['SENTRY_DSN'])
            && filter_var($_SERVER['SENTRY_DSN'], FILTER_VALIDATE_URL)
        ) {
            $client = ClientBuilder::create([
                'dsn' => $_SERVER['SENTRY_DSN']
            ])->getClient();

            $client->captureException($event->getException(), RequestListener::$scope);
        }
    }
}