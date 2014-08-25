<?php

// Redirector Extension 1.0.0 for Bolt
// by Foundry Code / Mike Anthony
// Minimum Bolt version: 2.0
// http://code.foundrybusiness.co.za/bolt-redirector
// Released under the MIT License

use Bolt\Extension\FoundryCode\Redirector;

$app['extensions']->register(new Redirector($app));