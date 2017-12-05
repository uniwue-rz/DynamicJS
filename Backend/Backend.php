<?php
/**
 * The Meta class for the Backends
 *
 * @author Pouyan Azari <pouyan.azari@uni-wuerzburg.de>
 * @license MIT
 */
namespace Piwik\Plugins\DynamicJS\Backend;

use Piwik\Cache;
use Piwik\Config;
use Twig_Environment;
use Twig_Loader_Array;

class Backend
{
    /**
     * Placeholder for the accepted domains
     *
     * @var string
     */
    protected $acceptedDomains;
    
    /**
     * Placeholder for status of cache
     *
     * @var boolean
     */
    protected $cacheActive;

    /**
     * Placeholder for the cache object
     *
     * @var Cache
     */
    protected $cache;

    /**
     * Placeholder for the default email address
     *
     * @var string
     */
    protected $defaultEmail;

    /**
     * Placeholder for the recursion level
     *
     * @var int
     */
    protected $recursionLevel;

    /**
     * Placeholder for the Add Hosts enable
     *
     * @var boolean
     */
    protected $enableAddHost;

    /**
     * Placeholder for enable add user
     *
     * @var boolean
     */
    protected $enableAddUser;

    /**
     * Placeholder for the active backend
     *
     * @var string
     */
    protected $defaultBackend;

    /**
     * Placeholder for the default access
     *
     * @var string
     */
    protected $defaultAccess;

    /**
     * Placeholder for twig script template which is used to create the script
     *
     * @var string
     */
    protected $scriptTemplate;

    /**
     * Constructor
     *
     */
    public function __construct()
    {
        require __DIR__."/../vendor/autoload.php";
        $this->acceptedDomains = Config::getInstance()->DynamicJS['accepted_domains'];
        $this->cacheActive = (boolean) Config::getInstance()->DynamicJS['enable_cache'];
        $this->defaultEmail = Config::getInstance()->DynamicJS['default_email'];
        $this->recursionLevel = (int) Config::getInstance()->DynamicJS['recursion_level'];
        $this->enableAddHost = (boolean) Config::getInstance()->DynamicJS['enable_add_host'];
        $this->enableAddUser = (boolean) Config::getInstance()->DynamicJS['enable_add_user'];
        $this->defaultBackend = Config::getInstance()->DynamicJS['default_backend'];
        $this->defaultAccess = Config::getInstance()->DynamicJS['default_access'];
        $this->scriptTemplate = Config::getInstance()->DynamicJS['script_template'];
        $this->cache = Cache::getLazyCache();
    }

    /**
     * Returns the site id from the given url
     *
     * @param string $url The URL that should be used the get the site id
     *
     * @return int
     */
    public function getSiteId($url)
    {
        return 0;
    }

    /**
     * Returns the name of the given backend
     *
     * @return string
     */
    public function getName()
    {
        return "";
    }

    /**
     * Returns the list of variables the backend needs
     *
     * @return array
     */
    public function getVariables()
    {
        return array();
    }

    /**
     * Saves the given value in cache. The accepted values can be boolean, numbers, strings and arrays
     *
     * @param string $key       The key to the given value
     * @param mix    $value     The value that should be saved in cache
     * @param int    $life      The life of data in cache in seconds
     *
     * @throws \Exception
     *
     * @link https://github.com/piwik/component-cache/blob/master/README.md#lazy
     */
    public function saveInCache($key, $value, $life = 600)
    {
        $this->cache->save($key, $value, $life);
    }

    /**
     * Flushes the cache
     *
     */
    public function flushCache()
    {
        $this->cache->flushAll();
    }

    /**
     * Searches for the given value in cache. Returns null if not found.
     *
     * @param string $key The key to the file
     *
     * @return mix|null
     */
    public function findInCache($key)
    {
        if ($this->cache->contains($key) === true) {
            return $this->cache->fetch($key);
        }

        return null;
    }

    /**
     *
     * Generates random password for the user that does not have password. This is valid
     * option if the login is done with the help of a SSO/Shibboleth Mechanism.
     *
     * @param  int $length The length of the given password.
     *
     * @link https://stackoverflow.com/a/31284266 with CC-BY-SA BY Scott Arciszewski (https://stackoverflow.com/u/2224584)
     *
     * @throws \Exception
     */
    public function generatePassword($length = 14)
    {
        $keySpace = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $str = '';
        $max = mb_strlen($keySpace, '8bit') - 1;
        if ($max < 1) {
            throw new Exception('$keySpace must be at least two characters long');
        }
        for ($i = 0; $i < $length; ++$i) {
            $str .= $keySpace[random_int(0, $max)];
        }
        return $str;
    }

