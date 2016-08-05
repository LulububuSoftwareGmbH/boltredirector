<?php

namespace Bolt\Extension\Sahassar\Boltredirector;

use Bolt\Extension\SimpleExtension;
use Silex\Application;

/**
 * Boltredirector extension class.
 *
 * @author Svante Richter <svante.richter@gmail.com>
 */
class BoltredirectorExtension extends SimpleExtension
{
    /**
     * @inheritdoc
     */
    protected function registerServices(Application $app)
    {
        $config = new Config\Config($this->getConfig());
        $redirector = new Redirector\Redirector($config);
        $app->before([$redirector, 'handle'], Application::EARLY_EVENT);
    }
}
