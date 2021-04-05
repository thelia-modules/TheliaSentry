<?php
/*************************************************************************************/
/*      This file is part of the Thelia package.                                     */
/*                                                                                   */
/*      Copyright (c) OpenStudio                                                     */
/*      email : dev@thelia.net                                                       */
/*      web : http://www.thelia.net                                                  */
/*                                                                                   */
/*      For the full copyright and license information, please view the LICENSE.txt  */
/*      file that was distributed with this source code.                             */
/*************************************************************************************/

namespace TheliaSentry;

use Sentry\ClientBuilder;
use Sentry\ClientInterface;
use Thelia\Module\BaseModule;

class TheliaSentry extends BaseModule
{
    /** @var string */
    const DOMAIN_NAME = 'theliasentry';

    /*
     * You may now override BaseModuleInterface methods, such as:
     * install, destroy, preActivation, postActivation, preDeactivation, postDeactivation
     *
     * Have fun !
     */

    /**
     * @return bool
     */
    public static function badHostDetection()
    {
        if (!isset($_SERVER['SENTRY_BAD_HOST_DETECTION'])) {
            return false;
        }

        return $_SERVER['SENTRY_BAD_HOST_DETECTION'] === '1' ? true : false;
    }

    /**
     * @return bool
     */
    public static function badSchemeDetection()
    {
        if (!isset($_SERVER['SENTRY_BAD_SCHEME_DETECTION'])) {
            return false;
        }

        return $_SERVER['SENTRY_BAD_SCHEME_DETECTION'] === '1' ? true : false;
    }

    /**
     * @return ClientInterface|null
     */
    public static function getClient()
    {
        static $client = null;

        if (class_exists('\Sentry\ClientBuilder')
            && isset($_SERVER['SENTRY_DSN'])
            && filter_var($_SERVER['SENTRY_DSN'], FILTER_VALIDATE_URL)
            && null === $client
        ) {
            $client = ClientBuilder::create([
                'dsn' => $_SERVER['SENTRY_DSN']
            ])->getClient();
        }

        return $client;
    }
}
