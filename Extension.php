<?php

/**
 * Bolt Redirector
 * Easily manage 301 redirects; useful for migrating from another platform.
 *
 * Author: Foundry Code / Mike Anthony
 * Minimum Bolt version: 2.0
 * Docs: http://code.foundrybusiness.co.za/bolt-redirector
 *
 * Released under the MIT License
 */

namespace Bolt\Extension\FoundryCode;

use Bolt\BaseExtension;
use Silex\Application as BoltApplication;
use Symfony\Component\HttpFoundation\Request;

class Redirector extends BaseExtension
{
    public $wildcards = array(
        'all' => '.*',
        'alpha' => '[a-z]+',
        'alphanum' => '[a-z0-9]+',
        'any' => '[a-z0-9\.\-_%\=\s]+',
        'ext' => 'aspx?|f?cgi|s?html?|jhtml|rbml|jsp|phps?',
        'num' => '[0-9]+',
        'segment' => '[a-z0-9\-_]+',
        'segments' => '[a-z0-9\-_/]+',
    );

    public $smartWildcards = array(
        'ext' => 'ext',
        'name|title|page|post|user|model' => 'segment',
        'path' => 'segments',
        'year|month|day|id' => 'num',
    );

    public $errors = array(
        'InvalidAssertion' => 'Invalid assertion condition supplied. Please provide a boolean condition.',
        'InvalidVariableDeclaration' => 'Invalid variable declaration: A valid variable declaration starts with a letter or underscore, followed by any number of letters, numbers, or underscores.',
        'MissingVariables' => 'Un-processed variable in redirect destination. Please add the `%s` variable to the `variables` group in `Redirector/config.yml`. (Destination: `%s`)',
        'LonghandStringDestination' => 'Redirect destination must be a string.',
        'LonghandStringJITSource' => 'JIT source must be a string.',
        'LonghandStringSource' => 'Redirect source must be a string.',
        'ShorthandStringJITSource' => 'Short-hand JIT source must be a string.',
        'ShorthandStringSource' => 'Short-hand redirect source must be a string.',
    );

    /**
     * Provide an access key to the Extensions Manager in the admin environment.
     *
     * @return string
     */

    public function getName()
    {
        return "BoltRedirector";
    }

    /**
     * Initialise the extension's functions
     *
     * @return void
     */
    public function initialize()
    {
        // Get the extension's configuration
        $this->initializeConfiguration();

        // Go!
        $this->handleRedirects();
    }

    /**
     * Initialise the extension's configuration
     *
     * @return void
     */
    public function initializeConfiguration()
    {
        // Get the routing.yml configuration
        $this->routes = $this->app['config']->get('routing');

        // Set the configuration defaults
        $config = array(
            'options' => array(
                'autoSlug' => true,
                'appendQueryString' => false,
                'ignoreErrors' => false,
            ),
            'redirects' => array(),
            'jits' => array(),
            'variables' => array(),
        );
        // Merge these with the actual configuration definitions
        // in config.yml, which take precedence over the defaults
        $this->config = array_merge($config, $this->config);

        // Assign configuration groups to arrays in object
        $configGroups = array('options', 'redirects', 'jits', 'variables');
        foreach($configGroups as $group) {
            if (!empty($this->config[$group])) {
                $this->$group = $this->config[$group];
            } else {
                // Take 'empty groups' from the .yml file into account.
                $this->$group = array();
            }
        }
    }

    /**
     * Throw an exception
     *
     * @return void
     * @throw \Exception
     */
    public function except($message = null, $code = null)
    {
        if (isset($this->errors[$message])) {
            $message = $this->errors[$message];
        }

        throw new \Exception($message, $code);
    }

    /**
     * Perform a value-assertion and throw an exception upon failure
     *
     * @return void
     */
    public function assert($condition = false, $error = 'InvalidAssertion')
    {
        ($condition) or $this->except($error);
    }

    /**
     * Make input slugs more friendly. Like cats.
     *
     * @param $input
     * @return string
     */
    public function slugify($input)
    {
        $input = preg_replace("~%u([0-9a-f]{3,4})~i", "&#x\\1;", urldecode($input));
        $input = preg_replace('~[^\\pL\d\/]+~u', '-', $input);
        $input = trim($input, '-');
        $input = iconv('utf-8', 'us-ascii//TRANSLIT', $input);
        $input = strtolower($input);
        $input = preg_replace('~[^-\w\/]+~', '', $input);

        return (empty($input))? '' : $input;
    }

