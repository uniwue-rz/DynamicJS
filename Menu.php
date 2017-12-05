<?php

/**
 * Part of the Piwik Login Shibboleth Plug-in.
 */

namespace Piwik\Plugins\DynamicJS;

use Piwik\Menu\MenuAdmin;
use Piwik\Piwik;
use Piwik\Plugin\Menu as PiwikMenu;

/**
 * The Menu configuration of the Plug-in.
 *
 * Here the menu settings of the plug-in can be set. At this moment the plug-in settings will reside in Settings.
 * If there are more menu related functionalities are needed, they can be added here.
 *
 * @author Pouyan Azari <pouyan.azari@uni-wuerzburg.de>
 * @license MIT
 * @copyright 2014-2016 University of Wuerzburg
 * @copyright 2014-2016 Pouyan Azari
 */
class Menu extends PiwikMenu
{
    /**
     * Configures the menu inherited from Menu.
     *
     * @param Menu $menu The global Menu object.
     */
    public function configureAdminMenu(MenuAdmin $menu)
    {
        if (Piwik::hasUserSuperUserAccess()) {
            $menu->addSystemItem('DynamicJS', $this->urlForAction('admin'), $order = 30);
        }
    }
}