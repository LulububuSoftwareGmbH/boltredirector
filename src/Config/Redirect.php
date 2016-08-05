<?php

namespace Bolt\Extension\Sahassar\Boltredirector\Config;

use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * Locale configuration class.
 *
 * @author Svante Richter <svante.richter@gmail.com>
 */
class Redirect extends ParameterBag
{
    private $wildcards = array(
        'all' => '.?',
        'alpha' => '[a-z]+',
        'alphanum' => '[a-z0-9]+',
        'any' => '[a-z0-9\.\-_%\=\s]+',
        'ext' => 'aspx?|f?cgi|s?html?|jhtml|rbml|jsp|phps?',
        'num' => '[0-9]+',
        'segment' => '[a-z0-9\-_]+',
        'segments' => '[a-z0-9\-_/]+',
    );

    private $smartWildcards = array(
        'ext' => 'ext',
        'name|title|page|post|user|model' => 'segment',
        'path' => 'segments',
        'year|month|day|id' => 'num',
    );

    private $nonCaptureMatcher = "~<([a-z0-9\-_\|]+)>~";

    private $routeMatcher = "~^route\:\s+?([a-z\-_]+)$~";

    private $pattern;

    /**
     * Constructor, sets the config
     *
     * @param $redirect
     */
    public function __construct($redirect, $config)
    {
        $this->config = $config;
        $redirect['from'] = trim($redirect['from'], '/');
        $redirect['to'] = trim($redirect['to'], '/');
        parent::__construct($redirect);
    }

    /**
     * Prep regexes and replacements
     */
    public function prepare()
    {
        // Set the combined wildcard
        $this->wildcardmatch = '~\{([a-z]+):(' . join('|', $this->wildcards) . ')\}~';

        // Check for a non-capture group in the source and convert to regex equivalent
        if (preg_match($this->nonCaptureMatcher, $this->get('from'))) {
            $this->set('from', preg_replace($this->nonCaptureMatcher, "(?:\\1)", $this->get('from')));
        }

        // Convert smart wildcards into normal ones
        foreach ($this->smartWildcards as $wildcard => $wildcardType) {
            $smartWildcardMatcher = "~\{($wildcard)\}~i";
            if (preg_match($smartWildcardMatcher, $this->get('from'))) {
                $this->set('from', preg_replace($smartWildcardMatcher, "{\\1:$wildcardType}", $this->get('from')));
            }
        }

        // Convert the wildcards into expressions for replacement
        $this->computedWildcards = preg_replace_callback($this->wildcardmatch, function ($captures) {
            $this->computedReplacements[] = $captures[1];
            return '(' . $this->wildcards[$captures[2]] . ')';
        }, $this->get('from'));
    }

    /**
     * Check if this redirect matches the path
     *
     * @param string $path
     *
     * @return bool
     */
    public function match($path)
    {
        return preg_match("~^" . $this->computedWildcards . "$~i", $path);
    }

    /**
     * Get the url to return
     *
     * @param string $path
     *
     * @return string
     */
    public function getResult($path)
    {
        // Check to see if we have these conversions in the requested path and replace where necessary
        $result = preg_replace_callback("~^" . $this->computedWildcards . "$~i", function ($captures) {
            $result = $this->get('to');
            for ($c = 1, $n = count($captures); $c < $n; ++$c) {
                $value = array_shift($this->computedReplacements);
                $result = str_replace('{' . $value . '}', $captures[$c], $result);
            }
            return $result;
        }, $path);

        // Replace variables with actual data
        foreach ($this->config->getVariables() as $variable => $data) {
            $result = str_replace("{@$variable}", ltrim($data, '/'), $result);
        }

        // Check for Just In Time replacements and apply where necessary
        foreach ($this->config->getJits() as $jitReplace => $jitWith) {
            // Match and replace
            $jitMatcher = "~$jitReplace~i";
            if (preg_match($jitMatcher, $result)) {
                $result = preg_replace($jitMatcher, trim($jitWith, '/'), $result);
            }
        }

        return $result;
    }
}
