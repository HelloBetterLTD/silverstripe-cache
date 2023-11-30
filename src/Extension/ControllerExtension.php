<?php

namespace HelloBetter\Cache\Extension;

use HelloBetter\Cache\Handler\CacheHandler;
use SilverStripe\CMS\Controllers\ContentController;
use SilverStripe\Core\Extension;

class ControllerExtension extends Extension
{

    public function onBeforeInit()
    {
        /* @var ContentController $controller */
        $controller = $this->owner;
        CacheHandler::check_ignored(
            get_class($controller),
            get_class($controller->data())
        );
    }

}