    /**
     * Check for a redirect. If it exists, then redirect to it's
     * converted replacement.
     *
     * @return void
     */
    public function handleRedirects()
    {
        $self = $this;
        $app = $this->app;

        // Register this extension's actions as an early event
        $app->before(function (Request $request) use ($self, $app) {
            if (empty($self->redirects)) {
                return;
            }
            $requestedPath = trim($request->getPathInfo(), '/');

            // Get the available wildcards, prepare for pattern match
            $availableWildcards = '';
            foreach ($self->wildcards as $wildcard => $expression) {
                $availableWildcards .= "$wildcard|";
            }
            $availableWildcards = rtrim($availableWildcards, '|');

            // Assign the wildcard pattern check
            $pattern = '~\{([a-z]+):(' . $availableWildcards . ')\}~';

            // Loop through defined redirects
            foreach ($self->redirects as $redirectName => $redirectData) {
                $self->computedReplacements = array();

                // Check for short-hand notation
                if (!is_array($redirectData)) {
                    $self->assert(is_string($redirectName), 'ShorthandStringSource');
                    $self->source = trim($redirectName, '/');
                    $self->destination = trim($redirectData, '/');
                } else {
                    $self->assert(is_string($redirectData["from"]), 'LonghandStringSource');
                    $self->assert(is_string($redirectData["to"]), 'LonghandStringDestination');
                    $self->source = trim($redirectData['from'], '/');
                    $self->destination = trim($redirectData['to'], '/');
                }

                // Check for a query string
                $self->sourceQueryString = '';
                if ($self->options['appendQueryString']) {
                    $queryString = $request->getQueryString();
                    if (!is_null($queryString)) {
                        $self->sourceQueryString = "?$queryString";
                    }
                }

                // Check for a non-capture group in the source and convert to regex equivalent
                $nonCaptureMatcher = "~<([a-z0-9\-_\|]+)>~";
                if (preg_match($nonCaptureMatcher, $self->source)) {
                    $self->source = preg_replace($nonCaptureMatcher, "(?:\\1)", $self->source);
                }

                // Check if we're redirecting to a route and apply the path if it exists
                $routeMatcher = "~^route\:\s+?([a-z\-_]+)$~";
                if (preg_match($routeMatcher, $self->destination, $matches)) {
                    if (isset($self->routes[$matches[1]])) {
                        $self->destination = trim($self->routes[$matches[1]]['path'], '/');
                    }
                }

                // Check for a protocol and apply a prefix based on the findings
                $self->prefix = '';
                if (!preg_match("~^(https?|ftps?)\://~", $self->destination)) {
                    $self->prefix = '/';
                }

                // Convert smart wildcards into normal ones
                foreach ($self->smartWildcards as $wildcard => $wildcardType) {
                    $smartWildcardMatcher = "~\{($wildcard)\}~i";
                    if (preg_match($smartWildcardMatcher, $self->source)) {
                        $self->source = preg_replace($smartWildcardMatcher, "{\\1:$wildcardType}", $self->source);
                    }
                }

                // Convert the wildcards into expressions for replacement
                $computedWildcards = preg_replace_callback($pattern, function ($captures) use ($self) {
                    $self->computedReplacements[] = $captures[1];

                    return '(' . $self->wildcards[$captures[2]] . ')';
                }, $self->source);

                // Check to see if we have these conversions in the requested path and replace where necessary
                if (preg_match("~^$computedWildcards$~i", $requestedPath)) {
                    $convertedWildcards = preg_replace_callback("~^$computedWildcards$~i", function ($captures) use ($self) {
                        $result = $self->destination;
                        for ($c = 1, $n = count($captures); $c < $n; ++$c) {
                            $value = array_shift($self->computedReplacements);
                            if ($self->options['autoSlug']) {
                                $captures[$c] = $self->slugify($captures[$c]);
                            }
                            $result = str_replace('{' . $value . '}', $captures[$c], $result);
                        }

                        return $result;
                    }, $requestedPath);

                    // Merge global variables with those defined by the user
                    $self->variables = array_merge($self->variables, array(
                        'admin_path' => $app['config']->get('general/branding/path'),
                    ));
                    // Replace variables with actual data
                    foreach ($self->variables as $variable => $data) {
                        $self->assert(preg_match('~^[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*$~', $variable), 'InvalidVariableDeclaration');
                        $convertedWildcards = str_replace("{@$variable}", ltrim($data, '/'), $convertedWildcards);
                    }

                    // Check for Just In Time replacements and apply where necessary
                    foreach ($self->jits as $jitName => $jitData) {
                        // Check for short-hand notation
                        if (is_string($jitData)) {
                            $self->assert(is_string($jitName), 'ShorthandStringJITSource');
                            $jitReplace = $jitName;
                            $jitWith = $jitData;
                        } else {
                            $self->assert(is_string($jitData['replace']), 'LonghandStringJITSource');
                            $jitReplace = $jitData['replace'];
                            $jitWith = $jitData['with'];
                        }
                        // Match and replace
                        $jitMatcher = "~$jitReplace~i";
                        if (preg_match($jitMatcher, $convertedWildcards)) {
                            $convertedWildcards = preg_replace($jitMatcher, trim($jitWith, '/'), $convertedWildcards);
                        }
                    }

                    // Check for un-processed variables and throw an exception if there are any
                    if (preg_match('~\{@(.*)\}~', $convertedWildcards, $variableMatches)) {
                        $self->except(sprintf($self->errors['MissingVariables'], $variableMatches[1], $convertedWildcards));
                    }

                    // Redirect the user to the final, processed path
                    return $app->redirect(strtolower("{$self->prefix}{$convertedWildcards}{$self->sourceQueryString}"), 301);
                }
            }
        }, BoltApplication::EARLY_EVENT);
    }
}
