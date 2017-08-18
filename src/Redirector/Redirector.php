<?php

namespace Bolt\Extension\Sahassar\Boltredirector\Redirector;

use Bolt\Extension\Sahassar\Boltredirector\Config\Config;
use Symfony\Component\HttpFoundation\Request;
use Silex\Application;

/**
 * Boltredirector Redirector class.
 *
 * @author Svante Richter <svante.richter@gmail.com>
 */
class Redirector
{
    private $config;

    /**
     * Constructor.
     *
     * @param array $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * Before handler that handles redirects
     *
     * @param Request     $request
     * @param Application $app
     */
    public function handle(Request $request, Application $app)
    {
        $path = trim($request->getPathInfo(), '/');
        foreach ($this->config->getRedirects() as $redirect) {

            $redirect->prepare();

            if ($redirect->match($path)) {
                $result = $redirect->getResult($path);

                // Only prefix Bolt redirects
                if (substr($result, 0, 4 ) !== "http") {
                    $result = '/' . $result;
                }

                return $app->redirect($result, 301);
            }
        }
    }
}