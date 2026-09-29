<?php

/**
 * ---------------------------------------------------------------------
 * MedMetric CMMS - shared helpers
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

use Session;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

/**
 * Toolbox: small shared helpers used across the plugin.
 */
class Toolbox
{
    /**
     * Plugin configuration context.
     *
     * @return string
     */
    public static function configContext(): string {
        return 'plugin:medmetriccmms';
    }

    /**
     * Generate a sequential business code for a table, e.g. WO-000042.
     *
     * @param string $prefix  prefix, like 'WO'
     * @param string $table   full table name
     * @param string $field   code column
     *
     * @return string
     */
    public static function generateCode(string $prefix, string $table, string $field = 'code'): string {
        global $DB;
        $count = 0;
        $iterator = $DB->request(['COUNT' => 'c', 'FROM' => $table]);
        if (count($iterator)) {
            $row = $iterator->current();
            $count = (int) ($row['c'] ?? 0);
        }
        $code = sprintf('%s-%06d', $prefix, $count + 1);
        // Ensure uniqueness even after deletes
        $iter2 = $DB->request(['FROM' => $table, 'WHERE' => [$field => $code], 'LIMIT' => 1]);
        if (count($iter2)) {
            $code = sprintf('%s-%s%06d', $prefix, strtoupper(substr(md5(uniqid('', true)), 0, 2)), $count + 1);
        }
        return $code;
    }

    /**
     * Current session entity id.
     *
     * @return int
     */
    public static function currentEntity(): int {
        return (int) ($_SESSION['glpiactive_entity'] ?? 0);
    }

    /**
     * True when the current user is acting inside an entity subtree.
     *
     * @return bool
     */
    public static function isRecursiveView(): bool {
        return !empty($_SESSION['glpiactive_entity_recursive']);
    }

    /**
     * Format minutes as human readable duration.
     *
     * @param int $minutes duration
     *
     * @return string
     */
    public static function formatDuration(int $minutes): string {
        if ($minutes < 60) {
            return sprintf(_n('%d minute', '%d minutes', $minutes), $minutes);
        }
        $hours = floor($minutes / 60);
        $rest = $minutes % 60;
        if ($hours < 24) {
            return $rest > 0
                ? sprintf(_n('%d hour', '%d hours', $hours) . ' %d min', $hours, $rest)
                : sprintf(_n('%d hour', '%d hours', $hours), $hours);
        }
        $days = floor($hours / 24);
        return sprintf(_n('%d day', '%d days', $days), $days);
    }

    /**
     * Truncate a long text safely for tables and widgets.
     *
     * @param string $text  text
     * @param int    $limit max characters
     *
     * @return string
     */
    public static function truncate(string $text, int $limit = 120): string {
        $text = trim(strip_tags($text));
        if (mb_strlen($text) <= $limit) {
            return $text;
        }
        return mb_substr($text, 0, $limit - 3) . '...';
    }

    /**
     * Status CSS class helper for themed badges.
     *
     * @param int $status status constant
     *
     * @return string
     */
    public static function statusBadgeClass(int $status): string {
        return match ($status) {
            WorkOrder::STATUS_NEW => 'medmetric-badge--new',
            WorkOrder::STATUS_ASSIGNED => 'medmetric-badge--assigned',
            WorkOrder::STATUS_IN_PROGRESS => 'medmetric-badge--progress',
            WorkOrder::STATUS_WAITING_PARTS => 'medmetric-badge--waiting',
            WorkOrder::STATUS_DONE, WorkOrder::STATUS_CLOSED => 'medmetric-badge--done',
            WorkOrder::STATUS_CANCELLED => 'medmetric-badge--cancelled',
            default => 'medmetric-badge--neutral',
        };
    }

    /**
     * Send a JSON response and stop.
     *
     * @param mixed $data payload
     *
     * @return never
     */
    public static function sendJson($data): never {
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
