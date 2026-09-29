<?php

/**
 * ---------------------------------------------------------------------
 * MedMetric CMMS - AI model registry
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
use Html;
use Session;

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

/**
 * AiModel: registry of AI providers/models usable for diagnosis and plans.
 */
class AiModel extends CommonDBTM
{
    public static $rightname = 'plugin_medmetriccmms';

    public const PURPOSE_DIAGNOSIS = 1;
    public const PURPOSE_PLANNING  = 2;
    public const PURPOSE_REPORTING = 3;

    public $dohistory = true;

    /**
     * Type name.
     *
     * @param int $nb count
     *
     * @return string
     */
    public static function getTypeName($nb = 0) {
        return _n('AI model', 'AI models', $nb, 'medmetriccmms');
    }

    /**
     * Icon.
     *
     * @return string
     */
    public static function getIcon() {
        return 'ti-sparkles';
    }

    /**
     * Pick the active model for a purpose.
     *
     * @param int $purpose one of PURPOSE_*
     *
     * @return array|null
     */
    public static function getActiveForPurpose(int $purpose): ?array {
        global $DB;
        if (!$DB) {
            return null;
        }
        $iterator = $DB->request([
            'FROM'  => self::getTable(),
            'WHERE' => ['is_active' => 1, 'OR' => [['purpose' => $purpose], ['purpose' => 0]]],
            'ORDER' => ['date_mod DESC'],
            'LIMIT' => 1,
        ]);
        $row = count($iterator) ? $iterator->current() : null;
        return $row !== null ? $row : null;
    }

    /**
     * Always store the API key masked on form display.
     *
     * @param array $options options
     *
     * @return void
     */
    public function showForm($ID, array $options = []) {
        $this->initForm($ID, $options);
        $this->showFormHeader($options);

        echo "<tr class='tab_bg_1'><th colspan='4'>" . htmlescape(self::getTypeName(1)) . "</th></tr>";

        echo "<tr class='tab_bg_1'><td>" . htmlescape(__('Name')) . "</td><td>";
        Html::input('name', ['value' => $this->fields['name'] ?? '']);
        echo "</td><td>" . htmlescape(__('Provider')) . "</td><td>";
        $providers = ['openai' => 'OpenAI', 'groq' => 'Groq', 'azure' => 'Azure OpenAI', 'ollama' => 'Ollama (local)'];
        Html::select('provider', $providers, ['value' => $this->fields['provider'] ?? 'openai', 'display' => true]);
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'><td>" . htmlescape(__('Model identifier')) . "</td><td>";
        Html::input('model_id', ['value' => $this->fields['model_id'] ?? '', 'placeholder' => 'gpt-4o-mini / llama-3.3-70b-versatile']);
        echo "</td><td>" . htmlescape(__('API base URL')) . "</td><td>";
        Html::input('base_url', ['value' => $this->fields['base_url'] ?? '', 'placeholder' => 'https://api.groq.com/openai/v1']);
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'><td>" . htmlescape(__('API key')) . "</td><td>";
        Html::input('api_key', ['value' => '', 'type' => 'password', 'placeholder' => __('(unchanged)')]);
        echo "</td><td>" . htmlescape(__('Temperature')) . "</td><td>";
        Html::input('temperature', ['value' => $this->fields['temperature'] ?? '0.2', 'size' => 6]);
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'><td>" . htmlescape(__('Max tokens')) . "</td><td>";
        Html::input('max_tokens', ['value' => $this->fields['max_tokens'] ?? 1024, 'size' => 8]);
        echo "</td><td>" . htmlescape(__('Purpose')) . "</td><td>";
        $purposes = [
            0 => __('Any'),
            self::PURPOSE_DIAGNOSIS => __('Diagnosis'),
            self::PURPOSE_PLANNING  => __('Maintenance planning'),
            self::PURPOSE_REPORTING => __('Reporting'),
        ];
        Html::select('purpose', $purposes, ['value' => $this->fields['purpose'] ?? 0, 'display' => true]);
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'><td>" . htmlescape(__('Active')) . "</td><td>";
        Html::select('is_active', ['0' => __('No'), '1' => __('Yes')], ['value' => $this->fields['is_active'] ?? 1, 'display' => true]);
        echo "</td><td>" . htmlescape(__('Comments')) . "</td><td>";
        Html::textarea(['name' => 'comment', 'value' => $this->fields['comment'] ?? '', 'rows' => 2]);
        echo "</td></tr>";

        $this->showFormButtons($options);
        return true;
    }

    /**
     * Never echo the stored key.
     *
     * @param string $field field name
     * @param mixed  $values values
     * @param array  $options options
     *
     * @return string
     */
    public static function getSpecificValueToDisplay($field, $values, array $options = []) {
        if ($field === 'api_key') {
            return '••••••••';
        }
        return parent::getSpecificValueToDisplay($field, $values, $options);
    }

    /**
     * Keep old key when the form sends an empty one.
     *
     * @param array $input input data
     * @param int   $ID    item id
     * @param array $options options
     *
     * @return array
     */
    public function prepareInputForUpdate($input) {
        if (isset($input['api_key']) && $input['api_key'] === '') {
            unset($input['api_key']);
        }
        return parent::prepareInputForUpdate($input);
    }

    /**
     * Hash the key before insert.
     *
     * @param array $input input data
     *
     * @return array
     */
    public function prepareInputForAdd($input) {
        if (!isset($input['api_key']) || $input['api_key'] === '') {
            $input['api_key'] = null;
        }
        return parent::prepareInputForAdd($input);
    }
}
