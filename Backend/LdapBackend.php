<?php
/**
 * This is the LDAP backend that is used to translate the uri to host
 *
 * @author Pouyan Azari <pouyan.azari@uni-wuerzburg.de>
 * @license MIT
 */

namespace Piwik\Plugins\DynamicJS\Backend;

use Symfony\Component\Ldap\Adapter\ExtLdap\Adapter;
use Symfony\Component\Ldap\Ldap as SymfonyLdap;

use Piwik\Config;
use Piwik\Container\StaticContainer;

class LdapBackend extends Backend
{
    /**
     * Placeholder for the DN
     *
     * @var string
     */
    private $dn;

    /**
     * Placeholder for the LDAP resource
     *
     * @var SymfonyLdap
     */
    private $ldap;

    /**
     * Placeholder for the logger
     *
     * @var LoggerInterface
     */
    private $logger;

    /**
     * Placeholder for the filter
     *
     * @var string
     */
    private $filter;

    /**
     * Placeholder for the username key
     *
     * @var string
     */
    private $userNameKey;

    /**
     * Placeholder for the username regex
     *
     * @var string
     */
    private $userNameRegex;

    /**
     * Placeholder for the Username regex match key
     *
     * @var string
     */
    private $userNameRegexMatchKey;

    /**
     * Placeholder for the binding username
     *
     * @var string
     */
    private $bindUser;

    /**
     * Placeholder for the bind password
     *
     * @var string
     */
    private $bindPassword;

    /**
     * Placeholder for the Username info Username key
     *
     * @var string
     */
    private $usernameInfoUsernameKey;

    /**
     * Placeholder for the Username info EMail Key
     *
     * @var string
     */
    private $usernameInfoEMailKey;

    /**
     * Placeholder for the Username Info Alias key
     *
     * @var string
     */
    private $usernameInfoAliasKey;

    /**
     *
     * Placeholder for the Username info Filter
     *
     * @var string
     */
    private $usernameInfoFilter;

    /**
     * Placeholder for the username info DN
     *
     * @var string
     */
    private $usernameInfoDn;

    /**
     * Placeholder for the piwik backend
     *
     * @var PiwikBackend
     */
    private $piwikBackend;

    /**
     * Constructor
     *
     */
    public function __construct()
    {
        parent::__construct();
        $config = array(
            "host" => Config::getInstance()->DynamicJS['ldap_host'],
            "port" => Config::getInstance()->DynamicJS['ldap_port'],
            "encryption" => Config::getInstance()->DynamicJS['ldap_encryption'],
            "options" => array(),
            "version" => 3
        );
        $this->dn = Config::getInstance()->DynamicJS['ldap_dn'];
        $this->filter = Config::getInstance()->DynamicJS['ldap_filter'];
        $this->userNameKey = Config::getInstance()->DynamicJS['ldap_username_key'];
        $this->userNameRegex = Config::getInstance()->DynamicJS['ldap_username_regex'];
        $this->userNameRegexMatchKey = Config::getInstance()->DynamicJS['ldap_username_regex_match_key'];
        $this->bindUser = Config::getInstance()->DynamicJS['ldap_username'];
        $this->bindPassword = Config::getInstance()->DynamicJS['ldap_password'];
        $this->logger = StaticContainer::getContainer()->get('Psr\Log\LoggerInterface');
        $this->usernameInfoUsernameKey = Config::getInstance()->DynamicJS['ldap_username_info_username_key'];
        $this->usernameInfoAliasKey =  Config::getInstance()->DynamicJS['ldap_username_info_alias_key'];
        $this->usernameInfoFilter = Config::getInstance()->DynamicJS['ldap_username_info_filter'];
        $this->usernameInfoEMailKey = Config::getInstance()->DynamicJS['ldap_username_info_email_key'];
        $this->usernameInfoDn =  Config::getInstance()->DynamicJS['ldap_username_info_dn'];
        $this->ldap = SymfonyLdap::create("ext_ldap", $config);
        $this->piwikBackend = StaticContainer::getContainer()->get('Piwik\Plugins\DynamicJS\Backend\PiwikBackend');
    }

    /**
     * Returns the name for the given backend
     *
     * @return string
     */
    public function getName()
    {
        return "LdapBackend";
    }

