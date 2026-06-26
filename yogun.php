<?php
require __DIR__ . "/config/db.php";
if (session_status() === PHP_SESSION_NONE) session_start();

$mesaj = $_GET["m"] ?? "";
$hata  = $_GET["e"] ?? "";

include __DIR__ . "/partials/header.php";
?>
<link rel="stylesheet" href="assets/css/yogun.css" />

<div class="site-arka">
  <main class="container shell py-4 py-lg-5">

    <?php if ($mesaj): ?>
      <div class="alert alert-success"><?= htmlspecialchars($mesaj) ?></div>
    <?php endif; ?>

    <?php if ($hata): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($hata) ?></div>
    <?php endif; ?>

    <section class="ym-hero mb-4">
      <div class="ym-hero__inner">
        <div class="ym-hero__left">
          <div class="badge rounded-pill text-bg-light border ym-badge">
            🔒 Yoğun Çalışma • Dikkat Dağıtıcıya Karşı
          </div>

          <h1 class="ym-title">Derin odak için kilitli oturum</h1>

          <p class="ym-sub">
            Sekme değişimleri ve gereksiz duraklatmalar takip edilir.
            Hedef: “tek iş, tek ekran”.
          </p>
          <div class="ym-rules">
            <div class="ym-rule"><i class="bi bi-eye me-2"></i> Sekme değişince uyarı + sayaç</div>
            <div class="ym-rule"><i class="bi bi-arrows-fullscreen me-2"></i>Sesli uyarılar</div>
            <div class="ym-rule"><i class="bi bi-pause-circle me-2"></i> Duraklatma hakkı: 1 kez (2 dk)</div>
          </div>
        </div>

        <div class="ym-hero__right">
          <img class="ym-hero__art" src="assets/img/blog-hero.png" alt="Odak illüstrasyon" />
        </div>
      </div>
    </section>

    <form class="ym-card" action="lib/kamera_kontrol.php" method="post" >
      <input type="hidden" name="mod_id" value="2">
  <input type="hidden" name="geldigi_sayfa" value="yogun">
      <input type="hidden" name="mod_id" value="2">
      <div class="ym-card__head">
        <h2 class="ym-card__title">Oturum ayarları</h2>
        
      </div>

      <div class="ym-section">
        <div class="ym-label">Çalışma süresi</div>
        <div class="ym-pills">
          <label class="pill">
            <input type="radio" name="work_min" value="45">
            <span>45 dk</span>
          </label>

          <label class="pill">
            <input type="radio" name="work_min" value="60" checked>
            <span>60 dk</span>
          </label>

          <label class="pill">
            <input type="radio" name="work_min" value="90">
            <span>90 dk</span>
          </label>

        </div>
      </div>

      

      <div class="ym-actions">
        <button class="btn btn-primary btn-lg px-4" type="submit">
          <i class="bi bi-lock-fill me-1"></i> İleri
        </button>

        <a class="btn btn-outline-secondary btn-lg" href="modlar.php">İptal</a>
      </div>
    </form>

  </main>
</div>

<?php include __DIR__ . "/partials/footer.php"; ?>