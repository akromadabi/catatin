<?php
require 'vendor/autoload.php';
$c = collect([]);
$c->prepend(['role' => 'owner']);
echo json_encode(['members' => $c]);