    /**
     * Returns the list of Variables
     *
     * @return array
     */
    public function getVariables()
    {
        return array(
            new BackendVariable("ldap_host", "text", "", "DynamicJS_LdapHost", "DynamicJS_LdapHostDesc", $this->getName()),
            new BackendVariable("ldap_port", "text", "", "DynamicJS_LdapPort", "DynamicJS_LdapPortDesc", $this->getName()),
            new BackendVariable("ldap_encryption", "text", "", "DynamicJS_LdapEncryption", "DynamicJS_LdapEncryptionDesc", $this->getName()),
            new BackendVariable("ldap_dn", "text", "", "DynamicJS_LdapDN", "DynamicJS_LdapDNDesc", $this->getName()),
            new BackendVariable("ldap_filter", "text", "", "DynamicJS_LdapFilter", "DynamicJS_LdapFilterDesc", $this->getName()),
            new BackendVariable("ldap_username", "text", "", "DynamicJS_LdapUserName", "DynamicJS_LdapUserNameDesc", $this->getName()),
            new BackendVariable("ldap_password", "password", "", "DynamicJS_LdapPassword", "DynamicJS_LdapPasswordDesc", $this->getName()),
            new BackendVariable("ldap_username_key", "text", "", "DynamicJS_LdapUsernameKey", "DynamicJS_LdapUsernameKeyDesc", $this->getName()),
            new BackendVariable("ldap_username_regex", "text", "", "DynamicJS_LdapUsernameRegex", "DynamicJS_LdapUsernameRegexDesc", $this->getName()),
            new BackendVariable("ldap_username_regex_match_key", "text", "", "DynamicJS_LdapUsernameRegexMatchKey", "DynamicJS_LdapUsernameRegexMatchKeyDesc", $this->getName()),
            new BackendVariable("ldap_username_info_email_key", "text", "mail", "DynamicJS_LdapUsernameInfoEmailKey", "DynamicJS_LdapUsernameInfoEmailKeyDesc", $this->getName()),
            new BackendVariable("ldap_username_info_alias_key", "text", "", "DynamicJS_LdapUsernameInfoAliasKey", "DynamicJS_LdapUsernameInfoAliasKeyDesc", $this->getName()),
            new BackendVariable("ldap_username_info_filter", "text", "", "DynamicJS_LdapUsernameInfoFilter", "DynamicJS_LdapUsernameInfoFilterDesc", $this->getName()),
            new BackendVariable("ldap_username_info_username_key", "text", "uid", "DynamicJS_LdapUsernameInfoUsernameKey", "DynamicJS_LdapUsernameInfoUsernameKeyDesc", $this->getName()),
            new BackendVariable("ldap_username_info_dn", "text", "", "DynamicJS_LdapUsernameInfoDn", "DynamicJS_LdapUsernameInfoDnDesc", $this->getName())
        );
    }

    /**
     * Returns the site id from the given url
     *
     * @param string $url The url that should be used to get the url
     *
     * @return int
     */
    public function getSiteId($url)
    {
        $siteId = 0;
        $parsedUrl = $this->getPossibleUrls($url, $this->recursionLevel);
        $existingUrl = $this->urlExists($parsedUrl);
        $urlWithSchema = $this->getUrlWithScheme($existingUrl);
        if ($existingUrl !== null) {
            $siteId = $this->piwikBackend->getSiteId($urlWithSchema, $this->recursionLevel, true);
            if ($siteId !== 0) {
                return $siteId;
            } else {
                // Add the not existing host to Piwik
                if ($this->enableAddHost === true) {
                    $siteId = $this->piwikBackend->addSite($urlWithSchema, 1, true);
                }
                // Add the access user to piwik
                if ($this->enableAddHost === true && $this->enableAddUser === true) {
                    $parsedUrl = parse_url($urlWithSchema);
                    $accessUsers = $this->getAccessUsers($parsedUrl["host"], $parsedUrl["path"], "", "", false);
                    foreach ($accessUsers as $user) {
                        $userInformation = $this->getUserInformation($user);
                        $this->piwikBackend->createUser(
                            $userInformation["username"],
                            $userInformation["alias"],
                            $userInformation["password"],
                            $userInformation["email"]
                        );
                        $this->piwikBackend->addAccessToUser($userInformation["username"], $siteId, $this->defaultAccess);
                    }
                }
                return $siteId;
            }
        }
        return $siteId;
    }

    /**
     * Creates alias from the LDAP entry
     *
     * @param string    $aliasKeyString  The keys for the given alias
     * @param LdapEntry $ldapEntry       The ldap entry from the server
     *
     * @return string
     */
    public function createAlias($aliasKeyString, $ldapEntry)
    {
        $aliasKeys = array_map("trim", explode(",", $aliasKeyString));
        $alias = "";
        if (sizeof($aliasKeys) === 1) {
            $alias = $ldapEntry->getAttribute($aliasKeys[0])[0];
        }
        if (sizeof($aliasKeys > 1)) {
            foreach ($aliasKeys as $k) {
                $alias = $alias . $ldapEntry->getAttribute($k)[0]. " ";
            }
        }
        return trim($alias);
    }

    /**
     * Returns an array with user information
     *
     * @param string $username The username that should be used to return the user infromation
     *
     * @return array `array("alias" => "", "username" => "", "password"=> "", "email"=>"")`
     */
    public function getUserInformation($username)
    {
        $result = array(
            "alias" => null,
            "username" => null,
            "password" => null,
            "email" => null,
        );
        // Bind to LDAP server
        $this->ldap->bind($this->bindUser, $this->bindPassword);
        $filter = new LdapFilter($this->usernameInfoFilter);
        $filterString = $filter->render(array("username" => $username));
        $ldapResult = $this->ldap->query($this->usernameInfoDn, $filterString)->execute();
        if ($ldapResult->count() === 1) {
            $result["email"] = $ldapResult[0]->getAttribute($this->usernameInfoEMailKey)[0];
            $result["username"] = $ldapResult[0]->getAttribute($this->usernameInfoUsernameKey)[0];
            $result["alias"] = $this->createAlias($this->usernameInfoAliasKey, $ldapResult[0]);
        }
        if ($ldapResult->count() > 1) {
            throw new \Exception("The uid is not unique in LDAP, how can it be possible :D");
        }
        return $result;
    }

