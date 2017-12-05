<?php
/**
 * This class manages the configuration for the plugin.
 *
 * @author Pouyan Azari <pouyan.azari@uni-wuerzburg.de>
 * @license MIT
 */

namespace Piwik\Plugins\DynamicJS;

use Piwik\Config as PiwikConfig;
use Piwik\Container\StaticContainer;

class Config
{
    /**
     * Placeholder for the default config
     *
     * @var array
     */
    public static $defaultConfig = array(
        "default_backend" => "",
        "accepted_domains" => "",
        "default_email" => "",
        "recursion_level" => 1,
        "enable_cache" => true,
        "enable_add_host" => false,
        "enable_add_user" => false,
        "backend_paths" => "",
        "default_access" => "view",
        "script_template" => "",
        "enable_no_script" => 1
    );

    /**
     * Returns an INI option value that is stored in the `[ShibbolethLogin]` config section.
     *
     * @param string $optionName name of the given option
     *
     * @return mixed
     */
    public static function getConfigOption($optionName)
    {
        return self::getConfigOptionFrom(PiwikConfig::getInstance()->DynamicJS, $optionName);
    }

    /**
     * Returns the configuration options from the form.
     *
     * @param mix    $config     Option to be set.
     * @param string $optionName The name of option.
     */
    public static function getConfigOptionFrom($config, $optionName)
    {
        if (isset($config[$optionName])) {
            return $config[$optionName];
        }

        return self::getDefaultConfigOptionValue($optionName);
    }

    /**
     * Returns the default value of the given option.
     *
     * @param string $optionName
     *
     * @return mix
     */
    public static function getDefaultConfigOptionValue($optionName)
    {
        return @self::$defaultConfig[$optionName];
    }

    /**
     * Returns the plugins options with values from the default
     * for the values not set.
     *
     * @return array
     */
    public static function getPluginOptionValuesWithDefaults()
    {
        $result = self::$defaultConfig;
        foreach ($result as $name => $ignore) {
            $actualValue = self::getConfigOption($name);
            if (isset($actualValue)) {
                $result[$name] = $actualValue;
            }
        }
        $result = array_merge($result, PiwikConfig::getInstance()->DynamicJS);
        // This is done because of the encoding problems
        // More info here: 
        if (isset($result["script_template"]) === true) {
            $result["script_template"] =  html_entity_decode($result["script_template"], ENT_QUOTES, 'UTF-8');
        }
        return $result;
    }
    /**
     * Save the plugin options.
     *
     * @param $config
     */
    public static function savePluginOptions($config)
    {
        $logger = StaticContainer::getContainer()->get('Psr\Log\LoggerInterface');
        $dynamicJs = PiwikConfig::getInstance()->DynamicJS;
        // Update the existing values
        foreach (self::$defaultConfig as $name => $value) {
            if (isset($config[$name])) {
                // This is needed as angularJS returns true or false string which should be converted to variables
                if ($config[$name] === "true") {
                    $config[$name] = 1;
                }
                if ($config[$name] === "false") {
                    $config[$name] = 0;
                }
                $dynamicJs[$name] = trim($config[$name]);
            }
        }
        // Adds new values
        $dynamicJs = \array_merge($dynamicJs, $config);
        $allConfig = array();
        foreach ($dynamicJs as $k => $v) {
            // This one removes the non printable random appearing character
            //@link https://stackoverflow.com/questions/8781911/remove-non-ascii-characters-from-string
            $value = preg_replace('/[[:^print:]]/', '', $v);
        }
        PiwikConfig::getInstance()->DynamicJS = $allConfig;
        PiwikConfig::getInstance()->forceSave();
        $logger->info("DynamicJS Plugin Settings Changed");
    }
}
