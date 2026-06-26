<?php
require_once __DIR__ . "/skor_hesapla.php";

$veriler = [
    "mod_id" => 2, // 1 = yks, 2 = yogun
    "sure_dk" => 45,
    "mola_dk" => 5,
    "uzun_ayrilik" => 2,
    "kameradan_ayrilma" => 1,
    "esneme" => 3,
    "goz_kapali" => 2,
    "etrafa_bakma" => 4,
    "etrafa_odaklanma" => 2,
    "duraklatma_sayisi" => 1,
    "sekme_degisim" => 1
];

$sonuc = odakSkoruHesapla($veriler);

echo "<pre>";
print_r($veriler);
print_r($sonuc);
echo "</pre>";