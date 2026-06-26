<?php
require __DIR__ . "/config/db.php";
if (session_status() === PHP_SESSION_NONE) session_start();

if (!isset($_SESSION["kullanici"])) {
  header("Location: giris.php");
  exit;
}

$k = $_SESSION["kullanici"];
$mesaj = $_GET["m"] ?? "";
$hata  = $_GET["e"] ?? "";

$s = $pdo->prepare("SELECT id, ad, soyad, eposta, profil_resmi, rol FROM kullanicilar WHERE id=? LIMIT 1");
$s->execute([$k["id"]]);
$u = $s->fetch(PDO::FETCH_ASSOC);

if ($u) {
  $_SESSION["kullanici"]["ad"]     = $u["ad"];
  $_SESSION["kullanici"]["soyad"]  = $u["soyad"];
  $_SESSION["kullanici"]["eposta"] = $u["eposta"];
  $_SESSION["kullanici"]["profil"] = $u["profil_resmi"] ?? "";
  $_SESSION["kullanici"]["rol"]    = $u["rol"] ?? ($_SESSION["kullanici"]["rol"] ?? "kullanici");
  $k = $_SESSION["kullanici"];
}

$profilSrc = trim((string)($k["profil"] ?? ""));
$profilSrc = str_replace(['..','\\'], ['', '/'], $profilSrc);
$profilSrc = preg_replace('#/+#', '/', $profilSrc); // // -> /
$profilSrc = ltrim($profilSrc, "/");

include __DIR__ . "/partials/header.php";
?>

