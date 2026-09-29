<?php

/**
 * ---------------------------------------------------------------------
 * MedMetric CMMS - profile rights / permissions
 * ---------------------------------------------------------------------
 * LICENSE
 * This file is part of MedMetric CMMS.
 * MedMetric CMMS is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 * ---------------------------------------------------------------------
 */

namespace GlpiPlugin\Medmetriccmms;

use Profile as GlpiProfile;
use ProfileRight;
use Session;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

/**
 * Profile: plugin permission helpers around the plugin_medmetriccmms right.
 */
class Profile
{
    public const RIGHTNAME = 'plugin_medmetriccmms';

    /**
     * Install default rights: super-admin gets all, technician read+update.
     *
     * @return void
     */
    public static function installRights(): void {
        $rights = [
            'plugin_medmetriccmms' => ALLSTANDARDRIGHT,
        ];
        ProfileRight::addProfileRights($rights);
    }

    /**
     * Remove rights on uninstall.
     *
     * @return void
     */
    public static function uninstallRights(): void {
        ProfileRight::deleteProfileRights([self::RIGHTNAME]);
    }

    /**
     * Give full rights to the super-admin profile if it exists.
     *
     * @return void
     */
    public static function grantSuperAdmin(): void {
        global $DB;
        $iterator = $DB->request([
            'FROM'  => 'glpi_profiles',
            'WHERE' => ['interface' => 'central'],
            'LIMIT' => 1,
        ]);
        if (!count($iterator)) {
            return;
        }
        ProfileRight::updateProfileRights((int) $iterator->current()['id'], [
            self::RIGHTNAME => ALLSTANDARDRIGHT,
        ]);
    }

    /**
     * Current user can view plugin data.
     *
     * @return bool
     */
    public static function canView(): bool {
        return Session::haveRight(self::RIGHTNAME, READ);
    }

    /**
     * Current user can edit plugin data.
     *
     * @return bool
     */
    public static function canUpdate(): bool {
        return Session::haveRight(self::RIGHTNAME, UPDATE);
    }

    /**
     * Show the rights matrix inside each profile form (addtabon).
     *
     * @param array $params hook params with 'item' being the Profile
     *
     * @return void
     */
    public static function showForProfile(array $params = []): void {
        $profile = $params['item'] ?? null;
        if (!$profile instanceof GlpiProfile || !$profile->fields['id']) {
            return;
        }
        if (!Session::haveRight('profile', READ)) {
            return;
        }
        echo "<div class='medmetric-card'>";
        echo "<h3>" . htmlescape(__('MedMetric CMMS rights')) . "</h3>";
        echo "<p class='medmetric-muted'>" . htmlescape(__('Standard rights (view, add, update, delete) apply to every MedMetric CMMS item type.')) . "</p>";
        echo "</div>";
    }
}
