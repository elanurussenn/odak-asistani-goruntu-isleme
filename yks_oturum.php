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
$ders_adi = "Ders";
$calisma_suresi_dk = 45;
$mola_suresi_dk = 10;
$durum_mesaji = "";
$ders_id = 0;
$mod_id = 0;


$oturum_sonlandi = (isset($_GET['durum']) && $_GET['durum'] === 'iptal' && !empty($_SESSION['oturum_sonlandi']));

if ($oturum_sonlandi) {
    $durum_mesaji = "Oturum sonlandırıldı.";
}


if ($_SERVER["REQUEST_METHOD"] === "POST") {
    
    unset($_SESSION['oturum_sonlandi']);
    $oturum_sonlandi = false;
    $durum_mesaji = "";

    $ders_id = (int)($_POST["ders_id"] ?? ($_SESSION["yks_verisi"]["ders_id"] ?? 0));
    $calisma_suresi_dk = (int)($_POST["work_min"] ?? ($_SESSION["yks_verisi"]["calisma_dakika"] ?? 45));
    $mola_suresi_dk = (int)($_POST["break_min"] ?? ($_SESSION["yks_verisi"]["mola_dakika"] ?? 10));
    $mod_id = (int)($_SESSION["yks_verisi"]["mod_id"] ?? 0);

    if ($ders_id <= 0) {
        header("Location: yks.php?e=" . urlencode("Ders seçmelisin."));
        exit;
    }

    try {
        $ders_sorgu = $pdo->prepare("SELECT ad FROM dersler WHERE id = ? LIMIT 1");
        $ders_sorgu->execute([$ders_id]);
        $ders_bilgisi = $ders_sorgu->fetch(PDO::FETCH_ASSOC);

        if ($ders_bilgisi && !empty($ders_bilgisi["ad"])) {
            $ders_adi = $ders_bilgisi["ad"];
        }

        $_SESSION["oturum_ders_adi"] = $ders_adi;
        $_SESSION["oturum_calisma_suresi_dk"] = $calisma_suresi_dk;
        $_SESSION["oturum_mola_suresi_dk"] = $mola_suresi_dk;
        $_SESSION["oturum_mod_id"] = $mod_id;
    } catch (PDOException $e) {
        die("Veritabanı hatası: " . $e->getMessage());
    }
} else {
    $ders_id = (int)($_SESSION["yks_verisi"]["ders_id"] ?? 0);
    $calisma_suresi_dk = (int)($_SESSION["yks_verisi"]["calisma_dakika"] ?? 45);
    $mola_suresi_dk = (int)($_SESSION["yks_verisi"]["mola_dakika"] ?? 10);
    $mod_id = (int)($_SESSION["yks_verisi"]["mod_id"] ?? 0);

    if ($ders_id > 0) {
        try {
            $ders_sorgu = $pdo->prepare("SELECT ad FROM dersler WHERE id = ? LIMIT 1");
            $ders_sorgu->execute([$ders_id]);
            $ders_bilgisi = $ders_sorgu->fetch(PDO::FETCH_ASSOC);

            if ($ders_bilgisi && !empty($ders_bilgisi["ad"])) {
                $ders_adi = $ders_bilgisi["ad"];
            }
        } catch (PDOException $e) {
            $ders_adi = "Ders";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Odak Asistanı | YKS Oturumu</title>
    <link rel="alternate icon" href="assets/img/favicon.svg">
    <link rel="stylesheet" href="assets/css/yks_oturum.css">
</head>
<body>
<div class='site-arka'>
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
                    🌀Kamera açıldığında görüntü burada görünecek🌀
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
                <div class="sure-ust-satir">
                    <div class="ders-etiketi">
                        <div class="onay-ikon">✓</div>
                        <span><?php echo htmlspecialchars($ders_adi); ?></span>
                        <span>/</span>
                        <span><?php echo htmlspecialchars($calisma_suresi_dk); ?></span>
                        <span>dakika</span>
                    </div>

                    <div class="durum-badge" id="odakDurumuYazisi">
                        <?php echo $durum_mesaji ? "Oturum iptal edildi" : "Odak oturumu aktif"; ?>
                    </div>
                </div>

                <div class="sure-kart">
                    <div class="halka-alani">
                        <div class="halka halka-kucuk">
                            <div class="halka-ic">
                                <div class="halka-sure" id="halkaSureYazisi">00:00</div>
                                <div class="halka-yazi">Kalan çalışma süresi</div>
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
                        <h3 class="panel-baslik">Canlı Analiz</h3>
                        <p class="panel-aciklama">Kısa ve tek satır metrikler</p>
                    </div>
                </div>

                <div class="veri-grid">
                    <div class="veri-kutu" id="uzunAyrilikSayisiYazisi">Uzun Ayrılık: 0</div>
                    <div class="veri-kutu" id="kameradanAyrilmaSayisiYazisi">Kameradan Ayrılma: 0</div>
                    <div class="veri-kutu" id="esnemeSayisi">Esneme: 0</div>
                    <div class="veri-kutu" id="gozKapaliSayisi">Göz Kapalı: 0</div>
                    <div class="veri-kutu" id="etrafaBakmaSayisi">Etrafa Bakma: 0</div>
                    <div class="veri-kutu" id="etrafaOdaklanmaSayisi">Etrafa Odaklanma: 0</div>

                    <div class="veri-kutu veri-kutu-genis veri-kutu-ciftli">
                        <span id="bakisYonu">Bakış: Düz</span>
                    </div>

                    <div class="veri-kutu veri-kutu-genis" id="genelDurumMesaji">Mesaj: Analiz bekleniyor...</div>
                    <div class="veri-kutu veri-kutu-genis" id="duraklatmaSayisiYazisi">Duraklatma Sayısı: 0</div>
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

    const kipirdanmaSayisiYazisi = document.getElementById("kipirdanmaSayisiYazisi");
    const odakDisiSayisiYazisi = document.getElementById("odakDisiSayisiYazisi");
    const bakisYonuYazisi = document.getElementById("bakisYonu");
    const duraklatmaSayisiYazisi = document.getElementById("duraklatmaSayisiYazisi");

    const bitirFormu = document.getElementById("bitirFormu");
    const otomatikBittiInput = document.getElementById("otomatikBittiInput");

    const userId = <?php echo (int)$kullanici_id; ?>;
    const dersId = <?php echo (int)$ders_id; ?>;
    const modId = <?php echo (int)$mod_id; ?>;
    const oturumId = <?php echo (int)($_SESSION["last_oturum_id"] ?? 0); ?>;

    const resetKey = `yks_reset_yapildi_${oturumId}`;
    const sureKey = `yks_kalan_sure_${oturumId}`;
    const duraklatmaKey = `yks_duraklatma_${oturumId}`;
    const analizStateKey = `yks_analiz_state_${oturumId}`;

    let kameraAkisi = null;
    let analizAraligi = null;
    let sayacAraligi = null;
    let kameraAcikMi = false;
    let oturumZatenBitirildi = <?php echo $oturum_sonlandi ? 'true' : 'false'; ?>;
    let geriAktifMi = <?php echo $oturum_sonlandi ? 'true' : 'false'; ?>;

    let duraklatmaSayisi = parseInt(sessionStorage.getItem(duraklatmaKey), 10);
    if (isNaN(duraklatmaSayisi) || duraklatmaSayisi < 0) {
        duraklatmaSayisi = 0;
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
            }

            if (typeof veri.etrafa_bakma_sayisi !== "undefined") {
                etrafaBakmaKutusu.textContent = "Etrafa Bakma: " + String(veri.etrafa_bakma_sayisi ?? 0);
            }

            if (typeof veri.etrafa_odaklanma_sayisi !== "undefined") {
                etrafaOdaklanmaKutusu.textContent = "Etrafa Odaklanma: " + String(veri.etrafa_odaklanma_sayisi ?? 0);
            }

            if (typeof veri.kipirdanma !== "undefined" && kipirdanmaSayisiYazisi) {
                kipirdanmaSayisiYazisi.textContent = "Kıpırdanma: " + String(veri.kipirdanma ?? 0);
            }

            if (typeof veri.odak_disi_sayisi !== "undefined" && odakDisiSayisiYazisi) {
                odakDisiSayisiYazisi.textContent = "Odak Dışı: " + String(veri.odak_disi_sayisi ?? 0);
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
        } catch (e) {
            console.error("Analiz state okunamadı:", e);
        }
    }

    function sureyiYaz(saniye) {
        const dakika = Math.floor(saniye / 60);
        const kalanSaniye = saniye % 60;
        return String(dakika).padStart(2, "0") + ":" + String(kalanSaniye).padStart(2, "0");
    }

    function ekranSureleriniGuncelle() {
        halkaSureYazisi.textContent = sureyiYaz(kalanSureSn);
    }

    function duraklatmaSayisiniGuncelle() {
        duraklatmaSayisiYazisi.textContent = "Duraklatma Sayısı: " + duraklatmaSayisi;
    }

    function sayaciBaslat() {
        if (sayacAraligi) clearInterval(sayacAraligi);

        sayacAraligi = setInterval(() => {
            if (kalanSureSn > 0) {
                kalanSureSn--;
                sessionStorage.setItem(sureKey, String(kalanSureSn));
                ekranSureleriniGuncelle();
                duraklatmaSayisiniGuncelle();
            } else {
                clearInterval(sayacAraligi);
                sayacAraligi = null;

                oturumZatenBitirildi = true;
                geriAktifMi = true;

                analizSonucuYazisi.textContent = "AI Analiz: Süre tamamlandı";
                odakDurumuYazisi.textContent = "Oturum tamamlandı";
                genelDurumMesaji.textContent = "Mesaj: Çalışma süresi tamamlandı.";

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
                await fetch("http://127.0.0.1:5000/yks_reset", {
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
            odakDurumuYazisi.textContent = "Odak oturumu aktif";
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
            odakDurumuYazisi.textContent = "Odak analizi durduruldu";
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
                mod: "yks"
            })
        })
        .then(cevap => cevap.json())
        .then(veri => {
            console.log("GELEN LOGS:", veri.logs);

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
                })
                .then(response => response.json())
                .then(logSonuc => {
                    console.log("PHP LOG SONUCU:", logSonuc);
                })
                .catch(err => {
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

            if (kipirdanmaSayisiYazisi && typeof veri.kipirdanma !== "undefined") {
                kipirdanmaSayisiYazisi.textContent = "Kıpırdanma: " + String(veri.kipirdanma ?? 0);
            }

            if (odakDisiSayisiYazisi && typeof veri.odak_disi_sayisi !== "undefined") {
                odakDisiSayisiYazisi.textContent = "Odak Dışı: " + String(veri.odak_disi_sayisi ?? 0);
            }

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

            analizStateKaydet({
                uzun_ayrilik_sayisi: veri.uzun_ayrilik_sayisi ?? 0,
                kameradan_ayrilma_sayisi: veri.kameradan_ayrilma_sayisi ?? 0,
                esneme_sayisi: veri.esneme_sayisi ?? 0,
                goz_kapali_sayisi: veri.goz_kapali_sayisi ?? 0,
                etrafa_bakma_sayisi: veri.etrafa_bakma_sayisi ?? 0,
                etrafa_odaklanma_sayisi: veri.etrafa_odaklanma_sayisi ?? 0,
                kipirdanma: veri.kipirdanma ?? 0,
                odak_disi_sayisi: veri.odak_disi_sayisi ?? 0,
                bakis_yonu: veri.bakis_yonu ?? "duz",
                analiz_metni: analizSonucuYazisi.textContent,
                mesaj_metni: genelDurumMesaji.textContent
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

    ekranSureleriniGuncelle();
    duraklatmaSayisiniGuncelle();
    analizStateYukle();

    bitirFormu.addEventListener("submit", () => {
        geriAktifMi = true;
        tumOturumStorageTemizle();
    });

    <?php if (empty($durum_mesaji)): ?>
    kameraAcBtn.addEventListener("click", kamerayiAc);

    kameraKapatBtn.addEventListener("click", () => {
        const eskiDeger = duraklatmaSayisi;

        duraklatmaSayisi++;
        sessionStorage.setItem(duraklatmaKey, String(duraklatmaSayisi));
        duraklatmaSayisiniGuncelle();

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
                    event_type: "duraklatma",
                    old_value: eskiDeger,
                    new_value: duraklatmaSayisi,
                    created_at: new Date().toLocaleString("sv-SE").replace("T", " ")
                }
            ])
        })
        .then(response => response.json())
        .then(logSonuc => {
            console.log("DURAKLATMA LOG SONUCU:", logSonuc);
        })
        .catch(err => {
            console.error("Duraklatma log gönderme hatası:", err);
        });

        kamerayiKapat(true);
    });
    <?php endif; ?>

    geriBtn.addEventListener("click", () => {
        if (!geriAktifMi) return;

        tumOturumStorageTemizle();
        kamerayiKapat(true);
        window.location.href = "yks.php";
    });
})();
</script>

</body>
</html>