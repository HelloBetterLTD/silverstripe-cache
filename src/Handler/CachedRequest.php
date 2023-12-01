<?php

namespace HelloBetter\Cache\Handler;

use Psr\SimpleCache\CacheInterface;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Core\Injector\Injectable;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\FieldType\DBDatetime;
use SilverStripe\Security\SecurityToken;

class CachedRequest
{

    use Injectable;

    private HTTPResponse|null $response = null;
    private $cachedAt = null;


    public function __construct($response)
    {
        $this->response = $response;
        $this->cachedAt = DBDatetime::now()->getValue();
    }

    public function respond()
    {
        if ($this->response) {
            $body = $this->response->getBody();
            // update security IDs for forms
            if ($securityId = SecurityToken::getSecurityID()) {
                $body = preg_replace(
                    '/\<input type="hidden" name="SecurityID" value="\w+"/',
                    "<input type=\"hidden\" name=\"SecurityID\" value=\"{$securityId}\"",
                    $body ?? ''
                );
                $this->response->setBody($body);
            }
        }
        return $this->response;
    }

    /**
     * Returns the cache factory
     * @return CacheInterface
     * @throws \Psr\Container\NotFoundExceptionInterface
     */
    public static function cache_factory() : CacheInterface
    {
        return Injector::inst()->get(CacheInterface::class . '.SiteCache');
    }

    public static function clear() : void
    {
        $cache = self::cache_factory();
        $cache->clear();
    }

}
