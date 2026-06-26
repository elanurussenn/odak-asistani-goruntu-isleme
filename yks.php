<?php
require __DIR__ . "/config/db.php";
if (session_status() === PHP_SESSION_NONE) session_start();

$stmt = $pdo->prepare("SELECT id, kod, ad, seviye, ikon, renk
                       FROM dersler WHERE aktif=1
                       ORDER BY sira ASC, id ASC");
$stmt->execute();
$dersler = $stmt->fetchAll(PDO::FETCH_ASSOC);

$mesaj = $_GET["m"] ?? "";
$hata  = $_GET["e"] ?? "";

include __DIR__ . "/partials/header.php";
?>
<link rel="stylesheet" href="assets/css/yks.css" />

<div class="site-arka">
  <main class="container shell py-4 py-lg-5">

    <?php if ($mesaj): ?>
      <div class="alert alert-success"><?= htmlspecialchars($mesaj) ?></div>
    <?php endif; ?>
    <?php if ($hata): ?>
      <div class="alert alert-danger"><?= htmlspecialchars($hata) ?></div>
    <?php endif; ?>

    <section class="yks-hero mb-4">
      <div class="yks-hero__inner">
        <div class="yks-hero__left">
          <div class="badge rounded-pill text-bg-light border yks-badge">
            🎓 YKS Modu • Ders bazlı odak analizi
          </div>
          <h1 class="yks-title">Hangi dersle odak oturumu başlatmak istersin?</h1>
          <p class="yks-sub">Bir ders seç, süreyi ayarla ve odak oturumunu başlat.</p>
          <div class="yks-hint">
            <i class="bi bi-shield-check me-2"></i>
            Kamera ile odak ölçümü (opsiyon) — video kaydı yok, yalnızca skor.
          </div>
        </div>

        <div class="yks-hero__right">
          <img class="yks-hero__art" src="assets/img/blog-hero.png" alt="YKS illüstrasyon" />
        </div>
      </div>
    </section>

    <form class="yks-card" action="lib/kamera_kontrol.php" method="post">
      <input type="hidden" name="mod_id" value="1">
      <div class="yks-card__head">
        <h2 class="yks-card__title">1) Ders seç</h2>
        <div class="yks-card__meta">TYT / AYT dersleri</div>
      </div>

      <div class="row g-3">
        <?php foreach ($dersler as $d): ?>
          <?php
            $renk = $d["renk"] ?: "#2563eb";
            $ikon = $d["ikon"] ?: "bi-book";
            $id   = (int)$d["id"];
          ?>
          <div class="col-12 col-md-6 col-lg-3">
            <label class="ders-tile">
              <input class="ders-radio" type="radio" name="ders_id" value="<?= $id ?>" required>
              <div class="ders-tile__inner">
                <div class="ders-tile__top">
                  <div class="ders-icon" style="--c: <?= htmlspecialchars($renk) ?>;">
                    <i class="bi <?= htmlspecialchars($ikon) ?>"></i>
                  </div>
                  <span class="ders-badge"><?= htmlspecialchars($d["seviye"]) ?></span>
                </div>

                <div class="ders-name"><?= htmlspecialchars($d["ad"]) ?></div>
                <div class="ders-sub">Odak skoru ve süre bu derse yazılır</div>
              </div>
            </label>
          </div>
        <?php endforeach; ?>
      </div>

      <hr class="yks-sep">

      <div class="yks-card__head">
        <h2 class="yks-card__title">2) Süre seç</h2>
        <div class="yks-card__meta">Varsayılan: 45 dk</div>
      </div>

      <div class="yks-pills">
        <label class="pill">
          <input type="radio" name="work_min" value="25">
          <span>25 dk</span>
        </label>
        
        <label class="pill">
          <input type="radio" name="work_min" value="45" checked>
          <span>45 dk</span>
        </label>

        <label class="pill">
          <input type="radio" name="work_min" value="60">
          <span>60 dk</span>
        </label>

        <label class="pill">
          <input type="radio" name="work_min" value="1">
          <span>1 dk</span>
        </label>

      </div>

     

        
      </div>

      <div class="yks-actions">
        <button class="btn btn-primary btn-lg px-4" type="submit">
          <i class="bi bi-play-fill me-1"></i> İleri
        </button>
        <a class="btn btn-outline-secondary btn-lg" href="modlar.php">İptal</a>
      </div>
    </form>

  </main>
</div>

<?php include __DIR__ . "/partials/footer.php"; ?>