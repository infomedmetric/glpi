<?php
/**
 * MedEquip CMMS — Standalone One-Click Installer
 *
 * This file works on ANY cPanel/LiteSpeed server without GLPI routing.
 * It creates the database config, imports the GLPI schema, seeds CMMS data,
 * and then redirects to the login page.
 *
 * Upload this to: public_html/cmms/public/
 * Visit: https://yourdomain.com/install_cmms_standalone.php
 */

error_reporting(E_ALL);
ini_set('display_errors', '0'); // Don't show errors to end users
ini_set('log_errors', '1');

$project_root = dirname(__DIR__); // goes from public/ to project root
$config_file  = $project_root . '/config/config_db.php';

// If already installed, show login link
if (file_exists($config_file)) {
    include_once $config_file;
    if (class_exists('DB', false)) {
        ?>
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>MedEquip CMMS — Already Installed</title>
            <style>
                body { font-family: system-ui; display: flex; justify-content: center; align-items: center; min-height: 100vh; margin: 0; background: #f0fdfa; }
                .card { background: white; padding: 40px; border-radius: 16px; box-shadow: 0 4px 20px rgba(0,0,0,0.1); text-align: center; max-width: 500px; }
                a { display: inline-block; margin-top: 20px; padding: 12px 24px; background: #0d9488; color: white; text-decoration: none; border-radius: 8px; }
            </style>
        </head>
        <body>
            <div class="card">
                <h1>✅ MedEquip CMMS is Installed</h1>
                <p>Database configuration already exists.</p>
                <a href="./index.php">→ Go to Login Page</a>
            </div>
        </body>
        </html>
        <?php
        exit;
    }
}

// Handle form submission
$error = '';
$step  = 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db_host = trim($_POST['db_host'] ?? 'localhost');
    $db_user = trim($_POST['db_user'] ?? '');
    $db_pass = $_POST['db_pass'] ?? '';
    $db_name = trim($_POST['db_name'] ?? '');

    if (empty($db_user) || empty($db_name)) {
        $error = 'Please fill in database name and username.';
    } else {
        // Test connection (without selecting a database yet)
        $link = @new mysqli($db_host, $db_user, $db_pass);
        if ($link->connect_error) {
            $error = 'Cannot connect to MySQL: ' . htmlspecialchars($link->connect_error)
                   . '<br>Make sure the credentials are correct in cPanel → MySQL® Databases.';
        } else {
            // Create database if it doesn't exist
            $db_name_escaped = $link->real_escape_string($db_name);
            $link->query("CREATE DATABASE IF NOT EXISTS `$db_name_escaped` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

            // Select the database
            if (!$link->select_db($db_name)) {
                $error = 'Cannot select database <strong>' . htmlspecialchars($db_name) . '</strong>. '
                       . 'Make sure the database exists in cPanel → MySQL® Databases.';
            } else {
                // Check if tables already exist (maybe schema was already imported)
                $result = $link->query("SHOW TABLES LIKE 'glpi_configs'");
                $schema_exists = ($result && $result->num_rows > 0);

                if (!$schema_exists) {
                    // Step 2: Import GLPI schema
                    $step = 2;

                    $sql_file = $project_root . '/install/mysql/glpi-empty.sql';
                    if (!file_exists($sql_file)) {
                        $error = 'GLPI schema file not found at: ' . htmlspecialchars($sql_file)
                               . '<br>Please verify all project files are uploaded.';
                    } else {
                        $sql_content = file_get_contents($sql_file);
                        if ($sql_content === false) {
                            $error = 'Cannot read GLPI schema file.';
                        } else {
                            // Import SQL with proper error handling
                            $import_result = importSQL($link, $sql_content, $db_name_escaped);

                            if ($import_result['errors'] > 0 && $import_result['success'] === 0) {
                                // Complete failure
                                $error = 'Schema import failed:<br>'
                                       . htmlspecialchars(implode('<br>', array_slice($import_result['messages'], -5)));
                            } else {
                                // Run CMMS taxonomy seed (non-critical)
                                $seed_errors = runSeedScript($db_host, $db_user, $db_pass, $db_name);

                                // Update URL base in glpi_configs
                                $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
                                $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
                                $uri    = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? ''), '/public');
                                $url_base = $scheme . '://' . $host . $uri;
                                $link->query("UPDATE glpi_configs SET value = " . $link->real_escape_string($url_base) . " WHERE name = 'url_base'");

                                // Write config file
                                $config_content = "<?php\n";
                                $config_content .= "class DB extends DBmysql {\n";
                                $config_content .= "   public \$dbhost     = " . var_export($db_host, true) . ";\n";
                                $config_content .= "   public \$dbuser     = " . var_export($db_user, true) . ";\n";
                                $config_content .= "   public \$dbpassword = " . var_export($db_pass, true) . ";\n";
                                $config_content .= "   public \$dbdefault  = " . var_export($db_name, true) . ";\n";
                                $config_content .= "   public \$use_utf8mb4 = true;\n";
                                $config_content .= "}\n";

                                // Ensure config directory exists
                                $config_dir = $project_root . '/config';
                                if (!is_dir($config_dir)) {
                                    mkdir($config_dir, 0755, true);
                                }

                                if (file_put_contents($config_file, $config_content) !== false) {
                                    $step = 3; // Success!
                                } else {
                                    $error = 'Schema imported successfully, but cannot write config file. '
                                           . 'Check that the <code>config/</code> folder is writable (chmod 755). '
                                           . 'You can manually create <code>config/config_db.php</code>.';
                                }
                            }
                        }
                    }
                } else {
                    // Schema already exists, just write config
                    $config_content = "<?php\n";
                    $config_content .= "class DB extends DBmysql {\n";
                    $config_content .= "   public \$dbhost     = " . var_export($db_host, true) . ";\n";
                    $config_content .= "   public \$dbuser     = " . var_export($db_user, true) . ";\n";
                    $config_content .= "   public \$dbpassword = " . var_export($db_pass, true) . ";\n";
                    $config_content .= "   public \$dbdefault  = " . var_export($db_name, true) . ";\n";
                    $config_content .= "   public \$use_utf8mb4 = true;\n";
                    $config_content .= "}\n";

                    $config_dir = $project_root . '/config';
                    if (!is_dir($config_dir)) {
                        mkdir($config_dir, 0755, true);
                    }

                    if (file_put_contents($config_file, $config_content) !== false) {
                        $step = 3;
                    } else {
                        $error = 'Cannot write config file. Check folder permissions.';
                    }
                }
            }
            $link->close();
        }
    }
}

