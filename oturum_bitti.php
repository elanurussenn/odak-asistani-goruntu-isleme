<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (empty($_SESSION["oturum_sonlandi"])) {
    header("Location: index.php");
    exit;
}

$mod_id = (int)($_SESSION["son_mod_id"] ?? 0);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
   <title>Odak Asistanı | Yoğun Oturum</title>
    <link rel="alternate icon" href="assets/img/favicon.svg">
    <style>
        * {
            box-sizing: border-box;
        }

        html, body {
            margin: 0;
            padding: 0;
            min-height: 100%;
            font-family: Arial, sans-serif;
        }

        body {
            min-height: 100vh;
        }

        .site-arka {
            position: relative;
            min-height: 100vh;
            padding: 26px;
            overflow: hidden;

            display: flex;
            align-items: center;
            justify-content: center;

            background: linear-gradient(180deg,
                rgba(245,248,255,1) 0%,
                rgba(236,240,255,1) 55%,
                rgba(229,234,255,1) 100%
            );
        }

        .site-arka::before {
            content: "";
            position: absolute;
            inset: -120px;
            z-index: 0;
            pointer-events: none;

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

        .site-arka::after {
            content: "";
            position: absolute;
            inset: 0;
            z-index: 0;
            pointer-events: none;

            opacity: .82;
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

        .site-arka > * {
            position: relative;
            z-index: 1;
        }

        .kutu {
            width: 100%;
            max-width: 420px;
            background: #fff;
            padding: 32px;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0,0,0,.08);
            text-align: center;
        }

        h2 {
            margin-top: 0;
            margin-bottom: 12px;
            color: #111827;
        }

        p {
            color: #444;
            margin-bottom: 24px;
            line-height: 1.5;
        }

        .btnler {
            display: flex;
            gap: 12px;
            justify-content: center;
            flex-wrap: wrap;
        }

        a {
            text-decoration: none;
            padding: 10px 16px;
            border-radius: 10px;
            font-weight: bold;
            display: inline-block;
        }

        .evet {
            background: #4f46e5;
            color: #fff;
        }

        .hayir {
            background: #e5e7eb;
            color: #111827;
        }

        .evet:hover {
            background: #4338ca;
        }

        .hayir:hover {
            background: #d1d5db;
        }
    </style>
</head>

<body>
    <div class="site-arka">
        <div class="kutu">
            <h2>Oturum tamamlandı</h2>

            <p>
                Odak skoru hesaplandı. Oturum analizi ve kişisel önerileri görmek ister misiniz?
            </p>

            <div class="btnler">
                <a class="evet" href="odak_analiz.php">Evet, göster</a>

                <?php if ($mod_id === 2): ?>
                    <a class="hayir" href="yogun.php?durum=sonlandi">Hayır</a>
                <?php else: ?>
                    <a class="hayir" href="yks.php?durum=sonlandi">Hayır</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>