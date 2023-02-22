<?php
/**
 * DynamicJS Controller
 *
 * All the webpages Dynamic Controller generates, are from here.
 *
 * @author Pouyan Azari <pouyan.azari@uni-wuerzburg.de>
 * @license MIT
 */

namespace Piwik\Plugins\DynamicJS;

use Piwik\Piwik;
use Piwik\Common;
use Piwik\View;
use Piwik\Url;
use Piwik\Plugin\ControllerAdmin;
use Piwik\Plugins\DynamicJS\Backend\BackendManager;
use Piwik\Container\StaticContainer;

use Twig\Extension\StringLoaderExtension;

class Controller extends \Piwik\Plugin\Controller
{

    /**
     * This is the admin configuration part
     *
     * @return View
     */
    public function admin()
    {
        $backendManager = new BackendManager();
        Piwik::checkUserHasSuperUserAccess();
        $view = new View('@DynamicJS/admin');
        $view->getTwig()->addExtension(new StringLoaderExtension());
        $this->setBasicVariablesView($view);
        ControllerAdmin::setBasicVariablesAdminView($view);
        $backendList = $backendManager->getBackends();
        $view->backendList = $this->getBackendSelectList($backendList);
        $view->backendObjects = $this->getBackendObjects($backendList);
        $view->DynamicJSConfig = Config::getPluginOptionValuesWithDefaults();
        return $view->render();
    }

    /**
     * Returns the completed backend objects created
     *
     * @param array $backendList The backend list that should be used to create the backend objects
     *
     * @return array
     */
    private function getBackendObjects($backendList)
    {
        $backendObjects = array();
        foreach ($backendList as $k => $v) {
            $backendObjects[$k] =  StaticContainer::getContainer()->get($v);
        }
        return $backendObjects;
    }

    /**
     * Creates the array that can be used in the backend selection list
     *
     * @param array $backendList The backend list that should be used
     *
     * @return array
     */
    private function getBackendSelectList($backendList)
    {
        $result = array();
        foreach ($backendList as $k => $backendClass) {
            $result[$k] = $k;
        }

        return $result;
    }

    /**
     * This creates the Javascript for the given website
     *
     * @return View
     */
    public function index()
    {
        header('Content-Type: text/javascript');
        header('access-control-allow-origin: *');
        $url = $this->getUrl();
        $api = new API();
        return $api->getScript($url);
    }

    /**
     * Returns the sanitized URL to the domain processor.
     *
     * @return string
     */
    private function getUrl()
    {
        $rawDomain = '';
        try {
            $rawDomain = Common::getRequestVar('domain');
        } catch (\Exception $e) {
            $rawDomain = '';
        }
        if (array_key_exists('HTTP_REFERER', $_SERVER) && $rawDomain === '') {
            $rawDomain = $_SERVER['HTTP_REFERER'];
            $rawDomain = rtrim($rawDomain, '/');
        }
        return $rawDomain;
    }

    /**
     * Redirects to the right side id and records the data. This can be used for
     * the user that disable javascript
     *
     */
    public function redirect()
    {
        $url = $this->getUrl();
        $api = new API();
        $redirectUrl = $api->getNoScriptRedirectionUrl($url);
        Url::RedirectToUrl($redirectUrl);
    }
}
