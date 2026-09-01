<?php
/**
 * ---------------------------------------------------------------------
 *
 * MedEquip CMMS — One-Click Installer
 *
 * Single-file installer that:
 * 1. Checks server requirements (PHP, extensions, MariaDB)
 * 2. Tests database connection
 * 3. Imports GLPI schema + default data
 * 4. Seeds CMMS taxonomy
 * 5. Creates the admin account
 *
 * Usage: Upload to your GLPI root/public/, visit in browser, follow steps.
 *
 * ---------------------------------------------------------------------
 */

error_reporting(E_ALL);
ini_set('display_errors', '0');
ini_set('log_errors', '1');

// Prevent direct access to already-installed system
if (file_exists(__DIR__ . '/../config/config_db.php')) {
    include_once __DIR__ . '/../config/config_db.php';
    if (class_exists('DB', false)) {
        echo '<!DOCTYPE html><html><head><title>Already Installed</title></head><body>';
        echo '<h1>MedEquip CMMS is already installed.</h1>';
        echo '<p>To reinstall, delete <code>config/config_db.php</code> and refresh this page.</p>';
        echo '<p><a href="../index.php">→ Go to login page</a></p>';
        echo '</body></html>';
        exit;
    }
}

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Handle form submissions
$step = (int)($_GET['step'] ?? 1);
$error = '';
$success = '';

// ─── Requirement check helpers ────────────────────────────────────────

function checkPHPVersion(): array {
    $errors = [];
    if (version_compare(PHP_VERSION, '8.2.0', '<')) {
        $errors[] = 'PHP 8.2+ required (current: ' . PHP_VERSION . ')';
    }
    return $errors;
}

function checkPHPExtensions(): array {
    $required = ['gd', 'curl', 'mbstring', 'intl', 'openssl', 'xml', 'bcmath', 'json', 'zip'];
    $missing = [];
    if (!extension_loaded('mysqli') && !function_exists('mysqli_connect')) {
        $missing[] = 'mysqli';
    }
    foreach ($required as $ext) {
        if (!extension_loaded($ext)) {
            $missing[] = $ext;
        }
    }
    return $missing;
}

function checkMySQLConnection(string $host, string $user, string $pass): array {
    $errors = [];
    $link = @new mysqli($host, $user, $pass);
    if ($link->connect_error) {
        $errors[] = 'Cannot connect to MySQL: ' . $link->connect_error;
    } else {
        $version = $link->server_info;
        if (version_compare($version, '10.5.0', '<')) {
            $errors[] = 'MariaDB 10.5+ or MySQL 5.7+ required (current: ' . $version . ')';
        }
    }
    if (isset($link)) {
        $link->close();
    }
    return $errors;
}

// ─── SQL import ───────────────────────────────────────────────────────

function splitSQL(string $sql_content): array {
    $statements = [];
    $current    = '';
    $in_string  = false;
    $string_char = '';
    $len = strlen($sql_content);
    $i = 0;

    while ($i < $len) {
        $char = $sql_content[$i];

        if ($in_string) {
            $current .= $char;
            if ($char === $string_char && ($i === 0 || $sql_content[$i - 1] !== '\\')) {
                $in_string = false;
            }
            $i++;
            continue;
        }

        if ($char === '\'' || $char === '"') {
            $in_string = true;
            $string_char = $char;
            $current .= $char;
            $i++;
            continue;
        }

        if ($char === '-' && $i + 1 < $len && $sql_content[$i + 1] === '-') {
            while ($i < $len && $sql_content[$i] !== "\n") { $i++; }
            continue;
        }

        if ($char === '/' && $i + 1 < $len && $sql_content[$i + 1] === '*') {
            $i += 2;
            while ($i < $len - 1) {
                if ($sql_content[$i] === '*' && $sql_content[$i + 1] === '/') { $i += 2; break; }
                $i++;
            }
            continue;
        }

        if ($char === ';') {
            $trimmed = trim($current);
            if (!empty($trimmed)) { $statements[] = $trimmed; }
            $current = '';
            $i++;
            continue;
        }

        $current .= $char;
        $i++;
    }

    $trimmed = trim($current);
    if (!empty($trimmed)) { $statements[] = $trimmed; }

    return $statements;
}

