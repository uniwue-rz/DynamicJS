<?php

namespace Piwik\Plugins\DynamicJS;

use Piwik\Piwik;
use Piwik\SettingsPiwik;
use Piwik\Cache;
use Piwik\Plugins\DynamicJS\Backend\BackendManager;
use Piwik\Config as PiwikConfig;
use Piwik\Container\StaticContainer;

class API extends \Piwik\Plugin\API
{
    /**
     * Returns the site id from the given url
     *
     * @param string $url The url that should be used
     *
     * @return int
     */
    public function getSiteId($url)
    {
        $activeBackend = $this->getActiveBackend();
        return $activeBackend->getSiteId($url);
    }

    /**
     * Returns the Script for the given website
     *
     * @param string $url The url that should be used to find the script
     *
     * @return int
     */
    public function getScript($url)
    {
        $activeBackend = $this->getActiveBackend();
        $siteId = $activeBackend->getSiteId($url);
        return $activeBackend->createScript($siteId);
    }

    /**
     * Creates the noscript redirection url from the input URL
     * 
     * @param string $url The url that should be used to create the no redirect script
     * 
     * @return string
     */
    public function getNoScriptRedirectionUrl($url){
        $piwikURL = SettingsPiwik::getPiwikUrl();
        $activeBackend = $this->getActiveBackend();
        $siteId = $activeBackend->getSiteId($url);
        $result = "$piwikURL"."piwik.php?idsite=$siteId&rec=1";
        return $result;
    }

    /**
     * Returns an instance of active backend
     *
     * @return Backend
     */
    private function getActiveBackend()
    {
        $backendManager = new BackendManager();
        $availableBackends = $backendManager->getBackends();
        $activeBackendName = PiwikConfig::getInstance()->DynamicJS['default_backend'];
        $activeBackend = StaticContainer::getContainer()->get($availableBackends[$activeBackendName]);
        // Fallback to piwik backend
        if($activeBackend === null){
            $activeBackend = StaticContainer::getContainer()->get("Piwik\Plugins\DynamicJS\Backend\PiwikBackend");
        }
        return $activeBackend;
    }
    /**
     * Saves the configuration to the given file.
     *
     * @param string $data JSON encoded config array.
     *
     * @return array
     *
     * @throws Exception if user does not have super access, if this is not a POST method or
     *                   if JSON is not supplied.
     */
    public function saveSettings($data)
    {
        $this->checkHttpMethodIsPost();
        Piwik::checkUserHasSuperUserAccess();
        Config::savePluginOptions($data);
        return array('result' => 'success', 'message' => Piwik::translate('General_YourChangesHaveBeenSaved'));
    }

    /**
     * Flushes the whole cache (Lazy) using the available methods
     *
     * @return array
     *
     * @throws Exception If the data is not
     */
    public function flushCache()
    {
        Piwik::checkUserHasSuperUserAccess();
        $cache = Cache::getLazyCache();
        $cache->flushAll();
        return array('result' => 'success', 'message' => Piwik::translate('DynamicJS_FlushCacheSuccess'));
    }

    /**
     * Check is the method sending the data is post.
     *
     * @throws \Exception
     */
    private function checkHttpMethodIsPost()
    {
        if ($_SERVER['REQUEST_METHOD'] != 'POST') {
            throw new Exception('Invalid HTTP method.');
        }
    }
}
