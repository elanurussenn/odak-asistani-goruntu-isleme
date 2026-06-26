
<?php
require __DIR__ . '/config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION["kullanici"]) || !isset($_SESSION["kullanici"]["id"])) {
    header("Location: giris.php");
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: yks.php");
    exit;
}

$kullanici_id = (int)$_SESSION["kullanici"]["id"];
$ders_id = (int)($_POST["ders_id"] ?? 0);
$calisma_suresi_dk = (int)($_POST["work_min"] ?? 45);
$mola_suresi_dk = (int)($_POST["break_min"] ?? 10);
$mod_id = (int)($_POST["mod_id"] ?? 0);

if ($ders_id <= 0) {
    header("Location: yks.php?e=" . urlencode("Ders seçmelisin."));
    exit;
}

try {
    $ders_sorgu = $pdo->prepare("SELECT ad FROM dersler WHERE id = ? LIMIT 1");
    $ders_sorgu->execute([$ders_id]);
    $ders_bilgisi = $ders_sorgu->fetch(PDO::FETCH_ASSOC);

    $ders_adi = $ders_bilgisi["ad"] ?? "Ders";

    $oturum_kaydet = $pdo->prepare("
        INSERT INTO odak_oturumlari
        (kullanici_id, mod_id, ders_id, sure_dk, mola_dk, baslangic, kullanici_sonlandirdi)
        VALUES (?, ?, ?, ?, 0, NOW(), 0)
    ");
     
    $oturum_kaydet->execute([
        $kullanici_id,
        $mod_id,
        $ders_id,
        $calisma_suresi_dk
    ]);

    $oturum_id = (int)$pdo->lastInsertId();

    $_SESSION["last_oturum_id"] = $oturum_id;
    $_SESSION["oturum_ders_adi"] = $ders_adi;
    $_SESSION["oturum_calisma_suresi_dk"] = $calisma_suresi_dk;
    $_SESSION["oturum_mola_suresi_dk"] = $mola_suresi_dk;
    $_SESSION["oturum_mod_id"] = $mod_id;

    $_SESSION["yks_verisi"] = [
        "ders_id" => $ders_id,
        "calisma_dakika" => $calisma_suresi_dk,
        "mola_dakika" => $mola_suresi_dk,
        "mod_id" => $mod_id
    ];

    header("Location: yks_oturum.php");
    exit;

} catch (PDOException $e) {
    die("Veritabanı hatası: " . $e->getMessage());
}