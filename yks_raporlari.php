<?php
require __DIR__ . "/config/db.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION["kullanici"]) || !isset($_SESSION["kullanici"]["id"])) {
    header("Location: giris.php");
    exit;
}

$kullanici_id = (int)$_SESSION["kullanici"]["id"];


$ozetSorgu = $pdo->prepare("
    SELECT 
        COUNT(*) AS toplam_oturum,
        COALESCE(AVG(odak_skoru), 0) AS ortalama_skor,
        COALESCE(MAX(odak_skoru), 0) AS en_yuksek_skor,
        COALESCE(SUM(sure_dk), 0) AS toplam_calisma_suresi,
        COALESCE(SUM(mola_dk), 0) AS toplam_mola_suresi
    FROM odak_oturumlari
    WHERE kullanici_id = ?
      AND mod_id = 1
      AND kullanici_sonlandirdi = 1
");
$ozetSorgu->execute([$kullanici_id]);
$ozet = $ozetSorgu->fetch(PDO::FETCH_ASSOC);


$sonOturumSorgu = $pdo->prepare("
    SELECT 
        id,
        odak_skoru
    FROM odak_oturumlari
    WHERE kullanici_id = ?
      AND mod_id = 1
      AND kullanici_sonlandirdi = 1
    ORDER BY id DESC
    LIMIT 7
");
$sonOturumSorgu->execute([$kullanici_id]);
$sonOturumlar = $sonOturumSorgu->fetchAll(PDO::FETCH_ASSOC);
$sonOturumlar = array_reverse($sonOturumlar);


$seviyeSorgu = $pdo->prepare("
    SELECT 
        odak_seviyesi,
        COUNT(*) AS adet
    FROM odak_oturumlari
    WHERE kullanici_id = ?
      AND mod_id = 1
      AND kullanici_sonlandirdi = 1
    GROUP BY odak_seviyesi
");
$seviyeSorgu->execute([$kullanici_id]);
$seviyeVerileri = $seviyeSorgu->fetchAll(PDO::FETCH_ASSOC);


$dersSorgu = $pdo->prepare("
    SELECT
        d.id,
        d.ad AS ders_adi,
        COUNT(oo.id) AS toplam_oturum,
        COALESCE(SUM(oo.sure_dk), 0) AS toplam_sure,
        COALESCE(AVG(oo.odak_skoru), 0) AS ortalama_skor,
        COALESCE(MAX(oo.odak_skoru), 0) AS en_yuksek_skor
    FROM odak_oturumlari oo
    INNER JOIN dersler d ON d.id = oo.ders_id
    WHERE oo.kullanici_id = ?
      AND oo.mod_id = 1
      AND oo.kullanici_sonlandirdi = 1
    GROUP BY d.id, d.ad
    ORDER BY ortalama_skor DESC, toplam_oturum DESC
");
$dersSorgu->execute([$kullanici_id]);
$dersRaporlari = $dersSorgu->fetchAll(PDO::FETCH_ASSOC);


$grafikEtiketleri = [];
$grafikSkorlari = [];

foreach ($sonOturumlar as $oturum) {
    $grafikEtiketleri[] = "Oturum #" . $oturum["id"];
    $grafikSkorlari[] = (int)($oturum["odak_skoru"] ?? 0);
}

$seviyeEtiketleri = [];
$seviyeAdetleri = [];

foreach ($seviyeVerileri as $satir) {
    $seviyeEtiketleri[] = $satir["odak_seviyesi"] ?: "Belirsiz";
    $seviyeAdetleri[] = (int)$satir["adet"];
}

$dersGrafikEtiketleri = [];
$dersGrafikSkorlari = [];

foreach ($dersRaporlari as $ders) {
    $dersGrafikEtiketleri[] = $ders["ders_adi"];
    $dersGrafikSkorlari[] = round((float)$ders["ortalama_skor"], 1);
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>YKS Raporları</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body{
            font-family: Arial, sans-serif;
            background:#f5f7fb;
            margin:0;
            padding:30px;
        }
        .wrap{
            max-width:1200px;
            margin:0 auto;
        }
        .ust{
            margin-bottom:24px;
        }
        h1{
            margin:0 0 10px 0;
        }
        .kartlar{
            display:grid;
            grid-template-columns:repeat(auto-fit, minmax(220px, 1fr));
            gap:16px;
            margin-bottom:24px;
        }
        .kart{
            background:#fff;
            border-radius:16px;
            padding:20px;
            box-shadow:0 8px 24px rgba(0,0,0,.06);
        }
        .etiket{
            font-size:14px;
            color:#6b7280;
            margin-bottom:8px;
        }
        .deger{
            font-size:28px;
            font-weight:bold;
            color:#111827;
        }
        .grafik-grid{
            display:grid;
            grid-template-columns:1fr 1fr;
            gap:20px;
            margin-bottom:24px;
        }
        .grafik-kart{
            background:#fff;
            border-radius:16px;
            padding:20px;
            box-shadow:0 8px 24px rgba(0,0,0,.06);
        }
        .ders-liste{
            margin-top:24px;
        }
        .ders-kart{
            background:#fff;
            border-radius:16px;
            padding:18px;
            box-shadow:0 8px 24px rgba(0,0,0,.06);
            margin-bottom:14px;
        }
        .ders-grid{
            display:grid;
            grid-template-columns:repeat(auto-fit, minmax(160px, 1fr));
            gap:14px;
            margin-top:14px;
        }
        .alan{
            background:#f8fafc;
            border-radius:12px;
            padding:14px;
        }
        .alan-etiket{
            font-size:13px;
            color:#6b7280;
            margin-bottom:6px;
        }
        .alan-deger{
            font-size:18px;
            font-weight:bold;
            color:#111827;
        }
        @media (max-width: 900px){
            .grafik-grid{
                grid-template-columns:1fr;
            }
        }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="ust">
            <h1>YKS Raporları</h1>
            <p>YKS modundaki çalışma performansınızın özet raporu.</p>
        </div>

        <div class="kartlar">
            <div class="kart">
                <div class="etiket">Toplam Oturum</div>
                <div class="deger"><?= (int)($ozet["toplam_oturum"] ?? 0) ?></div>
            </div>

            <div class="kart">
                <div class="etiket">Ortalama Odak Skoru</div>
                <div class="deger"><?= round((float)($ozet["ortalama_skor"] ?? 0), 1) ?></div>
            </div>

            <div class="kart">
                <div class="etiket">En Yüksek Skor</div>
                <div class="deger"><?= (int)($ozet["en_yuksek_skor"] ?? 0) ?></div>
            </div>

            <div class="kart">
                <div class="etiket">Toplam Çalışma Süresi</div>
                <div class="deger"><?= (int)($ozet["toplam_calisma_suresi"] ?? 0) ?> dk</div>
            </div>

            <div class="kart">
                <div class="etiket">Toplam Mola Süresi</div>
                <div class="deger"><?= (int)($ozet["toplam_mola_suresi"] ?? 0) ?> dk</div>
            </div>
        </div>

        <div class="grafik-grid">
            <div class="grafik-kart">
                <h3>Son 7 Oturum Odak Skoru</h3>
                <canvas id="skorGrafigi"></canvas>
            </div>

            <div class="grafik-kart">
                <h3>Odak Seviyesi Dağılımı</h3>
                <canvas id="seviyeGrafigi"></canvas>
            </div>
        </div>

        <div class="grafik-kart" style="margin-bottom:24px;">
            <h3>Ders Bazlı Ortalama Odak Skoru</h3>
            <canvas id="dersGrafigi"></canvas>
        </div>

        <div class="ders-liste">
            <div class="grafik-kart">
                <h3>Ders Bazlı Rapor</h3>

                <?php if (!empty($dersRaporlari)): ?>
                    <?php foreach ($dersRaporlari as $ders): ?>
                        <div class="ders-kart">
                            <h4><?= htmlspecialchars($ders["ders_adi"] ?? "Bilinmeyen Ders") ?></h4>

                            <div class="ders-grid">
                                <div class="alan">
                                    <div class="alan-etiket">Toplam Oturum</div>
                                    <div class="alan-deger"><?= (int)($ders["toplam_oturum"] ?? 0) ?></div>
                                </div>

                                <div class="alan">
                                    <div class="alan-etiket">Toplam Süre</div>
                                    <div class="alan-deger"><?= (int)($ders["toplam_sure"] ?? 0) ?> dk</div>
                                </div>

                                <div class="alan">
                                    <div class="alan-etiket">Ortalama Skor</div>
                                    <div class="alan-deger"><?= round((float)($ders["ortalama_skor"] ?? 0), 1) ?></div>
                                </div>

                                <div class="alan">
                                    <div class="alan-etiket">En Yüksek Skor</div>
                                    <div class="alan-deger"><?= (int)($ders["en_yuksek_skor"] ?? 0) ?></div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p>Henüz ders bazlı rapor oluşturacak veri bulunmuyor.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        const skorCtx = document.getElementById('skorGrafigi').getContext('2d');
        new Chart(skorCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode($grafikEtiketleri, JSON_UNESCAPED_UNICODE) ?>,
                datasets: [{
                    label: 'Odak Skoru',
                    data: <?= json_encode($grafikSkorlari) ?>,
                    backgroundColor: 'rgba(79, 70, 229, 0.7)',
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100
                    }
                }
            }
        });

        const seviyeCtx = document.getElementById('seviyeGrafigi').getContext('2d');
        new Chart(seviyeCtx, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode($seviyeEtiketleri, JSON_UNESCAPED_UNICODE) ?>,
                datasets: [{
                    data: <?= json_encode($seviyeAdetleri) ?>,
                    backgroundColor: [
                        '#4f46e5',
                        '#22c55e',
                        '#f59e0b',
                        '#ef4444',
                        '#94a3b8'
                    ]
                }]
            },
            options: {
                responsive: true
            }
        });

        const dersCtx = document.getElementById('dersGrafigi').getContext('2d');
        new Chart(dersCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode($dersGrafikEtiketleri, JSON_UNESCAPED_UNICODE) ?>,
                datasets: [{
                    label: 'Ortalama Odak Skoru',
                    data: <?= json_encode($dersGrafikSkorlari) ?>,
                    backgroundColor: 'rgba(34, 197, 94, 0.7)',
                    borderRadius: 8
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 100
                    }
                }
            }
        });
    </script>
</body>
</html>