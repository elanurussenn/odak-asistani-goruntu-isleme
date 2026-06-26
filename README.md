# Odak Asistanı - Görüntü İşleme Tabanlı Ders Çalışma Odak Analizi

Odak Asistanı, öğrencilerin ders çalışma sürecindeki dikkat ve odak durumlarını analiz etmek için geliştirilmiş web tabanlı bir çalışma takip sistemidir. Proje; kamera görüntüsü üzerinden yüz, göz ve baş hareketlerini analiz eder, çalışma oturumlarını takip eder, odak dışı davranışları kaydeder ve oturum sonunda kullanıcıya odak skoru ile kişiselleştirilmiş öneriler sunar.

Bu sistem, özellikle ders çalışan öğrencilerin verimli çalışma alışkanlıkları kazanmasına yardımcı olmak amacıyla hazırlanmıştır. Kullanıcılar çalışma modu seçebilir, ders bazlı oturum başlatabilir, çalışma süresini takip edebilir ve oturum sonunda performanslarını raporlar üzerinden inceleyebilir.

## Projenin Amacı

Bu projenin amacı, görüntü işleme teknikleri kullanarak öğrencinin çalışma sırasında ne kadar odaklı kaldığını analiz etmektir. Sistem, kullanıcının kamera karşısındaki durumunu takip ederek odak kaybına işaret edebilecek davranışları tespit eder.

Proje kapsamında aşağıdaki durumlar analiz edilir:

* Kullanıcının kamerada görünüp görünmediği
* Yüzün doğru pozisyonda olup olmadığı
* Kullanıcının kameradan ayrılması
* Uzun süre kameradan uzak kalması
* Göz kapalı kalma durumu
* Esneme hareketleri
* Etrafa bakma davranışı
* Çalışma dışı bir noktaya uzun süre odaklanma
* Oturumu duraklatma
* Sekme değiştirme
* Mola süresi ve oturum disiplini

Bu veriler kullanılarak oturum sonunda 0 ile 100 arasında bir odak skoru hesaplanır.

## Genel Sistem Yapısı

Proje iki ana bölümden oluşur:

### 1. Web Uygulaması

Web tarafı PHP, MySQL, HTML, CSS, JavaScript ve Bootstrap ile geliştirilmiştir. Kullanıcı kayıt/giriş işlemleri, çalışma modları, ders seçimi, oturum ekranı, bildirimler, blog sayfası, profil yönetimi, planlayıcı ve rapor ekranları bu bölümde yer alır.

### 2. Görüntü İşleme Servisi

Görüntü işleme tarafı Python ve Flask ile geliştirilmiştir. Web uygulamasından gelen kamera kareleri Flask servisine gönderilir. Python tarafında OpenCV ve MediaPipe kullanılarak yüz tespiti, yüz landmark analizi, göz/baş hareketi ve davranış analizi yapılır.

## Kullanılan Teknolojiler

### Web Tarafı

* PHP
* MySQL / MariaDB
* HTML
* CSS
* JavaScript
* Bootstrap
* PDO
* JSON
* MediaDevices / getUserMedia API

### Python ve Görüntü İşleme Tarafı

* Python
* Flask
* Flask-CORS
* OpenCV
* MediaPipe
* NumPy
* Base64 görüntü işleme
* Face Detection
* Face Landmark Analysis

### Geliştirme Ortamı

* XAMPP
* Apache
* MySQL / MariaDB
* phpMyAdmin
* Web tarayıcısı
* Dahili veya harici kamera

## Proje Özellikleri

* Kullanıcı kayıt ve giriş sistemi
* Kullanıcı profil düzenleme
* Avatar seçme
* Şifre değiştirme
* Bildirim sistemi
* Blog ve blog detay sayfaları
* Admin blog yönetimi
* Admin kullanıcı yönetimi
* Planlayıcı / görev ekleme sistemi
* YKS modu
* Yoğun çalışma modu
* Kamera ile başlangıç pozisyon kontrolü
* Gerçek zamanlı odak analizi
* Oturum başlatma, duraklatma ve sonlandırma
* Oturum sonunda odak skoru hesaplama
* Davranış loglarını kaydetme
* Ders bazlı odak raporları
* Kullanıcıya özel öneri üretme
* İstatistik ve raporlama sayfaları

## Çalışma Modları

### YKS Modu

YKS modu, öğrencilerin ders bazlı odak oturumu başlatmasını sağlar. Kullanıcı TYT veya AYT derslerinden birini seçerek çalışma süresini belirler. Oturum boyunca sistem, kullanıcının kamera karşısındaki dikkat durumunu takip eder.