/**
 * Import SQL content into the database.
 * Handles comments, multi-line statements, and large files.
 */
function importSQL(mysqli $link, string $sql_content, string $db_name): array
{
    $messages = [];
    $success  = 0;
    $skipped  = 0;
    $errors   = 0;

    // Remove BOM if present
    if (substr($sql_content, 0, 3) === "\xEF\xBB\xBF") {
        $sql_content = substr($sql_content, 3);
    }

    // Select the database
    $link->select_db($db_name);

    // Set a large max_allowed_packet for big inserts
    $link->query("SET SESSION sql_mode = 'NO_AUTO_VALUE_ON_ZERO'");

    // Split the SQL file into individual statements
    // We need to handle: comments, DELIMITER changes, multi-line strings
    $statements = splitSQL($sql_content);

    $total = count($statements);
    $i = 0;

    foreach ($statements as $statement) {
        $i++;
        $trimmed = trim($statement);

        // Skip empty statements
        if (empty($trimmed)) {
            continue;
        }

        // Execute
        $result = $link->query($trimmed);
        if ($result) {
            $success++;
        } else {
            $error_msg = $link->error;
            // Some errors are non-critical (table already exists, duplicate key, etc.)
            if (stripos($error_msg, 'already exists') !== false
                || stripos($error_msg, 'Duplicate entry') !== false
                || stripos($error_msg, 'Duplicate key') !== false
                || stripos($error_msg, 'Cannot add foreign key') !== false
            ) {
                $skipped++;
            } else {
                $errors++;
                if ($errors <= 10) {
                    $messages[] = "Statement $i error: $error_msg";
                }
            }
        }
    }

    if ($success === 0 && $errors > 0) {
        $messages[] = "All $errors statements failed. Check MySQL user privileges.";
    }

    return [
        'success'  => $success,
        'skipped'  => $skipped,
        'errors'   => $errors,
        'messages' => $messages,
    ];
}

