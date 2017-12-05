<?php
/**
 * Piwik - free/libre analytics platform
 *
 * @link http://piwik.org
 * @license http://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\DynamicJS;

class DynamicJS extends \Piwik\Plugin
{

    /**
     * Register the hooks to this Plugin.
     */
    public function getListHooksRegistered()
    {
        $hooks = array(
            'AssetManager.getJavaScriptFiles' => 'getJsFiles',
        );

        return $hooks;
    }

    /**
     * Adds the JavaScript files that the plug-in needs to the global list.
     *
     * @param array $jsFiles The array containing the JavaScript file paths
     */
    public function getJsFiles(&$jsFiles)
    {
        $jsFiles[] = 'plugins/DynamicJS/angularjs/admin/admin.controller.js';        
    }

    /**
     * Adds the style sheets files that the plug-in needs to the global list.
     *
     * @param array $stylesheetFiles The array containing the style sheet file paths

     */
    public function getStylesheetFiles(&$stylesheetFiles)
    {
        $stylesheetFiles[] = 'plugins/DynamicJS/angularjs/admin/admin.controller.less';
    }
}
