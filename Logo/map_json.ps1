$inputFile = "c:\laragon\www\catatin\logo\backup_groovy_converted.json"
$outputFile = "c:\laragon\www\catatin\logo\backup_groovy_mapped.json"

$data = Get-Content -Raw -Path $inputFile | ConvertFrom-Json

$categoryMap = @{
    'kebutuhan rumah tangga' = 'Kebutuhan Rumah Tangga'
    'belanja mingguan' = 'Kebutuhan Rumah Tangga'
    'kontrakan' = 'Kebutuhan Rumah Tangga'
    'bpjs' = 'Kebutuhan Rumah Tangga'
    
    'kebutuhan mazin' = 'Kebutuhan Anak'
    'kebutuhan debay' = 'Kebutuhan Anak'
    'skincare bocil' = 'Kebutuhan Anak'
    
    'self award bojo' = 'Self Reward Bojo'
    'self award bocil' = 'Self Reward Bocil'
    
    'operasional groovy' = 'Operasional Bisnis'
    'gaji host groovy' = 'Operasional Bisnis'
    'gaji host lunara' = 'Operasional Bisnis'
    'aset groovy' = 'Operasional Bisnis'
    'affiliate bocil' = 'Operasional Bisnis'
    'kiddio' = 'Operasional Bisnis'
    
    'sodaqoh' = 'Sosial & Sedekah'
    'zakat' = 'Sosial & Sedekah'
    'beri hadiah' = 'Sosial & Sedekah'
    'lebaran 2026' = 'Sosial & Sedekah'
}

foreach ($item in $data) {
    if ($item.jenis -eq 'pengeluaran') {
        $originalCatName = $item.kategori.Trim()
        $lowerCat = $originalCatName.ToLower()
        
        if ($categoryMap.ContainsKey($lowerCat)) {
            $newCatName = $categoryMap[$lowerCat]
            
            if ($originalCatName.ToLower() -ne $newCatName.ToLower()) {
                if ([string]::IsNullOrWhiteSpace($item.keterangan)) {
                    $item.keterangan = "($originalCatName)"
                } else {
                    $item.keterangan = "$($item.keterangan) ($originalCatName)"
                }
            }
            $item.kategori = $newCatName
        }
    }
}

$data | ConvertTo-Json -Depth 10 | Set-Content -Path $outputFile
Write-Host "Done! File saved to $outputFile"
