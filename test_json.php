<?php
require 'vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$project = \App\Models\Project::first();
if ($project) {
    $members = $project->members()
        ->where('status', 'active')
        ->with('user:id,name,email,avatar')
        ->get()
        ->map(fn($m) => [
            'id'        => $m->id,
            'user_id'   => $m->user_id,
            'name'      => $m->user?->name ?? 'Unknown',
            'role'      => $m->role,
        ])->values();
        
    echo json_encode(['members' => $members]);
}
