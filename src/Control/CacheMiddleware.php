<?php

namespace HelloBetter\Cache\Control;

use HelloBetter\Cache\Handler\CacheHandler;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\Middleware\HTTPMiddleware;
use SilverStripe\Core\Config\Configurable;

class CacheMiddleware implements HTTPMiddleware
{

    use Configurable;

    public function process(HTTPRequest $request, callable $delegate)
    {
        $response = null;
        $handler = CacheHandler::inst($request);
        if (!$handler->getEnabled()) {
            $response = $this->delegate($request, $delegate);
            $handler->logMissedResponse($response);
            return $response;
        }

        $cachedResult = $handler->findResult($request);
        if ($cachedResult) {
            $response = $cachedResult->respond();
            $handler->logHitResponse($response);
            return $response;
        } else {
            $response = $this->delegate($request, $delegate);
            $handler->logMissedResponse($response);
        }

        if ($handler->getEnabled()) {
            $handler->cacheResponse($request, $response);
        } else {
            $handler->logSkippedResponse($response);
        }

        return $response;

    }


    private function delegate(HTTPRequest $request, callable $delegate)
    {
        return $delegate($request);
    }

}