function importSQL(mysqli $link, string $sql_content, string $db_name): array {
    $messages = [];
    $success  = 0;
    $skipped  = 0;
    $errors   = 0;

    if (substr($sql_content, 0, 3) === "\xEF\xBB\xBF") {
        $sql_content = substr($sql_content, 3);
    }

    $link->select_db($db_name);
    $link->query("SET SESSION sql_mode = 'NO_AUTO_VALUE_ON_ZERO'");

    $statements = splitSQL($sql_content);

    foreach ($statements as $i => $statement) {
        $result = $link->query($statement);
        if ($result) {
            $success++;
        } else {
            $error_msg = $link->error;
            if (stripos($error_msg, 'already exists') !== false
                || stripos($error_msg, 'Duplicate entry') !== false
                || stripos($error_msg, 'Duplicate key') !== false
                || stripos($error_msg, 'Cannot add foreign key') !== false
            ) {
                $skipped++;
            } else {
                $errors++;
                if ($errors <= 10) {
                    $messages[] = "Statement " . ($i + 1) . " error: $error_msg";
                }
            }
        }
    }

    return ['success' => $success, 'skipped' => $skipped, 'errors' => $errors, 'messages' => $messages];
}

function runSeedScript(string $host, string $user, string $pass, string $dbname): array {
    $errors = [];
    $seed_script = dirname(__DIR__) . '/tools/seed_cmms_taxonomy.php';

    if (!file_exists($seed_script)) {
        $errors[] = 'Seed script not found (non-critical)';
        return $errors;
    }

    $cmd = 'php ' . escapeshellarg($seed_script)
         . ' --host=' . escapeshellarg($host)
         . ' --user=' . escapeshellarg($user)
         . ' --pass=' . escapeshellarg($pass)
         . ' --db=' . escapeshellarg($dbname)
         . ' 2>&1';

    $output = [];
    $return_code = 0;
    exec($cmd, $output, $return_code);

    if ($return_code !== 0) {
        $errors[] = 'Seed script returned code ' . $return_code . ' (non-critical)';
    }

    return $errors;
}

