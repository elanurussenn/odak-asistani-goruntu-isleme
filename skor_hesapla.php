<?php

function odakSeviyesiBelirle(int $puan): string
{
    if ($puan >= 85) return "Yuksek";
    if ($puan >= 70) return "Iyi";
    if ($puan >= 55) return "Orta";
    if ($puan >= 35) return "Dusuk";
    return "Cok Dusuk";
}

function skorClamp(float $puan): int
{
    $puan = (int)round($puan);

    if ($puan < 0) return 0;
    if ($puan > 100) return 100;

    return $puan;
}

function cezaSinirla(float $ceza, float $maks): float
{
    return min($ceza, $maks);
}

function metrikOran(int $adet, int $sure_dk): float
{
    return $adet / max(1, $sure_dk);
}

function metrikSeviyesi(float $oran, float $dusuk, float $orta, float $yuksek): string
{
    if ($oran >= $yuksek) return "Kritik";
    if ($oran >= $orta) return "Yuksek";
    if ($oran >= $dusuk) return "Orta";
    return "Dusuk";
}

function oneriyiEkle(
    array &$oneriler,
    string $kategori,
    string $seviye,
    string $baslik,
    string $aciklama,
    string $neden,
    string $aksiyon,
    int $puan
): void {
    $oneriler[] = [
        "kategori" => $kategori,
        "seviye" => $seviye,
        "baslik" => $baslik,
        "aciklama" => $aciklama,
        "neden" => $neden,
        "aksiyon" => $aksiyon,
        "puan" => $puan
    ];
}

function temelMetrikleriAl(array $veriler): array
{
    return [
        "sure_dk" => max(1, (int)($veriler["sure_dk"] ?? 1)),
        "mola_dk" => max(0, (int)($veriler["mola_dk"] ?? 0)),

        "uzun_ayrilik" => max(0, (int)($veriler["uzun_ayrilik"] ?? 0)),
        "kameradan_ayrilma" => max(0, (int)($veriler["kameradan_ayrilma"] ?? 0)),
        "esneme" => max(0, (int)($veriler["esneme"] ?? 0)),
        "goz_kapali" => max(0, (int)($veriler["goz_kapali"] ?? 0)),
        "etrafa_bakma" => max(0, (int)($veriler["etrafa_bakma"] ?? 0)),
        "etrafa_odaklanma" => max(0, (int)($veriler["etrafa_odaklanma"] ?? 0)),
        "duraklatma_sayisi" => max(0, (int)($veriler["duraklatma_sayisi"] ?? 0)),
        "sekme_degisim" => max(0, (int)($veriler["sekme_degisim"] ?? 0)),

        
        "kullanici_sonlandirdi" => (int)($veriler["kullanici_sonlandirdi"] ?? 0),
    ];
}

function odakSkoruHesaplaYKS(array $veriler): int
{
    $m = temelMetrikleriAl($veriler);

    $sure = $m["sure_dk"];
    $puan = 100.0;

  

    if ($m["kullanici_sonlandirdi"] === 1) {
        $puan -= 18;
    }

    $puan -= cezaSinirla($m["uzun_ayrilik"] * 12, 30);
    $puan -= cezaSinirla($m["kameradan_ayrilma"] * 4.5, 24);
    $puan -= cezaSinirla($m["etrafa_odaklanma"] * 7, 22);
    $puan -= cezaSinirla($m["etrafa_bakma"] * 3.5, 18);
    $puan -= cezaSinirla($m["goz_kapali"] * 4.5, 16);
    $puan -= cezaSinirla($m["esneme"] * 2.5, 10);
    $puan -= cezaSinirla($m["duraklatma_sayisi"] * 6, 18);
    $puan -= cezaSinirla($m["sekme_degisim"] * 9, 27);

    $molaOrani = $m["mola_dk"] / $sure;
    $puan -= cezaSinirla(($m["mola_dk"] * 1.2) + ($molaOrani * 18), 18);

   
    $toplamDikkatIhlali =
        $m["etrafa_bakma"] +
        $m["etrafa_odaklanma"] +
        $m["goz_kapali"] +
        $m["esneme"];

    if ($toplamDikkatIhlali >= 6) {
        $puan -= min(10, ($toplamDikkatIhlali - 5) * 2);
    }

    return skorClamp($puan);
}

