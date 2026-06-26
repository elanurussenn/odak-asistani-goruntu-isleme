<?php
require __DIR__ . "/config/db.php";
require_once __DIR__ . "/skor_hesapla.php";

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION["kullanici"]) || !isset($_SESSION["kullanici"]["id"])) {
    header("Location: giris.php");
    exit;
}

$kullanici_id = (int)$_SESSION["kullanici"]["id"];


$gecmisOturumIstek = isset($_GET["oturum_id"]) && (int)$_GET["oturum_id"] > 0;

if (!$gecmisOturumIstek && empty($_SESSION["oturum_sonlandi"])) {
    header("Location: index.php");
    exit;
}

$oturum_id = (int)($_GET["oturum_id"] ?? ($_SESSION["son_oturum_id"] ?? 0));

if ($oturum_id <= 0) {
    header("Location: istatistik.php");
    exit;
}


$oturumDetaySorgu = $pdo->prepare("
    SELECT 
        id,
        kullanici_id,
        ders_id,
        mod_id,
        sure_dk,
        mola_dk,
        baslangic,
        kullanici_sonlandirdi,
        odak_skoru,
        odak_seviyesi
    FROM odak_oturumlari
    WHERE id = ?
      AND kullanici_id = ?
    LIMIT 1
");
$oturumDetaySorgu->execute([$oturum_id, $kullanici_id]);
$oturumDetay = $oturumDetaySorgu->fetch(PDO::FETCH_ASSOC);

if (!$oturumDetay) {
    header("Location: istatistik.php");
    exit;
}


$metrikler = [
    "toplam_log"          => 0,
    "uzun_ayrilik"        => 0,
    "kameradan_ayrilma"   => 0,
    "esneme"              => 0,
    "goz_kapali"          => 0,
    "etrafa_bakma"        => 0,
    "etrafa_odaklanma"    => 0,
    "duraklatma_sayisi"   => 0,
    "sekme_degisim"       => 0
];

$logSayisiSorgu = $pdo->prepare("
    SELECT COUNT(*) AS toplam_log
    FROM odak_loglari
    WHERE oturum_id = ?
      AND kullanici_id = ?
");
$logSayisiSorgu->execute([$oturum_id, $kullanici_id]);
$logSayisi = $logSayisiSorgu->fetch(PDO::FETCH_ASSOC);

$metrikler["toplam_log"] = (int)($logSayisi["toplam_log"] ?? 0);

$logMetrikSorgu = $pdo->prepare("
    SELECT 
        event_type,
        MAX(CAST(new_value AS UNSIGNED)) AS toplam
    FROM odak_loglari
    WHERE oturum_id = ?
      AND kullanici_id = ?
    GROUP BY event_type
");
$logMetrikSorgu->execute([$oturum_id, $kullanici_id]);
$logMetrikleri = $logMetrikSorgu->fetchAll(PDO::FETCH_ASSOC);

foreach ($logMetrikleri as $log) {
    $eventType = trim((string)($log["event_type"] ?? ""));
    $toplam = (int)($log["toplam"] ?? 0);

    if ($eventType === "duraklatma") {
        $metrikler["duraklatma_sayisi"] = $toplam;
    } elseif ($eventType === "sekme_degisim") {
        $metrikler["sekme_degisim"] = $toplam;
    } elseif (array_key_exists($eventType, $metrikler)) {
        $metrikler[$eventType] = $toplam;
    }
}


$veriler = array_merge($oturumDetay, $metrikler);
$hesaplananSonuc = odakSkoruHesapla($veriler);

$mod_id = (int)($oturumDetay["mod_id"] ?? 0);

$sessiondakiSonOturum = (
    !$gecmisOturumIstek &&
    !empty($_SESSION["oturum_sonlandi"]) &&
    (int)($_SESSION["son_oturum_id"] ?? 0) === $oturum_id
);

if ($sessiondakiSonOturum) {
    $skor = (int)($_SESSION["son_odak_skoru"] ?? ($hesaplananSonuc["skor"] ?? 0));
    $seviye = $_SESSION["son_odak_seviyesi"] ?? ($hesaplananSonuc["seviye"] ?? "Belirsiz");
    $oneriler = $_SESSION["son_odak_onerileri"] ?? [];

    if (empty($oneriler)) {
        $oneriler = $hesaplananSonuc["oneriler"] ?? [];
    }
} else {
    $skor = ($oturumDetay["odak_skoru"] !== null)
        ? (int)$oturumDetay["odak_skoru"]
        : (int)($hesaplananSonuc["skor"] ?? 0);

    $seviye = !empty($oturumDetay["odak_seviyesi"])
        ? $oturumDetay["odak_seviyesi"]
        : ($hesaplananSonuc["seviye"] ?? "Belirsiz");

    $oneriler = $hesaplananSonuc["oneriler"] ?? [];
}

$zamanAraliklari = [];
$enYogunAralik = null;
$enCokIhlalTuru = null;
$enUzunSeri = 0;
$seriBaslangic = null;
$seriBitis = null;
$loglar = [];

function ihlalTurunuGuzellestir(string $tur): string
{
    $map = [
        "uzun_ayrilik" => "Uzun Ayrılık",
        "kameradan_ayrilma" => "Kameradan Ayrılma",
        "esneme" => "Esneme",
        "goz_kapali" => "Göz Kapalı",
        "etrafa_bakma" => "Etrafa Bakma",
        "etrafa_odaklanma" => "Etrafa Odaklanma",
        "duraklatma" => "Duraklatma",
        "sekme_degisim" => "Sekme Değişimi",
        "kipirdanma" => "Kıpırdanma",
        "odak_disi" => "Odak Dışı",
        "Bilinmeyen" => "Bilinmeyen"
    ];

    return $map[$tur] ?? ucwords(str_replace("_", " ", $tur));
}

if ($oturum_id > 0) {
    $oturumSorgu = $pdo->prepare("
        SELECT baslangic
        FROM odak_oturumlari
        WHERE id = ?
          AND kullanici_id = ?
        LIMIT 1
    ");
    $oturumSorgu->execute([$oturum_id, $kullanici_id]);
    $oturum = $oturumSorgu->fetch(PDO::FETCH_ASSOC);

    $oturumBaslangic = $oturum["baslangic"] ?? null;

    $logSorgu = $pdo->prepare("
        SELECT created_at, event_type
        FROM odak_loglari
        WHERE oturum_id = ?
          AND kullanici_id = ?
        ORDER BY created_at ASC
    ");
    $logSorgu->execute([$oturum_id, $kullanici_id]);
    $loglar = $logSorgu->fetchAll(PDO::FETCH_ASSOC);

    if ($oturumBaslangic && !empty($loglar)) {
        $baslangicTs = strtotime($oturumBaslangic);

        $aralikSayac = [];

        foreach ($loglar as $log) {
            $logTs = strtotime($log["created_at"] ?? "");

            if ($logTs === false || $baslangicTs === false) {
                continue;
            }

            $farkDakika = floor(($logTs - $baslangicTs) / 60);

            if ($farkDakika < 0) {
                $farkDakika = 0;
            }

            $blokBaslangic = floor($farkDakika / 5) * 5;
            $blokBitis = $blokBaslangic + 4;
            $etiket = $blokBaslangic . "-" . $blokBitis . ". dk";

            if (!isset($aralikSayac[$etiket])) {
                $aralikSayac[$etiket] = 0;
            }

            $aralikSayac[$etiket]++;
        }

        arsort($aralikSayac);
        $zamanAraliklari = $aralikSayac;

        if (!empty($zamanAraliklari)) {
            $ilkAnahtar = array_key_first($zamanAraliklari);

            $enYogunAralik = [
                "aralik" => $ilkAnahtar,
                "adet" => $zamanAraliklari[$ilkAnahtar]
            ];
        }

        $turSayac = [];

        foreach ($loglar as $log) {
            $tur = trim((string)($log["event_type"] ?? ""));

            if ($tur === "") {
                $tur = "Bilinmeyen";
            }

            if (!isset($turSayac[$tur])) {
                $turSayac[$tur] = 0;
            }

            $turSayac[$tur]++;
        }

        arsort($turSayac);

        if (!empty($turSayac)) {
            $ilkTur = array_key_first($turSayac);

            $enCokIhlalTuru = [
                "tur" => $ilkTur,
                "adet" => $turSayac[$ilkTur]
            ];
        }

        $anlikSeri = 0;
        $anlikSeriBaslangic = null;
        $oncekiTs = null;

        foreach ($loglar as $log) {
            $logTs = strtotime($log["created_at"] ?? "");

            if ($logTs === false) {
                continue;
            }

            if ($oncekiTs === null) {
                $anlikSeri = 1;
                $anlikSeriBaslangic = $logTs;
            } else {
                $farkSn = $logTs - $oncekiTs;

                if ($farkSn <= 60) {
                    $anlikSeri++;
                } else {
                    if ($anlikSeri > $enUzunSeri) {
                        $enUzunSeri = $anlikSeri;
                        $seriBaslangic = $anlikSeriBaslangic;
                        $seriBitis = $oncekiTs;
                    }

                    $anlikSeri = 1;
                    $anlikSeriBaslangic = $logTs;
                }
            }

            $oncekiTs = $logTs;
        }

        if ($anlikSeri > $enUzunSeri) {
            $enUzunSeri = $anlikSeri;
            $seriBaslangic = $anlikSeriBaslangic;
            $seriBitis = $oncekiTs;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <title>Odak Asistanı | Analiz</title>
    <link rel="alternate icon" href="assets/img/favicon.svg">

    <style>
        * {
            box-sizing: border-box;
        }

        html,
        body {
            margin: 0;
            padding: 0;
            min-height: 100%;
        }

        body {
            font-family: Arial, sans-serif;
            color: #1f2340;
            background: #eef2ff;
        }

        .site-arka {
            position: relative;
            min-height: 100vh;
            padding: 22px 18px;
            overflow: hidden;

            display: flex;
            align-items: flex-start;
            justify-content: center;

            background:
                linear-gradient(180deg,
                    #f8faff 0%,
                    #eef2ff 45%,
                    #e7eaff 100%
                );
        }

        .site-arka::before {
            content: "";
            position: absolute;
            inset: -120px;
            z-index: 0;
            pointer-events: none;

            background:
                radial-gradient(760px 380px at 12% 10%,
                    rgba(124, 92, 255, .20) 0%,
                    rgba(124, 92, 255, 0) 64%
                ),
                radial-gradient(820px 420px at 90% 8%,
                    rgba(80, 150, 255, .16) 0%,
                    rgba(80, 150, 255, 0) 68%
                ),
                radial-gradient(920px 500px at 50% 105%,
                    rgba(91, 70, 255, .20) 0%,
                    rgba(91, 70, 255, 0) 70%
                );
            filter: blur(2px);
        }

        .site-arka::after {
            content: "";
            position: absolute;
            inset: 0;
            z-index: 0;
            pointer-events: none;
            opacity: .72;

            background:
                radial-gradient(circle, rgba(255,255,255,.90) 0 1px, transparent 2.2px) 0 0 / 52px 52px,
                radial-gradient(circle, rgba(255,255,255,.62) 0 1px, transparent 2.2px) 22px 16px / 78px 78px,
                radial-gradient(circle, rgba(255,255,255,.34) 0 4px, transparent 5px) 18px 28px / 118px 118px,
                radial-gradient(circle, rgba(255,255,255,.22) 0 8px, transparent 9px) 70px 82px / 230px 230px;
        }

        .site-arka > * {
            position: relative;
            z-index: 1;
        }

        .wrap {
            width: 100%;
            max-width: 980px;
            margin: 0 auto;
        }

        .kart {
            background: rgba(255, 255, 255, .86);
            border: 1px solid rgba(117, 104, 255, .13);
            border-radius: 18px;
            padding: 18px 20px;
            margin-bottom: 13px;
            box-shadow: 0 12px 32px rgba(62, 58, 130, .09);
            backdrop-filter: blur(12px);
        }

        .ust {
            text-align: center;
            padding: 24px 20px 22px;
            background:
                linear-gradient(135deg,
                    rgba(255,255,255,.94),
                    rgba(245,247,255,.82)
                );
        }

        h1 {
            margin: 0 0 10px;
            font-size: 28px;
            color: #18204f;
            letter-spacing: -.3px;
            line-height: 1.15;
        }

        h3 {
            margin: 0 0 12px;
            color: #1c245b;
            font-size: 17px;
            line-height: 1.25;
        }

        p {
            color: #4f5573;
            line-height: 1.45;
            font-size: 14px;
            margin: 8px 0;
        }

        .skor {
            display: inline-flex;
            align-items: center;
            justify-content: center;

            width: 88px;
            height: 88px;
            margin: 6px auto 12px;

            border-radius: 50%;
            font-size: 36px;
            font-weight: 800;
            color: #ffffff;

            background:
                radial-gradient(circle at 30% 25%,
                    #8b8cff 0%,
                    #5b5cf0 42%,
                    #4338ca 100%
                );

            box-shadow:
                0 14px 28px rgba(79, 70, 229, .25),
                inset 0 1px 0 rgba(255,255,255,.35);
        }

        .seviye-alani {
            margin-top: 4px;
            color: #2f365f;
            font-size: 15px;
        }

        .seviye-alani strong {
            color: #1d2670;
        }

        .oturum-id {
            color: #6b7091;
            font-size: 13px;
            margin-top: 5px;
        }

        .mini-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
            gap: 10px;
        }

        .mini-kart {
            background:
                linear-gradient(135deg,
                    rgba(250,251,255,.97),
                    rgba(242,245,255,.90)
                );
            border-radius: 15px;
            padding: 14px 15px;
            border: 1px solid rgba(123, 111, 255, .14);
            box-shadow: 0 8px 18px rgba(75, 70, 140, .06);
        }

        .etiket {
            font-size: 12px;
            color: #74799b;
            margin-bottom: 5px;
            font-weight: 700;
        }

        .deger {
            font-size: 18px;
            font-weight: 800;
            color: #26308a;
            margin-bottom: 4px;
            line-height: 1.2;
        }

        .aciklama {
            color: #5d6383;
            font-size: 13px;
        }

        .liste {
            margin: 0;
            padding: 0;
            list-style: none;
        }

        .liste li {
            margin-bottom: 7px;
            padding: 9px 11px;
            border-radius: 12px;
            background: rgba(248, 250, 255, .88);
            border: 1px solid rgba(125, 119, 255, .11);
            color: #3f4567;
            font-size: 14px;
        }

        .liste li strong {
            color: #25308a;
        }

        .bos-veri {
            color: #74799b;
            margin: 0;
            padding: 10px 12px;
            border-radius: 12px;
            background: rgba(248,250,255,.78);
            border: 1px dashed rgba(125, 119, 255, .25);
            font-size: 14px;
        }

        .kart p strong {
            color: #25308a;
        }

        .oneri-kart {
            position: relative;
            overflow: hidden;
            padding-left: 24px;
        }

        .oneri-kart::before {
            content: "";
            position: absolute;
            left: 0;
            top: 15px;
            bottom: 15px;
            width: 4px;
            border-radius: 0 8px 8px 0;
            background: linear-gradient(180deg, #6366f1, #8b5cf6);
        }

        .alt-linkler {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            align-items: center;
            margin-top: 4px;
            padding-bottom: 6px;
        }

        .geri {
            display: inline-flex;
            align-items: center;
            justify-content: center;

            min-height: 38px;
            padding: 9px 15px;

            background: linear-gradient(135deg, #5b5cf0, #4338ca);
            color: #fff;
            text-decoration: none;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 800;
            box-shadow: 0 10px 20px rgba(79, 70, 229, .22);
            transition: transform .18s ease, box-shadow .18s ease, background .18s ease;
        }

        .geri:hover {
            transform: translateY(-1px);
            box-shadow: 0 13px 25px rgba(79, 70, 229, .30);
            background: linear-gradient(135deg, #6366f1, #3730a3);
        }

        @media (max-width: 700px) {
            .site-arka {
                padding: 16px 12px;
            }

            .wrap {
                max-width: 100%;
            }

            .kart {
                padding: 15px;
                border-radius: 16px;
                margin-bottom: 11px;
            }

            .ust {
                padding: 20px 15px 18px;
            }

            h1 {
                font-size: 24px;
            }

            h3 {
                font-size: 16px;
                margin-bottom: 10px;
            }

            p {
                font-size: 13.5px;
            }

            .skor {
                width: 78px;
                height: 78px;
                font-size: 31px;
            }

            .mini-grid {
                grid-template-columns: 1fr;
                gap: 8px;
            }

            .mini-kart {
                padding: 12px;
                border-radius: 13px;
            }

            .deger {
                font-size: 17px;
            }

            .geri {
                width: 100%;
                font-size: 13.5px;
            }
        }
    </style>
</head>

<body>
    <div class="site-arka">
        <div class="wrap">

            <div class="kart ust">
                <h1>Odak Analizi</h1>

                <div class="skor">
                    <?= (int)$skor ?>
                </div>

                <div class="seviye-alani">
                    <strong>Seviye:</strong>
                    <?= htmlspecialchars((string)$seviye) ?>
                </div>

                <div class="oturum-id">
                    Oturum ID:
                    <?= (int)$oturum_id ?>
                </div>
            </div>

            <div class="kart">
                <h3>İhlal Yoğunluğu Özeti</h3>

                <div class="mini-grid">
                    <div class="mini-kart">
                        <div class="etiket">En yoğun zaman aralığı</div>

                        <div class="deger">
                            <?= $enYogunAralik ? htmlspecialchars($enYogunAralik["aralik"]) : "-" ?>
                        </div>

                        <div class="aciklama">
                            <?= $enYogunAralik ? (int)$enYogunAralik["adet"] . " ihlal" : "Veri yok" ?>
                        </div>
                    </div>

                    <div class="mini-kart">
                        <div class="etiket">En sık ihlal türü</div>

                        <div class="deger">
                            <?php if ($enCokIhlalTuru): ?>
                                <?= htmlspecialchars(ihlalTurunuGuzellestir((string)$enCokIhlalTuru["tur"])) ?>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </div>

                        <div class="aciklama">
                            <?= $enCokIhlalTuru ? (int)$enCokIhlalTuru["adet"] . " kez" : "Veri yok" ?>
                        </div>
                    </div>

                    <div class="mini-kart">
                        <div class="etiket">En uzun art arda ihlal serisi</div>

                        <div class="deger">
                            <?= (int)$enUzunSeri ?>
                        </div>

                        <div class="aciklama">
                            <?php if ($seriBaslangic && $seriBitis): ?>
                                <?= date("H:i:s", $seriBaslangic) ?> - <?= date("H:i:s", $seriBitis) ?>
                            <?php else: ?>
                                Veri yok
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (!empty($zamanAraliklari)): ?>
                <div class="kart">
                    <h3>Zaman Aralıklarına Göre Dağılma</h3>

                    <ul class="liste">
                        <?php foreach ($zamanAraliklari as $aralik => $adet): ?>
                            <li>
                                <strong><?= htmlspecialchars((string)$aralik) ?>:</strong>
                                <?= (int)$adet ?> ihlal
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php else: ?>
                <div class="kart">
                    <h3>Zaman Aralıklarına Göre Dağılma</h3>
                    <p class="bos-veri">Bu oturum için log verisi bulunamadı.</p>
                </div>
            <?php endif; ?>

            <?php if (!empty($oneriler)): ?>
                <?php foreach ($oneriler as $oneri): ?>
                    <div class="kart oneri-kart">
                        <h3><?= htmlspecialchars((string)($oneri["baslik"] ?? "")) ?></h3>

                        <p>
                            <strong>Kategori:</strong>
                            <?= htmlspecialchars((string)($oneri["kategori"] ?? "")) ?>
                        </p>

                        <p>
                            <strong>Seviye:</strong>
                            <?= htmlspecialchars((string)($oneri["seviye"] ?? "")) ?>
                        </p>

                        <p>
                            <strong>Durum:</strong>
                            <?= htmlspecialchars((string)($oneri["aciklama"] ?? "")) ?>
                        </p>

                        <p>
                            <strong>Olası neden:</strong>
                            <?= htmlspecialchars((string)($oneri["neden"] ?? "")) ?>
                        </p>

                        <p>
                            <strong>Öneri:</strong>
                            <?= htmlspecialchars((string)($oneri["aksiyon"] ?? "")) ?>
                        </p>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="kart">
                    <p class="bos-veri">Bu oturum için henüz detaylı öneri verisi oluşmadı.</p>
                </div>
            <?php endif; ?>

            <div class="alt-linkler">
                <?php if ($gecmisOturumIstek): ?>
                    <a class="geri" href="istatistik.php?mod=<?= $mod_id === 2 ? 'yogun' : 'yks' ?>">İstatistiklere dön</a>
                <?php endif; ?>

                <?php if ($mod_id === 2): ?>
                    <a class="geri" href="yogun.php?durum=sonlandi">Yoğun seçim ekranına dön</a>
                <?php else: ?>
                    <a class="geri" href="yks.php?durum=sonlandi">YKS seçim ekranına dön</a>
                <?php endif; ?>
            </div>

        </div>
    </div>
</body>
</html>