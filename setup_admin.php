<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

// Ensure personal_access_tokens table exists (create if not)
if (!Schema::hasTable('personal_access_tokens')) {
    echo "Tabel personal_access_tokens belum ada, membuat secara manual...\n";
    DB::statement("
        CREATE TABLE `personal_access_tokens` (
            `id` bigint unsigned NOT NULL AUTO_INCREMENT,
            `tokenable_type` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
            `tokenable_id` bigint unsigned NOT NULL,
            `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
            `token` varchar(64) COLLATE utf8mb4_unicode_ci NOT NULL,
            `abilities` text COLLATE utf8mb4_unicode_ci,
            `last_used_at` timestamp NULL DEFAULT NULL,
            `expires_at` timestamp NULL DEFAULT NULL,
            `created_at` timestamp NULL DEFAULT NULL,
            `updated_at` timestamp NULL DEFAULT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `personal_access_tokens_token_unique` (`token`),
            KEY `personal_access_tokens_tokenable_type_tokenable_id_index` (`tokenable_type`,`tokenable_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
}

$u = User::where('username', 'admin')->first();
if (!$u) {
    $u = new User();
    $u->username = 'admin';
}
$u->password_hash = Hash::make('admin123');
$u->save();

echo "BERHASIL!\n";
echo "Username: " . $u->username . "\n";
echo "Password: admin123\n";
