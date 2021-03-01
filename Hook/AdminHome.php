<?php

namespace TheliaSentry\Hook;

use Thelia\Core\Event\Hook\HookRenderEvent;
use Thelia\Core\Hook\BaseHook;

class AdminHome extends BaseHook
{
    public function onMainBeforeContent(HookRenderEvent $event)
    {
        if (isset($_SERVER['SENTRY_DSN']) && filter_var($_SERVER['SENTRY_DSN'], FILTER_VALIDATE_URL)) {
            return;
        }

        $event->add(
<<<HTML
<div class="alert alert-danger">
    The module TheliaSentry is not configured.
    <br/>
    <br/>
    If this is the production environment.
    <br/>
    Please add the environment variable <b>SENTRY_DSN</b> in the .env file.
    <br/>
    Example :
    <br/> 
    SENTRY_DSN=https://xxxxxxx@xxxx.xxx.xx/x
    <br/>
    <br/>
    If this is not the production environment.
    <br/>
    Please deactivate the module TheliaSentry
</div>
HTML
        );
    }
}