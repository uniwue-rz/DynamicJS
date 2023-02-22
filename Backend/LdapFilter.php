<?php
/**
 * LDAP Filter is used to create the filters needed for the given query in LDAP.
 * It uses the twig engine which makes creating the filters with variables much easier.
 * 
 * @author Pouyan Azari <pouyan.azari@uni-wuerzburg.de>
 * @license MIT
 */

namespace Piwik\Plugins\DynamicJS\Backend;

use Twig\Environment;
use Twig\Loader\ArrayLoader;

class LdapFilter
{
    /**
     * Placeholder for the twig
     *
     * @var Environment
     */
    private $twig;

    /**
     * Constructor
     *
     * @param string $filter The filter in the given string
     */
    public function __construct($filter)
    {
        $loader = new ArrayLoader(["filter" => $filter]);
        $this->twig = new Environment($loader);
    }

    /**
     * Renders the given template with the parameters
     *
     * @param array $params The parameters that should be used in the given filter
     */
    public function render($params)
    {
        return $this->twig->render("filter", $params);
    }
}
