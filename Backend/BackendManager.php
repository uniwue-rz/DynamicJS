<?php
/**
 * This is a simple manager for the Backends.
 *
 *
 * @author Pouyan Azari <pouyan.azari@uni-wuerzburg.de>
 * @license MIT
 */

namespace Piwik\Plugins\DynamicJS\Backend;

use RecursiveIteratorIterator;
use RecursiveDirectoryIterator;
use RegexIterator;

use Piwik\Container\StaticContainer;
use Piwik\Config;
use Piwik\Cache;
use Piwik\Piwik;

class BackendManager
{
    /**
     * Placeholder for the flag if the cache is active
     *
     * @var boolean
     */
    private $cacheActive;

    public function __construct()
    {
        $this->cacheActive = Config::getInstance()->DynamicJS['enable_cache'];
    }

    /**
    * Returns the list of Backends available on in the Plugin. The BackendPlugins should have a 'Backend' at the end of their name.
    *
    * @param array $backendPaths The extra paths for the backends
    *
    * @link https://stackoverflow.com/a/27440555 CC-BY-SA by https://stackoverflow.com/u/3437428 Loïc Faugeron
    */
    public function getBackends($backendPaths = array())
    {
        if ($this->cacheActive === true) {
            $cache = Cache::getLazyCache();
            $cacheKey = "DynamicJS_Backends";
            if ($cache->contains($cacheKey) === true) {
                return $cache->fetch($cacheKey);
            }
        }
        $defaultPath = array(__DIR__."");
        $allPath = \array_merge($defaultPath, $backendPaths);
        $backends = array();
        foreach ($allPath as $path) {
            $allFiles = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path));
            $phpFiles = new RegexIterator($allFiles, '/[A-Za-z0-9]+Backend\.php$/');
            foreach ($phpFiles as $f) {
                $content = file_get_contents($f->getRealPath());
                $tokens = token_get_all($content);
                $namespace = "";
                for ($index = 0; isset($tokens[$index]); $index++) {
                    if (!isset($tokens[$index][0])) {
                        continue;
                    }
                    if (T_NAMESPACE === $tokens[$index][0]) {
                        $index += 2;
                        while (isset($tokens[$index]) && is_array($tokens[$index])) {
                            $namespace .= $tokens[$index++][1];
                        }
                    }
                    if (T_CLASS === $tokens[$index][0]) {
                        $index += 2; // Skip class keyword and whitespace
                        $backend = $namespace.'\\'.$tokens[$index][1];
                        $backendObject = StaticContainer::getContainer()->get($backend);
                        if ($backendObject->getName() !== "PiwikBackend") {
                            $backends[$backendObject->getName()] = $backend;
                        }
                    }
                }
            }
        }
        if ($this->cacheActive === true) {
            $cache->save($cacheKey, $backends, 600);
        }
        return $backends;
    }
}
