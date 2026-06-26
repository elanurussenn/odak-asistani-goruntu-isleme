<?php
require __DIR__ . "/config/db.php";

$hata = "";
$basari = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $ad = trim($_POST["ad"] ?? "");
  $soyad = trim($_POST["soyad"] ?? "");
  $eposta = trim($_POST["eposta"] ?? "");
  $sifre1 = $_POST["sifre1"] ?? "";
  $sifre2 = $_POST["sifre2"] ?? "";

  if ($ad === "" || $soyad === "" || $eposta === "" || $sifre1 === "" || $sifre2 === "") {
    $hata = "Lütfen tüm alanları doldurun.";
  } elseif (!filter_var($eposta, FILTER_VALIDATE_EMAIL)) {
    $hata = "E-posta formatı hatalı.";
  } elseif ($sifre1 !== $sifre2) {
    $hata = "Şifreler aynı değil.";
  } elseif (mb_strlen($sifre1) < 6) {
    $hata = "Şifre en az 6 karakter olmalı.";
  } else {
    $sorgu = $pdo->prepare("SELECT id FROM kullanicilar WHERE eposta = ?");
    $sorgu->execute([$eposta]);

    if ($sorgu->fetch()) {
      $hata = "Bu e-posta zaten kayıtlı.";
    } else {
      $sifre_hash = password_hash($sifre1, PASSWORD_DEFAULT);

      $ekle = $pdo->prepare("
        INSERT INTO kullanicilar (ad, soyad, eposta, sifre_hash, rol)
        VALUES (?, ?, ?, ?, 'uye')
      ");
      $ekle->execute([$ad, $soyad, $eposta, $sifre_hash]);

      $basari = "Kayıt başarılı! Şimdi giriş yapabilirsin.";
    }
  }
}
?>

<?php include __DIR__ . "/partials/header.php"; ?>

<style>
.site-arka{
  position: relative;
  min-height: calc(100vh - 110px);
  padding: 34px 16px 52px;
  overflow: hidden;
  isolation: isolate;
  background: linear-gradient(180deg,
    rgba(245,248,255,1) 0%,
    rgba(236,240,255,1) 55%,
    rgba(229,234,255,1) 100%
  );
}

.site-arka::before{
  content:"";
  position:absolute;
  inset:-120px;
  z-index:0;
  pointer-events:none;
  background:
    radial-gradient(1000px 520px at 50% 12%,
      rgba(120,120,255,0.32) 0%,
      rgba(120,120,255,0.00) 62%
    ),
    radial-gradient(1200px 620px at 50% 92%,
      rgba(90,70,255,0.22) 0%,
      rgba(90,70,255,0.00) 70%
    ),
    radial-gradient(900px 520px at 10% 55%,
      rgba(106,92,255,0.12) 0%,
      rgba(106,92,255,0.00) 68%
    );
  filter: blur(1px);
}

.site-arka::after{
  content:"";
  position:absolute;
  inset:0;
  z-index:0;
  pointer-events:none;
  opacity:.82;
  filter: blur(.10px);
  background:
    radial-gradient(circle, rgba(255,255,255,.92) 0 1.2px, transparent 2.3px) 0 0 / 62px 62px,
    radial-gradient(circle, rgba(255,255,255,.80) 0 1.2px, transparent 2.3px) 18px 12px / 78px 78px,
    radial-gradient(circle, rgba(255,255,255,.70) 0 1.1px, transparent 2.2px) 36px 28px / 96px 96px,
    radial-gradient(circle, rgba(255,255,255,.62) 0 1.1px, transparent 2.2px) 54px 44px / 120px 120px,
    radial-gradient(circle, rgba(255,255,255,.30) 0 4.5px, transparent 5.6px) 10px 20px / 100px 100px,
    radial-gradient(circle, rgba(255,255,255,.26) 0 5.5px, transparent 6.6px) 44px 62px / 130px 130px,
    radial-gradient(circle, rgba(255,255,255,.24) 0 6.5px, transparent 7.6px) 90px 30px / 160px 160px,
    radial-gradient(circle, rgba(255,255,255,.22) 0 5.5px, transparent 6.6px) 130px 110px / 190px 190px,
    radial-gradient(circle, rgba(255,255,255,.20) 0 8px, transparent 9px) 30px 120px / 210px 210px,
    radial-gradient(circle, rgba(255,255,255,.18) 0 9.5px, transparent 10.5px) 140px 70px / 250px 250px,
    radial-gradient(circle, rgba(255,255,255,.16) 0 11px, transparent 12px) 220px 160px / 290px 290px,
    radial-gradient(circle, rgba(255,255,255,.15) 0 10px, transparent 11px) 280px 40px / 320px 320px,
    radial-gradient(circle, rgba(255,255,255,.12) 0 18px, transparent 19px) 60px 40px / 360px 360px,
    radial-gradient(circle, rgba(255,255,255,.10) 0 22px, transparent 23px) 260px 200px / 460px 460px;
}

.site-arka > *{
  position: relative;
  z-index: 1;
}

.uye-kapsayici{
  max-width: 620px;
  margin: 0 auto;
}

