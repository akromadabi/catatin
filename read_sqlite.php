<?php
try {
    $db = new PDO('sqlite:C:\laragon\www\catatin\Logo\backup groovy');
    $res = $db->query("SELECT name FROM sqlite_master WHERE type='table'");
    $tables = $res->fetchAll(PDO::FETCH_ASSOC);
    print_r($tables);
    
    foreach ($tables as $t) {
        if ($t['name'] != 'android_metadata' && $t['name'] != 'sqlite_sequence') {
            echo "\n--- " . $t['name'] . " ---\n";
            $data = $db->query("SELECT * FROM " . $t['name'] . " LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
            print_r($data);
        }
    }
} catch(Exception $e) {
    echo $e->getMessage();
}
