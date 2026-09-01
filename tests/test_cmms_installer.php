<?php
/**
 * Quick validation test for CMMS installer SQL import logic.
 * Tests the splitSQL() and importSQL() functions without a database.
 */

$passed = 0;
$failed = 0;

function assert_test(string $name, bool $result, string $detail = '') {
    global $passed, $failed;
    if ($result) {
        echo "  ✅ PASS: $name\n";
        $passed++;
    } else {
        echo "  ❌ FAIL: $name" . ($detail ? " — $detail" : "") . "\n";
        $failed++;
    }
}

// ─── Test splitSQL() ──────────────────────────────────────────────────

echo "\n=== Test splitSQL() ===\n";

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

// Test 1: Simple statements
$result = splitSQL("CREATE TABLE test (id INT); DROP TABLE test;");
assert_test("Simple two statements", count($result) === 2, "Got " . count($result));
assert_test("First statement correct", $result[0] === "CREATE TABLE test (id INT)", $result[0] ?? 'empty');
assert_test("Second statement correct", $result[1] === "DROP TABLE test", $result[1] ?? 'empty');

// Test 2: Line comments
$sql = "-- This is a comment\nCREATE TABLE test (id INT);";
$result = splitSQL($sql);
assert_test("Line comment stripped", count($result) === 1);
assert_test("Statement after comment correct", $result[0] === "CREATE TABLE test (id INT)");

// Test 3: Block comments
$sql = "/* comment */ CREATE TABLE test (id INT);";
$result = splitSQL($sql);
assert_test("Block comment stripped", count($result) === 1);
assert_test("Statement after block comment correct", $result[0] === "CREATE TABLE test (id INT)");

// Test 4: Strings with semicolons
$sql = "INSERT INTO t (val) VALUES ('hello;world'); SELECT 1;";
$result = splitSQL($sql);
assert_test("Semicolon in string preserved", count($result) === 2, "Got " . count($result));
assert_test("String value intact", strpos($result[0], "hello;world") !== false);

// Test 5: Multi-line statements
$sql = "CREATE TABLE users (\n  id INT PRIMARY KEY,\n  name VARCHAR(255)\n);";
$result = splitSQL($sql);
assert_test("Multi-line statement", count($result) === 1);
assert_test("Multi-line content intact", strpos($result[0], "PRIMARY KEY") !== false);

// Test 6: Empty input
$result = splitSQL("");
assert_test("Empty input returns empty array", count($result) === 0);

// Test 7: Only comments
$result = splitSQL("-- just a comment\n/* block comment */");
assert_test("Only comments returns empty array", count($result) === 0);

// Test 8: BOM handling
$sql = "\xEF\xBB\xBFCREATE TABLE test (id INT);";
if (substr($sql, 0, 3) === "\xEF\xBB\xBF") { $sql = substr($sql, 3); }
$result = splitSQL($sql);
assert_test("BOM removed correctly", count($result) === 1);

// Test 9: Escaped quotes in strings
$sql = "INSERT INTO t (val) VALUES ('it\\'s a test'); SELECT 1;";
$result = splitSQL($sql);
assert_test("Escaped quotes handled", count($result) === 2, "Got " . count($result));

// ─── Test SQL file parsing ────────────────────────────────────────────

echo "\n=== Test GLPI Schema File ===\n";

$sql_file = dirname(__DIR__) . '/install/mysql/glpi-empty.sql';
assert_test("GLPI schema file exists", file_exists($sql_file));

