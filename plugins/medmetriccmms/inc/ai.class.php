<?php

/**
 * ---------------------------------------------------------------------
 * MedMetric CMMS - AI orchestration (OpenAI / Groq compatible)
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

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

/**
 * Ai: chat completion orchestration against OpenAI-compatible APIs.
 * Works with OpenAI, Groq, Azure OpenAI or any compatible endpoint.
 */
class Ai
{
    public const ACTION_DIAGNOSE = 'diagnose';
    public const ACTION_PLAN     = 'plan';
    public const ACTION_SUMMARIZE = 'summarize';

    /**
     * Resolve the request profile (model, key, base url, temperature).
     *
     * @param int $purpose one of AiModel::PURPOSE_*
     *
     * @return array|null null when no key is configured
     */
    public static function resolveProfile(int $purpose = AiModel::PURPOSE_DIAGNOSIS): ?array {
        $model_row = AiModel::getActiveForPurpose($purpose);
        if ($model_row !== null) {
            $key = trim((string) ($model_row['api_key'] ?? ''));
            if ($key !== '') {
                return [
                    'provider'    => $model_row['provider'] ?? 'openai',
                    'base_url'    => rtrim((string) ($model_row['base_url'] ?: 'https://api.openai.com/v1'), '/'),
                    'model'       => $model_row['model_id'] ?? 'gpt-4o-mini',
                    'api_key'     => $key,
                    'temperature' => (float) ($model_row['temperature'] ?? 0.2),
                    'max_tokens'  => (int) ($model_row['max_tokens'] ?? 1024),
                ];
            }
        }

        $cfg = Config::getAll();
        $key = trim((string) ($cfg['ai_api_key'] ?? ''));
        if ($key === '') {
            return null;
        }
        return [
            'provider'    => $cfg['ai_provider'] ?? 'openai',
            'base_url'    => rtrim((string) ($cfg['ai_base_url'] ?? 'https://api.openai.com/v1'), '/'),
            'model'       => $cfg['ai_model'] ?? 'gpt-4o-mini',
            'api_key'     => $key,
            'temperature' => (float) ($cfg['ai_temperature'] ?? 0.2),
            'max_tokens'  => 1024,
        ];
    }

    /**
     * Whether AI features are usable with current configuration.
     *
     * @return bool
     */
    public static function isConfigured(): bool {
        return self::resolveProfile() !== null;
    }

    /**
     * Run a chat completion.
     *
     * @param array  $messages  OpenAI style messages
     * @param string $action    action label for logging
     * @param int    $purpose   purpose constant
     *
     * @return array {success: bool, content: string, error?: string}
     */
    public static function chat(array $messages, string $action = self::ACTION_DIAGNOSE, int $purpose = AiModel::PURPOSE_DIAGNOSIS): array {
        $profile = self::resolveProfile($purpose);
        if ($profile === null) {
            return [
                'success' => false,
                'content' => '',
                'error'   => __('No AI provider configured. Add an API key in MedMetric CMMS settings.'),
            ];
        }

        $payload = [
            'model'       => $profile['model'],
            'messages'    => $messages,
            'temperature' => $profile['temperature'],
            'max_tokens'  => $profile['max_tokens'],
        ];

        $started = microtime(true);
        $result = self::callEndpoint($profile['base_url'], $profile['api_key'], $payload);
        $ms = (int) ((microtime(true) - $started) * 1000);

        $usage_tokens = (int) ($result['json']['usage']['total_tokens'] ?? 0);
        $content = '';
        $error = null;
        if ($result['success']) {
            $content = (string) ($result['json']['choices'][0]['message']['content'] ?? '');
            if ($content === '') {
                $error = __('Empty AI response');
            }
        } else {
            $error = $result['error'] ?? __('AI request failed');
        }

        Log::addAi($action, ['model' => $profile['model']], $error !== null ? ('ERROR: ' . $error) : Toolbox::truncate($content, 500), $usage_tokens, $ms);

        return $result['success'] && $content !== ''
            ? ['success' => true, 'content' => $content]
            : ['success' => false, 'content' => '', 'error' => $error];
    }

