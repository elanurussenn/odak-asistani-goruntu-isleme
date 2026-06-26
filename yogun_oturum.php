<?php
require __DIR__ . '/config/db.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION["kullanici"]) || !isset($_SESSION["kullanici"]["id"])) {
    header("Location: giris.php");
    exit;
}

$kullanici_id = (int)$_SESSION["kullanici"]["id"];
$calisma_suresi_dk = 45;
$durum_mesaji = "";
$mod_id = 2;
$ders_id = 0;


$durum = $_GET['durum'] ?? '';

$oturum_sonlandi = (
    in_array($durum, ['iptal', 'sonlandi'], true) &&
    !empty($_SESSION['oturum_sonlandi'])
);

if ($oturum_sonlandi) {
    $durum_mesaji = "Oturum sonlandırıldı.";
}


if ($_SERVER["REQUEST_METHOD"] === "POST") {
    unset($_SESSION['oturum_sonlandi']);
    $oturum_sonlandi = false;
    $durum_mesaji = "";

    $calisma_suresi_dk = (int)($_POST["work_min"] ?? ($_SESSION["yogun_verisi"]["calisma_dakika"] ?? 45));
    $mod_id = (int)($_POST["mod_id"] ?? ($_SESSION["yogun_verisi"]["mod_id"] ?? 2));

    $_SESSION["oturum_calisma_suresi_dk"] = $calisma_suresi_dk;
    $_SESSION["oturum_mod_id"] = $mod_id;
} else {
    $calisma_suresi_dk = (int)($_SESSION["yogun_verisi"]["calisma_dakika"] ?? 45);
    $mod_id = (int)($_SESSION["yogun_verisi"]["mod_id"] ?? 2);
}

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    unset($_SESSION['oturum_sonlandi']);
    $oturum_sonlandi = false;
    $durum_mesaji = "";

    $calisma_suresi_dk = (int)($_POST["work_min"] ?? ($_SESSION["yogun_verisi"]["calisma_dakika"] ?? 45));
    $mod_id = (int)($_POST["mod_id"] ?? ($_SESSION["yogun_verisi"]["mod_id"] ?? 2));
    $mola_suresi_dk = (int)($_POST["break_min"] ?? ($_SESSION["yogun_verisi"]["mola_dakika"] ?? 10));

    $_SESSION["oturum_calisma_suresi_dk"] = $calisma_suresi_dk;
    $_SESSION["oturum_mola_suresi_dk"] = $mola_suresi_dk;
    $_SESSION["oturum_mod_id"] = $mod_id;

    try {
        $oturum_kaydet = $pdo->prepare("
            INSERT INTO odak_oturumlari
            (kullanici_id, mod_id, ders_id, sure_dk, mola_dk, baslangic, kullanici_sonlandirdi)
            VALUES (?, ?, ?, ?, ?, NOW(), 0)
        ");

        $oturum_kaydet->execute([
            $kullanici_id,
            $mod_id,
            null,
            $calisma_suresi_dk,
            $mola_suresi_dk
        ]);

        $_SESSION["last_oturum_id"] = (int)$pdo->lastInsertId();
    } catch (PDOException $e) {
        die("Veritabanı hatası: " . $e->getMessage());
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Odak Asistanı | Yoğun Oturum</title>
    <link rel="alternate icon" href="assets/img/favicon.svg">
    <link rel="stylesheet" href="assets/css/yogun_oturum.css">

    <style>
        .oturum-bilgi-listesi {
            display: grid;
            gap: 12px;
            margin-bottom: 18px;
        }

        .oturum-bilgi-karti {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 14px 16px;
            border-radius: 18px;
            background: rgba(255,255,255,.72);
            border: 1px solid rgba(125, 119, 255, .12);
            box-shadow: 0 10px 24px rgba(77, 73, 160, .08);
            backdrop-filter: blur(10px);
        }

        .oturum-bilgi-ikon {
            width: 38px;
            height: 38px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            background: linear-gradient(135deg, #eef0ff, #f8f5ff);
            color: #4b4fcf;
            flex-shrink: 0;
        }

        .oturum-bilgi-icerik {
            display: flex;
            flex-direction: column;
            gap: 4px;
            min-width: 0;
        }

        .oturum-bilgi-baslik {
            font-size: 14px;
            font-weight: 700;
            color: #24233a;
            line-height: 1.2;
        }

        .oturum-bilgi-detay {
            font-size: 13px;
            color: #686885;
            line-height: 1.3;
        }

        .oturum-etiketi {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 14px;
            border-radius: 999px;
            background: #f3f4ff;
            color: #4c50d4;
            font-size: 13px;
            font-weight: 700;
        }

        .oturum-etiketi .nokta {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            background: #4c50d4;
        }
    </style>
</head>
<body>
    <audio id="uyariSesi" src="assets/audio/uyari.mp3" preload="auto"></audio>

<div class="site-arka">
<div class="sayfa-kabuk">

    <div class="ust-geri-alani">
        <button type="button" class="btn btn-ikincil" id="geriBtn">🠔 Geri</button>
    </div>

    <?php if (!empty($durum_mesaji)): ?>
        <div class="ust-uyari">
            <?php echo htmlspecialchars($durum_mesaji); ?>
        </div>
    <?php endif; ?>

    <div class="oturum-ust">

        <section class="panel panel-kamera buyuk-kamera-panel">
            <div class="panel-baslik-satiri">
                <h3 class="panel-baslik">Canlı Kamera</h3>
            </div>

            <div class="kamera-alani kare-kamera-alani">
                <video id="video" autoplay playsinline muted style="transform: scaleX(-1); -webkit-transform: scaleX(-1);"></video>
                <div class="kamera-bekleme" id="kameraBeklemeYazisi">
                    🌀 Kamera açıldığında görüntü burada görünecek 🌀
                </div>
            </div>

            <div class="alt-mini-analiz">
                <div class="mini-analiz-grid">
                    <div class="mini-kutu" id="kameraDurumuYazisi">Kamera: Kapalı</div>
                    <div class="mini-kutu" id="analizSonucuYazisi">
                        <?php echo $durum_mesaji ? "AI Analiz: Oturum sonlandırıldı." : "AI Analiz: Bekleniyor..."; ?>
                    </div>
                </div>
            </div>
        </section>

        <aside class="sag-kolon">

            <section class="panel panel-sure">
                <div class="sure-ust-satir yogun">
                    <div class="oturum-etiketi">
                        <span class="nokta"></span>
                        <span>Yoğun Oturum</span>
                        <span>/</span>
                        <span><?php echo htmlspecialchars($calisma_suresi_dk); ?> dk</span>
                    </div>

                    <div class="durum-badge" id="odakDurumuYazisi">
                        <?php echo $durum_mesaji ? "Oturum iptal edildi" : "Yoğun oturum aktif"; ?>
                    </div>
                </div>

                <div class="sure-kart">
                    <div class="halka-alani">
                        <div class="halka halka-kucuk">
                            <div class="halka-ic">
                                <div class="halka-sure" id="halkaSureYazisi">00:00</div>
                                <div class="halka-yazi">Kalan yoğun çalışma süresi</div>
                            </div>
                        </div>
                    </div>

                    <div class="buton-alani yatay-butonlar">
                        <button type="button" class="btn btn-birincil" id="kameraAcBtn" <?php echo $oturum_sonlandi ? 'disabled' : ''; ?>>
                            Kamerayı Aç
                        </button>

                        <button type="button" class="btn btn-ikincil" id="kameraKapatBtn" <?php echo $oturum_sonlandi ? 'disabled' : ''; ?>>
                            Duraklat
                        </button>

                        <form action="oturum_sonlandir.php" method="POST" id="bitirFormu">
                            <input type="hidden" name="mod" value="yogun">
                            <input type="hidden" name="mola_dk" id="molaDkInput" value="0">
                            <input type="hidden" name="otomatik_bitti" id="otomatikBittiInput" value="0">

                            <button type="submit" class="btn btn-birincil" <?php echo $oturum_sonlandi ? 'disabled' : ''; ?>>
                                Çalışmayı Bitir
                            </button>
                        </form>
                    </div>
                </div>
            </section>

            <section class="panel panel-veriler">
                <div class="panel-baslik-satiri tekli">
                    <div>
                        <h3 class="panel-baslik">Canlı Bildirim</h3>
                        <p class="panel-aciklama">Yoğun oturum boyunca dikkat takibi</p>
                    </div>
                </div>

                <div class="veri-grid">
                    <div class="veri-kutu" id="uzunAyrilikSayisiYazisi">Uzun Ayrılık: 0</div>
                    <div class="veri-kutu" id="kameradanAyrilmaSayisiYazisi">Kameradan Ayrılma: 0</div>
                    <div class="veri-kutu" id="esnemeSayisi">Esneme: 0</div>
                    <div class="veri-kutu" id="gozKapaliSayisi">Göz Kapalı: 0</div>
                    <div class="veri-kutu" id="etrafaBakmaSayisi">Etrafa Bakma: 0</div>
                    <div class="veri-kutu" id="etrafaOdaklanmaSayisi">Etrafa Odaklanma: 0</div>

                    <div class="veri-kutu veri-kutu-genis veri-kutu-ciftli veri-kutu-vurgu">
                        <span id="bakisYonu">Bakış: Düz</span>
                    </div>

                    <div class="veri-kutu veri-kutu-genis veri-kutu-vurgu" id="genelDurumMesaji">
                        Mesaj: Analiz bekleniyor...
                    </div>

                    <div class="veri-kutu veri-kutu-genis" id="molaSuresiKutusu">
                        Toplam Mola Süresi: 00:00
                    </div>

                    <div class="veri-kutu veri-kutu-genis" id="sekmeDegisimSayisiYazisi">
                        Sekme Değişim Sayısı: 0
                    </div>

                    <div class="veri-kutu veri-kutu-genis" id="sekmeDurumuYazisi">
                        Sekme Durumu: Aktif
                    </div>

                    <div class="veri-kutu veri-kutu-genis" id="cikisDurumuYazisi">
                        Çıkış Durumu: Yok
                    </div>

                    <div class="veri-kutu veri-kutu-genis" id="duraklatmaBilgilendirme">
                        1 duraklatma hakkın var.
                    </div>

                    <div class="veri-kutu" id="duraklatmaSayisiYazisi">0</div>
                    <div class="veri-kutu" id="duraklatmaHakYazisi">1</div>
                </div>
            </section>
        </aside>
    </div>
</div>

<canvas id="goruntuTuvali"></canvas>
</div>

<script>
(() => {
    const video = document.getElementById("video");
    const kameraAcBtn = document.getElementById("kameraAcBtn");
    const kameraKapatBtn = document.getElementById("kameraKapatBtn");
    const geriBtn = document.getElementById("geriBtn");
    const kameraDurumuYazisi = document.getElementById("kameraDurumuYazisi");
    const analizSonucuYazisi = document.getElementById("analizSonucuYazisi");
    const kameraBeklemeYazisi = document.getElementById("kameraBeklemeYazisi");
    const halkaSureYazisi = document.getElementById("halkaSureYazisi");
    const odakDurumuYazisi = document.getElementById("odakDurumuYazisi");
    const goruntuTuvali = document.getElementById("goruntuTuvali");

    const genelDurumMesaji = document.getElementById("genelDurumMesaji");
    const uzunAyrilikKutusu = document.getElementById("uzunAyrilikSayisiYazisi");
    const kameradanAyrilmaKutusu = document.getElementById("kameradanAyrilmaSayisiYazisi");
    const esnemeKutusu = document.getElementById("esnemeSayisi");
    const gozKapaliKutusu = document.getElementById("gozKapaliSayisi");
    const etrafaBakmaKutusu = document.getElementById("etrafaBakmaSayisi");
    const etrafaOdaklanmaKutusu = document.getElementById("etrafaOdaklanmaSayisi");
    const molaSuresiKutusu = document.getElementById("molaSuresiKutusu");
    const sekmeDegisimSayisiYazisi = document.getElementById("sekmeDegisimSayisiYazisi");
    const bakisYonuYazisi = document.getElementById("bakisYonu");
    const sekmeDurumuYazisi = document.getElementById("sekmeDurumuYazisi");
    const cikisDurumuYazisi = document.getElementById("cikisDurumuYazisi");
    const duraklatmaHakYazisi = document.getElementById("duraklatmaHakYazisi");
    const duraklatmaSayisiYazisi = document.getElementById("duraklatmaSayisiYazisi");
    const duraklatmaBilgilendirme = document.getElementById("duraklatmaBilgilendirme");
    const uyariSesi = document.getElementById("uyariSesi");

    const bitirFormu = document.getElementById("bitirFormu");
    const molaDkInput = document.getElementById("molaDkInput");
    const otomatikBittiInput = document.getElementById("otomatikBittiInput");

    const userId = <?php echo (int)$kullanici_id; ?>;
    const dersId = <?php echo (int)$ders_id; ?>;
    const modId = <?php echo (int)$mod_id; ?>;
    const oturumId = <?php echo (int)($_SESSION["last_oturum_id"] ?? 0); ?>;

    const resetKey = `yogun_reset_yapildi_${oturumId}`;
    const sureKey = `yogun_kalan_sure_${oturumId}`;
    const analizStateKey = `yogun_analiz_state_${oturumId}`;
    const duraklatmaKey = `yogun_duraklatma_${oturumId}`;
    const sekmeDegisimKey = `yogun_sekme_degisim_${oturumId}`;

    let kameraAkisi = null;
    let analizAraligi = null;
    let sayacAraligi = null;
    let kameraAcikMi = false;
    let oturumZatenBitirildi = <?php echo $oturum_sonlandi ? 'true' : 'false'; ?>;
    let geriAktifMi = <?php echo $oturum_sonlandi ? 'true' : 'false'; ?>;
    let molaSuresiSn = 0;
    let sonGozKapaliSayisi = 0;

    const toplamDuraklatmaHakki = 1;
    const duraklatmaSuresiDakika = 5;

    let duraklatmaSayisi = parseInt(sessionStorage.getItem(duraklatmaKey), 10);
    if (isNaN(duraklatmaSayisi) || duraklatmaSayisi < 0) {
        duraklatmaSayisi = 0;
    }

    let sekmeDegisimSayisi = parseInt(sessionStorage.getItem(sekmeDegisimKey), 10);
    if (isNaN(sekmeDegisimSayisi) || sekmeDegisimSayisi < 0) {
        sekmeDegisimSayisi = 0;
    }

    const varsayilanSureSn = <?php echo $calisma_suresi_dk * 60; ?>;
    let kalanSureSn = parseInt(sessionStorage.getItem(sureKey), 10);
    if (isNaN(kalanSureSn) || kalanSureSn < 0) {
        kalanSureSn = varsayilanSureSn;
    }

    function analizStateKaydet(data) {
        sessionStorage.setItem(analizStateKey, JSON.stringify(data));
    }

    function analizStateTemizle() {
        sessionStorage.removeItem(analizStateKey);
    }

    function tumOturumStorageTemizle() {
        sessionStorage.removeItem(resetKey);
        sessionStorage.removeItem(sureKey);
        sessionStorage.removeItem(duraklatmaKey);
        sessionStorage.removeItem(sekmeDegisimKey);
        analizStateTemizle();
    }

    function analizStateYukle() {
        const raw = sessionStorage.getItem(analizStateKey);
        if (!raw) return;

        try {
            const veri = JSON.parse(raw);

            if (typeof veri.uzun_ayrilik_sayisi !== "undefined") {
                uzunAyrilikKutusu.textContent = "Uzun Ayrılık: " + String(veri.uzun_ayrilik_sayisi ?? 0);
            }

            if (typeof veri.kameradan_ayrilma_sayisi !== "undefined") {
                kameradanAyrilmaKutusu.textContent = "Kameradan Ayrılma: " + String(veri.kameradan_ayrilma_sayisi ?? 0);
            }

            if (typeof veri.esneme_sayisi !== "undefined") {
                esnemeKutusu.textContent = "Esneme: " + String(veri.esneme_sayisi ?? 0);
            }

            if (typeof veri.goz_kapali_sayisi !== "undefined") {
                gozKapaliKutusu.textContent = "Göz Kapalı: " + String(veri.goz_kapali_sayisi ?? 0);
                sonGozKapaliSayisi = veri.goz_kapali_sayisi ?? 0;
            }

            if (typeof veri.etrafa_bakma_sayisi !== "undefined") {
                etrafaBakmaKutusu.textContent = "Etrafa Bakma: " + String(veri.etrafa_bakma_sayisi ?? 0);
            }

            if (typeof veri.etrafa_odaklanma_sayisi !== "undefined") {
                etrafaOdaklanmaKutusu.textContent = "Etrafa Odaklanma: " + String(veri.etrafa_odaklanma_sayisi ?? 0);
            }

            if (typeof veri.bakis_yonu !== "undefined") {
                let bakisMetni = "Düz";

                if (veri.bakis_yonu === "saga_bakiyor") bakisMetni = "Sağa";
                else if (veri.bakis_yonu === "sola_bakiyor") bakisMetni = "Sola";
                else if (veri.bakis_yonu === "yukari_bakiyor") bakisMetni = "Yukarı";
                else if (veri.bakis_yonu === "asagi_bakiyor") bakisMetni = "Aşağı";
                else if (veri.bakis_yonu === "duz") bakisMetni = "Düz";
                else if (veri.bakis_yonu === "yuz_yok") bakisMetni = "Yüz Yok";

                bakisYonuYazisi.textContent = "Bakış: " + bakisMetni;
            }

            if (veri.analiz_metni) {
                analizSonucuYazisi.textContent = veri.analiz_metni;
            }

            if (veri.mesaj_metni) {
                genelDurumMesaji.textContent = veri.mesaj_metni;
            }

            if (typeof veri.mola_suresi_sn !== "undefined") {
                molaSuresiSn = veri.mola_suresi_sn ?? 0;
                molaSuresiniGuncelle(molaSuresiSn);

                if (molaDkInput) {
                    molaDkInput.value = Math.floor(molaSuresiSn / 60);
                }
            }
        } catch (e) {
            console.error("Analiz state okunamadı:", e);
        }
    }

    function sekmeDegisimSayisiniGuncelle() {
        if (sekmeDegisimSayisiYazisi) {
            sekmeDegisimSayisiYazisi.textContent = "Sekme Değişim Sayısı: " + String(sekmeDegisimSayisi);
        }
    }

    function sureyiYaz(saniye) {
        const dakika = Math.floor(saniye / 60);
        const kalanSaniye = saniye % 60;
        return String(dakika).padStart(2, "0") + ":" + String(kalanSaniye).padStart(2, "0");
    }

    function molaSuresiniGuncelle(saniye) {
        if (!molaSuresiKutusu) return;
        molaSuresiKutusu.textContent = "Toplam Mola Süresi: " + sureyiYaz(saniye || 0);
    }

    function ekranSureleriniGuncelle() {
        halkaSureYazisi.textContent = sureyiYaz(kalanSureSn);
    }

    function duraklatmaHaklariniGuncelle() {
        const kalanHak = Math.max(0, toplamDuraklatmaHakki - duraklatmaSayisi);

        if (duraklatmaSayisiYazisi) {
            duraklatmaSayisiYazisi.textContent = String(duraklatmaSayisi);
        }

        if (duraklatmaHakYazisi) {
            duraklatmaHakYazisi.textContent = String(kalanHak);
        }

        if (duraklatmaBilgilendirme) {
            if (duraklatmaSayisi <= 0) {
                duraklatmaBilgilendirme.textContent = `1 duraklatma hakkın var. Kullanım süresi ${duraklatmaSuresiDakika} dk.`;
            } else {
                duraklatmaBilgilendirme.textContent = "Duraklatma hakkı tüketildi.";
            }
        }
    }

    function sayaciBaslat() {
        if (sayacAraligi) clearInterval(sayacAraligi);

        sayacAraligi = setInterval(() => {
            if (kalanSureSn > 0) {
                kalanSureSn--;
                sessionStorage.setItem(sureKey, String(kalanSureSn));
                ekranSureleriniGuncelle();
            } else {
                clearInterval(sayacAraligi);
                sayacAraligi = null;

                oturumZatenBitirildi = true;
                geriAktifMi = true;

                analizSonucuYazisi.textContent = "AI Analiz: Süre tamamlandı";
                odakDurumuYazisi.textContent = "Oturum tamamlandı";
                genelDurumMesaji.textContent = "Mesaj: Çalışma süresi tamamlandı.";

                if (molaDkInput) {
                    molaDkInput.value = molaSuresiSn;
                }

                if (otomatikBittiInput) {
                    otomatikBittiInput.value = "1";
                }

                tumOturumStorageTemizle();
                kamerayiKapat(false);

                setTimeout(() => {
                    bitirFormu.submit();
                }, 800);
            }
        }, 1000);
    }

    function sayaciDurdur() {
        if (sayacAraligi) {
            clearInterval(sayacAraligi);
            sayacAraligi = null;
        }
    }

    function oturumuSistemBitir() {
        if (oturumZatenBitirildi) return;
        oturumZatenBitirildi = true;
        geriAktifMi = true;

        tumOturumStorageTemizle();

        kamerayiKapat(true);
        analizSonucuYazisi.textContent = "AI Analiz: Oturum kapatıldı";
        odakDurumuYazisi.textContent = "Oturum kapatıldı";
        genelDurumMesaji.textContent = "Mesaj: Sistem oturumu kapattı.";

        bitirFormu.submit();
    }

    async function kamerayiAc() {
        if (kameraAcikMi || oturumZatenBitirildi) return;

        try {
            if (!sessionStorage.getItem(resetKey)) {
                await fetch("http://127.0.0.1:5000/yogun_reset", {
                    method: "POST"
                });

                sessionStorage.setItem(resetKey, "1");
            }

            kameraAkisi = await navigator.mediaDevices.getUserMedia({
                video: true,
                audio: false
            });

            video.srcObject = kameraAkisi;
            kameraAcikMi = true;

            kameraDurumuYazisi.textContent = "Kamera: Açık";
            kameraBeklemeYazisi.style.display = "none";
            odakDurumuYazisi.textContent = "Yoğun oturum aktif";
            genelDurumMesaji.textContent = "Mesaj: Canlı analiz başladı.";

            if (analizAraligi) clearInterval(analizAraligi);
            analizAraligi = setInterval(goruntuAnalizEt, 300);

            sayaciBaslat();
        } catch (hata) {
            kameraDurumuYazisi.textContent = "Kamera: İzin verilmedi";
            analizSonucuYazisi.textContent = "AI Analiz: Kamera açılamadı";
            odakDurumuYazisi.textContent = "Başlatılamadı";
            genelDurumMesaji.textContent = "Mesaj: Kamera başlatılamadı.";
            console.error(hata);
        }
    }

    function kamerayiKapat(sureyiDeDurdur = true) {
        if (kameraAkisi) {
            kameraAkisi.getTracks().forEach(iz => iz.stop());
            kameraAkisi = null;
        }

        video.srcObject = null;
        kameraAcikMi = false;

        if (analizAraligi) {
            clearInterval(analizAraligi);
            analizAraligi = null;
        }

        if (sureyiDeDurdur) {
            sayaciDurdur();
            odakDurumuYazisi.textContent = "Yoğun analiz durduruldu";
        }

        kameraDurumuYazisi.textContent = "Kamera: Kapalı";
        kameraBeklemeYazisi.style.display = "flex";
        genelDurumMesaji.textContent = "Mesaj: Kamera kapalı.";
    }

    function goruntuAnalizEt() {
        if (!video.videoWidth || !video.videoHeight) return;

        const cizimAlani = goruntuTuvali.getContext("2d");
        goruntuTuvali.width = video.videoWidth;
        goruntuTuvali.height = video.videoHeight;

        cizimAlani.clearRect(0, 0, goruntuTuvali.width, goruntuTuvali.height);

        cizimAlani.save();
        cizimAlani.scale(-1, 1);
        cizimAlani.drawImage(
            video,
            -goruntuTuvali.width,
            0,
            goruntuTuvali.width,
            goruntuTuvali.height
        );
        cizimAlani.restore();

        const goruntuVerisi = goruntuTuvali.toDataURL("image/jpeg");

        fetch("http://127.0.0.1:5000/analiz", {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({
                image: goruntuVerisi,
                mod: "yogun"
            })
        })
        .then(cevap => cevap.json())
        .then(veri => {
            if (veri.logs && veri.logs.length > 0) {
                fetch("log_event.php", {
                    method: "POST",
                    headers: {
                        "Content-Type": "application/json"
                    },
                    body: JSON.stringify(
                        veri.logs.map(log => ({
                            user_id: userId,
                            oturum_id: oturumId,
                            mod_id: modId,
                            ders_id: dersId,
                            event_type: log.event_type,
                            old_value: log.old_value,
                            new_value: log.new_value,
                            created_at: log.created_at
                        }))
                    )
                }).catch(err => {
                    console.error("Log gönderme hatası:", err);
                });
            }

            if (veri.error) {
                analizSonucuYazisi.textContent = "AI Analiz: Hata";
                genelDurumMesaji.textContent = "Mesaj: Analiz hatası oluştu.";
                return;
            }

            if (veri.uyari) {
                analizSonucuYazisi.textContent = "AI Analiz: " + (veri.sonuc || "Analiz") + " - " + veri.uyari;
                genelDurumMesaji.textContent = "Mesaj: " + veri.uyari;
            } else {
                analizSonucuYazisi.textContent = "AI Analiz: " + (veri.sonuc || "Analiz alındı");
                genelDurumMesaji.textContent = "Mesaj: " + (veri.sonuc || "Analiz alındı");
            }

            if (typeof veri.uzun_ayrilik_sayisi !== "undefined") {
                uzunAyrilikKutusu.textContent = "Uzun Ayrılık: " + String(veri.uzun_ayrilik_sayisi ?? 0);
            }

            if (typeof veri.kameradan_ayrilma_sayisi !== "undefined") {
                kameradanAyrilmaKutusu.textContent = "Kameradan Ayrılma: " + String(veri.kameradan_ayrilma_sayisi ?? 0);
            }

            if (veri.goz_kapali_sayisi > sonGozKapaliSayisi) {
                uyariSesi.currentTime = 0;
                uyariSesi.play().catch(e => console.error("Ses çalma hatası:", e));

                genelDurumMesaji.style.color = "red";
                setTimeout(() => {
                    genelDurumMesaji.style.color = "";
                }, 2000);

                sonGozKapaliSayisi = veri.goz_kapali_sayisi;
            }

            if (typeof veri.esneme_sayisi !== "undefined") {
                esnemeKutusu.textContent = "Esneme: " + String(veri.esneme_sayisi ?? 0);
            }

            if (typeof veri.goz_kapali_sayisi !== "undefined") {
                gozKapaliKutusu.textContent = "Göz Kapalı: " + String(veri.goz_kapali_sayisi ?? 0);
            }

            if (typeof veri.etrafa_bakma_sayisi !== "undefined") {
                etrafaBakmaKutusu.textContent = "Etrafa Bakma: " + String(veri.etrafa_bakma_sayisi ?? 0);
            }

            if (typeof veri.etrafa_odaklanma_sayisi !== "undefined") {
                etrafaOdaklanmaKutusu.textContent = "Etrafa Odaklanma: " + String(veri.etrafa_odaklanma_sayisi ?? 0);
            }

            if (typeof veri.mola_suresi_sn !== "undefined") {
                molaSuresiSn = veri.mola_suresi_sn ?? 0;
                molaSuresiniGuncelle(molaSuresiSn);

                if (molaDkInput) {
                    molaDkInput.value = Math.floor(molaSuresiSn / 60);
                }
            }

            if (typeof veri.bakis_yonu !== "undefined") {
                let bakisMetni = "Düz";

                if (veri.bakis_yonu === "saga_bakiyor") bakisMetni = "Sağa";
                else if (veri.bakis_yonu === "sola_bakiyor") bakisMetni = "Sola";
                else if (veri.bakis_yonu === "yukari_bakiyor") bakisMetni = "Yukarı";
                else if (veri.bakis_yonu === "asagi_bakiyor") bakisMetni = "Aşağı";
                else if (veri.bakis_yonu === "duz") bakisMetni = "Düz";
                else if (veri.bakis_yonu === "yuz_yok") bakisMetni = "Yüz Yok";

                bakisYonuYazisi.textContent = "Bakış: " + bakisMetni;
            }

            analizStateKaydet({
                uzun_ayrilik_sayisi: veri.uzun_ayrilik_sayisi ?? 0,
                kameradan_ayrilma_sayisi: veri.kameradan_ayrilma_sayisi ?? 0,
                esneme_sayisi: veri.esneme_sayisi ?? 0,
                goz_kapali_sayisi: veri.goz_kapali_sayisi ?? 0,
                etrafa_bakma_sayisi: veri.etrafa_bakma_sayisi ?? 0,
                etrafa_odaklanma_sayisi: veri.etrafa_odaklanma_sayisi ?? 0,
                bakis_yonu: veri.bakis_yonu ?? "duz",
                analiz_metni: analizSonucuYazisi.textContent,
                mesaj_metni: genelDurumMesaji.textContent,
                mola_suresi_sn: veri.mola_suresi_sn ?? 0
            });

            if (veri.oturum_kapat === true) {
                oturumuSistemBitir();
                return;
            }
        })
        .catch(() => {
            analizSonucuYazisi.textContent = "AI Analiz: Python bağlantı hatası";
            genelDurumMesaji.textContent = "Mesaj: Analiz servisine ulaşılamadı.";
        });
    }

    document.addEventListener("visibilitychange", () => {
        if (document.hidden) {
            if (sekmeDurumuYazisi) sekmeDurumuYazisi.textContent = "Sekme değişti, sayaç ve uyarı takibi aktif";
            if (cikisDurumuYazisi) cikisDurumuYazisi.textContent = "Kullanıcı pencere dışına çıktı";
        } else {
            if (sekmeDurumuYazisi) sekmeDurumuYazisi.textContent = "Sekme aktif, izleme normal";
            if (cikisDurumuYazisi) cikisDurumuYazisi.textContent = "Pencere tekrar aktif";
        }
    });

    document.addEventListener("visibilitychange", () => {
        if (!kameraAcikMi || oturumZatenBitirildi) return;
        if (!document.hidden) return;

        const eskiDeger = sekmeDegisimSayisi;
        const yeniDeger = eskiDeger + 1;

        fetch("log_event.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify([
                {
                    user_id: userId,
                    oturum_id: oturumId,
                    mod_id: modId,
                    ders_id: dersId,
                    event_type: "sekme_degisim",
                    old_value: eskiDeger,
                    new_value: yeniDeger,
                    created_at: new Date().toLocaleString("sv-SE").replace("T", " ")
                }
            ])
        })
        .then(res => res.text())
        .then(() => {
            sekmeDegisimSayisi = yeniDeger;
            sessionStorage.setItem(sekmeDegisimKey, String(sekmeDegisimSayisi));
            sekmeDegisimSayisiniGuncelle();
        })
        .catch(err => {
            console.error("Sekme değişim log gönderme hatası:", err);
        });
    });

    window.addEventListener("beforeunload", () => {
        if (cikisDurumuYazisi) {
            cikisDurumuYazisi.textContent = "Çıkış algılandı";
        }
    });

    ekranSureleriniGuncelle();
    duraklatmaHaklariniGuncelle();
    analizStateYukle();
    sekmeDegisimSayisiniGuncelle();

    bitirFormu.addEventListener("submit", () => {
        if (molaDkInput) {
            molaDkInput.value = molaSuresiSn;
        }

        geriAktifMi = true;
        oturumZatenBitirildi = true;
        kamerayiKapat(true);
    });

    <?php if (empty($durum_mesaji)): ?>
    kameraAcBtn.addEventListener("click", kamerayiAc);

    kameraKapatBtn.addEventListener("click", () => {
        if (duraklatmaSayisi >= toplamDuraklatmaHakki) {
            genelDurumMesaji.textContent = "Mesaj: Duraklatma hakkın bitti.";
            duraklatmaHaklariniGuncelle();
            return;
        }

        const eskiDeger = duraklatmaSayisi;
        duraklatmaSayisi++;
        sessionStorage.setItem(duraklatmaKey, String(duraklatmaSayisi));
        duraklatmaHaklariniGuncelle();

        fetch("log_event.php", {
            method: "POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify([
                {
                    user_id: userId,
                    oturum_id: oturumId,
                    mod_id: modId,
                    ders_id: 0,
                    event_type: "duraklatma",
                    old_value: eskiDeger,
                    new_value: duraklatmaSayisi,
                    created_at: new Date().toLocaleString("sv-SE").replace("T", " ")
                }
            ])
        }).catch(err => {
            console.error("Duraklatma log gönderme hatası:", err);
        });

        kamerayiKapat(true);
    });
    <?php endif; ?>

    geriBtn.addEventListener("click", () => {
        if (!geriAktifMi) return;

        tumOturumStorageTemizle();
        kamerayiKapat(true);
        window.location.href = "yogun.php";
    });
})();
</script>

</body>
</html>