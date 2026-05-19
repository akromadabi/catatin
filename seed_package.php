<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$p = App\Models\Package::create([
    'name' => 'Free',
    'price' => 0,
    'transaction_limit' => 100,
    'description' => 'Gratis untuk UMKM pemula'
]);

App\Models\User::whereNull('package_id')->update(['package_id' => $p->id]);
echo "Done\n";