/**
 * Split SQL content into individual executable statements.
 */
function splitSQL(string $sql_content): array
{
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

        // Check for string start
        if ($char === '\'' || $char === '"') {
            $in_string = true;
            $string_char = $char;
            $current .= $char;
            $i++;
            continue;
        }

        // Check for line comment --
        if ($char === '-' && $i + 1 < $len && $sql_content[$i + 1] === '-') {
            // Skip to end of line
            while ($i < $len && $sql_content[$i] !== "\n") {
                $i++;
            }
            continue;
        }

        // Check for block comment /* */
        if ($char === '/' && $i + 1 < $len && $sql_content[$i + 1] === '*') {
            $i += 2;
            while ($i < $len - 1) {
                if ($sql_content[$i] === '*' && $sql_content[$i + 1] === '/') {
                    $i += 2;
                    break;
                }
                $i++;
            }
            continue;
        }

        // Statement delimiter
        if ($char === ';') {
            $trimmed = trim($current);
            if (!empty($trimmed)) {
                $statements[] = $trimmed;
            }
            $current = '';
            $i++;
            continue;
        }

        $current .= $char;
        $i++;
    }

    // Final statement
    $trimmed = trim($current);
    if (!empty($trimmed)) {
        $statements[] = $trimmed;
    }

    return $statements;
}

/**
 * Run the CMMS taxonomy seed script.
 */
