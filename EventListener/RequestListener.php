<?php


namespace TheliaSentry\EventListener;

use Sentry\State\Scope;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Thelia\Core\Thelia;

class RequestListener
{
    /** @var Scope */
    public static $scope;

    public function __construct()
    {
        static::$scope = new Scope();
    }

    /**
     * This method is called for each request handled by the framework and
     * fills the Sentry scope with information about the current user.
     *
     * @param GetResponseEvent $event The event
     */
    public function handleKernelRequestEvent(GetResponseEvent $event)
    {
        if (!$event->isMasterRequest()) {
            return;
        }

        static::$scope->setTag('url', (string) $event->getRequest()->getUri());

        if (null !== $event->getRequest()->attributes->get('_route')) {
            static::$scope->setTag('route', (string) $event->getRequest()->attributes->get('_route'));
        }

        static::$scope->setTag('thelia_version', Thelia::THELIA_VERSION);

        static::$scope->setUser([
            'ip_address' => (string) $event->getRequest()->getClientIp()
        ]);
    }
}