<?php
require __DIR__ . '/config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['kullanici']) || !isset($_SESSION['kullanici']['id'])) {
    header("Location: giris.php");
    exit;
}

$kullanici_id = (int) $_SESSION['kullanici']['id'];

$sql = "SELECT id, baslik, aciklama, oncelik, tamamlandi, olusturulma_tarihi
        FROM planlayici_gorevler
        WHERE kullanici_id = :kullanici_id
        ORDER BY tamamlandi ASC, id DESC";
$stmt = $pdo->prepare($sql);
$stmt->execute([':kullanici_id' => $kullanici_id]);
$gorevler = $stmt->fetchAll(PDO::FETCH_ASSOC);

$toplam = count($gorevler);
$tamamlanan = 0;

foreach ($gorevler as $gorev) {
    if ((int)$gorev['tamamlandi'] === 1) {
        $tamamlanan++;
    }
}

$bekleyen = $toplam - $tamamlanan;

include __DIR__ . '/partials/header.php';
?>

<link rel="stylesheet" href="assets/css/plan.css">
<div class="arka_site">
<div class="planlayici-wrap">
    <div class="planlayici-hero">
        <div>
            <h1>Planlayıcı ✴</h1>
            <p>Görevlerini ekle, tamamla ve takip et.</p>
        </div>
    </div>

    <div class="istatistik-kutulari">
        <div class="istatistik-kart">
            <span>Toplam Görev</span>
            <strong><?php echo $toplam; ?></strong>
        </div>

        <div class="istatistik-kart">
            <span>Tamamlanan</span>
            <strong><?php echo $tamamlanan; ?></strong>
        </div>

        <div class="istatistik-kart">
            <span>Bekleyen</span>
            <strong><?php echo $bekleyen; ?></strong>
        </div>
    </div>

    <div class="planlayici-grid">
        <div class="sol-kolon">
            <div class="kart gorev-ekle-kart">
                <h2>Yeni Görev Ekle</h2>

                <form method="POST" action="/odak_projesi/gorev_ekle.php" class="gorev-form">
                    <div class="form-grup">
                        <label for="baslik">Görev Başlığı</label>
                        <input type="text" name="baslik" id="baslik" required>
                    </div>

                    <div class="form-grup">
                        <label for="aciklama">Açıklama</label>
                        <textarea name="aciklama" id="aciklama" rows="4"></textarea>
                    </div>

                    <div class="form-grup">
                        <label for="oncelik">Öncelik</label>
                        <select name="oncelik" id="oncelik">
                            <option value="Dusuk">Düşük</option>
                            <option value="Orta" selected>Orta</option>
                            <option value="Yuksek">Yüksek</option>
                        </select>
                    </div>

                    <button type="submit" class="btn-ekle">Görev Ekle</button>
                </form>
            </div>
        </div>

        <div class="sag-kolon">
            <div class="kart gorevler-kart">
                <h2>Görevlerim</h2>

                <?php if (empty($gorevler)): ?>
                    <div class="bos-alan">Henüz görev eklenmemiş.</div>
                <?php else: ?>
                    <div class="gorev-listesi">
                        <?php foreach ($gorevler as $gorev): ?>
                            <?php
                                $tamamlandiMi = (int)$gorev['tamamlandi'] === 1;
                                $oncelikClass = 'oncelik-orta';

                                if ($gorev['oncelik'] === 'Dusuk') {
                                    $oncelikClass = 'oncelik-dusuk';
                                } elseif ($gorev['oncelik'] === 'Yuksek') {
                                    $oncelikClass = 'oncelik-yuksek';
                                }
                            ?>

                            <div class="gorev-item <?php echo $tamamlandiMi ? 'tamamlandi' : ''; ?>">
                                <div class="gorev-bilgi">
                                    <h3><?php echo htmlspecialchars($gorev['baslik']); ?></h3>

                                    <?php if (!empty($gorev['aciklama'])): ?>
                                        <p><?php echo nl2br(htmlspecialchars($gorev['aciklama'])); ?></p>
                                    <?php endif; ?>

                                    <div class="gorev-meta">
                                        <span class="oncelik-badge <?php echo $oncelikClass; ?>">
                                            <?php echo htmlspecialchars($gorev['oncelik']); ?>
                                        </span>

                                        <span class="tarih">
                                            <?php echo date('d.m.Y H:i', strtotime($gorev['olusturulma_tarihi'])); ?>
                                        </span>
                                    </div>
                                </div>

                                <div class="gorev-islemler">
                                    <form method="POST" action="/odak_projesi/gorev_durum.php">
                                        <input type="hidden" name="id" value="<?php echo (int)$gorev['id']; ?>">
                                        <button type="submit" class="btn-durum">
                                            <?php echo $tamamlandiMi ? 'Geri Al' : 'Tamamla'; ?>
                                        </button>
                                    </form>

                                    <form method="POST" action="/odak_projesi/gorev_sil.php" onsubmit="return confirm('Bu görevi silmek istiyor musun?');">
                                        <input type="hidden" name="id" value="<?php echo (int)$gorev['id']; ?>">
                                        <button type="submit" class="btn-sil">Sil</button>
                                    </form>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
</div>

<?php include __DIR__ . '/partials/footer.php'; ?>