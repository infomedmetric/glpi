<?php

/**
 * ---------------------------------------------------------------------
 * MedMetric CMMS - external service providers
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
 * Vendor: external service providers and suppliers.
 */
class Vendor extends CommonDBTM
{
    public static $rightname = 'plugin_medmetriccmms';
    public $dohistory = true;

    public const KIND_SUPPLIER = 1;
    public const KIND_SERVICE  = 2;
    public const KIND_BOTH     = 3;

    /**
     * Type name.
     *
     * @param int $nb count
     *
     * @return string
     */
    public static function getTypeName($nb = 0) {
        return _n('Vendor', 'Vendors', $nb, 'medmetriccmms');
    }

    /**
     * Icon.
     *
     * @return string
     */
    public static function getIcon() {
        return 'ti-truck-delivery';
    }

    /**
     * Kind labels.
     *
     * @return array
     */
    public static function getKinds(): array {
        return [
            self::KIND_SUPPLIER => __('Supplier'),
            self::KIND_SERVICE  => __('Service provider'),
            self::KIND_BOTH     => __('Supplier and service'),
        ];
    }

    /**
     * Search options for the standard list.
     *
     * @return array
     */
    public function rawSearchOptions() {
        $tab = [];
        $tab[] = ['id' => 'common', 'name' => __('Characteristics')];

        $tab[] = ['id' => '1', 'table' => $this->getTable(), 'field' => 'name',
            'name' => __('Name'), 'datatype' => 'itemlink', 'massiveaction' => false];
        $tab[] = ['id' => '2', 'table' => $this->getTable(), 'field' => 'vendorkind',
            'name' => __('Kind'), 'datatype' => 'specific'];
        $tab[] = ['id' => '3', 'table' => $this->getTable(), 'field' => 'phonenumber',
            'name' => __('Phone'), 'datatype' => 'string'];
        $tab[] = ['id' => '4', 'table' => $this->getTable(), 'field' => 'email',
            'name' => __('Email'), 'datatype' => 'email'];
        $tab[] = ['id' => '5', 'table' => $this->getTable(), 'field' => 'website',
            'name' => __('Website'), 'datatype' => 'link'];
        $tab[] = ['id' => '6', 'table' => $this->getTable(), 'field' => 'contact_name',
            'name' => __('Contact'), 'datatype' => 'string'];
        $tab[] = ['id' => '16', 'table' => $this->getTable(), 'field' => 'comment',
            'name' => __('Comments'), 'datatype' => 'text'];
        return $tab;
    }

    /**
     * Specific displays.
     *
     * @param string $field   field
     * @param mixed  $values  values
     * @param array  $options options
     *
     * @return string
     */
    public static function getSpecificValueToDisplay($field, $values, array $options = []) {
        if (!is_array($values)) {
            $values = [$field => $values];
        }
        if ($field === 'vendorkind') {
            $kinds = self::getKinds();
            return htmlescape($kinds[(int) ($values[$field] ?? 0)] ?? '');
        }
        return parent::getSpecificValueToDisplay($field, $values, $options);
    }

    /**
     * Show the standard GLPI form.
     *
     * @param int   $ID      item id
     * @param array $options options
     *
     * @return bool
     */
    public function showForm($ID, array $options = []) {
        $this->initForm($ID, $options);
        $this->showFormHeader($options);

        echo "<tr class='tab_bg_1'><td>" . htmlescape(__('Name')) . "</td><td>";
        Html::input('name', ['value' => $this->fields['name'] ?? '']);
        echo "</td><td>" . htmlescape(__('Kind')) . "</td><td>";
        Html::select('vendorkind', self::getKinds(), ['value' => $this->fields['vendorkind'] ?? self::KIND_SERVICE, 'display' => true]);
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'><td>" . htmlescape(__('Contact')) . "</td><td>";
        Html::input('contact_name', ['value' => $this->fields['contact_name'] ?? '']);
        echo "</td><td>" . htmlescape(__('Phone')) . "</td><td>";
        Html::input('phonenumber', ['value' => $this->fields['phonenumber'] ?? '']);
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'><td>" . htmlescape(__('Email')) . "</td><td>";
        Html::input('email', ['value' => $this->fields['email'] ?? '']);
        echo "</td><td>" . htmlescape(__('Website')) . "</td><td>";
        Html::input('website', ['value' => $this->fields['website'] ?? '']);
        echo "</td></tr>";

        echo "<tr class='tab_bg_1'><td>" . htmlescape(__('Comments')) . "</td><td colspan='3'>";
        Html::textarea(['name' => 'comment', 'value' => $this->fields['comment'] ?? '', 'rows' => 3]);
        echo "</td></tr>";

        $this->showFormButtons($options);
        return true;
    }
}