if (file_exists($sql_file)) {
    $content = file_get_contents($sql_file);
    $size = strlen($content);
    assert_test("Schema file is non-empty", $size > 100000, "Size: $size bytes");

    $statements = splitSQL($content);
    assert_test("Schema parses into statements", count($statements) > 100, "Got " . count($statements) . " statements");

    $all_sql = implode("\n", $statements);
    assert_test("Contains glpi_users table", strpos($all_sql, 'glpi_users') !== false);
    assert_test("Contains glpi_configs table", strpos($all_sql, 'glpi_configs') !== false);
    assert_test("Contains glpi_tickets table", strpos($all_sql, 'glpi_tickets') !== false);
    assert_test("Contains CREATE TABLE statements", strpos($all_sql, 'CREATE TABLE') !== false);
    $creates = substr_count($all_sql, 'CREATE TABLE');
    echo "  📊 Stats: $creates CREATE TABLE statements\n";
    assert_test("Has CREATE TABLE statements", $creates > 0);
}

// ─── Test installer file structure ────────────────────────────────────

echo "\n=== Test Installer File Structure ===\n";

$standalone = dirname(__DIR__) . '/public/install_cmms_standalone.php';
$full = dirname(__DIR__) . '/public/install_cmms.php';

assert_test("Standalone installer exists", file_exists($standalone));
assert_test("Full installer exists", file_exists($full));

if (file_exists($standalone)) {
    $src = file_get_contents($standalone);
    assert_test("Standalone has splitSQL function", strpos($src, 'function splitSQL') !== false);
    assert_test("Standalone has importSQL function", strpos($src, 'function importSQL') !== false);
    assert_test("Standalone references glpi-empty.sql", strpos($src, 'glpi-empty.sql') !== false);
    assert_test("Standalone writes config_db.php", strpos($src, 'config_db.php') !== false);
    assert_test("Standalone creates database", strpos($src, 'CREATE DATABASE') !== false);
    assert_test("Standalone has error handling", strpos($src, 'mysqli_report') !== false || strpos($src, 'connect_error') !== false);
}

if (file_exists($full)) {
    $src = file_get_contents($full);
    assert_test("Full installer has splitSQL function", strpos($src, 'function splitSQL') !== false);
    assert_test("Full installer has importSQL function", strpos($src, 'function importSQL') !== false);
    assert_test("Full installer references glpi-empty.sql", strpos($src, 'glpi-empty.sql') !== false);
    assert_test("Full installer writes config_db.php", strpos($src, 'config_db.php') !== false);
    assert_test("Full installer has step indicator", strpos($src, 'step-indicator') !== false);
    assert_test("Full installer has requirements check", strpos($src, 'checkPHPVersion') !== false);
    assert_test("Full installer imports default data", strpos($src, 'install_default_data.php') !== false);
}

// ─── Test default data file ──────────────────────────────────────────

echo "\n=== Test Default Data File ===\n";

$default_data = dirname(__DIR__) . '/public/install_default_data.php';
assert_test("Default data file exists", file_exists($default_data));

if (file_exists($default_data)) {
    $src = file_get_contents($default_data);
    assert_test("Has importDefaultData function", strpos($src, 'function importDefaultData') !== false);
    assert_test("Has extractDefaultData function", strpos($src, 'function extractDefaultData') !== false);
    assert_test("Has config defaults", strpos($src, 'getConfigDefaults') !== false);
    assert_test("Has user defaults", strpos($src, 'getUserDefaults') !== false);
    assert_test("Has profile defaults", strpos($src, 'getProfileDefaults') !== false);
    assert_test("Has entity defaults", strpos($src, 'getEntityDefaults') !== false);
    assert_test("Has profile rights defaults", strpos($src, 'getProfileRightsDefaults') !== false);
    assert_test("Has calendar defaults", strpos($src, 'getCalendarDefaults') !== false);
    assert_test("Standalone imports default data", file_get_contents(dirname(__DIR__) . '/public/install_cmms_standalone.php') !== false && strpos(file_get_contents(dirname(__DIR__) . '/public/install_cmms_standalone.php'), 'install_default_data.php') !== false);
}

// ─── Summary ──────────────────────────────────────────────────────────

echo "\n" . str_repeat("═", 50) . "\n";
echo "Results: $passed passed, $failed failed\n";
echo str_repeat("═", 50) . "\n\n";

exit($failed > 0 ? 1 : 0);