function odakSkoruHesaplaYogun(array $veriler): int
{
    $m = temelMetrikleriAl($veriler);

    $sure = $m["sure_dk"];
    $puan = 100.0;

   
    if ($m["kullanici_sonlandirdi"] === 1) {
        $puan -= 24;
    }

    $puan -= cezaSinirla($m["uzun_ayrilik"] * 15, 35);
    $puan -= cezaSinirla($m["kameradan_ayrilma"] * 6, 30);
    $puan -= cezaSinirla($m["etrafa_odaklanma"] * 9, 26);
    $puan -= cezaSinirla($m["etrafa_bakma"] * 4.5, 22);
    $puan -= cezaSinirla($m["goz_kapali"] * 5.5, 18);
    $puan -= cezaSinirla($m["esneme"] * 3, 12);
    $puan -= cezaSinirla($m["duraklatma_sayisi"] * 8, 24);
    $puan -= cezaSinirla($m["sekme_degisim"] * 12, 36);

    $molaOrani = $m["mola_dk"] / $sure;
    $puan -= cezaSinirla(($m["mola_dk"] * 1.8) + ($molaOrani * 24), 24);

    $toplamDikkatIhlali =
        $m["etrafa_bakma"] +
        $m["etrafa_odaklanma"] +
        $m["goz_kapali"] +
        $m["esneme"];

    if ($toplamDikkatIhlali >= 5) {
        $puan -= min(14, ($toplamDikkatIhlali - 4) * 2.5);
    }

    return skorClamp($puan);
}

