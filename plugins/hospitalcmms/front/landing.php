<?php
/**
 * ---------------------------------------------------------------------
 *
 * Hospital CMMS - Landing Page & Registration
 *
 * ---------------------------------------------------------------------
 */

include("../../../inc/includes.php");

// Check if user is already logged in
if (Session::getLoginUserID()) {
    Html::redirect($CFG_GLPI['root_doc'] . "/front/central.php");
    exit();
}

// Handle registration form submission
if (isset($_POST["register_trial"])) {
    $hospital_name = trim($_POST['hospital_name'] ?? '');
    $contact_name = trim($_POST['contact_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $country = trim($_POST['country'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    // Validate inputs
    $errors = [];

    if (empty($hospital_name)) {
        $errors[] = __('Hospital name is required');
    }
    if (empty($contact_name)) {
        $errors[] = __('Contact name is required');
    }
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = __('Valid email address is required');
    }
    if (empty($password) || strlen($password) < 8) {
        $errors[] = __('Password must be at least 8 characters');
    }
    if ($password !== $password_confirm) {
        $errors[] = __('Passwords do not match');
    }

    // Check if email already exists
    if (empty($errors)) {
        $existing = PluginHospitalcmmsSubscription::checkEmailExists($email);
        if ($existing) {
            $errors[] = __('An account with this email already exists');
        }
    }

    if (empty($errors)) {
        // Create subscription
        $result = PluginHospitalcmmsSubscription::createTrialSubscription([
            'hospital_name' => $hospital_name,
            'contact_name'  => $contact_name,
            'email'         => $email,
            'phone'         => $phone,
            'country'       => $country,
            'password'      => $password,
        ]);

        if ($result['success']) {
            // Send confirmation email
            PluginHospitalcmmsSubscription::sendConfirmationEmail($email, $hospital_name, $result['login']);

            Session::addMessageAfterRedirect(
                __('Registration successful! Check your email for login credentials.'),
                false,
                INFO
            );
            Html::redirect($CFG_GLPI['root_doc'] . "/plugins/hospitalcmms/front/landing.php?registered=1");
            exit();
        } else {
            $errors[] = $result['message'] ?? __('Registration failed. Please try again.');
        }
    }
}

// Get subscription stats for display
$stats = PluginHospitalcmmsSubscription::getStats();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo __('Hospital CMMS - Medical Equipment Management'); ?></title>
    <link rel="icon" type="image/x-icon" href="<?php echo $CFG_GLPI['root_doc']; ?>/pics/favicon.ico">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #0a1628 0%, #1a365d 50%, #0d4a8c 100%);
            min-height: 100vh;
            color: #fff;
        }

        .hero {
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 40px 20px;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><circle cx="50" cy="50" r="40" fill="none" stroke="rgba(255,255,255,0.03)" stroke-width="0.5"/></svg>') repeat;
            background-size: 100px 100px;
        }

        .hero::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(circle at 30% 20%, rgba(59, 130, 246, 0.15) 0%, transparent 50%),
                        radial-gradient(circle at 70% 80%, rgba(16, 185, 129, 0.1) 0%, transparent 50%);
            pointer-events: none;
        }

        .hero-content {
            position: relative;
            z-index: 1;
            text-align: center;
            max-width: 1200px;
            width: 100%;
        }

        .logo {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 15px;
            margin-bottom: 40px;
        }

        .logo-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 28px;
            box-shadow: 0 10px 40px rgba(16, 185, 129, 0.3);
        }

        .logo-text {
            font-size: 28px;
            font-weight: 700;
            background: linear-gradient(135deg, #fff 0%, #10b981 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero h1 {
            font-size: 56px;
            font-weight: 700;
            margin-bottom: 20px;
            line-height: 1.1;
            text-shadow: 0 2px 40px rgba(0, 0, 0, 0.3);
        }

        .hero h1 span {
            background: linear-gradient(135deg, #10b981 0%, #3b82f6 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero-subtitle {
            font-size: 22px;
            color: rgba(255, 255, 255, 0.8);
            margin-bottom: 50px;
            max-width: 700px;
            margin-left: auto;
            margin-right: auto;
            line-height: 1.6;
        }

        .content-grid {
            display: grid;
            grid-template-columns: 1fr 400px;
            gap: 60px;
            align-items: start;
            margin-top: 60px;
        }

        @media (max-width: 900px) {
            .content-grid {
                grid-template-columns: 1fr;
                gap: 40px;
            }
        }

        .features {
            text-align: left;
        }

        .feature-card {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 16px;
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
        }

        .feature-card:hover {
            background: rgba(255, 255, 255, 0.08);
            border-color: rgba(16, 185, 129, 0.3);
            transform: translateY(-2px);
        }

        .feature-icon {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.2) 0%, rgba(59, 130, 246, 0.2) 100%);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-bottom: 16px;
        }

        .feature-title {
            font-size: 18px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        .feature-desc {
            font-size: 14px;
            color: rgba(255, 255, 255, 0.7);
            line-height: 1.5;
        }

        .registration-form {
            background: rgba(255, 255, 255, 0.95);
            border-radius: 24px;
            padding: 40px;
            box-shadow: 0 25px 80px rgba(0, 0, 0, 0.3);
        }

        .form-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .form-header h2 {
            color: #1a365d;
            font-size: 28px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .form-header p {
            color: #64748b;
            font-size: 14px;
        }

        .trial-badge {
            display: inline-block;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            color: #374151;
            font-size: 14px;
            font-weight: 500;
            margin-bottom: 6px;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 12px 16px;
            border: 2px solid #e5e7eb;
            border-radius: 10px;
            font-size: 15px;
            transition: all 0.2s ease;
            background: #f9fafb;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: #10b981;
            background: #fff;
            box-shadow: 0 0 0 4px rgba(16, 185, 129, 0.1);
        }

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        .submit-btn {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            color: white;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 10px;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(16, 185, 129, 0.4);
        }

        .form-footer {
            text-align: center;
            margin-top: 20px;
            font-size: 12px;
            color: #9ca3af;
        }

        .form-footer a {
            color: #10b981;
            text-decoration: none;
        }

        .error-message {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #dc2626;
            padding: 12px 16px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .success-message {
            background: #f0fdf4;
            border: 1px solid #bbf7d0;
            color: #16a34a;
            padding: 12px 16px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
        }

        .stats-bar {
            display: flex;
            justify-content: center;
            gap: 50px;
            margin-top: 60px;
            padding: 30px;
            background: rgba(255, 255, 255, 0.05);
            border-radius: 16px;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .stat-item {
            text-align: center;
        }

        .stat-number {
            font-size: 36px;
            font-weight: 700;
            background: linear-gradient(135deg, #10b981 0%, #3b82f6 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .stat-label {
            font-size: 14px;
            color: rgba(255, 255, 255, 0.7);
            margin-top: 5px;
        }

        .footer {
            text-align: center;
            padding: 40px 20px;
            color: rgba(255, 255, 255, 0.5);
            font-size: 14px;
        }

        .footer a {
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
        }

        .footer a:hover {
            color: #fff;
        }

        .login-link {
            text-align: center;
            margin-top: 30px;
            padding-top: 30px;
            border-top: 1px solid #e5e7eb;
        }

        .login-link a {
            color: #10b981;
            text-decoration: none;
            font-weight: 500;
        }

        .login-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <section class="hero">
        <div class="hero-content">
            <div class="logo">
                <div class="logo-icon">🏥</div>
                <div class="logo-text">Hospital CMMS</div>
            </div>

            <h1>Manage Your Medical Equipment<br><span>With Confidence</span></h1>

            <p class="hero-subtitle">
                The complete Computerized Maintenance Management System designed specifically for hospitals.
                Track equipment, schedule maintenance, and ensure compliance across all departments.
            </p>

            <div class="content-grid">
                <div class="features">
                    <div class="feature-card">
                        <div class="feature-icon">📊</div>
                        <div class="feature-title">Complete Equipment Tracking</div>
                        <div class="feature-desc">
                            Register all medical equipment under departments. Track serial numbers,
                            calibration dates, warranty info, and maintenance history.
                        </div>
                    </div>

                    <div class="feature-card">
                        <div class="feature-icon">🔧</div>
                        <div class="feature-title">Preventive Maintenance</div>
                        <div class="feature-desc">
                            Schedule and automate preventive maintenance tasks. Never miss critical
                            maintenance deadlines again.
                        </div>
                    </div>

                    <div class="feature-card">
                        <div class="feature-icon">🔐</div>
                        <div class="feature-title">Role-Based Access Control</div>
                        <div class="feature-desc">
                            Department-based permissions ensure staff only see their department's
                            equipment while admins have full visibility.
                        </div>
                    </div>

                    <div class="feature-card">
                        <div class="feature-icon">📈</div>
                        <div class="feature-title">Real-time Dashboard</div>
                        <div class="feature-desc">
                            Get instant insights into equipment status, upcoming maintenance,
                            and calibration schedules across all departments.
                        </div>
                    </div>
                </div>

                <div class="registration-form">
                    <div class="form-header">
                        <span class="trial-badge">🎁 20-DAY FREE TRIAL</span>
                        <h2>Start Managing Equipment Today</h2>
                        <p>No credit card required. Full access to all features.</p>
                    </div>

                    <?php if (!empty($errors)): ?>
                        <div class="error-message">
                            <?php echo implode('<br>', $errors); ?>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($_GET['registered']) && $_GET['registered'] == 1): ?>
                        <div class="success-message">
                            ✓ Registration successful! Check your email for login credentials.
                        </div>
                    <?php endif; ?>

                    <form method="post" action="">
                        <div class="form-group">
                            <label for="hospital_name">Hospital / Medical Center Name *</label>
                            <input type="text" id="hospital_name" name="hospital_name"
                                   value="<?php echo htmlescape($_POST['hospital_name'] ?? ''); ?>"
                                   placeholder="Enter hospital name" required>
                        </div>

                        <div class="form-group">
                            <label for="contact_name">Contact Person *</label>
                            <input type="text" id="contact_name" name="contact_name"
                                   value="<?php echo htmlescape($_POST['contact_name'] ?? ''); ?>"
                                   placeholder="Full name" required>
                        </div>

                        <div class="form-group">
                            <label for="email">Work Email *</label>
                            <input type="email" id="email" name="email"
                                   value="<?php echo htmlescape($_POST['email'] ?? ''); ?>"
                                   placeholder="you@hospital.com" required>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="phone">Phone</label>
                                <input type="tel" id="phone" name="phone"
                                       value="<?php echo htmlescape($_POST['phone'] ?? ''); ?>"
                                       placeholder="+1 (555) 000-0000">
                            </div>

                            <div class="form-group">
                                <label for="country">Country</label>
                                <select id="country" name="country">
                                    <option value="">Select country</option>
                                    <option value="US" <?php echo ($_POST['country'] ?? '') === 'US' ? 'selected' : ''; ?>>United States</option>
                                    <option value="UK" <?php echo ($_POST['country'] ?? '') === 'UK' ? 'selected' : ''; ?>>United Kingdom</option>
                                    <option value="CA" <?php echo ($_POST['country'] ?? '') === 'CA' ? 'selected' : ''; ?>>Canada</option>
                                    <option value="AU" <?php echo ($_POST['country'] ?? '') === 'AU' ? 'selected' : ''; ?>>Australia</option>
                                    <option value="DE" <?php echo ($_POST['country'] ?? '') === 'DE' ? 'selected' : ''; ?>>Germany</option>
                                    <option value="FR" <?php echo ($_POST['country'] ?? '') === 'FR' ? 'selected' : ''; ?>>France</option>
                                    <option value="SA" <?php echo ($_POST['country'] ?? '') === 'SA' ? 'selected' : ''; ?>>Saudi Arabia</option>
                                    <option value="AE" <?php echo ($_POST['country'] ?? '') === 'AE' ? 'selected' : ''; ?>>UAE</option>
                                    <option value="IN" <?php echo ($_POST['country'] ?? '') === 'IN' ? 'selected' : ''; ?>>India</option>
                                    <option value="OTHER" <?php echo ($_POST['country'] ?? '') === 'OTHER' ? 'selected' : ''; ?>>Other</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="password">Password *</label>
                                <input type="password" id="password" name="password"
                                       placeholder="Min 8 characters" required minlength="8">
                            </div>

                            <div class="form-group">
                                <label for="password_confirm">Confirm Password *</label>
                                <input type="password" id="password_confirm" name="password_confirm"
                                       placeholder="Confirm password" required>
                            </div>
                        </div>

                        <button type="submit" name="register_trial" class="submit-btn">
                            Start Free Trial →
                        </button>

                        <div class="form-footer">
                            By registering, you agree to our
                            <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a>.
                        </div>

                        <div class="login-link">
                            Already have an account? <a href="<?php echo $CFG_GLPI['root_doc']; ?>/index.php">Log in here</a>
                        </div>
                    </form>
                </div>
            </div>

            <div class="stats-bar">
                <div class="stat-item">
                    <div class="stat-number"><?php echo number_format($stats['total_hospitals'] ?? 0); ?>+</div>
                    <div class="stat-label">Hospitals Registered</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo number_format($stats['total_equipment'] ?? 0); ?>+</div>
                    <div class="stat-label">Equipment Tracked</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number"><?php echo number_format($stats['total_maintenance'] ?? 0); ?>+</div>
                    <div class="stat-label">Maintenance Tasks</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">99.9%</div>
                    <div class="stat-label">Uptime</div>
                </div>
            </div>
        </div>
    </section>

    <footer class="footer">
        <p>© <?php echo date('Y'); ?> Hospital CMMS. All rights reserved.</p>
        <p style="margin-top: 10px;">
            <a href="#">Privacy Policy</a> ·
            <a href="#">Terms of Service</a> ·
            <a href="#">Support</a>
        </p>
    </footer>
</body>
</html>
