<?php
require __DIR__ . "/config/db.php";
require __DIR__ . "/lib/bildirim.php";

if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION["kullanici"])) {
  header("Location: giris.php");
  exit;
}

$id = (int)($_SESSION["kullanici"]["id"] ?? 0);
if ($id <= 0) {
  header("Location: profil.php?e=" . urlencode("Oturum hatası."));
  exit;
}

$allowed = [
  "assets/img/profile-1.png",
  "assets/img/profile-2.png",
  "assets/img/profile-3.png",
  "assets/img/profile-4.png",
  "assets/img/profile-5.png",
  "assets/img/profile-6.png",
];

try {
  if (isset($_GET["sil"])) {
    $q = $pdo->prepare("UPDATE kullanicilar SET profil_resmi=NULL WHERE id=?");
    $q->execute([$id]);

    $_SESSION["kullanici"]["profil"] = "";

    header("Location: profil.php?m=" . urlencode("Avatar kaldırıldı ✅"));
    exit;
  }

  $avatar = trim((string)($_POST["avatar"] ?? ""));
  $avatar = preg_replace('#/+#', '/', $avatar); 

  if ($avatar === "" || !in_array($avatar, $allowed, true)) {
    header("Location: profil.php?e=" . urlencode("Geçersiz avatar seçimi."));
    exit;
  }

  $q = $pdo->prepare("UPDATE kullanicilar SET profil_resmi=? WHERE id=?");
  $q->execute([$avatar, $id]);

  $_SESSION["kullanici"]["profil"] = $avatar;
   bildirim_ekle($pdo, $id, "hesap", "avatar_guncellendi", "Avatar güncellendi", "Profil avatarınız başarıyla değiştirildi.");
  header("Location: profil.php?m=" . urlencode("Avatar güncellendi ✅") . "&sound=confirm");
exit;
  
} catch (Exception $ex) {
  header("Location: profil.php?e=" . urlencode("Avatar güncellenirken hata oluştu."));
  exit;
}