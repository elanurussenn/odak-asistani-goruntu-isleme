<?php
require __DIR__ . "/config/db.php";
require_once __DIR__ . "/skor_hesapla.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION["kullanici"]["id"])) {
    header("Location: giris.php");
    exit;
}

$kullanici_id = (int)$_SESSION["kullanici"]["id"];
$oturum_id    = (int)($_SESSION["last_oturum_id"] ?? 0);
$mola_dk      = (int)($_POST["mola_dk"] ?? 0);


$otomatik_bitti = (int)($_POST["otomatik_bitti"] ?? 0);
$kullanici_sonlandirdi = ($otomatik_bitti === 1) ? 0 : 1;

if ($oturum_id <= 0) {
    header("Location: yks_oturum.php?durum=hata");
    exit;
}


$kontrol = $pdo->prepare("
    SELECT id, mod_id
    FROM odak_oturumlari
    WHERE id = ?
      AND kullanici_id = ?
    LIMIT 1
");
$kontrol->execute([$oturum_id, $kullanici_id]);
$kontrolOturum = $kontrol->fetch(PDO::FETCH_ASSOC);

if (!$kontrolOturum) {
    header("Location: index.php");
    exit;
}


$guncelle = $pdo->prepare("
    UPDATE odak_oturumlari
    SET kullanici_sonlandirdi = ?,
        mola_dk = ?
    WHERE id = ?
      AND kullanici_id = ?
");
$guncelle->execute([
    $kullanici_sonlandirdi,
    $mola_dk,
    $oturum_id,
    $kullanici_id
]);


$sorgu = $pdo->prepare("
    SELECT 
        id,
        kullanici_id,
        ders_id,
        mod_id,
        sure_dk,
        mola_dk,
        baslangic,
        kullanici_sonlandirdi
    FROM odak_oturumlari
    WHERE id = ?
      AND kullanici_id = ?
    LIMIT 1
");
$sorgu->execute([$oturum_id, $kullanici_id]);
$oturum = $sorgu->fetch(PDO::FETCH_ASSOC);

if (!$oturum) {
    header("Location: yks_oturum.php?durum=hata");
    exit;
}


$metrikler = [
    "toplam_log"          => 0,
    "uzun_ayrilik"        => 0,
    "kameradan_ayrilma"   => 0,
    "esneme"              => 0,
    "goz_kapali"          => 0,
    "etrafa_bakma"        => 0,
    "etrafa_odaklanma"    => 0,
    "duraklatma_sayisi"   => 0,
    "sekme_degisim"       => 0
];

$logSayisiSorgu = $pdo->prepare("
    SELECT COUNT(*) AS toplam_log
    FROM odak_loglari
    WHERE oturum_id = ?
      AND kullanici_id = ?
");
$logSayisiSorgu->execute([$oturum_id, $kullanici_id]);
$logSayisi = $logSayisiSorgu->fetch(PDO::FETCH_ASSOC);

$metrikler["toplam_log"] = (int)($logSayisi["toplam_log"] ?? 0);

$logSorgu = $pdo->prepare("
    SELECT 
        event_type,
        MAX(CAST(new_value AS UNSIGNED)) AS toplam
    FROM odak_loglari
    WHERE oturum_id = ?
      AND kullanici_id = ?
    GROUP BY event_type
");
$logSorgu->execute([$oturum_id, $kullanici_id]);
$loglar = $logSorgu->fetchAll(PDO::FETCH_ASSOC);

foreach ($loglar as $log) {
    $eventType = trim((string)($log["event_type"] ?? ""));
    $toplam = (int)($log["toplam"] ?? 0);

    if ($eventType === "duraklatma") {
        $metrikler["duraklatma_sayisi"] = $toplam;
    } elseif ($eventType === "sekme_degisim") {
        $metrikler["sekme_degisim"] = $toplam;
    } elseif (array_key_exists($eventType, $metrikler)) {
        $metrikler[$eventType] = $toplam;
    }
}


$veriler = array_merge($oturum, $metrikler);

$sonuc = odakSkoruHesapla($veriler);

$odak_skoru    = (int)($sonuc["skor"] ?? 0);
$odak_seviyesi = $sonuc["seviye"] ?? "belirsiz";
$oneriler      = $sonuc["oneriler"] ?? [];


$skorGuncelle = $pdo->prepare("
    UPDATE odak_oturumlari
    SET odak_skoru = ?,
        odak_seviyesi = ?
    WHERE id = ?
      AND kullanici_id = ?
");
$skorGuncelle->execute([$odak_skoru, $odak_seviyesi, $oturum_id, $kullanici_id]);


$mod_id = (int)($oturum["mod_id"] ?? 0);

$_SESSION["oturum_sonlandi"]      = true;
$_SESSION["son_odak_skoru"]       = $odak_skoru;
$_SESSION["son_odak_seviyesi"]    = $odak_seviyesi;
$_SESSION["son_odak_onerileri"]   = $oneriler;
$_SESSION["son_mod_id"]           = $mod_id;
$_SESSION["son_oturum_id"]        = $oturum_id;


unset($_SESSION["last_oturum_id"]);


header("Location: oturum_bitti.php");
exit;