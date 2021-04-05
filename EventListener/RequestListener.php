<?php


namespace TheliaSentry\EventListener;

use Sentry\State\Scope;
use Symfony\Component\HttpKernel\Event\GetResponseEvent;
use Thelia\Core\HttpFoundation\Request;
use Thelia\Core\HttpFoundation\Session\Session;
use Thelia\Core\Thelia;
use Thelia\Model\ConfigQuery;
use Thelia\Model\LangQuery;
use TheliaSentry\TheliaSentry;

class RequestListener
{
    /** @var Scope */
    public static $scope;

    /** @var string */
    protected $cacheDir;

    public function __construct($cacheDir)
    {
        $this->cacheDir = $cacheDir;
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

        if (null !== $referer = $event->getRequest()->headers->get('referer')) {
            static::$scope->setTag('referer', $referer);
        }

        if (null !== $event->getRequest()->attributes->get('_route')) {
            static::$scope->setTag('route', (string) $event->getRequest()->attributes->get('_route'));
        }

        static::$scope->setTag('thelia_version', Thelia::THELIA_VERSION);

        $user = [
            'ip_address' => (string) $event->getRequest()->getClientIp()
        ];

        /** @var null|Session $session */
        $session = $event->getRequest()->getSession();

        if ($session !== null) {
            if (null !== $session->getCustomerUser()) {
                $user['customer_id'] = $session->getCustomerUser()->getId();
            }

            if (null !== $session->getAdminUser()) {
                $user['admin_id'] = $session->getAdminUser()->getId();
            }

            if (null !== $session->getLang()) {
                $user['lang'] = $session->getLang()->getCode();
            }

            if (null !== $session->getCurrency()) {
                $user['currency'] = $session->getCurrency()->getCode();
            }
        }

        static::$scope->setUser($user);

        /** @var Request $request */
        $request = $event->getRequest();

        $this->detectBadHost($request);
    }

    protected function detectBadHost(Request $request)
    {
        $multiDomain = ConfigQuery::isMultiDomainActivated();

        $cache = $this->getCache();

        !$multiDomain ?
            $this->detectBadSchemeOrHostSingleDomain($request, $cache)
            : $this->detectBadSchemeOrHostMultiDomain($request, $cache);
    }

    protected function detectBadSchemeOrHostSingleDomain(Request $request, array $cache)
    {
        $requestHost = $request->getHost();
        $requestScheme = $request->getScheme();

        if (TheliaSentry::badHostDetection() && isset($cache['site_host']) && $cache['site_host']!== $requestHost) {
            $cacheFile = $this->cacheDir . DIRECTORY_SEPARATOR  . 'sentry-bad-host-' . md5($requestHost);

            if (!file_exists($cacheFile) && null !== $client = TheliaSentry::getClient()) {
                $message = 'Bad host ' . $requestHost;
                $client->captureMessage('Bad host ' . $requestHost, null, static::$scope);
                file_put_contents($cacheFile, $message);
            }
        }

        if (TheliaSentry::badSchemeDetection() && isset($cache['site_scheme']) && $cache['site_scheme'] !== $requestScheme) {
            $cacheFile = $this->cacheDir . DIRECTORY_SEPARATOR  . 'sentry-bad-scheme-' . md5($requestHost . $requestScheme);

            if (!file_exists($cacheFile) && null !== $client = TheliaSentry::getClient()) {
                $message = 'Bad scheme ' . $requestScheme . ' for host ' . $requestHost;
                $client->captureMessage($message, null, static::$scope);
                file_put_contents($cacheFile, $message);
            }
        }
    }

    protected function detectBadSchemeOrHostMultiDomain(Request $request, array $cache)
    {
        if (!isset($cache['domain'])) {
            return;
        }

        $requestHost = $request->getHost();
        $requestScheme = $request->getScheme();

        if (TheliaSentry::badHostDetection()) {
            $find = false;
            /** @var array $domainInfo */
            foreach ($cache['domain'] as $domainInfo) {
                if (isset($domainInfo['site_host']) && $domainInfo['site_host'] === $requestHost) {
                    $find = true;
                }
            }

            $cacheFile = $this->cacheDir . DIRECTORY_SEPARATOR  . 'sentry-bad-host-' . md5($requestHost);

            if (!$find && !file_exists($cacheFile) && null !== $client = TheliaSentry::getClient()) {
                $message = 'Bad host ' . $requestHost;
                $client->captureMessage($message, null, static::$scope);
                file_put_contents($cacheFile, $message);
            }
        }

        if (TheliaSentry::badSchemeDetection()) {
            $find = false;
            /** @var array $domainInfo */
            foreach ($cache['domain'] as $domainInfo) {
                if (isset($domainInfo['site_scheme']) && $domainInfo['site_scheme'] === $requestScheme . $requestScheme) {
                    $find = true;
                }
            }

            $cacheFile = $this->cacheDir . DIRECTORY_SEPARATOR  . 'sentry-bad-scheme-' . md5($requestHost);

            if (!$find && !file_exists($cacheFile) && null !== $client = TheliaSentry::getClient()) {
                $message = 'Bad scheme ' . $requestScheme . ' for host ' . $requestHost;
                $client->captureMessage($message, null, static::$scope);
                file_put_contents($cacheFile, $message);
            }
        }
    }

    /**
     * @return array
     */
    protected function getCache(): array
    {
        $cacheFilePath = $this->cacheDir . DIRECTORY_SEPARATOR . 'sentry-request-cache.php';

        $multiDomain = ConfigQuery::isMultiDomainActivated();

        if (!file_exists($cacheFilePath)) {
            $urlInfo = parse_url(trim(ConfigQuery::getConfiguredShopUrl()));

            $cacheContent = '<?php return [';

            if (isset($urlInfo['host'])) {
                $cacheContent .= '"site_host" => "' . $urlInfo['host'] . '",';
            }

            if (isset($urlInfo['scheme'])) {
                $cacheContent .= '"site_scheme" => "' . $urlInfo['scheme'] . '",';
            }

            if ($multiDomain) {
                $langs = LangQuery::create()
                    ->filterByActive(true)
                    ->filterByVisible(true)
                    ->find();

                $cacheContent .= '"domain" => [';
                foreach ($langs as $lang) {
                    $urlInfo = parse_url(trim($lang->getUrl()));

                    $cacheContent .= '"' . $lang->getLocale(). '" => [';
                    if (isset($urlInfo['host'])) {
                        $cacheContent .= '"site_host" => "' . $urlInfo['host'] . '",';
                    }

                    if (isset($urlInfo['scheme'])) {
                        $cacheContent .= '"site_scheme" => "' . $urlInfo['scheme'] . '",';
                    }
                    $cacheContent .= '],';
                }
                $cacheContent .= '],';
            }

            $cacheContent .= '];';

            file_put_contents($cacheFilePath, $cacheContent);
        }

        return require $cacheFilePath;
    }
}