<link rel="stylesheet" href="assets/css/profil.css" />
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<div class="site-arka">
  <section class="container profil-wrap">

    <div class="profil-hero">
      <div class="profil-hero-inner">
        <div class="profil-avatar">
          <?php if ($profilSrc): ?>
            <img src="<?= htmlspecialchars($profilSrc) ?>" alt="Profil">
          <?php else: ?>
            <div class="profil-avatar-fallback">
              <i class="bi bi-person-fill"></i>
            </div>
          <?php endif; ?>
        </div>

        <div class="profil-meta">
          <h1 class="profil-name"><?= htmlspecialchars($k["ad"]." ".$k["soyad"]." ".'🫧') ?></h1>
          <div class="profil-mail"><?= htmlspecialchars($k["eposta"]) ?></div>
        </div>
      </div>
    </div>

    <?php if ($mesaj): ?>
      <div class="alert alert-success mt-3"><?= htmlspecialchars($mesaj) ?></div>
    <?php endif; ?>

    <?php if ($hata): ?>
      <div class="alert alert-danger mt-3"><?= htmlspecialchars($hata) ?></div>
    <?php endif; ?>

    <div class="row g-4 mt-2">

      <div class="col-lg-3">
        <div class="card profil-card shadow-sm">
          <div class="card-body p-3">
            <div class="profil-side-title">Ayarlar</div>

            <div class="nav flex-column nav-pills gap-2" id="profilTabs" role="tablist">
              <button class="nav-link active" data-bs-toggle="pill" data-bs-target="#tab-bilgiler" type="button" role="tab">
                <i class="bi bi-person-lines-fill me-2"></i> Bilgiler
              </button>
              <button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-sifre" type="button" role="tab">
                <i class="bi bi-shield-lock me-2"></i> Şifre
              </button>
              
              <button class="nav-link" data-bs-toggle="pill" data-bs-target="#tab-avatar" type="button" role="tab">
                <i class="bi bi-stars me-2"></i> Avatar Seç
              </button>

              <hr class="my-3">

              <a class="btn btn-outline-danger w-100 rounded-4" href="cikis.php">
                <i class="bi bi-box-arrow-right me-2"></i> Çıkış Yap
              </a>
            </div>
          </div>
        </div>
      </div>

      <div class="col-lg-9">
        <div class="card profil-card shadow-sm">
          <div class="card-body p-4">

            <div class="tab-content">

              <div class="tab-pane fade show active" id="tab-bilgiler" role="tabpanel">
                <div class="profil-section-title">Profil Bilgileri</div>
                <div class="profil-section-sub">Ad/soyadını güncelle. (E-posta kilitli)</div>

                <form action="profil_guncelle.php" method="post" class="mt-3">
                  <div class="row g-3">
                    <div class="col-md-6">
                      <label class="form-label">Ad</label>
                      <input class="form-control profil-input" name="ad" value="<?= htmlspecialchars($k["ad"]) ?>" required>
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Soyad</label>
                      <input class="form-control profil-input" name="soyad" value="<?= htmlspecialchars($k["soyad"]) ?>" required>
                    </div>
                    <div class="col-12">
                      <label class="form-label">E-posta</label>
                      <input class="form-control profil-input" value="<?= htmlspecialchars($k["eposta"]) ?>" disabled>
                    </div>

                    <div class="col-12 mt-2">
                      <button class="btn btn-primary profil-btn w-100 rounded-4" type="submit" style='background-color:#842d9c;'>
                        Bilgilerimi Kaydet
                      </button>
                    </div>
                  </div>
                </form>
              </div>

              <div class="tab-pane fade" id="tab-sifre" role="tabpanel">
                <div class="profil-section-title">Şifre Değiştir</div>
                <div class="profil-section-sub">Mevcut şifreni doğrula, sonra yeni şifreyi kaydet.</div>

                <form action="sifre_degistir.php" method="post" class="mt-3">
                  <div class="row g-3">
                    <div class="col-md-6">
                      <label class="form-label">Mevcut Şifre</label>
                      <input class="form-control profil-input" type="password" name="mevcut" required>
                    </div>
                    <div class="col-md-6">
                      <label class="form-label">Yeni Şifre</label>
                      <input class="form-control profil-input" type="password" name="yeni" minlength="8" required>
                    </div>
                    <div class="col-12">
                      <label class="form-label">Yeni Şifre (Tekrar)</label>
                      <input class="form-control profil-input" type="password" name="yeni2" minlength="8" required>
                      <div class="form-text">En az 8 karakter önerilir.</div>
                    </div>

                    <div class="col-12 mt-2">
                      <button class="btn btn-primary profil-btn w-100 rounded-4" type="submit">
                        Şifremi Güncelle
                      </button>
                    </div>
                  </div>
                </form>
              </div>

        

              <div class="tab-pane fade" id="tab-avatar" role="tabpanel">
                <div class="profil-section-title">Avatar Seç</div>
                <div class="profil-section-sub">Hazır avatarlarından birini seç ve kaydet.</div>

                <?php
                  $avatarlar = [
                    "assets/img/profile-1.png",
                    "assets/img/profile-2.png",
                    "assets/img/profile-3.png",
                    "assets/img/profile-4.png",
                    "assets/img/profile-5.png",
                    "assets/img/profile-6.png",
                  ];

                  $secili = preg_replace('#/+#', '/', $profilSrc ?: "");
                ?>

                <form action="profil_avatar_sec.php" method="post" class="mt-3">
                  <div class="row g-3">
                    <?php foreach ($avatarlar as $i => $a): ?>
                      <?php $aNorm = preg_replace('#/+#', '/', $a); ?>
                      <div class="col-6 col-md-4">
                        <label class="avatar-card">
                          <input
                            class="avatar-radio"
                            type="radio"
                            name="avatar"
                            value="<?= htmlspecialchars($aNorm) ?>"
                            <?= ($secili === $aNorm) ? "checked" : "" ?>
                            required
                          >
                          <span class="avatar-img">
                            <img src="<?= htmlspecialchars($aNorm) ?>" alt="Avatar <?= $i+1 ?>">
                          </span>
                          <span class="avatar-check">
                            <i class="bi bi-check2-circle"></i>
                            Seç
                          </span>
                        </label>
                      </div>
                    <?php endforeach; ?>
                  </div>

                  <div class="mt-3 d-flex gap-2">
                    <button class="btn btn-primary profil-btn rounded-4 px-4" type="submit">
                      Avatarı Kaydet
                    </button>

                    <a class="btn btn-outline-secondary rounded-4 px-4" href="profil_avatar_sec.php?sil=1">
                      Avatarı Kaldır
                    </a>
                  </div>
                </form>
              </div>

            </div>

          </div>
        </div>
      </div>

    </div>
  </section>
  <?php $sound = $_GET["sound"] ?? ""; ?>

<script>
document.addEventListener("DOMContentLoaded", function () {
  const soundType = <?= json_encode($sound) ?>;

  if (soundType === "confirm") {
    const audio = new Audio("assets/audio/confirm.mp3");
    audio.volume = 0.4;
    audio.play().catch(function(error) {
      console.log("Ses çalınamadı:", error);
    });
  }
});
</script>
</div>

