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
     * Install default rights: create one `plugin_medmetriccmms` row for every
     * profile that does not have one yet (all rights disabled).
     *
     * Idempotent: safe to run again on upgrade, and never resets rights that
     * an administrator already granted through a profile form.
     *
     * @return void
     */
    public static function installRights(): void {
        global $DB;

        // Profiles that already have a plugin_medmetriccmms right row.
        $existing = [];
        foreach ($DB->request([
            'SELECT' => ['profiles_id'],
            'FROM'   => ProfileRight::getTable(),
            'WHERE'  => ['name' => self::RIGHTNAME],
        ]) as $row) {
            $existing[(int) $row['profiles_id']] = true;
        }

        // `updateProfileRights()` takes a name => rights map and creates the
        // missing row, which is the right call here. Note that
        // `addProfileRights()` expects a plain *list* of right names and would
        // insert the `ALLSTANDARDRIGHT` integer as the right name.
        foreach ($DB->request(['SELECT' => ['id'], 'FROM' => GlpiProfile::getTable()]) as $row) {
            $profiles_id = (int) $row['id'];
            if (!isset($existing[$profiles_id])) {
                ProfileRight::updateProfileRights($profiles_id, [self::RIGHTNAME => 0]);
            }
        }
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
     * Give full rights to the super-admin profile(s).
     *
     * Prefers the profile literally named "Super-Admin" (the GLPI default);
     * falls back to every profile that already has full `config` rights, so
     * administrators keep access on non-standard installations.
     *
     * @return void
     */
    public static function grantSuperAdmin(): void {
        global $DB;

        $profiles_ids = [];
        foreach ($DB->request([
            'SELECT' => ['id'],
            'FROM'   => GlpiProfile::getTable(),
            'WHERE'  => ['name' => 'Super-Admin'],
        ]) as $row) {
            $profiles_ids[] = (int) $row['id'];
        }

        if ($profiles_ids === []) {
            foreach ($DB->request([
                'SELECT' => ['profiles_id'],
                'FROM'   => ProfileRight::getTable(),
                'WHERE'  => ['name' => 'config', 'rights' => ALLSTANDARDRIGHT],
            ]) as $row) {
                $profiles_ids[] = (int) $row['profiles_id'];
            }
        }

        foreach (array_unique($profiles_ids) as $profiles_id) {
            ProfileRight::updateProfileRights($profiles_id, [
                self::RIGHTNAME => ALLSTANDARDRIGHT,
            ]);
        }
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