function odakOnerileriUret(array $veriler, int $mod_id, int $skor): array
{
    $oneriler = [];
    $m = temelMetrikleriAl($veriler);
    $sure = $m["sure_dk"];

    
    if ($m["kullanici_sonlandirdi"] === 1) {
        oneriyiEkle(
            $oneriler,
            "Oturum Disiplini",
            "Yuksek",
            "Oturum kullanıcı tarafından bitirildi",
            "Oturum planlanan akış tamamlanmadan kullanıcı tarafından sonlandırılmış görünüyor.",
            "Süre uzun gelmiş, hedef net olmamış veya motivasyon düşmüş olabilir.",
            "Bir sonraki oturumda daha kısa süre seç ve başlamadan önce tek hedef belirle.",
            95
        );
    }

    
    if ($skor < 55) {
        oneriyiEkle(
            $oneriler,
            "Genel Degerlendirme",
            "Yuksek",
            "Odak performansı belirgin şekilde düşük",
            "Bu oturumda birden fazla dikkat ve disiplin problemi birlikte görülmüş.",
            "Ortam, süre veya çalışma hedefi aynı anda zorlayıcı olmuş olabilir.",
            "Bir sonraki oturumda süreyi kısalt, telefonu uzaklaştır ve tek görevle başla.",
            90
        );
    } elseif ($skor < 70) {
        oneriyiEkle(
            $oneriler,
            "Genel Degerlendirme",
            "Orta",
            "Odak geliştirilebilir seviyede",
            "Oturum tamamen verimsiz değil ama bölünmeler skoru düşürmüş.",
            "Tekrar eden küçük dikkat kaymaları toplamda etki yaratmış olabilir.",
            "En sık görülen iki davranışı azaltmaya odaklan.",
            65
        );
    }

    
    if ($m["uzun_ayrilik"] >= 1) {
        oneriyiEkle(
            $oneriler,
            "Oturum Disiplini",
            metrikSeviyesi(metrikOran($m["uzun_ayrilik"], $sure), 0.02, 0.06, 0.12),
            "Uzun ayrılık odak akışını bozmuş",
            "Kameradan uzun süre ayrılma oturum bütünlüğünü ciddi etkiler.",
            "Çalışma başlamadan önce ihtiyaçlar hazır olmayabilir.",
            "Su, kalem, defter gibi ihtiyaçları oturumdan önce hazırla.",
            88
        );
    }

   
    if ($m["kameradan_ayrilma"] >= 2) {
        oneriyiEkle(
            $oneriler,
            "Oturum Disiplini",
            metrikSeviyesi(metrikOran($m["kameradan_ayrilma"], $sure), 0.05, 0.10, 0.18),
            "Kameradan ayrılma sık görülmüş",
            "Masada sabit kalmakta zorlanma var.",
            "Çalışma düzeni veya ortam bölünüyor olabilir.",
            "Daha kısa bloklarla çalış ve oturum süresince masada kalmayı hedefle.",
            78
        );
    }

    
    if ($m["sekme_degisim"] >= 1) {
        oneriyiEkle(
            $oneriler,
            "Dijital Dikkat",
            $m["sekme_degisim"] >= 3 ? "Kritik" : "Yuksek",
            "Sekme değişimi odak kaybı yaratmış",
            "Çalışma sırasında farklı sekmelere geçiş dijital dikkat dağınıklığına işaret eder.",
            "Bildirimler, kontrol etme alışkanlığı veya gereksiz sekmeler etkili olabilir.",
            "Oturum öncesi sadece gerekli sekmeleri açık bırak ve bildirimleri kapat.",
            82
        );
    }

    
    if ($m["duraklatma_sayisi"] >= 1) {
        oneriyiEkle(
            $oneriler,
            "Oturum Disiplini",
            $m["duraklatma_sayisi"] >= 3 ? "Kritik" : "Yuksek",
            "Duraklatma oturum ritmini kesmiş",
            "Duraklatmalar odak akışını böler ve geri dönüşü zorlaştırır.",
            "Süre uzun veya çalışma hedefi net olmayabilir.",
            "Daha kısa süre seç ve duraklatmadan tamamlamayı hedefle.",
            75
        );
    }

    
    if ($m["etrafa_odaklanma"] >= 2 || $m["etrafa_bakma"] >= 3) {
        oneriyiEkle(
            $oneriler,
            "Dikkat Daginikligi",
            "Yuksek",
            "Görsel dikkat sık dağılmış",
            "Bakışların çalışma dışı alanlara kaymış.",
            "Ortamda hareket, telefon veya zihinsel dalgınlık olabilir.",
            "Masanı sadeleştir, telefonu görüş alanından çıkar ve tam ekran çalış.",
            70
        );
    }

   
    if ($m["goz_kapali"] >= 2 || $m["esneme"] >= 3) {
        oneriyiEkle(
            $oneriler,
            "Fiziksel Yorgunluk",
            "Orta",
            "Yorgunluk belirtileri görülmüş",
            "Göz kapama veya esneme enerji düşüklüğünü gösterebilir.",
            "Uyku, ortam ışığı veya uzun süre hareketsizlik etkili olabilir.",
            "Oturumdan önce su iç, ortamı aydınlat ve kısa hareket molası yap.",
            60
        );
    }

   
    if (($m["mola_dk"] / $sure) >= 0.15 || $m["mola_dk"] >= 5) {
        oneriyiEkle(
            $oneriler,
            "Mola Yonetimi",
            "Orta",
            "Mola süresi yüksek",
            "Mola süresi oturum verimini düşürmüş olabilir.",
            "Molaların uzaması geri dönüşü zorlaştırır.",
            "Molaya başlamadan önce dönüş süresi belirle.",
            55
        );
    }

   
    if (empty($oneriler)) {
        oneriyiEkle(
            $oneriler,
            "Genel Degerlendirme",
            "Dusuk",
            "Odak dengeli görünüyor",
            "Bu oturumda belirgin bir sorun alanı öne çıkmıyor.",
            "Davranışlar genel olarak kontrollü.",
            $mod_id === 2
                ? "Bu dengeyi koruyorsan yoğun mod süresini yavaşça artırabilirsin."
                : "Bu ritmi koruyarak ders oturumlarını sürdürebilirsin.",
            10
        );
    }

    usort($oneriler, function ($a, $b) {
        return $b["puan"] <=> $a["puan"];
    });

    return array_slice($oneriler, 0, 5);
}

function odakSkoruHesapla(array $veriler): array
{
    $mod_id = (int)($veriler["mod_id"] ?? 0);

    if ($mod_id === 2) {
        $skor = odakSkoruHesaplaYogun($veriler);
    } else {
        $skor = odakSkoruHesaplaYKS($veriler);
    }

    return [
        "skor" => $skor,
        "seviye" => odakSeviyesiBelirle($skor),
        "oneriler" => odakOnerileriUret($veriler, $mod_id, $skor)
    ];
}