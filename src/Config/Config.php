<?php

namespace Bolt\Extension\Sahassar\Boltredirector\Config;

use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * General configuration class.
 *
 * @author Svante Richter <svante.richter@gmail.com>
 */
class Config extends ParameterBag
{
    /**
     * Constructor.
     *
     * @param $config
     */
    public function __construct($config)
    {
        parent::__construct($config);

        $this->remove('redirects');
        foreach ($config['redirects'] as $name => $parameters) {
            if (!is_array($parameters)) {
                $this->parameters['redirects'][] = new Redirect(['from' => $name, 'to' => $parameters], $this);
            } else {
                $this->parameters['redirects'][] = new Redirect($parameters, $this);
            }
        }

        $this->remove('jits');
        foreach ($config['jits'] as $name => $parameters) {
            if (!is_array($parameters)) {
                $this->parameters['jits'][$name] = $parameters;
            } else {
                $this->parameters['jits'][$parameters['replace']] = $parameters['with'];
            }
        }
    }

    /**
     * @return array
     */
    public function getRedirects()
    {
        return $this->get('redirects');
    }

    /**
     * @param array $redirects
     */
    public function setRedirects(array $redirects)
    {
        $this->set('redirects', $redirects);
    }

    /**
     * @return array
     */
    public function getJits()
    {
        return $this->get('jits');
    }

    /**
     * @param array $jits
     */
    public function setJits(array $jits)
    {
        $this->set('jits', $jits);
    }

    /**
     * @return array
     */
    public function getVariables()
    {
        return $this->get('variables');
    }

    /**
     * @param array $variables
     */
    public function setVariables(array $variables)
    {
        $this->set('variables', $variables);
    }
}
