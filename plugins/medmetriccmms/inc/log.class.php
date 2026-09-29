<?php

/**
 * ---------------------------------------------------------------------
 * MedMetric CMMS - audit and AI request log
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

use CommonDBTM;
use Session;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

/**
 * Log: audit trail and AI request journal.
 */
class Log extends CommonDBTM
{
    public static $rightname = 'plugin_medmetriccmms';
    public static $notable = true;

    public const KIND_AUDIT = 1;
    public const KIND_AI    = 2;
    public const KIND_CRON  = 3;
    public const KIND_ERROR = 4;

    /**
     * Type name.
     *
     * @param int $nb count
     *
     * @return string
     */
    public static function getTypeName($nb = 0) {
        return _n('Log', 'Logs', $nb, 'medmetriccmms');
    }

    /**
     * Register an audit entry. Never throws.
     *
     * @param int    $kind   one of KIND_*
     * @param string $action short action label
     * @param array  $params optional context
     *
     * @return void
     */
    public static function record(int $kind, string $action, array $params = []): void {
        try {
            global $DB;
            $DB->insertOrDie(self::getTable(), array_merge([
                'logkind'      => $kind,
                'action'       => mb_substr($action, 0, 190),
                'itemtype'     => $params['itemtype'] ?? null,
                'items_id'     => $params['items_id'] ?? 0,
                'users_id'     => $params['users_id'] ?? ($_SESSION['glpiID'] ?? 0),
                'response_summary' => isset($params['response']) ? mb_substr((string) $params['response'], 0, 2000) : null,
                'request_payload'  => isset($params['request']) ? mb_substr((string) $params['request'], 0, 2000) : null,
                'tokens_used'  => $params['tokens'] ?? 0,
                'duration_ms'  => $params['duration_ms'] ?? 0,
                'date_creation' => date('Y-m-d H:i:s'),
            ], []), 'MedMetric CMMS log insert failed');
        } catch (\Throwable $e) {
            // Logging must never break the application
        }
    }

    /**
     * Register an AI request.
     *
     * @param string $action   action label
     * @param array  $request  request payload summary
     * @param string $response response summary
     * @param int    $tokens   tokens used
     * @param int    $ms       duration in ms
     *
     * @return void
     */
    public static function addAi(string $action, array $request, string $response, int $tokens = 0, int $ms = 0): void {
        self::record(self::KIND_AI, $action, [
            'request'  => json_encode($request, JSON_UNESCAPED_UNICODE),
            'response' => $response,
            'tokens'   => $tokens,
            'duration_ms' => $ms,
            'users_id' => $_SESSION['glpiID'] ?? 0,
        ]);
    }

    /**
     * Recent AI requests for the central widget.
     *
     * @param int $limit rows
     *
     * @return array
     */
    public static function recentAi(int $limit = 10): array {
        global $DB;
        $rows = [];
        $iterator = $DB->request([
            'FROM'   => self::getTable(),
            'WHERE'  => ['logkind' => self::KIND_AI],
            'ORDER'  => ['date_creation DESC'],
            'LIMIT'  => $limit,
        ]);
        foreach ($iterator as $row) {
            $rows[] = $row;
        }
        return $rows;
    }

    /**
     * Search options for the standard list.
     *
     * @return array
     */
    public function rawSearchOptions() {
        $tab = [];
        $tab[] = ['id' => '1', 'table' => $this->getTable(), 'field' => 'action',
            'name' => __('Action'), 'datatype' => 'specific'];
        $tab[] = ['id' => '2', 'table' => $this->getTable(), 'field' => 'logkind',
            'name' => __('Kind'), 'datatype' => 'specific'];
        $tab[] = ['id' => '3', 'table' => $this->getTable(), 'field' => 'itemtype',
            'name' => __('Item type'), 'datatype' => 'specific'];
        $tab[] = ['id' => '4', 'table' => $this->getTable(), 'field' => 'items_id',
            'name' => __('Item ID'), 'datatype' => 'integer'];
        $tab[] = ['id' => '5', 'table' => $this->getTable(), 'field' => 'tokens_used',
            'name' => __('Tokens'), 'datatype' => 'integer'];
        $tab[] = ['id' => '6', 'table' => $this->getTable(), 'field' => 'duration_ms',
            'name' => __('Duration (ms)'), 'datatype' => 'integer'];
        $tab[] = ['id' => '7', 'table' => $this->getTable(), 'field' => 'response_summary',
            'name' => __('Response summary'), 'datatype' => 'text'];
        $tab[] = ['id' => '19', 'table' => $this->getTable(), 'field' => 'date_creation',
            'name' => __('Creation date'), 'datatype' => 'datetime'];
        return $tab;
    }

    /**
     * Human readable values for specific datatypes.
     *
     * @param array  $options search option
     * @param string $value   raw value
     * @param array  $values  all row values
     *
     * @return string
     */
    public static function getSpecificValueToDisplay($field, $values, array $options = []) {
        if (!is_array($values)) {
            $values = [$field => $values];
        }
        switch ($field) {
            case 'logkind':
                $kinds = [self::KIND_AUDIT => __('Audit'), self::KIND_AI => __('AI request'),
                    self::KIND_CRON => __('Cron'), self::KIND_ERROR => __('Error')];
                return htmlescape($kinds[$values[$field]] ?? (string) $values[$field]);
            case 'action':
                return htmlescape(Toolbox::truncate((string) ($values[$field] ?? ''), 80));
        }
        return parent::getSpecificValueToDisplay($field, $values, $options);
    }
}
