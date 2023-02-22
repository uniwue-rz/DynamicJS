<?php
/**
 * This is Piwik Backend Connection. All the Piwik Related methods should be added here.
 *
 * @author Pouyan Azari <pouyan.azari@uni-wuerzburg.de>
 * @license MIT
 */
namespace Piwik\Plugins\DynamicJS\Backend;

use Piwik\Access;
use Piwik\Container\StaticContainer;
use Piwik\Config;
use Piwik\Piwik;
use Piwik\Plugins\SitesManager\API as SitesManagerAPI;
use Piwik\Plugins\UsersManager\API as UsersManagerAPI;

class PiwikBackend extends Backend
{
    /**
     * Placeholder for the site manager
     *
     * @var Piwik\Plugins\SitesManager\API
     */
    private $sitesManager;

    /**
     * Placeholder for the user manager
     *
     * @var Piwik\Plugins\UsersManager\API
     */
    private $usersManager;

    public function __construct()
    {
        parent::__construct();
        $this->sitesManager = StaticContainer::getContainer()->get('Piwik\Plugins\SitesManager\API');
        $this->usersManager = StaticContainer::getContainer()->get('Piwik\Plugins\UsersManager\API');
    }

    /**
     * Returns the variables for the given backend
     *
     * @return array
     */
    public function getVariables()
    {
        return array();
    }

    /**
     * Returns the name of the given backend
     *
     * @return string
     */
    public function getName()
    {
        return "PiwikBackend";
    }

    /**
     * Returns the site id from the given url. When not found returns 0
     *
     * @param string  $url      The url that should be used to get the site id
     * @param int     $level    The recursion level for the url search
     * @param boolean $exact    If the exact url should be searched
     *
     *
     * @return int | 0
     */
    public function getSiteId($url, $level = 1, $exact = false)
    {
        $siteId = 0;
        if ($this->isDomainAccepted($url) === false) {
            return 0;
        }
        // Check if the wanted data exists in cache
        if ($this->cacheActive === true) {
            $cacheKey = $this->getCacheKey(__CLASS__, __FUNCTION__, array($url));
            $cachedValue = $this->findInCache($cacheKey);
            if ($cachedValue !== null) {
                return $cachedValue;
            }
        }
        // Go through the process
        if ($exact === false) {
            $parsedUrls = $this->getPossibleUrls($url, $level);
            $normalizedUrls = $this->getNormalizedUrl($parsedUrls);
            // List the normalized URLS
            foreach ($normalizedUrls as $urlList) {
                foreach ($urlList as $u) {
                    if ($siteId === 0) {
                        $siteId = $this->getSiteIdSingle($u);
                    }
                }
            }
        } else {
            $urls = $this->getCompleteUrls($url);
            foreach ($urls as $u) {
                if ($siteId === 0) {
                    $siteId = $this->getSiteIdSingle($u);
                }
            }
        }
        // Save the data in cache
        if ($this->cacheActive === true) {
            $this->saveInCache($cacheKey, $siteId);
        }
        return $siteId;
    }

    /**
     * Returns the site id for the given single url.
     *
     * @param string $url The url that should be used to search
     *
     * @return int
     */
    public function getSiteIdSingle($url)
    {
        return (int) Access::doAsSuperUser(function () use ($url) {
            return $this->sitesManager->getSitesIdFromSiteUrl($url)[0]['idsite'] ?? 0;
        });
    }

    /**
     * Adds the given website to piwik to be tracked
     *
     * @param string $url The url of the given website to be tracked
     * @param int    $level The recursion level that should be used for the search
     * @param boolean $exact If the exact search should be done
     *
     * @return int | 0
     */
    public function addSite($url, $level = 1, $exact = false)
    {
        // Accepted Domains can go through
        if ($this->isDomainAccepted($url) === false) {
            return 0;
        }

        return $this->getSiteId($url, $level, $exact) ?:
            Access::doAsSuperUser(function () use ($url) {
                return $this->sitesManager->addSite(
                    $url,
                    $this->getCompleteUrls($url)
                );
            }
        );
    }

    /**
     * Adds the access to the given user for the given site Id. It is only used by new websites
     *
     * @param string $username      The username that should be used.
     * @param int    $siteId        The site id that the user should get access to
     * @param string $accessType    The type of access the user should get, default is view
     *
     * @return bool
     *
     * @throws \Exception
     */
    public function addAccessToUser($username, $siteId, $accessType = "view")
    {
        if ($this->userExists($username) === false) {
            throw new \Exception("Username $username does not exists");
        }

        if ($this->isUserSuperUser($username) === false) {
            Access::doAsSuperUser(function () use ($username, $accessType, $siteId) {
                $this->usersManager->setUserAccess($username, $accessType, [$siteId]);
            });
        }

        return true;
    }

    /**
     * Checks if the given user is a super user
     *
     * @param string $username The user name that should be checked for superUser Access
     *
     * @return boolean
     */
    public function isUserSuperUser($username)
    {
        return Access::doAsSuperUser(function () use ($username) {
            return Piwik::hasTheUserSuperUserAccess($username);
        });
    }

    /**
     * Creates a new User in piwik backend from the given data.
     *
     * @param string $username The user that should be used for the given user
     * @param string $alias    The alias for the given user
     * @param string $password The password for the given username
     * @param string $email    The email for the given user
     *
     * @return bool
     *
     * @throws \Exception
     */
    public function createUser($username, $alias, $password = null, $email = null)
    {
        // Check if the given user exists
        if ($this->userExists($username) === false) {
            $password = $password === null ? $this->generatePassword() : $password;
            $email = $email === null ? $this->defaultEmail : $email;
            Access::doAsSuperUser(function () use ($username, $password, $email, $alias) {
                $this->usersManager->addUser($username, $password, $email, $alias);
            });
        }
        
        return true;
    }

    /**
     * Checks if the given user exists
     *
     * @param string $username The username that should be checked for existence in Piwik Backend
     *
     * @return bool
     *
     * @throws \Exception
     */
    public function userExists($username)
    {
        return Access::doAsSuperUser(function () use ($username) {
            return $this->usersManager->userExists($username);
        });
    }

    /**
     * Returns the list of access the given user have
     *
     * @param string $username The username its accesses should be returned
     *
     * @return array
     */
    public function getUserAccess($username)
    {
        return Access::doAsSuperUser(function () use ($username) {
            return $this->usersManager->getSitesAccessFromUser($username);
        });
    }
}