    /**
     * Low level HTTP call to an OpenAI-compatible chat endpoint.
     *
     * @param string $base_url API root
     * @param string $api_key  bearer key
     * @param array  $payload  request body
     *
     * @return array {success, json, error}
     */
    protected static function callEndpoint(string $base_url, string $api_key, array $payload): array {
        $url = $base_url . '/chat/completions';
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Authorization: Bearer ' . $api_key,
            ],
            CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);
        $raw = curl_exec($ch);
        $errno = curl_errno($ch);
        $http_code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($errno) {
            return ['success' => false, 'json' => [], 'error' => sprintf(__('Connection error (%s)'), $errno)];
        }
        $json = json_decode((string) $raw, true);
        if (!is_array($json)) {
            return ['success' => false, 'json' => [], 'error' => sprintf(__('Invalid response (HTTP %s)'), $http_code)];
        }
        if ($http_code >= 400) {
            $msg = $json['error']['message'] ?? ('HTTP ' . $http_code);
            return ['success' => false, 'json' => $json, 'error' => $msg];
        }
        return ['success' => true, 'json' => $json, 'error' => null];
    }

    /**
     * Build diagnosis prompt context for an equipment.
     *
     * @param int $equipments_id equipment id
     *
     * @return string|null
     */
    protected static function equipmentContext(int $equipments_id): ?string {
        $equipment = new Equipment();
        if (!$equipment->getFromDB($equipments_id)) {
            return null;
        }
        $history = [];
        global $DB;
        $iterator = $DB->request([
            'FROM'  => WorkOrder::getTable(),
            'WHERE' => ['plugin_medmetriccmms_equipments_id' => $equipments_id],
            'ORDER' => ['date DESC'],
            'LIMIT' => 10,
        ]);
        foreach ($iterator as $row) {
            $history[] = sprintf('%s [%s] %s: %s', (string) $row['date'], (string) $row['status'],
                (string) $row['name'], Toolbox::truncate((string) $row['content'], 150));
        }
        return sprintf(
            "Equipment: %s\nType: %s\nCriticality: %s\nStatus: %s\nIn service since: %s\nLast maintenance: %s\nRecent work orders:\n%s",
            (string) $equipment->fields['name'],
            (string) EquipmentType::getTypeName(),
            (string) $equipment->fields['criticality'],
            (string) Equipment::getStatusName((int) $equipment->fields['status']),
            (string) $equipment->fields['commissioning_date'],
            (string) $equipment->fields['last_maintenance'],
            $history === [] ? '- none -' : implode("\n", $history)
        );
    }

    /**
     * AI failure diagnosis for a work order / equipment.
     *
     * @param int    $equipments_id equipment id
     * @param string $symptoms      reported symptoms
     *
     * @return array
     */
    public static function diagnose(int $equipments_id, string $symptoms): array {
        $context = self::equipmentContext($equipments_id);
        if ($context === null) {
            return ['success' => false, 'content' => '', 'error' => __('Equipment not found')];
        }
        $messages = [
            ['role' => 'system', 'content' =>
                'You are a senior hospital clinical engineering (biomedical) expert. ' .
                'Analyse the reported symptoms of the medical equipment below. Reply with: ' .
                '1) Most likely failure causes (ranked, short). 2) Immediate safety checks before use. ' .
                '3) Recommended corrective actions and parts to inspect. 4) When to escalate to the vendor. ' .
                'Be concise, use short bullet lists, no markdown headings.'],
            ['role' => 'user', 'content' => $context . "\n\nReported symptoms: " . $symptoms],
        ];
        return self::chat($messages, self::ACTION_DIAGNOSE, AiModel::PURPOSE_DIAGNOSIS);
    }

    /**
     * AI preventive maintenance plan suggestion.
     *
     * @param int    $equipmenttypes_id equipment type
     * @param string $usage_profile     usage description
     *
     * @return array
     */
    public static function suggestPlan(int $equipmenttypes_id, string $usage_profile): array {
        global $DB;
        $type_name = '';
        $iterator = $DB->request([
            'FROM'  => 'glpi_plugin_medmetriccmms_equipmenttypes',
            'WHERE' => ['id' => $equipmenttypes_id],
            'LIMIT' => 1,
        ]);
        if (count($iterator)) {
            $type_name = (string) ($iterator->current()['name'] ?? '');
        }
        $messages = [
            ['role' => 'system', 'content' =>
                'You are a hospital clinical engineering planner. Propose a preventive maintenance plan for the ' .
                'equipment type below. Reply with: 1) Suggested periodicity in days with justification. ' .
                '2) A checklist of 8 to 12 short tasks. 3) Required spare parts and tools. ' .
                '4) Safety/compliance notes. Plain bullets only.'],
            ['role' => 'user', 'content' => sprintf("Equipment type: %s\nUsage profile: %s", $type_name ?: 'unknown', $usage_profile)],
        ];
        return self::chat($messages, self::ACTION_PLAN, AiModel::PURPOSE_PLANNING);
    }

    /**
     * Summarize a work order for reporting.
     *
     * @param int $workorders_id work order id
     *
     * @return array
     */
    public static function summarizeWorkOrder(int $workorders_id): array {
        $wo = new WorkOrder();
        if (!$wo->getFromDB($workorders_id)) {
            return ['success' => false, 'content' => '', 'error' => __('Work order not found')];
        }
        $messages = [
            ['role' => 'system', 'content' =>
                'Summarize this maintenance work order in 3 short sentences for a hospital management report: ' .
                'what failed, what was done, and prevention advice.'],
            ['role' => 'user', 'content' => sprintf(
                "Title: %s\nDescription: %s\nSolution: %s\nStatus: %s\nDowntime: %s min",
                (string) $wo->fields['name'],
                Toolbox::truncate((string) $wo->fields['content'], 500),
                Toolbox::truncate((string) $wo->fields['solution_description'], 500),
                (string) $wo->fields['status'],
                (string) $wo->fields['downtime_duration']
            )],
        ];
        return self::chat($messages, self::ACTION_SUMMARIZE, AiModel::PURPOSE_REPORTING);
    }
}
