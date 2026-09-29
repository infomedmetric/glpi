<?php

/**
 * ---------------------------------------------------------------------
 * MedMetric CMMS - plugin settings
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

use Config as GlpiConfig;
use Html;
use Session;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

/**
 * Config: plugin settings (AI provider defaults, alerting, priorities).
 */
class Config
{
    public const CONTEXT = 'plugin:medmetriccmms';

    /**
     * Default configuration values.
     *
     * @return array
     */
    public static function getDefaults(): array {
        return [
            'ai_provider'      => 'openai',
            'ai_base_url'      => 'https://api.openai.com/v1',
            'ai_model'         => 'gpt-4o-mini',
            'ai_api_key'       => '',
            'ai_temperature'   => '0.2',
            'alert_frequency'  => '7',
            'default_priority' => '3',
        ];
    }

    /**
     * Read one configuration value.
     *
     * @param string $name    setting name
     * @param mixed  $default fallback
     *
     * @return mixed
     */
    public static function get(string $name, $default = null) {
        $values = self::getAll();
        return $values[$name] ?? $default;
    }

    /**
     * Read all configuration values merged with defaults.
     *
     * @return array
     */
    public static function getAll(): array {
        global $DB;
        if (!$DB) {
            // Unit-test context without a bootable GLPI
            return self::getDefaults();
        }
        $values = GlpiConfig::getConfigurationValues(self::CONTEXT);
        return array_merge(self::getDefaults(), $values);
    }

    /**
     * Persist configuration values.
     *
     * @param array $values values to store
     *
     * @return void
     */
    public static function set(array $values): void {
        $allowed = self::getDefaults();
        $to_save = [];
        foreach ($values as $key => $val) {
            if (array_key_exists($key, $allowed) && $key !== 'ai_api_key') {
                $to_save[$key] = is_string($val) ? trim($val) : $val;
            }
        }
        // API key stored only when explicitly provided
        if (isset($values['ai_api_key']) && $values['ai_api_key'] !== '') {
            $to_save['ai_api_key'] = $values['ai_api_key'];
        }
        if (!empty($to_save)) {
            GlpiConfig::setConfigurationValues(self::CONTEXT, $to_save);
        }
    }

    /**
     * Render the settings form (used by front/config.form.php).
     *
     * @return void
     */
    public static function showConfigForm(): void {
        $cfg = self::getAll();
        $can_edit = Session::haveRight('config', UPDATE);

        echo "<div class='medmetric-card'>";
        echo "<h2 class='medmetric-card__title'>" . htmlescape(__('MedMetric CMMS settings')) . "</h2>";
        echo "<form method='post' action='" . htmlescape(self::getFormURL()) . "'>";

        echo "<table class='tab_cadre_fixe medmetric-table'>";

        echo "<tr class='tab_bg_1'><td>" . htmlescape(__('AI provider')) . "</td><td>";
        $providers = [
            'openai' => __('OpenAI (GPT models)'),
            'groq'   => __('Groq (fast Llama models)'),
        ];
        Html::select('ai_provider', $providers, [
            'value'     => $cfg['ai_provider'],
            'display'   => true,
            'readonly'  => !$can_edit,
        ]);
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'><td>" . htmlescape(__('API base URL')) . "</td><td>";
        Html::input('ai_base_url', ['value' => $cfg['ai_base_url'], 'size' => 60, 'readonly' => !$can_edit]);
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'><td>" . htmlescape(__('Default model')) . "</td><td>";
        Html::input('ai_model', ['value' => $cfg['ai_model'], 'size' => 60, 'readonly' => !$can_edit]);
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'><td>" . htmlescape(__('API key')) . "</td><td>";
        Html::input('ai_api_key', [
            'value'     => '',
            'type'      => 'password',
            'size'      => 60,
            'readonly'  => !$can_edit,
            'placeholder' => $cfg['ai_api_key'] !== '' ? __('(saved - enter a new key to replace)') : __('Paste your API key'),
        ]);
        echo "<div class='small'>" . htmlescape(__('Stored per AI model below or here as global fallback.')) . "</div>";
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'><td>" . htmlescape(__('Sampling temperature')) . "</td><td>";
        Html::input('ai_temperature', ['value' => $cfg['ai_temperature'], 'size' => 6, 'readonly' => !$can_edit]);
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'><td>" . htmlescape(__('Maintenance alert horizon (days)')) . "</td><td>";
        Html::input('alert_frequency', ['value' => $cfg['alert_frequency'], 'size' => 4, 'readonly' => !$can_edit]);
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'><td>" . htmlescape(__('Default priority')) . "</td><td>";
        $priorities = [
            1 => __('Very low'), 2 => __('Low'), 3 => __('Medium'), 4 => __('High'), 5 => __('Very high'),
        ];
        Html::select('default_priority', $priorities, ['value' => $cfg['default_priority'], 'display' => true, 'readonly' => !$can_edit]);
        echo "</td></tr>";

        echo "</table>";
        if ($can_edit) {
            echo Html::hidden('_glpi_csrf_token', ['value' => Session::getNewCSRFToken()]);
            echo "<div class='center mt-2'>";
            echo "<button type='submit' class='btn btn-primary' name='update' value='1'>" . htmlescape(__('Save')) . "</button>";
            echo "</div>";
        }
        echo "</form></div>";
    }

    /**
     * Form URL for the config page.
     *
     * @return string
     */
    public static function getFormURL(): string {
        return '/plugins/medmetriccmms/front/config.form.php';
    }
}
