<?php
require 'vendor/autoload.php';
$c = collect([['role'=>'owner']]);
echo $c->contains('role', 'owner') ? 'YES' : 'NO';
