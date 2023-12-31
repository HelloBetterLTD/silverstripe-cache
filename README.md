# Static caching for Silverstripe 5 websites. 

Builds on the fly caching for Silverstripe 5 websites. 

Built separately but based on Damian Mooyman's dynamic cache module. 

## Requirements 

* Silverstripe 5+

## Configuration

Installing the module by default enables caching for requests. 

There are a set of configurations you can add to program the logics for caching. 

```
---
Name: custom_cache
After: '*'
---
HelloBetter\Cache\Handler\CacheHandler:
  extensions:
    - MyProject\Extension\CacheCustomisation # Build your own logic for caching
  ignoredClasses:
    - MyProject\MyPage\Page # ignore any pages here
  ignoredPatterns: '/(^\/admin)|(^\/test)|(^\/dev($|\/))|(\/[A-Z])/'
```

### Configs:

* **enabled** - enable or disable cache
* **ignoredClasses** - Ignored Page's or Controllers 
* **ignoredPatterns** - Ignore any URL patterns 
* **cache_ajax** - Set AJAX requests to be cached.


Use the `Cacheable` interface to build in custom PHP logic

```
use HelloBetter\Cache\Interface\Cacheable;
use SilverStripe\Core\Extension;

class CacheCustomisation extends Extention implment Cacheable 
{
    public function updateEnabled(bool &$enabled, HTTPRequest $request) : void
    {
        // your custom logic here.
    }

    public function updateKeyFragments(array &$fragments) : void
    {
        // your cache fragment updates here 
    }
}
```
