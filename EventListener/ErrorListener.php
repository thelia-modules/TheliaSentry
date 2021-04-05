<?php

namespace TheliaSentry\EventListener;

use Sentry\ClientBuilder;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use TheliaSentry\TheliaSentry;

class ErrorListener
{
    /** @var string */
    private $env;

    public function __construct(string $env)
    {
        $this->env = $env;
    }

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

        if (null !== $client = TheliaSentry::getClient()) {
            RequestListener::$scope->setTag('environment', $this->env);

            $client->captureException($event->getException(), RequestListener::$scope);
        }
    }
}