Bu modda amaç, öğrencinin belirli bir derse yönelik çalışma performansını ölçmek ve ders bazlı odak takibi yapmaktır.

### Yoğun Çalışma Modu

Yoğun çalışma modu, daha disiplinli ve kesintisiz çalışma oturumları için tasarlanmıştır. Bu modda odak kaybı, mola süresi, kameradan ayrılma, göz kapalı kalma ve sekme değişimi gibi davranışlar daha hassas şekilde değerlendirilir.

Yoğun mod, özellikle uzun ve dikkat gerektiren çalışma süreçlerinde kullanıcıya daha sıkı bir takip sistemi sunar.

## Görüntü İşleme Mantığı

Sistem, tarayıcı üzerinden kullanıcının kamera görüntüsünü alır. JavaScript tarafında belirli aralıklarla görüntü kareleri yakalanır ve base64 formatına dönüştürülerek Python Flask servisine gönderilir.

Python tarafında görüntü şu adımlardan geçer:

1. Base64 formatındaki görüntü alınır.
2. Görüntü OpenCV ile işlenebilir hale getirilir.
3. MediaPipe ile yüz tespiti yapılır.
4. Yüzün ekrandaki konumu analiz edilir.
5. Face landmark noktaları üzerinden göz, ağız ve baş hareketleri değerlendirilir.
6. Kullanıcının odak dışı davranışları belirlenir.
7. Analiz sonucu JSON olarak web uygulamasına geri gönderilir.

Bu yapı sayesinde web arayüzü ile görüntü işleme servisi birbirinden ayrılmış, daha modüler bir mimari oluşturulmuştur.

## Takip Edilen Davranışlar

Sistem, oturum boyunca kullanıcının çalışma disiplinini etkileyebilecek farklı davranışları takip eder.

### Kameradan Ayrılma

Kullanıcının yüzü kamerada görünmediğinde sistem bunu kameradan ayrılma olarak değerlendirir.

### Uzun Ayrılık

Kullanıcı belirli bir süreden daha uzun süre kamerada görünmezse bu durum uzun ayrılık olarak kaydedilir.

### Göz Kapalı Kalma

Gözlerin belirli süre kapalı kalması, yorgunluk veya uyuklama belirtisi olarak değerlendirilir.

### Esneme

Ağız ve yüz landmark noktaları analiz edilerek esneme davranışı tespit edilir.

### Etrafa Bakma

Kullanıcının bakış yönünün çalışma ekranından sapması etrafa bakma olarak değerlendirilir.

### Etrafa Odaklanma

Kullanıcının sağa veya sola uzun süre odaklanması, çalışma dışı bir noktaya dikkatinin kayması olarak yorumlanır.

### Duraklatma

Oturumun kullanıcı tarafından duraklatılması çalışma akışını etkileyen bir davranış olarak kaydedilir.

### Sekme Değişimi

Çalışma sırasında farklı sekmelere geçilmesi dijital dikkat dağınıklığı göstergesi olarak değerlendirilir.

## Odak Skoru Hesaplama

Oturum sonunda kullanıcının davranışlarına göre 0 ile 100 arasında bir odak skoru hesaplanır. Sistem başlangıç puanını 100 kabul eder. Oturum sırasında gerçekleşen olumsuz davranışlar bu puandan düşülür.

Skoru etkileyen temel değişkenler:

* Çalışma süresi
* Mola süresi
* Kameradan ayrılma sayısı
* Uzun ayrılık sayısı
* Göz kapalı kalma sayısı
* Esneme sayısı
* Etrafa bakma sayısı
* Etrafa odaklanma sayısı
* Duraklatma sayısı
* Sekme değişimi sayısı
* Kullanıcının oturumu erken bitirip bitirmemesi

Skor sonucuna göre kullanıcıya genel bir odak seviyesi atanır:

* 85 - 100: Yüksek
* 70 - 84: İyi
* 55 - 69: Orta
* 35 - 54: Düşük
* 0 - 34: Çok Düşük

## Öneri Mekanizması

Sistem yalnızca skor hesaplamakla kalmaz, kullanıcının oturumdaki davranışlarına göre öneriler de üretir.

Örnek öneri kategorileri:

* Oturum disiplini
* Dijital dikkat
* Dikkat dağınıklığı
* Fiziksel yorgunluk
* Mola yönetimi
* Genel değerlendirme

Örneğin, kullanıcı çok sık sekme değiştirdiyse sistem dijital dikkat dağınıklığına yönelik öneri üretir. Göz kapalı kalma veya esneme sayısı fazlaysa kullanıcıya ortam ışığını artırma, su içme veya kısa hareket molası verme gibi öneriler sunulur.

