<?php
/**
 * ---------------------------------------------------------------------
 *
 * Hospital CMMS - Subscription Management Admin Panel
 *
 * ---------------------------------------------------------------------
 */

include("../../../inc/includes.php");

Session::checkLoginUser();

// Check if user is admin
if (!Session::haveRight('config', UPDATE)) {
    Html::displayRightError();
}

$subscription = new PluginHospitalcmmsSubscription();

// Handle subscription updates
if (isset($_POST["update_subscription"])) {
    $subscription->check($_POST['id'], UPDATE);
    $subscription->update($_POST);
    Session::addMessageAfterRedirect(__('Subscription updated successfully.'));
    Html::back();
}

if (isset($_POST["cancel_subscription"])) {
    $subscription->check($_POST['id'], UPDATE);
    $DB->update(PluginHospitalcmmsSubscription::getTable(), [
        'status' => PluginHospitalcmmsSubscription::STATUS_CANCELLED,
        'is_active' => 0,
    ], [
        'id' => $_POST['id'],
    ]);
    Session::addMessageAfterRedirect(__('Subscription cancelled.'));
    Html::back();
}

if (isset($_POST["activate_subscription"])) {
    $subscription->check($_POST['id'], UPDATE);
    $DB->update(PluginHospitalcmmsSubscription::getTable(), [
        'status' => PluginHospitalcmmsSubscription::STATUS_ACTIVE,
        'is_active' => 1,
        'subscription_start' => date('Y-m-d H:i:s'),
        'subscription_end' => date('Y-m-d H:i:s', strtotime('+1 year')),
    ], [
        'id' => $_POST['id'],
    ]);
    Session::addMessageAfterRedirect(__('Subscription activated.'));
    Html::back();
}

// Display header
echo "<div class='center'>\n";
echo "<h2>" . __('Subscription Management') . "</h2>\n";

// Display subscription statistics
$stats = PluginHospitalcmmsSubscription::getStats();
echo "<div style='display: flex; justify-content: center; gap: 20px; margin: 20px 0;'>\n";
echo "<div style='background: #f0f9ff; padding: 20px; border-radius: 10px; min-width: 150px;'>\n";
echo "<h3 style='margin: 0; color: #0369a1;'>" . __('Total Hospitals') . "</h3>\n";
echo "<p style='font-size: 24px; margin: 10px 0; color: #1e40af;'>" . number_format($stats['total_hospitals']) . "</p>\n";
echo "</div>\n";

echo "<div style='background: #f0fdf4; padding: 20px; border-radius: 10px; min-width: 150px;'>\n";
echo "<h3 style='margin: 0; color: #16a34a;'>" . __('Active Trials') . "</h3>\n";
echo "<p style='font-size: 24px; margin: 10px 0; color: #15803d;'>" . number_format($stats['active_trials'] ?? 0) . "</p>\n";
echo "</div>\n";

echo "<div style='background: #fef2f2; padding: 20px; border-radius: 10px; min-width: 150px;'>\n";
echo "<h3 style='margin: 0; color: #dc2626;'>" . __('Expired Trials') . "</h3>\n";
echo "<p style='font-size: 24px; margin: 10px 0; color: #b91c1c;'>" . number_format($stats['expired_trials'] ?? 0) . "</p>\n";
echo "</div>\n";

echo "<div style='background: #faf5ff; padding: 20px; border-radius: 10px; min-width: 150px;'>\n";
echo "<h3 style='margin: 0; color: #7c3aed;'>" . __('Paid Subscriptions') . "</h3>\n";
echo "<p style='font-size: 24px; margin: 10px 0; color: #6d28d9;'>" . number_format($stats['paid_subscriptions'] ?? 0) . "</p>\n";
echo "</div>\n";
echo "</div>\n";

// Display subscription list
echo "<div style='margin: 20px auto; max-width: 1200px;'>\n";
echo "<h3>" . __('All Subscriptions') . "</h3>\n";

Search::show(PluginHospitalcmmsSubscription::class);

echo "</div>\n";

// Display plans information
echo "<div style='margin: 40px auto; max-width: 1000px;'>\n";
echo "<h3>" . __('Subscription Plans') . "</h3>\n";

foreach (PluginHospitalcmmsSubscription::PLANS as $planId => $plan) {
    echo "<div style='background: white; border: 1px solid #e5e7eb; border-radius: 12px; padding: 24px; margin: 16px 0;'>\n";
    echo "<div style='display: flex; justify-content: space-between; align-items: center;'>\n";
    echo "<div>\n";
    echo "<h4 style='margin: 0; font-size: 20px;'>" . htmlescape($plan['name']) . "</h4>\n";
    echo "<p style='color: #6b7280; margin: 5px 0;'>\n";
    echo "<strong>\$" . number_format($plan['price_monthly']) . "/month</strong> or <strong>\$" . number_format($plan['price_yearly']) . "/year</strong>\n";
    echo "</p>\n";
    echo "<p style='color: #374151;'>\n";
    echo "Equipment: " . ($plan['max_equipment'] == -1 ? 'Unlimited' : number_format($plan['max_equipment'])) . " | ";
    echo "Users: " . ($plan['max_users'] == -1 ? 'Unlimited' : number_format($plan['max_users'])) . "\n";
    echo "</p>\n";
    echo "</div>\n";
    echo "<div>\n";
    echo "<strong>Features:</strong> " . implode(', ', $plan['features']) . "\n";
    echo "</div>\n";
    echo "</div>\n";
    echo "</div>\n";
}

echo "</div>\n";
echo "</div>\n";
