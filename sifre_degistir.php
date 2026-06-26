
<?php
require __DIR__ . "/lib/bildirim.php";
require __DIR__ . "/config/db.php";
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION["kullanici"])) {
  header("Location: giris.php");
  exit;
}

$id = (int)($_SESSION["kullanici"]["id"] ?? 0);
if ($id <= 0) {
  header("Location: profil.php?e=" . urlencode("Oturum hatası. Lütfen tekrar giriş yap."));
  exit;
}

$mevcut = (string)($_POST["mevcut"] ?? "");
$yeni   = (string)($_POST["yeni"] ?? "");
$yeni2  = (string)($_POST["yeni2"] ?? "");

if ($mevcut === "" || $yeni === "" || $yeni2 === "") {
  header("Location: profil.php?e=" . urlencode("Tüm alanları doldur."));
  exit;
}

if ($yeni !== $yeni2) {
  header("Location: profil.php?e=" . urlencode("Yeni şifreler eşleşmiyor."));
  exit;
}

if (strlen($yeni) < 8) {
  header("Location: profil.php?e=" . urlencode("Yeni şifre en az 8 karakter olmalı."));
  exit;
}

try {
  $s = $pdo->prepare("SELECT sifre_hash FROM kullanicilar WHERE id=? LIMIT 1");
  $s->execute([$id]);
  $u = $s->fetch(PDO::FETCH_ASSOC);

  if (!$u || empty($u["sifre_hash"])) {
    header("Location: profil.php?e=" . urlencode("Kullanıcı bulunamadı veya şifre kaydı yok."));
    exit;
  }

  if (!password_verify($mevcut, $u["sifre_hash"])) {
    header("Location: profil.php?e=" . urlencode("Mevcut şifre yanlış."));
    exit;
  }

  if (password_verify($yeni, $u["sifre_hash"])) {
    header("Location: profil.php?e=" . urlencode("Yeni şifre, eski şifre ile aynı olamaz."));
    exit;
  }

  $hash = password_hash($yeni, PASSWORD_DEFAULT);

  $q = $pdo->prepare("UPDATE kullanicilar SET sifre_hash=? WHERE id=?");
  $q->execute([$hash, $id]);
  bildirim_ekle($pdo, $id, "hesap", "sifre_guncellendi", "Şifreniz güncellendi", "Hesap şifreniz başarıyla değiştirildi.");
  header("Location: profil.php?m=" . urlencode("Şifren güncellendi ✅") . "&sound=confirm");
exit;

} catch (Exception $ex) {
  header("Location: profil.php?e=" . urlencode("Şifre güncellenirken hata oluştu."));
  exit;
}

