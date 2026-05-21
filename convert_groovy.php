<?php
try {
    $db = new PDO('sqlite:C:\laragon\www\catatin\Logo\backup groovy');
    
    // Get Categories
    $res = $db->query("SELECT * FROM CATEGORY");
    $categories = [];
    while ($row = $res->fetch(PDO::FETCH_ASSOC)) {
        $categories[$row['_id']] = $row;
    }
    
    // Get Transactions
    $res = $db->query("SELECT * FROM TRANSACT");
    
    $json = [];
    while ($row = $res->fetch(PDO::FETCH_ASSOC)) {
        // type: 0 usually expense, 1 usually income in many of these apps. Let's assume.
        $catId = $row['id_category'];
        $catName = isset($categories[$catId]) ? $categories[$catId]['NAME'] : 'Lain-lain';
        $typeInt = $row['type'];
        
        $jenis = ($typeInt == 1) ? 'pemasukan' : 'pengeluaran';
        
        // Date is in milliseconds
        $dateStr = date('Y-m-d', $row['date'] / 1000);
        
        $json[] = [
            'tanggal' => $dateStr,
            'nominal' => (float)$row['amount'],
            'jenis' => $jenis,
            'keterangan' => $row['desc'],
            'kategori' => $catName
        ];
    }
    
    $jsonStr = json_encode($json, JSON_PRETTY_PRINT);
    file_put_contents('C:\laragon\www\catatin\Logo\backup_groovy_converted.json', $jsonStr);
    echo "Konversi berhasil! Disimpan di C:\laragon\www\catatin\Logo\backup_groovy_converted.json\n";
    echo "Total data: " . count($json);
} catch(Exception $e) {
    echo $e->getMessage();
}