.uye-kart{
  background: rgba(255,255,255,.74);
  border: 1px solid rgba(223,229,255,.95);
  border-radius: 28px;
  backdrop-filter: blur(10px);
  box-shadow: 0 20px 48px rgba(74, 89, 163, 0.10);
  padding: 28px 26px 24px;
}

.uye-badge{
  display: inline-flex;
  align-items: center;
  gap: 8px;
  padding: 9px 14px;
  border-radius: 999px;
  background: rgba(113,120,255,.10);
  color: #4b5bb3;
  font-weight: 800;
  font-size: 13px;
  margin-bottom: 14px;
}

.uye-baslik{
  margin: 0;
  font-size: 52px;
  line-height: 1;
  font-weight: 950;
  letter-spacing: -.03em;
  color: #3d4c89;
}

.uye-aciklama{
  margin: 10px 0 22px;
  color: #7080b6;
  font-size: 15px;
  font-weight: 700;
  line-height: 1.5;
}

.uye-kart .alert{
  border-radius: 16px;
  padding: 14px 16px;
  font-weight: 700;
  margin-bottom: 18px;
}

.uye-kart .alert-danger{
  background: rgba(255, 82, 82, .08);
  color: #c93d3d;
  border: 1px solid rgba(255, 82, 82, .18);
}

.uye-kart .alert-success{
  background: rgba(39, 174, 96, .10);
  color: #1f8a4c;
  border: 1px solid rgba(39, 174, 96, .18);
}

.uye-kart .alert-success a{
  color: inherit;
  font-weight: 900;
}

.uye-kart .form-label{
  display: block;
  margin-bottom: 8px;
  color: #46558f;
  font-weight: 800;
  font-size: 14px;
}

.uye-kart .form-control{
  width: 100%;
  height: 52px;
  border: none;
  outline: none;
  border-radius: 16px;
  background: #ffffff;
  box-shadow:
    inset 0 0 0 1px rgba(129,144,210,.22),
    0 8px 20px rgba(85,102,173,.06);
  padding: 0 16px;
  font-size: 14px;
  font-weight: 700;
  color: #34406f;
  transition: .22s ease;
}

.uye-kart .form-control:focus{
  box-shadow:
    inset 0 0 0 2px rgba(62,101,255,.42),
    0 10px 24px rgba(62,101,255,.10);
}

.uye-kart .form-control::placeholder{
  color: #a0aac8;
  font-weight: 600;
}

.uye-kart .btn-primary{
  width: 100%;
  height: 52px;
  border: none;
  border-radius: 16px;
   background: linear-gradient(135deg, #334675 0%,  #6a5cff 100%);
  color: #fff;
  font-size: 17px;
  font-weight: 900;
  box-shadow: 0 14px 26px rgba(47,107,255,.22);
  transition: .22s ease;
}

.uye-kart .btn-primary:hover{
  transform: translateY(-2px);
  box-shadow: 0 18px 30px rgba(47,107,255,.28);
}

.uye-kart .row.g-3{
  --bs-gutter-x: 14px;
  --bs-gutter-y: 12px;
}

@media (max-width: 768px){
  .site-arka{
    padding: 24px 10px 42px;
  }

  .uye-kapsayici{
    max-width: 560px;
  }

  .uye-kart{
    padding: 22px 16px 20px;
    border-radius: 22px;
  }

  .uye-baslik{
    font-size: 40px;
  }

  .uye-aciklama{
    font-size: 14px;
    margin-bottom: 18px;
  }

  .uye-kart .form-control,
  .uye-kart .btn-primary{
    height: 48px;
    border-radius: 14px;
  }
}
</style>

<div class="site-arka">
  <div class="uye-kapsayici">
    <div class="uye-kart">
      <div class="uye-badge">✨ Odak Asistanı</div>
      <h1 class="uye-baslik">Üye Ol</h1>
      <p class="uye-aciklama">Hesabını oluştur ve odak yolculuğuna hemen başla.</p>

      <?php if ($hata): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($hata) ?></div>
      <?php endif; ?>

      <?php if ($basari): ?>
        <div class="alert alert-success">
          <?= htmlspecialchars($basari) ?> <a href="giris.php">Giriş Yap</a>
        </div>
      <?php endif; ?>

      <form method="post">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Ad</label>
            <input class="form-control" name="ad" value="<?= htmlspecialchars($_POST["ad"] ?? "") ?>" required>
          </div>

          <div class="col-md-6">
            <label class="form-label">Soyad</label>
            <input class="form-control" name="soyad" value="<?= htmlspecialchars($_POST["soyad"] ?? "") ?>" required>
          </div>

          <div class="col-12">
            <label class="form-label">E-posta</label>
            <input class="form-control" name="eposta" type="email" value="<?= htmlspecialchars($_POST["eposta"] ?? "") ?>" required>
          </div>

          <div class="col-md-6">
            <label class="form-label">Şifre</label>
            <input class="form-control" name="sifre1" type="password" required>
          </div>

          <div class="col-md-6">
            <label class="form-label">Şifre (tekrar)</label>
            <input class="form-control" name="sifre2" type="password" required>
          </div>

          <div class="col-12 mt-2">
            <button class="btn btn-primary" type="submit">Kayıt Ol</button>
          </div>
        </div>
      </form>
    </div>
  </div>
</div>

<?php include __DIR__ . "/partials/footer.php"; ?>