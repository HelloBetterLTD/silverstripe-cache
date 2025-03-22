<?php

namespace HelloBetter\Cache\Handler;

use SilverStripe\Control\Director;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Core\Config\Configurable;
use SilverStripe\Core\Extensible;
use SilverStripe\Core\Flushable;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\ORM\FieldType\DBDatetime;
use SilverStripe\Versioned\Versioned;

class CacheHandler implements Flushable
{

    use Injectable;
    use Configurable;
    use Extensible;

    private static $enabled = true;
    private static $ignoredClasses = [];
    private static $ignoredHeaders = []; // TODO:
    private static $systemIgnoredPatterns = '/(^\/?admin)|(^\/?dev)|(^\/?Security)|(^\/?graphql)/';
    private static $ignoredPatterns = null;
    private static $cache_ajax = true;
    private static $inst = null;
    private static $cacheHeader = 'X-Cache';
    private static $responseHeader = 'X-Cache-Satus';

    protected bool $_enabled = false;
    protected string|null $_key = null;

    const MISSED = 'missed';

    public static function inst(HTTPRequest|null $request = null) : self
    {
        if (is_null(self::$inst)) {
            $inst = CacheHandler::create();
            if ($request) {
                $inst->onAfterInst($request);
            }
            self::$inst = $inst;
        }
        return self::$inst;
    }

    public static function flush()
    {
        CachedRequest::clear();
    }

    /**
     * Take a list of classnames from controllers and SiteTree extensions and validates them whether they can be cached.
     * @param ...$args
     * @return bool
     */
    public static function check_ignored(...$args)
    {
        if ($ignoredClasses = self::config()->get('ignoredClasses')) {
            $intersect = array_intersect($ignoredClasses, $args);
            if (!empty($intersect)) {
                $inst = self::inst();
                $inst->setEnabled(false);
            }
        }
        return in_array();
    }

    /**
     * Run an initial check and check for the pre-conditions to mark the request as cachable or not
     * @param HTTPRequest $request
     * @return void
     */
    protected function onAfterInst(HTTPRequest $request)
    {
        $enabled = self::config()->get('enabled');

        if (($stage = Versioned::get_stage()) && $stage !== Versioned::LIVE) {
            $enabled = false;
        }

        // ajax check
        if ($request->isAjax() && !self::config()->get('cacheAjax')) {
            $enabled = false;
        }

        // none GET request check
        if (!$request->isGET()) {
            $enabled = false;
        }

        // ignored patterns
        $sysIgnored = self::config()->get('systemIgnoredPatterns');
        if ($sysIgnored && preg_match($sysIgnored, $request->getURL()) === 1) {
            $enabled = false;
        }
        $ignored = self::config()->get('ignoredPatterns');
        if ($ignored && preg_match($ignored, $request->getURL()) === 1) {
            $enabled = false;
        }

        // disable for dev
        if (Director::isDev()) {
            $enabled = false;
        }

        // form errors
        if (($sessionVars = $request->getSession()->getAll()) !== null) {
            if (!empty($sessionVars['FormInfo'])
                && (
                    !empty($sessionVars['FormInfo']['errors'])
                    || !empty($sessionVars['FormInfo']['formError'])
                )
            ) {
                $enabled = false;
            }
        }
        $this->extend('updateEnabled', $enabled, $request);
        $this->setEnabled($enabled);
    }


    /**
     * Logged a missed cache message
     * @return void
     */
    public function logMissedResponse(HTTPResponse $response)
    {
        $responseHeader = self::config()->get('responseHeader');
        if ($responseHeader && !headers_sent()) {
            $response->addHeader($responseHeader, self::MISSED);
        }
    }

    /**
     * Logged a missed cache message
     * @return void
     */
    public function logHitResponse(HTTPResponse $response)
    {
        $responseHeader = self::config()->get('responseHeader');
        if ($responseHeader && !headers_sent()) {
            $response->addHeader($responseHeader, 'hit at ' . DBDatetime::now()->getValue());
        }
    }

    /**
     * Logged a missed cache message
     * @return void
     */
    public function logSkippedResponse(HTTPResponse $response)
    {
        $responseHeader = self::config()->get('responseHeader');
        if ($responseHeader && !headers_sent()) {
            $response->addHeader($responseHeader, 'Skipped at ' . DBDatetime::now()->getValue());
        }
    }

    /**
     * Generate a cache key for the current request
     * @param HTTPRequest $request
     * @return string
     */
    public function getCacheKey(HTTPRequest $request)
    {
        if (is_null($this->_key)) {
            $fragments = [
                'url' => $request->getURL(true),
                'protocol' => Director::protocol(),
                'stage' => Versioned::get_stage(),
            ];
            $this->invokeWithExtensions('updateKeyFragments', $fragments);
            $this->_key = 'static_' . md5(http_build_query($fragments));
        }
        return $this->_key;
    }

    /**
     * Find an already cached response
     *
     * @param HTTPRequest $request
     * @return CachedRequest|null
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function findResult(HTTPRequest $request) : CachedRequest|null
    {
        $key = $this->getCacheKey($request);
        $factory = CachedRequest::cache_factory();
        $result = $factory->get($key);
        if ($result) {
            return unserialize($result);
        }
        return null;
    }

    /**
     * Create a cached response
     *
     * @param HTTPRequest $request
     * @param HTTPResponse $response
     * @return void
     * @throws \Psr\Container\NotFoundExceptionInterface
     * @throws \Psr\SimpleCache\InvalidArgumentException
     */
    public function cacheResponse(HTTPRequest $request, HTTPResponse $response)
    {
        $key = $this->getCacheKey($request);
        $factory = CachedRequest::cache_factory();

        $cachedRequest = CachedRequest::create($response);
        $factory->set($key, serialize($cachedRequest));
    }

    public function getEnabled()
    {
        return $this->_enabled;
    }

    public function setEnabled(bool $enabled) : self
    {
        $this->_enabled = $enabled;
        return $this;
    }


}
