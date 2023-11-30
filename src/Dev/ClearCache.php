<?php

namespace HelloBetter\Cache\Dev;

use HelloBetter\Cache\Handler\CachedRequest;
use SilverStripe\Dev\BuildTask;

class ClearCache extends BuildTask
{

    protected $title = 'Clear website cache';
    protected $description = 'Clear all the static caches throughout the website';
    private static $segment = 'clear-cache';

    public function run($request)
    {
        CachedRequest::clear();
    }
}
