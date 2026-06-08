<?php
/**
 * Payrello Docker Auto-Setup Script
 *
 * - First boot:  imports schema, creates admin user, writes pp-config.php
 * - Subsequent boots: just rewrites pp-config.php from env vars (DB already populated)
 */

$db_host   = getenv('DB_HOST')   ?: 'db';
$db_port   = getenv('DB_PORT')   ?: '3306';
$db_name   = getenv('DB_NAME')   ?: 'payrello';
$db_user   = getenv('DB_USER')   ?: 'payrello';
$db_pass   = getenv('DB_PASS')   ?: 'payrello_secret';
$db_prefix = getenv('DB_PREFIX') ?: 'pp_';

$admin_name     = getenv('ADMIN_NAME')     ?: 'Administrator';
$admin_email    = getenv('ADMIN_EMAIL')    ?: 'admin@example.com';
$admin_username = getenv('ADMIN_USERNAME') ?: 'admin';
$admin_password = getenv('ADMIN_PASSWORD') ?: 'Admin@1234';

$config_file = '/var/www/html/pp-config.php';

// Connect to database
try {
    $dsn = "mysql:host=$db_host;port=$db_port;dbname=$db_name;charset=utf8mb4";
    $pdo = new PDO($dsn, $db_user, $db_pass, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
} catch (Throwable $e) {
    echo "[setup] ERROR: Cannot connect to database: " . $e->getMessage() . "\n";
    exit(1);
}

// Check if schema already exists by probing the admin table
$stmt = $pdo->query("SHOW TABLES LIKE '{$db_prefix}admin'");
$already_installed = ($stmt->rowCount() > 0);

if ($already_installed) {
    echo "[setup] Database already installed — writing pp-config.php and exiting.\n";
    write_config($config_file, $db_host, $db_user, $db_pass, $db_name, $db_prefix);
    exit(0);
}

echo "[setup] Starting Payrello first-run setup...\n";

// Import schema
echo "[setup] Importing database schema...\n";
$sql = file_get_contents('/var/www/html/pp-content/pp-install/db.sql');
if ($sql === false) {
    echo "[setup] ERROR: Cannot read db.sql\n";
    exit(1);
}

if ($db_prefix !== 'pp_') {
    $sql = str_replace('`pp_', '`' . $db_prefix, $sql);
}

// db.sql manages its own START TRANSACTION / COMMIT — don't wrap in PDO transaction.
// Use a separate connection with ATTR_AUTOCOMMIT=true so the SQL's own transaction
// control is respected without interference from PDO's transaction tracking.
try {
    $import_pdo = new PDO("mysql:host=$db_host;port=$db_port;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    // Temporarily disable strict mode to match the original installer behaviour
    $import_pdo->exec("SET SESSION sql_mode=''");

    foreach (array_filter(array_map('trim', explode(";\n", $sql))) as $query) {
        if ($query !== '') {
            $import_pdo->exec($query);
        }
    }
    echo "[setup] Schema imported successfully.\n";
} catch (Throwable $e) {
    echo "[setup] ERROR importing schema: " . $e->getMessage() . "\n";
    exit(1);
}
unset($import_pdo);

// Generate numeric IDs (mirrors generateItemID from pp-functions.php)
function generateItemID(int $length = 10): string {
    $id = '';
    for ($i = 0; $i < $length; $i++) {
        $id .= mt_rand(0, 9);
    }
    return $id;
}

$a_id     = generateItemID();
$brand_id = generateItemID();
$now      = date('Y-m-d H:i:s');

// Full permission schema (mirrors permissionSchema() from pp-functions.php)
$permission_schema = json_encode([
    'resources' => [
        'customers'         => ['create' => true, 'edit' => true, 'delete' => true],
        'transaction'       => ['edit' => true, 'delete' => true, 'approve' => true, 'cancel' => true, 'refund' => true, 'send_ipn' => true],
        'invoice'           => ['create' => true, 'edit' => true, 'delete' => true],
        'payment_link'      => ['create' => true, 'edit' => true, 'delete' => true],
        'gateways'          => ['create' => true, 'edit' => true, 'delete' => true],
        'addons'            => ['create' => true, 'edit' => true, 'delete' => true],
        'brand_settings'    => ['view' => true, 'edit' => true],
        'api_settings'      => ['view' => true, 'create' => true, 'edit' => true, 'delete' => true],
        'theme_settings'    => ['view' => true, 'edit' => true],
        'faq_settings'      => ['view' => true, 'create' => true, 'edit' => true, 'delete' => true],
        'currency_settings' => ['view' => true, 'sync_rate' => true, 'import' => true, 'edit' => true],
        'sms_data'          => ['create' => true, 'edit' => true, 'delete' => true],
        'device'            => ['connect' => true, 'delete' => true, 'balance_verification_for' => true],
        'brands'            => ['create' => true, 'edit' => true, 'delete' => true],
        'staff'             => ['create' => true, 'edit' => true, 'delete' => true, 'assign_brand_to' => true, 'edit_permission' => true, 'view_permission_list' => true, 'delete_permission_of' => true],
        'domains'           => ['whitelist' => true, 'edit' => true, 'delete' => true],
        'system_settings'   => ['manage_general' => true, 'manage_cron' => true, 'manage_update' => true, 'manage_import' => true],
    ],
    'pages' => [
        'dashboard'        => true,
        'reports'          => true,
        'customers'        => true,
        'transaction'      => true,
        'invoice'          => true,
        'payment_link'     => true,
        'gateways'         => true,
        'addons'           => true,
        'brand_settings'   => true,
        'sms_data'         => true,
        'device'           => true,
        'brands'           => true,
        'staff_management' => true,
        'domains'          => true,
        'system_settings'  => true,
    ],
]);

// Insert admin user, brand, permissions, default currency
echo "[setup] Creating admin account ($admin_username)...\n";
$hashed = password_hash($admin_password, PASSWORD_BCRYPT);

try {
    $pdo->prepare("INSERT INTO `{$db_prefix}admin` (a_id, full_name, username, email, password, temp_password, created_date, updated_date) VALUES (?,?,?,?,?,?,?,?)")
        ->execute([$a_id, $admin_name, $admin_username, $admin_email, $hashed, $hashed, $now, $now]);

    $pdo->prepare("INSERT INTO `{$db_prefix}brands` (brand_id, created_date, updated_date) VALUES (?,?,?)")
        ->execute([$brand_id, $now, $now]);

    $pdo->prepare("INSERT INTO `{$db_prefix}permission` (brand_id, a_id, permission, created_date, updated_date) VALUES (?,?,?,?,?)")
        ->execute([$brand_id, $a_id, $permission_schema, $now, $now]);

    $pdo->prepare("INSERT INTO `{$db_prefix}currency` (brand_id, code, symbol, created_date, updated_date) VALUES (?,?,?,?,?)")
        ->execute([$brand_id, 'BDT', '৳', $now, $now]);

    echo "[setup] Admin account created successfully.\n";
} catch (Throwable $e) {
    echo "[setup] ERROR creating admin: " . $e->getMessage() . "\n";
    exit(1);
}

write_config($config_file, $db_host, $db_user, $db_pass, $db_name, $db_prefix);

$app_port = getenv('APP_PORT') ?: '8080';
echo "[setup] ✓ Setup complete!\n";
echo "[setup]   Login URL   : http://localhost:$app_port/login\n";
echo "[setup]   Dashboard   : http://localhost:$app_port/admin/dashboard\n";
echo "[setup]   Username    : $admin_username\n";
echo "[setup]   Password    : $admin_password\n";

// ─────────────────────────────────────────────────────────────────────────────

function write_config(string $path, string $host, string $user, string $pass, string $name, string $prefix): void {
    $content = <<<PHP
<?php
    \$db_host   = '$host';
    \$db_user   = '$user';
    \$db_pass   = '$pass';
    \$db_name   = '$name';
    \$db_prefix = '$prefix';
?>
PHP;
    if (file_put_contents($path, $content) === false) {
        echo "[setup] ERROR: Cannot write $path\n";
        exit(1);
    }
    @chown($path, 'www-data');
    echo "[setup] pp-config.php written.\n";
}
