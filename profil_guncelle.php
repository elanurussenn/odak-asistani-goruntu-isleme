<?php
require __DIR__ . "/config/db.php";
require __DIR__ . "/lib/bildirim.php";

if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION["kullanici"])) {
  header("Location: giris.php");
  exit;
}

$ad    = trim($_POST["ad"] ?? "");
$soyad = trim($_POST["soyad"] ?? "");

if ($ad === "" || $soyad === "") {
  header("Location: profil.php?e=" . urlencode("Ad ve soyad boş olamaz."));
  exit;
}

$id = (int)($_SESSION["kullanici"]["id"] ?? 0);
if ($id <= 0) {
  header("Location: profil.php?e=" . urlencode("Oturum hatası. Lütfen tekrar giriş yap."));
  exit;
}

try {
  $q = $pdo->prepare("UPDATE kullanicilar SET ad=?, soyad=? WHERE id=?");
  $q->execute([$ad, $soyad, $id]);

  $_SESSION["kullanici"]["ad"] = $ad;
  $_SESSION["kullanici"]["soyad"] = $soyad;
  bildirim_ekle($pdo, $id, "hesap", "profil_guncellendi", "Profiliniz güncellendi", "Ad/Soyad bilgileriniz başarıyla güncellendi.");
  header("Location: profil.php?m=" . urlencode("Bilgilerin güncellendi ✅") . "&sound=confirm");
exit;

} catch (Exception $ex) {
  header("Location: profil.php?e=" . urlencode("Güncelleme sırasında hata oluştu."));
  exit;
}