    /**
     * Searches for the user that should have view access to the given url
     *
     * @param string  $domain             The domain of the given host
     * @param string  $path               The distinguished name that should be used for the search
     * @param string  $filter             The filter for the LDAP query
     * @param boolean $pathAsString       If the given path in parsedUrl should be used as string
     * @param boolean $withFrontSlash     If the frontSlash should be added to path
     * @param string  $usernameKey        The key for the username result
     *
     * @return array
     */
    public function getAccessUsers($domain, $path, $dn = "", $filter = "", $withFrontSlash = true, $usernameKey = null)
    {
        $users = array();
        $usernameKey = $usernameKey === null ? $this->userNameKey : $usernameKey;
        $queryResult = $this->queryLdapServer($domain, $path, $dn, $filter, $withFrontSlash);
        if ($queryResult->count() > 0) {
            $queryResultArray = $queryResult->toArray();
            $entry = $queryResultArray[0];
            $validUsers = $entry->getAttribute($usernameKey);
            $users = array_map(array($this, "extractUserFromResult"), $validUsers);
        }
        return $users;
    }

    /**
     * Extract User from the query result when it is set to do so. Use username as the placeholder
     *
     * @param string $usernameString The username string that should be used to extract the username
     * @param string $regex          The regex that should be used to get the username from the string
     *
     * @return string
     */
    public function extractUserFromResult($usernameString, $regex = null, $matchKey = null)
    {
        $regex = $regex === null ? $this->userNameRegex : $regex;
        $matchKey = $matchKey === null ? $this->userNameRegexMatchKey : $matchKey;
        $matched = (boolean) \preg_match($regex, $usernameString, $matches);
        if ($matched === true) {
            return $matches[$matchKey];
        }
        return "";
    }

    /**
     * Queries the LDAP server for the given parsed URL
     *
     * @param string  $domain            The domain that should be checked in ldap server
     * @param string  $path              The path that should be checked
     * @param string  $dn                The distinguished name that should be used for the search
     * @param string  $filter            The filter for the LDAP query
     * @param boolean $withFrontSlash    If the frontSlash should be added to path
     *
     * @return Collection|Null
     */
    public function queryLdapServer($domain, $path, $dn = "", $filter = "", $withFrontSlash = true)
    {
        // Returns cached data if it is active and available
        $this->ldap->bind($this->bindUser, $this->bindPassword);
        $dn = $dn === "" ? $this->dn : $dn;
        $filter = $filter === "" ? $this->filter : $filter;
        if ($withFrontSlash === true) {
            $path = "/" . $path;
        }
        $filter = new LdapFilter($filter);
        $filterVariables = array("path" => $path, "domain" => $domain);
        try {
            $filterString = $filter->render($filterVariables);
            $result = $this->ldap->query($dn, $filterString)->execute();
        } catch (\Exception $e) {
            $this->logger->error($e);
            $result = null;
        }
        return $result;
    }

    /**
     * Searches LDAP for the validity of the given URL as Host, Returns the url if exists. Returns null if none exit. Stops if found something
     *
     * @param array   $parsedUrl         The parsed URL which contains the domain and path array. An array("domain"=>"", "path" => "") is the input.
     * @param string  $dn                The distinguished name that should be used for the search
     * @param string  $filter            The filter for the LDAP query
     * @param boolean $withFrontSlash    If the frontSlash should be added to path
     *
     * @return string|null
     */
    public function urlExists($parsedUrl, $dn = "", $filter = "", $withFrontSlash = true)
    {
        if ($this->cacheActive === true) {
            $cacheKey = $this->getCacheKey(__CLASS__, __FUNCTION__, $parsedUrl);
            $cachedValue = $this->findInCache($cacheKey);
            if ($cachedValue !== null) {
                return $cachedValue;
            }
        }
        $result = null;
        $domain = $parsedUrl["domain"];
        // Allow domain it self always be the last resort.
        array_push($parsedUrl["path"], "");
        $paths  = $parsedUrl["path"];
        $resultCount = 0;
        foreach ($paths as $p) {
            try {
                $ldapResult = $this->queryLdapServer($domain, $p, $dn, $filter, $withFrontSlash);
                $resultCount = $ldapResult->count();
            } catch (\Exception $e) {
                $this->logger->error($e);
                $resultCount = 0;
            }
            if ($resultCount > 0 && $result === null) {
                $result = $domain."/".$p;
            }
        }
        if ($this->cacheActive === true) {
            $this->saveInCache($cacheKey, $cachedValue);
        }
        return $result;
    }
}
