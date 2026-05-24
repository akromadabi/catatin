<?php
require 'vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Http\Kernel::class);
$request = Illuminate\Http\Request::create('/api/projects/1/members', 'GET');
// Mock auth
$user = \App\Models\User::first();
$app->make('auth')->login($user);

$response = $kernel->handle($request);
echo $response->getContent();