## Veritabanı Yapısı

Projede kullanıcı bilgileri, oturum kayıtları, loglar, dersler, modlar, bildirimler, blog içerikleri ve planlayıcı görevleri veritabanında tutulur.

Projede kullanılan temel tablolar:

* kullanicilar
* modlar
* dersler
* odak_oturumlari
* odak_loglari
* bildirimler
* bloglar
* blog_begeniler
* kategoriler
* planlayici_gorevler
* iletisim_mesajlari

Veritabanı bağlantısı `config/db.php` dosyası üzerinden yapılmaktadır. Varsayılan veritabanı adı:

```txt
odak_asistani
```

## Proje Dosya Yapısı

```txt
23100011076_ElanurUssen/
│
├── odak_projesi/
│   ├── assets/
│   │   ├── audio/
│   │   ├── css/
│   │   └── img/
│   │
│   ├── config/
│   │   └── db.php
│   │
│   ├── lib/
│   │   ├── bildirim.php
│   │   └── kamera_kontrol.php
│   │
│   ├── partials/
│   │   ├── header.php
│   │   └── footer.php
│   │
│   ├── index.php
│   ├── giris.php
│   ├── uye-ol.php
│   ├── modlar.php
│   ├── yks.php
│   ├── yks_oturum.php
│   ├── yogun.php
│   ├── yogun_oturum.php
│   ├── odak_analiz.php
│   ├── oturum_baslat.php
│   ├── oturum_sonlandir.php
│   ├── oturum_bitti.php
│   ├── skor_hesapla.php
│   ├── log_event.php
│   ├── istatistik.php
│   ├── plan.php
│   ├── profil.php
│   ├── bildirimler.php
│   ├── blog.php
│   ├── blog-detay.php
│   ├── admin_panel.php
│   ├── admin_blog.php
│   └── admin_kullanicilar.php
│
└── PYTHON/
    ├── app.py
    ├── modeller/
    │   ├── blaze_face_short_range.tflite
    │   └── face_landmarker.task
    │
    └── modes/
        ├── baslangic_pos_ayarla.py
        ├── yks_mode.py
        └── yogun_mode.py
```

## Flask API Uç Noktaları

Python tarafında Flask servisi aşağıdaki uç noktaları kullanır:

| Endpoint                | Açıklama                                              |
| ----------------------- | ----------------------------------------------------- |
| `/`                     | Flask servisinin çalışıp çalışmadığını kontrol eder   |
| `/baslangic_pos_ayarla` | Kullanıcının başlangıç kamera pozisyonunu analiz eder |
| `/analiz`               | YKS veya Yoğun moda göre görüntü analizi yapar        |
| `/yks_reset`            | YKS modundaki analiz sayaçlarını sıfırlar             |
| `/yogun_reset`          | Yoğun moddaki analiz sayaçlarını sıfırlar             |

## Kurulum

### 1. Web Projesini Çalıştırma

Projeyi XAMPP içerisindeki `htdocs` klasörüne yerleştirin.

```txt
C:/xampp/htdocs/odak_projesi
```

XAMPP üzerinden Apache ve MySQL servislerini başlatın.

Ardından `config/db.php` dosyasındaki veritabanı bilgilerini kendi sisteminize göre düzenleyin.

```php
$DB_HOST = "localhost";
$DB_NAME = "odak_asistani";
$DB_USER = "root";
$DB_PASS = "";
```

Tarayıcıdan projeyi açın:

```txt
http://localhost/odak_projesi
```

### 2. Python Servisini Çalıştırma

Python tarafındaki klasöre girin:

```bash
cd PYTHON
```

Gerekli Python kütüphanelerini yükleyin:

```bash
pip install flask flask-cors opencv-python mediapipe numpy
```

Flask servisini başlatın:

```bash
python app.py
```

Servis varsayılan olarak şu adreste çalışır:

```txt
http://127.0.0.1:5000
```

Web uygulaması kamera görüntüsünü analiz etmek için bu servise istek gönderir. Bu nedenle odak analizi yapmadan önce Python servisinin açık olması gerekir.

## Kullanım Akışı