function runSeedScript(string $host, string $user, string $pass, string $dbname): array
{
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
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MedEquip CMMS — Setup</title>
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: system-ui, -apple-system, sans-serif;
            background: linear-gradient(135deg, #f0fdfa 0%, #e0f2f1 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        .card {
            background: white;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 4px 24px rgba(0,0,0,0.1);
            max-width: 550px;
            width: 100%;
        }
        .logo { text-align: center; margin-bottom: 30px; }
        .logo h1 { color: #0d9488; font-size: 28px; }
        .logo p { color: #6b7280; margin-top: 4px; }
        h2 { color: #1f2937; margin-bottom: 8px; font-size: 20px; }
        .subtitle { color: #6b7280; margin-bottom: 24px; font-size: 14px; }
        .field { margin-bottom: 16px; }
        label { display: block; font-weight: 600; color: #374151; margin-bottom: 6px; font-size: 14px; }
        input[type="text"], input[type="password"] {
            width: 100%; padding: 10px 14px; border: 1px solid #d1d5db;
            border-radius: 8px; font-size: 14px; transition: border 0.2s;
        }
        input:focus { outline: none; border-color: #0d9488; box-shadow: 0 0 0 3px rgba(13,148,136,0.1); }
        .hint { font-size: 12px; color: #9ca3af; margin-top: 4px; }
        button {
            width: 100%; padding: 12px; background: #0d9488; color: white;
            border: none; border-radius: 8px; font-size: 16px; font-weight: 600;
            cursor: pointer; margin-top: 8px; transition: background 0.2s;
        }
        button:hover { background: #0f766e; }
        button:disabled { background: #94a3b8; cursor: not-allowed; }
        .error {
            background: #fef2f2; border: 1px solid #fecaca; color: #dc2626;
            padding: 12px; border-radius: 8px; margin-bottom: 16px; font-size: 14px;
        }
        .steps { margin-top: 30px; padding-top: 20px; border-top: 1px solid #e5e7eb; }
        .steps h3 { color: #374151; font-size: 14px; margin-bottom: 10px; }
        .steps ol { font-size: 13px; color: #6b7280; padding-left: 20px; }
        .steps li { margin-bottom: 6px; }
        .steps code { background: #f3f4f6; padding: 2px 6px; border-radius: 4px; font-size: 12px; }
        .success { text-align: center; padding: 20px 0; }
        .success h2 { color: #065f46; font-size: 24px; margin-bottom: 10px; }
        .success .check { font-size: 60px; color: #10b981; }
        .success p { color: #6b7280; margin-bottom: 20px; }
        .success a {
            display: inline-block; padding: 12px 24px; background: #0d9488; color: white;
            text-decoration: none; border-radius: 8px; font-weight: 600;
        }
        .installing { text-align: center; padding: 40px 0; }
        .installing .spinner {
            width: 48px; height: 48px; border: 4px solid #e5e7eb;
            border-top: 4px solid #0d9488; border-radius: 50%;
            animation: spin 1s linear infinite; margin: 0 auto 20px;
        }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        .credentials { background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 16px; margin: 16px 0; text-align: left; }
        .credentials code { background: #e2e8f0; padding: 2px 6px; border-radius: 4px; font-size: 13px; }
        .warning { background: #fffbeb; border: 1px solid #fde68a; color: #92400e; padding: 12px; border-radius: 8px; margin-top: 16px; font-size: 13px; }
    </style>
</head>
<body>
    <div class="card">
        <div class="logo">
            <h1>⚕️ MedEquip CMMS</h1>
            <p>Medical Equipment Maintenance Management</p>
        </div>

        <?php if ($step === 3): ?>
            <!-- Step 3: Success! -->
            <div class="success">
                <div class="check">✅</div>
                <h2>Installation Complete!</h2>
                <p>MedEquip CMMS has been installed and configured successfully.</p>

                <div class="credentials">
                    <strong>Default Login:</strong><br>
                    Username: <code>glpi</code> &nbsp; Password: <code>glpi</code><br><br>
                    <strong>Note:</strong> Change the default password after first login!
                </div>

                <div style="margin-top: 20px;">
                    <a href="./index.php">→ Go to Login Page</a>
                </div>

                <div class="warning">
                    ⚠️ For security, delete <code>install_cmms_standalone.php</code> after you're done.
                </div>
            </div>

        <?php elseif ($step === 2): ?>
            <!-- Step 2: Installing (auto-refresh) -->
            <div class="installing">
                <div class="spinner"></div>
                <h2>Installing Database Schema...</h2>
                <p style="color: #6b7280;">This may take 30-60 seconds depending on your server.<br>Please don't close this page.</p>
            </div>

        <?php else: ?>
            <!-- Step 1: Form -->
            <h2>Database Setup</h2>
            <p class="subtitle">Enter your MySQL database credentials to connect.</p>

            <?php if ($error): ?>
                <div class="error"><?php echo $error; ?></div>
            <?php endif; ?>

            <form method="POST" id="setup-form">
                <div class="field">
                    <label>Database Host</label>
                    <input type="text" name="db_host" value="<?php echo htmlspecialchars($_POST['db_host'] ?? 'localhost'); ?>">
                    <div class="hint">Usually "localhost" on cPanel</div>
                </div>
                <div class="field">
                    <label>Database Name</label>
                    <input type="text" name="db_name" value="<?php echo htmlspecialchars($_POST['db_name'] ?? ''); ?>" required placeholder="e.g. youruser_cmms">
                    <div class="hint">Full name from cPanel → MySQL® Databases (e.g. yourusername_cmms)</div>
                </div>
                <div class="field">
                    <label>Database Username</label>
                    <input type="text" name="db_user" value="<?php echo htmlspecialchars($_POST['db_user'] ?? ''); ?>" required placeholder="e.g. youruser_cmms">
                    <div class="hint">Usually same as the database name on cPanel</div>
                </div>
                <div class="field">
                    <label>Database Password</label>
                    <input type="password" name="db_pass" required placeholder="Your MySQL password">
                </div>
                <button type="submit" id="submit-btn">Save Configuration & Install</button>
            </form>

            <div class="steps">
                <h3>Don't have a database yet?</h3>
                <ol>
                    <li>Open <strong>cPanel → MySQL® Databases</strong></li>
                    <li>Create database: enter <code>cmms</code> → click Create</li>
                    <li>Create user: enter <code>cmms</code> + password → click Create</li>
                    <li><strong>Add user to database</strong> → check ALL PRIVILEGES → click Make Changes</li>
                    <li>Copy the <strong>full database name</strong> (e.g. <code>yourusername_cmms</code>) into the form above</li>
                </ol>
            </div>
        <?php endif; ?>
    </div>

    <?php if ($step === 1): ?>
    <script>
        document.getElementById('setup-form').addEventListener('submit', function() {
            var btn = document.getElementById('submit-btn');
            btn.disabled = true;
            btn.textContent = 'Installing... Please wait...';
        });
    </script>
    <?php endif; ?>
</body>
</html>