    /**
     * Returns the list of possible urls
     *
     * @param string $url   The urls that should be used
     * @param int    $level The level of recursion for the given url
     *
     * @return array
     */
    public function getPossibleUrls($url, $level = 1)
    {
        if (\filter_var($url, FILTER_VALIDATE_URL) === false) {
            throw new \Exception("The given URL is not valid", 1);
        }
        $parsedUrl = \parse_url($url);
        $result = array();
        $result["domain"] = $parsedUrl["host"];
        $result["path"] = array();
        $paths = array();
        if(isset($result["path"]) === true){
            $paths = \explode("/", $parsedUrl["path"]);            
        }
        if (sizeof($paths) > 0) {
            for ($i = 1; $i < $level+1; $i++) {
                $path = \implode("/", \array_slice($paths, 1, $i));
                \array_push($result["path"], $path);
            }
        }
        $result["path"] = \array_reverse($result["path"]);
        return $result;
    }

    /**
     * Returns the list of possible urls from the given parsed URL.
     *
     * @param array $parsedUrl The url that is parsed with a domain and multiple possible domains
     *
     * @return array
     *
     * @uses https://github.com/piwik/piwik//blob/3.2.1-rc1/plugins/SitesManager/API.php#L449
     */
    public function getNormalizedUrl($parsedUrl = array())
    {
        $toBeProcessed = array();
        $result = array();
        if (isset($parsedUrl["domain"]) === false) {
            throw  new \Exception("The parsed URL does not have any domain");
        }
        $domain = $parsedUrl["domain"];
        foreach ($parsedUrl["path"] as $path) {
            $url = $domain."/".$path;
            \array_push($toBeProcessed, $url);
        }
        \array_push($toBeProcessed, $domain);
        foreach ($toBeProcessed as $p) {
            \array_push($result, $this->getCompleteUrls($p));
        }
        return $result;
    }

    /**
     * Creates a URL with scheme regardless what is was before
     *
     * @param string $url The url that should be converted
     * @param string $scheme The scheme that should be used
     *
     * @return string
     */
    public function getUrlWithScheme($url, $schema = "https")
    {
        $hostname = \str_replace('www.', '', $url);
        $hostname = \str_replace('http://', '', $hostname);
        $hostname = \str_replace('https://', '', $hostname);

        return $schema. "://" . "www." . $hostname;
    }

    /**
     * Creates the cache key form the function and the variables
     *
     * @param string $class     The class running this code
     * @param string $function  The name of function calling the cache Key
     * @param array  $variables The variables for the given cache key query
     *
     * @return string
     */
    public function getCacheKey($class, $function, $variables)
    {
        if(is_array($variables) === false){
            $variables = array($variables);
        }
        $variableString = serialize($variables);
        
        return hash('sha1', $class."-".$function."-".$variableString);
    }

    /**
     * Checks if the given URLs domain is accepted. It is used to avoid spam and DDOS attacks
     * on the system.
     *
     * @param string $url The url that should be checked for acceptance.
     *
     * @return bool
     */
    public function isDomainAccepted($url)
    {
        if (\filter_var($url, FILTER_VALIDATE_URL) === false) {
            return false;
        }
        // Check if the caching is active
        if ($this->cacheActive === true) {
            $cacheKey = $this->getCacheKey(__CLASS__, __FUNCTION__, array($url));
            if ($this->findInCache($cacheKey) !== null) {
                return $this->findInCache($cacheKey);
            }
        }
        $parsedUrl = \parse_url($url);
        $acceptedDomains = \array_map("trim", \explode(",", $this->acceptedDomains));
        $acceptedDomains = \array_map(
            function ($domain) {
                return ".*".preg_quote($domain)."$";
            },
            $acceptedDomains
        );
        $regex = '/('.\implode("|", $acceptedDomains).')/';
        $value = (bool) \preg_match($regex, $parsedUrl["host"]);
        // Save the result in cache if caching is active
        if ($this->cacheActive === true) {
            $this->saveInCache($cacheKey, $value);
        }
        return $value;
    }

    /**
     * Returns the complete list of possible urls with different schemas
     *
     *
     * @param string $url The url that should be used to create the complete list of urls
     *
     * @return array
     */
    public function getCompleteUrls($url)
    {
        $hostname = \str_replace('www.', '', $url);
        $hostname = \str_replace('http://', '', $hostname);
        $hostname = \str_replace('https://', '', $hostname);

        return array(
            $url,
            "http://" . $hostname,
            "http://www." . $hostname,
            "https://" . $hostname,
            "https://www." . $hostname
        );
    }

    /**
     * Creates the script for the given siteId
     *
     * @param string $siteId The site id that should be used to create the script
     *
     * @return string
     */
    public function createScript($siteId)
    {
        if ($this->cacheActive === true) {
            $cacheKey = $this->getCacheKey(__CLASS__, __FUNCTION__, array($siteId));
            if ($this->findInCache($cacheKey) !== null) {
                return $this->findInCache($cacheKey);
            }
        }
        $template = html_entity_decode($this->scriptTemplate, ENT_QUOTES, 'UTF-8');
        $loader = new Twig_Loader_Array(array("template" => $template));
        $twig = new Twig_Environment($loader);
        $result = $twig->render("template", array("siteId" => $siteId));
        if ($this->cacheActive === true) {
            $this->saveInCache($cacheKey, $result);
        }
        return $result;
    }
}