1. Kullanıcı sisteme kayıt olur veya giriş yapar.
2. Ana sayfadan çalışma modları ekranına geçer.
3. YKS modu veya Yoğun çalışma modu seçilir.
4. YKS modunda ders ve çalışma süresi belirlenir.
5. Kamera kontrol ekranında yüz pozisyonu kontrol edilir.
6. Sistem, kullanıcının uygun konumda olup olmadığını bildirir.
7. Oturum başlatılır.
8. Oturum boyunca kamera tabanlı odak analizi yapılır.
9. Odak dışı davranışlar loglanır.
10. Oturum tamamlandığında odak skoru hesaplanır.
11. Kullanıcıya skor, seviye ve öneriler gösterilir.
12. Kullanıcı geçmiş oturumlarını raporlar ve istatistikler üzerinden inceleyebilir.

## Raporlama ve İstatistikler

Proje, kullanıcının geçmiş çalışma oturumlarını analiz edebilmesi için raporlama ekranları içerir. Kullanıcı, ders bazlı performansını, odak skorlarını ve çalışma alışkanlıklarını sistem üzerinden takip edebilir.

Raporlama tarafında şu bilgiler değerlendirilebilir:

* Toplam çalışma süresi
* Oturum sayısı
* Ders bazlı performans
* Odak skoru
* Odak seviyesi
* Kameradan ayrılma sayısı
* Göz kapalı kalma ve esneme gibi yorgunluk belirtileri
* Dikkat dağınıklığı metrikleri
* Sistem tarafından üretilen öneriler

## Gizlilik Notu

Sistem kamera görüntüsünü odak analizi için kullanır. Projenin mevcut yapısında amaç video kaydı almak değil, görüntü kareleri üzerinden anlık analiz yaparak skor ve davranış metrikleri üretmektir.

Kullanıcıya ait oturum sonuçları, skorlar ve davranış logları veritabanına kaydedilir. Kamera kullanımı nedeniyle sistemin güvenilir ve şeffaf şekilde geliştirilmesi önemlidir.

## Sistemin Sınırlılıkları

Bu proje kamera tabanlı görüntü işleme yöntemleri kullandığı için bazı durumlarda analiz doğruluğu etkilenebilir.

Analizi etkileyebilecek durumlar:

* Ortam ışığının yetersiz olması
* Kameranın düşük çözünürlüklü olması
* Kullanıcının yüzünün kameraya dönük olmaması
* Yüzün kısmen kapanması
* Gözlük kullanımı
* Kamerada birden fazla kişinin görünmesi
* Ani ışık değişimleri
* Kullanıcının kamera açısından çıkması

Bu nedenle sistem, temel işlevleri çalışan bir prototip olarak değerlendirilmelidir. Daha geniş kullanıcı verileriyle test edilerek daha hassas hale getirilebilir.

## Geliştirilebilir Özellikler

* Mobil uyumluluk geliştirilebilir.
* Odak skoru algoritması daha fazla veriyle iyileştirilebilir.
* Öneri sistemi daha kişiselleştirilmiş hale getirilebilir.
* Kullanıcıların uzun dönemli gelişimi grafiklerle gösterilebilir.
* Daha fazla çalışma modu eklenebilir.
* Bildirim sistemi geliştirilebilir.
* Gerçek zamanlı uyarılar daha akıllı hale getirilebilir.
* Yapay zekâ tabanlı daha gelişmiş odak sınıflandırması eklenebilir.
* Admin paneli daha kapsamlı hale getirilebilir.
* Veritabanı için hazır SQL kurulum dosyası eklenebilir.

## Projenin Hedef Kitlesi

Bu proje özellikle aşağıdaki kullanıcılar için uygundur:

* YKS ve sınavlara hazırlanan öğrenciler
* Ders çalışma süresini takip etmek isteyen kullanıcılar
* Odaklanma problemi yaşayan bireyler
* Verimli çalışma alışkanlığı kazanmak isteyen öğrenciler
* Eğitim teknolojileri alanında çalışma yapmak isteyen geliştiriciler
* Görüntü işleme ve web entegrasyonu üzerine proje geliştiren öğrenciler

## Sonuç

Odak Asistanı, web tabanlı bir çalışma takip sistemi ile Python tabanlı görüntü işleme servisinin birlikte kullanıldığı kapsamlı bir projedir. Sistem; kullanıcının çalışma sürecini takip eder, kamera üzerinden odak dışı davranışları analiz eder, oturum sonunda odak skoru hesaplar ve kullanıcıya gelişim önerileri sunar.

Bu yönüyle proje yalnızca süre tutan basit bir çalışma uygulaması değildir. Kullanıcının davranışlarını analiz eden, ders bazlı performans takibi yapabilen ve öneri mekanizmasıyla çalışma verimliliğini artırmayı hedefleyen akıllı bir odak asistanı olarak tasarlanmıştır.

## Lisans

Bu proje eğitim amacıyla geliştirilmiştir.