// ─── Process form submissions ─────────────────────────────────────────

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    switch ($step) {
        case 2: // Validate requirements and DB credentials
            $db_host     = trim($_POST['db_host'] ?? 'localhost');
            $db_user     = trim($_POST['db_user'] ?? '');
            $db_pass     = $_POST['db_pass'] ?? '';
            $db_name     = trim($_POST['db_name'] ?? 'medequip_cmms');
            $admin_user  = trim($_POST['admin_user'] ?? 'admin');
            $admin_pass  = $_POST['admin_pass'] ?? '';
            $admin_email = trim($_POST['admin_email'] ?? '');

            if (empty($db_user) || empty($db_pass) || empty($db_name) || empty($admin_user) || empty($admin_pass)) {
                $error = 'Please fill in all required fields.';
                break;
            }

            // Check MySQL connection
            $errors = checkMySQLConnection($db_host, $db_user, $db_pass);
            if (!empty($errors)) {
                $error = implode('<br>', $errors);
                break;
            }

            // Store in session for step 3
            $_SESSION['install'] = [
                'db_host'     => $db_host,
                'db_user'     => $db_user,
                'db_pass'     => $db_pass,
                'db_name'     => $db_name,
                'admin_user'  => $admin_user,
                'admin_pass'  => $admin_pass,
                'admin_email' => $admin_email,
                'lang'        => $_POST['lang'] ?? 'en_GB',
            ];

            $step = 3;
            break;

        case 3: // Run installation
            if (!isset($_SESSION['install'])) {
                $error = 'Installation session expired. Please start over.';
                $step = 1;
                break;
            }

            $install = $_SESSION['install'];
            $link = @new mysqli($install['db_host'], $install['db_user'], $install['db_pass']);

            if ($link->connect_error) {
                $error = 'Cannot connect to MySQL: ' . $link->connect_error;
                $step = 1;
                break;
            }

            // Create database
            $db_escaped = $link->real_escape_string($install['db_name']);
            $link->query("CREATE DATABASE IF NOT EXISTS `$db_escaped` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

            if (!$link->select_db($install['db_name'])) {
                $error = 'Cannot select database ' . htmlspecialchars($install['db_name'])
                       . '. Check cPanel → MySQL® Databases.';
                $step = 1;
                $link->close();
                break;
            }

            // Check if schema already exists
            $result = $link->query("SHOW TABLES LIKE 'glpi_configs'");
            $schema_exists = ($result && $result->num_rows > 0);

            if (!$schema_exists) {
                // Import GLPI schema
                $sql_file = dirname(__DIR__) . '/install/mysql/glpi-empty.sql';
                if (!file_exists($sql_file)) {
                    $error = 'GLPI schema not found: ' . htmlspecialchars($sql_file);
                    $step = 1;
                    $link->close();
                    break;
                }

                $sql_content = file_get_contents($sql_file);
                if ($sql_content === false) {
                    $error = 'Cannot read GLPI schema file.';
                    $step = 1;
                    $link->close();
                    break;
                }

                $import = importSQL($link, $sql_content, $db_escaped);

                if ($import['success'] === 0 && $import['errors'] > 0) {
                    $error = 'Schema import failed:<br>' . htmlspecialchars(implode('<br>', array_slice($import['messages'], -5)));
                    $step = 1;
                    $link->close();
                    break;
                }
            }

            // Run CMMS seed (non-critical)
            runSeedScript($install['db_host'], $install['db_user'], $install['db_pass'], $install['db_name']);

            // Update URL base
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
            $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
            $uri    = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/');
            $url_base = $scheme . '://' . $host . $uri;
            $link->query("UPDATE glpi_configs SET value = '" . $link->real_escape_string($url_base) . "' WHERE name = 'url_base'");

            // Create config file
            $config_content  = "<?php\n";
            $config_content .= "class DB extends DBmysql {\n";
            $config_content .= "   public \$dbhost     = " . var_export($install['db_host'], true) . ";\n";
            $config_content .= "   public \$dbuser     = " . var_export($install['db_user'], true) . ";\n";
            $config_content .= "   public \$dbpassword = " . var_export($install['db_pass'], true) . ";\n";
            $config_content .= "   public \$dbdefault  = " . var_export($install['db_name'], true) . ";\n";
            $config_content .= "   public \$use_utf8mb4 = true;\n";
            $config_content .= "}\n";

            $config_dir = dirname(__DIR__) . '/config';
            if (!is_dir($config_dir)) {
                mkdir($config_dir, 0755, true);
            }

            $config_path = $config_dir . '/config_db.php';
            if (file_put_contents($config_path, $config_content) === false) {
                $error = 'Schema imported but cannot write config file. '
                       . 'Create <code>config/config_db.php</code> manually with your DB credentials.';
                $step = 1;
                $link->close();
                break;
            }

            // Set up default GLPI admin user (glpi/glpi)
            $link->query("UPDATE glpi_users SET password = '" . password_hash('glpi', PASSWORD_DEFAULT) . "' WHERE name = 'glpi'");

            // Set up custom admin user if provided
            if (!empty($install['admin_user']) && !empty($install['admin_pass'])) {
                $au = $link->real_escape_string($install['admin_user']);
                $ap = $link->real_escape_string(password_hash($install['admin_pass'], PASSWORD_DEFAULT));
                $ae = $link->real_escape_string($install['admin_email']);
                $link->query("INSERT INTO glpi_users (name, password, email, is_active, profiles_id, entities_id)
                    VALUES ('$au', '$ap', '$ae', 1, 4, 0)
                    ON DUPLICATE KEY UPDATE password = '$ap', email = '$ae'");
            }

            $link->close();
            $step = 4;
            $success = 'Installation complete!';
            break;
    }
}

// ─── Check requirements ──────────────────────────────────────────────

$phpVersionErrors  = checkPHPVersion();
$missingExtensions = checkPHPExtensions();
$requirementsOk    = empty($phpVersionErrors) && empty($missingExtensions);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MedEquip CMMS — One-Click Installer</title>
    <style>
        * { box-sizing: border-box; }
        :root { --primary: #0d9488; --primary-dark: #0f766e; }
        body {
            font-family: system-ui, -apple-system, sans-serif;
            background: linear-gradient(135deg, #f0fdfa 0%, #e0f2f1 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            padding: 20px;
        }
        .installer-card { max-width: 700px; width: 100%; margin: 20px auto; }
        .card { background: white; border-radius: 16px; box-shadow: 0 4px 24px rgba(0,0,0,0.1); padding: 40px; }
        .logo { text-align: center; margin-bottom: 24px; }
        .logo h1 { color: var(--primary); font-size: 28px; }
        .logo p { color: #6b7280; margin-top: 4px; }
        .step-indicator { display: flex; justify-content: center; margin-bottom: 30px; gap: 0; }
        .step { display: flex; align-items: center; font-size: 13px; color: #9ca3af; }
        .step.active { color: var(--primary); font-weight: 600; }
        .step.completed { color: #10b981; }
        .step-num {
            width: 30px; height: 30px; border-radius: 50%;
            background: #e5e7eb; color: #6b7280;
            display: flex; align-items: center; justify-content: center;
            margin-right: 6px; font-weight: 600; font-size: 13px;
        }
        .step.active .step-num { background: var(--primary); color: white; }
        .step.completed .step-num { background: #10b981; color: white; }
        .step-line { width: 30px; height: 2px; background: #e5e7eb; margin: 0 4px; align-self: center; }
        .step.completed + .step-line { background: #10b981; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        th, td { padding: 8px 12px; text-align: left; border-bottom: 1px solid #f3f4f6; font-size: 14px; }
        .met { color: #10b981; font-weight: 600; }
        .fail { color: #ef4444; font-weight: 600; }
        .alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; font-size: 14px; }
        .alert-success { background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46; }
        .alert-danger { background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; }
        .field { margin-bottom: 16px; }
        label { display: block; font-weight: 600; color: #374151; margin-bottom: 6px; font-size: 14px; }
        input[type="text"], input[type="password"], input[type="email"], select {
            width: 100%; padding: 10px 14px; border: 1px solid #d1d5db;
            border-radius: 8px; font-size: 14px; transition: border 0.2s;
        }
        input:focus, select:focus { outline: none; border-color: var(--primary); box-shadow: 0 0 0 3px rgba(13,148,136,0.1); }
        .hint { font-size: 12px; color: #9ca3af; margin-top: 4px; }
        .row { display: flex; gap: 16px; }
        .row > .col { flex: 1; }
        .btn {
            display: block; width: 100%; padding: 12px; border: none; border-radius: 8px;
            font-size: 16px; font-weight: 600; cursor: pointer; text-align: center;
            text-decoration: none; transition: background 0.2s;
        }
        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { background: var(--primary-dark); }
        .btn:disabled { background: #94a3b8; cursor: not-allowed; }
        hr { border: none; border-top: 1px solid #e5e7eb; margin: 24px 0; }
        h3 { color: #1f2937; margin-bottom: 16px; }
        h5 { color: #374151; margin-bottom: 12px; }
        .error-box { background: #fef2f2; border: 1px solid #ef4444; border-radius: 8px; padding: 15px; margin-bottom: 20px; color: #991b1b; font-size: 14px; }
        .success-box { background: #ecfdf5; border: 1px solid #10b981; border-radius: 8px; padding: 24px; text-align: center; }
        .success-box h3 { color: #065f46; margin: 12px 0 8px; }
        .credentials { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 16px; margin: 16px 0; text-align: left; font-size: 14px; }
        .credentials code { background: #e2e8f0; padding: 2px 6px; border-radius: 4px; }
        .footer { text-align: center; margin-top: 20px; color: #9ca3af; font-size: 12px; }
        .spinner { width: 48px; height: 48px; border: 4px solid #e5e7eb; border-top: 4px solid var(--primary); border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto 20px; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        .warning { background: #fffbeb; border: 1px solid #fde68a; color: #92400e; padding: 12px; border-radius: 8px; margin-top: 16px; font-size: 13px; text-align: center; }
    </style>
</head>
<body>
    <div class="installer-card">
        <div class="card">
            <div class="logo">
                <h1>⚕️ MedEquip CMMS</h1>
                <p>One-Click Installer</p>
            </div>

            <!-- Step Indicator -->
            <div class="step-indicator">
                <div class="step <?= $step >= 1 ? ($step > 1 ? 'completed' : 'active') : '' ?>">
                    <div class="step-num"><?= $step > 1 ? '✓' : '1' ?></div>
                    <span>Requirements</span>
                </div>
                <div class="step-line"></div>
                <div class="step <?= $step >= 2 ? ($step > 2 ? 'completed' : 'active') : '' ?>">
                    <div class="step-num"><?= $step > 2 ? '✓' : '2' ?></div>
                    <span>Configuration</span>
                </div>
                <div class="step-line"></div>
                <div class="step <?= $step >= 3 ? ($step > 3 ? 'completed' : 'active') : '' ?>">
                    <div class="step-num"><?= $step > 3 ? '✓' : '3' ?></div>
                    <span>Install</span>
                </div>
                <div class="step-line"></div>
                <div class="step <?= $step >= 4 ? 'active' : '' ?>">
                    <div class="step-num">4</div>
                    <span>Complete</span>
                </div>
            </div>

            <?php if (!empty($error)): ?>
                <div class="error-box"><?= $error ?></div>
            <?php endif; ?>

            <?php if ($step === 1): ?>
                <!-- Step 1: Requirements Check -->
                <h3>Server Requirements</h3>
                <table>
                    <tbody>
                        <tr>
                            <td>PHP Version (≥8.2)</td>
                            <td style="text-align:right">
                                <?php if (empty($phpVersionErrors)): ?>
                                    <span class="met">✓ <?= PHP_VERSION ?></span>
                                <?php else: ?>
                                    <span class="fail">✗ <?= $phpVersionErrors[0] ?></span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <tr>
                            <td>mysqli extension</td>
                            <td style="text-align:right">
                                <?php if (extension_loaded('mysqli') || function_exists('mysqli_connect')): ?>
                                    <span class="met">✓ Installed</span>
                                <?php else: ?>
                                    <span class="fail">✗ Missing</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php foreach (['gd', 'curl', 'mbstring', 'intl', 'openssl', 'xml', 'bcmath', 'json', 'zip'] as $ext): ?>
                        <tr>
                            <td><?= $ext ?> extension</td>
                            <td style="text-align:right">
                                <?php if (extension_loaded($ext)): ?>
                                    <span class="met">✓ Installed</span>
                                <?php else: ?>
                                    <span class="fail">✗ Missing</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <?php if ($requirementsOk): ?>
                    <div class="alert alert-success">✓ All server requirements are met!</div>
                    <a href="?step=2" class="btn btn-primary">
                        Continue to Configuration →
                    </a>
                <?php else: ?>
                    <div class="alert alert-danger">⚠ Please fix the missing requirements above before continuing.</div>
                <?php endif; ?>

            <?php elseif ($step === 2): ?>
                <!-- Step 2: Configuration -->
                <h3>Database & Admin Setup</h3>
                <p style="color:#6b7280; margin-bottom:20px; font-size:14px;">Enter your MySQL/MariaDB credentials and admin account details.</p>

                <form method="post" action="?step=2">
                    <div class="field">
                        <label>Database Host</label>
                        <input type="text" name="db_host" value="localhost" required>
                        <div class="hint">Usually "localhost" on cPanel</div>
                    </div>

                    <div class="row">
                        <div class="col">
                            <div class="field">
                                <label>Database Username</label>
                                <input type="text" name="db_user" required placeholder="youruser_cmms">
                                <div class="hint">From cPanel → MySQL® Databases</div>
                            </div>
                        </div>
                        <div class="col">
                            <div class="field">
                                <label>Database Password</label>
                                <input type="password" name="db_pass" required>
                            </div>
                        </div>
                    </div>

                    <div class="field">
                        <label>Database Name</label>
                        <input type="text" name="db_name" value="medequip_cmms" required>
                        <div class="hint">Create this database first in cPanel → MySQL® Databases</div>
                    </div>

                    <hr>

                    <h5>Admin Account</h5>
                    <p style="color:#6b7280; margin-bottom:16px; font-size:13px;">This will be your main login for the CMMS.</p>

                    <div class="row">
                        <div class="col">
                            <div class="field">
                                <label>Username</label>
                                <input type="text" name="admin_user" value="admin" required>
                            </div>
                        </div>
                        <div class="col">
                            <div class="field">
                                <label>Password</label>
                                <input type="password" name="admin_pass" required>
                            </div>
                        </div>
                        <div class="col">
                            <div class="field">
                                <label>Email</label>
                                <input type="email" name="admin_email" required>
                            </div>
                        </div>
                    </div>

                    <div class="field">
                        <label>Language</label>
                        <select name="lang">
                            <option value="en_GB">English</option>
                            <option value="fr_FR">Français</option>
                            <option value="es_ES">Español</option>
                            <option value="ar_SA">العربية</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary" id="submit-btn">
                        Install MedEquip CMMS
                    </button>
                </form>

            <?php elseif ($step === 3): ?>
                <!-- Step 3: Installing -->
                <div style="text-align:center; padding:40px 0;">
                    <div class="spinner"></div>
                    <h3>Installing MedEquip CMMS...</h3>
                    <p style="color:#6b7280;">This may take 30-60 seconds. Please don't close this page.</p>
                </div>
                <script>
                    setTimeout(function() {
                        document.getElementById('install-form').submit();
                    }, 1000);
                </script>
                <form id="install-form" method="post" action="?step=3"></form>

            <?php elseif ($step === 4): ?>
                <!-- Step 4: Complete -->
                <div class="success-box">
                    <div style="font-size:48px;">✅</div>
                    <h3>Installation Complete!</h3>
                    <p style="color:#6b7280;">MedEquip CMMS has been installed successfully.</p>
                </div>

                <div class="credentials">
                    <strong>Default Account:</strong><br>
                    Username: <code>glpi</code> &nbsp; Password: <code>glpi</code><br><br>
                    <?php if (!empty($_SESSION['install']['admin_user'])): ?>
                    <strong>Your Admin Account:</strong><br>
                    Username: <code><?= htmlspecialchars($_SESSION['install']['admin_user']) ?></code>
                    <?php endif; ?>
                </div>

                <div style="margin-top:24px;">
                    <h5>Next Steps</h5>
                    <ol style="font-size:14px; color:#374151; padding-left:20px; line-height:2;">
                        <li>Login with your admin account or glpi/glpi</li>
                        <li>Go to <strong>Administration → Users</strong> to create staff accounts</li>
                        <li>Assign users to departments (ICU, Radiology, etc.)</li>
                        <li>Go to <strong>Assets → Medical equipment</strong> to register equipment</li>
                    </ol>
                </div>

                <div style="margin-top:24px;">
                    <a href="../index.php" class="btn btn-primary">
                        Go to Login
                    </a>
                </div>

                <div class="warning">
                    ⚠️ For security, delete this installer file after you're done: <code>public/install_cmms.php</code>
                </div>
            <?php endif; ?>
        </div>

        <div class="footer">
            MedEquip CMMS v1.0 — Powered by GLPI
        </div>
    </div>

    <?php if ($step === 2): ?>
    <script>
        document.querySelector('form').addEventListener('submit', function() {
            var btn = document.getElementById('submit-btn');
            btn.disabled = true;
            btn.textContent = 'Installing... Please wait...';
        });
    </script>
    <?php endif; ?>
</body>
</html>
