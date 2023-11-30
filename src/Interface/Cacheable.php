<?php

namespace HelloBetter\Cache\Interface;

use SilverStripe\Control\HTTPRequest;

interface Cacheable
{

    public function updateEnabled(bool &$enabled, HTTPRequest $request) : void;

    public function updateKeyFragments(array &$fragments) : void;

}
