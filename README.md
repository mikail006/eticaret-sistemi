# 🏫 Gebze Hisar Store - Admin Panel

Modern ve kullanıcı dostu okul e-ticaret yönetim sistemi. Plus Jakarta Sans font ailesi ile tasarlanmış, responsive ve profesyonel admin paneli.

## ✨ Özellikler

### 🎨 **Modern UI/UX Tasarım**
- Plus Jakarta Sans font ailesi
- Temiz ve minimalist arayüz
- Responsive mobil uyumlu tasarım
- Gelişmiş sidebar navigasyon sistemi
- Hover efektleri ve smooth geçişler

### 🔍 **Akıllı Arama Sistemi**
- Anlık arama popup'ı (300x500px)
- Öğrenci, Ürün, Sipariş kategorilerinde arama
- Gerçek zamanlı filtreleme
- Hızlı erişim linkleri

### 🧭 **Navigasyon Sistemi**
- Sağ alt köşe "Navigasyon" butonu (morumsu gradient)
- Hızlı erişim popup'ı
- Sipariş Ara, Öğrenci Ekle, Ürün Ekle seçenekleri
- Üst popup animasyonu

### 📦 **Gelişmiş Ürün Yönetimi**
- **Ürün Bilgileri**: Ad, kod, fiyat, kategori, barkod
- **Türk Lirası Desteği**: Bayrak + TL gösterimi
- **Kategori Sistemi**: Giyim, Kırtasiye, Spor, Aksesuar
- **Hedef Sınıf Seçimi**: Popup ile çoklu seçim
- **Varyasyon Sistemi**: Renk, beden, numara
- **Karakter Sayacı**: 1000 karakterlik açıklama alanı

### 🖼️ **Gelişmiş Resim Yönetimi**
- Drag & Drop yükleme
- Çoklu resim seçimi
- **Ana Resim İşareti**: Kırmızı daire (1. resim)
- **Drag & Drop Sıralama**: Resimleri sürükleyerek sıralama
- **Blurlu Silme Butonu**: Hover'da çöp kutusu iconu
- 120x120px preview boyutu

### 👥 **Sınıf ve Öğrenci Yönetimi**
- Popup ile sınıf seçimi
- Custom checkbox tasarımı
- Seçilen sınıfları tag görünümü
- X ile hızlı silme

### 🎯 **Varyasyon Sistemi**
- Renk, Beden, Numara seçenekleri
- Custom varyasyon ekleme
- Tag görünümü ile seçilenleri gösterme
- X ile hızlı silme

### 🔔 **Bildirim Sistemi**
- Alttan 100px yukarıda popup
- Success, Error, Warning türleri
- Renkli iconlar (yeşil ✓, kırmızı ✗, sarı ⚠)
- Tıklayarak kapatma

## 🛠️ Teknoloji Stack

- **Backend**: PHP 8.1+
- **Database**: MySQL/PostgreSQL
- **Frontend**: HTML5, CSS3, JavaScript (ES6+)
- **Font**: Plus Jakarta Sans (Google Fonts)
- **Icons**: Custom SVG icon set
- **Upload**: Multiple file upload with drag & drop
- **Responsive**: Mobile-first approach

## 📁 Proje Yapısı

gebze-hisar-store-admin/
├── 📄 add_product.php          # Ana ürün ekleme sayfası
├── 📄 config.php               # Veritabanı yapılandırması  
├── 📄 admin_login.php          # Admin giriş sistemi
├── 📄 admin_orders.php         # Sipariş yönetimi
├── 📄 student_management.php   # Öğrenci yönetimi
├── 📄 index.php                # Dashboard/Pano
├── 📁 uploads/                 # Yüklenen resimler
├── 📄 README.md                # Bu dokümantasyon
└── 📄 .gitignore               # Git ignore dosyası

## 🚀 Kurulum

### 1. Sistem Gereksinimleri
- PHP 8.1+
- MySQL 5.7+ / PostgreSQL 12+
- Apache/Nginx web server
- 2GB+ RAM
- 10GB+ disk alanı

### 2. Projeyi Klonla
git clone https://github.com/kullanici-adi/gebze-hisar-store-admin.git
cd gebze-hisar-store-admin

### 3. Veritabanı Kurulumu
-- config.php dosyasını düzenle
$host = 'localhost';
$dbname = 'gebze_hisar_db';
$username = 'your_username';  
$password = 'your_password';

### 4. Dosya İzinleri
chmod 755 uploads/
chmod 644 *.php

### 5. Web Server Yapılandırması
Apache `.htaccess` veya Nginx yapılandırması gerekebilir.

## 💻 Kullanım

### Admin Panel Erişimi
1. `/admin_login.php` sayfasından giriş yapın
2. Dashboard'da sistem özetini görüntüleyin
3. Sol menüden istediğiniz bölüme geçin

### Ürün Ekleme Süreci
1. **Ürün Bilgileri**: Ad, kod, fiyat girin
2. **Kategori Seçimi**: Dropdown'dan kategori seçin  
3. **Sınıf Seçimi**: "Sınıf Ekle" ile popup açın
4. **Varyasyonlar**: Renk, beden, numara ekleyin
5. **Resimler**: Drag & drop ile yükleyin, sıralayın
6. **Kaydetme**: "Ürünü Ekle" ile işlemi tamamlayın

## 🎨 Tasarım Sistemi

### Renkler
- **Ana Renk**: `#050E1A` (Koyu lacivert)
- **Arka Plan**: `#F3F3F5` (Açık gri)
- **Beyaz**: `#FFFFFF` (Kartlar, popup'lar)
- **Başarı**: `#22c55e` (Yeşil)  
- **Hata**: `#ef4444` (Kırmızı)
- **Uyarı**: `#f59e0b` (Turuncu)

### Tipografi
- **Font**: Plus Jakarta Sans
- **Başlık**: 700 weight
- **Metin**: 500 weight
- **Alt Başlık**: 600 weight

### Boyutlar
- **Sidebar**: 300px genişlik
- **Form**: 1075px max genişlik  
- **Popup**: 600px max genişlik
- **Resim Preview**: 120x120px
- **Border Radius**: 12px standart

## 🔧 Özelleştirme

### Yeni Kategori Ekleme
// add_product.php dosyasında
$categories = ['Giyim', 'Kırtasiye', 'Spor', 'Aksesuar', 'YENİ_KATEGORİ'];

### Yeni Sınıf Ekleme
// Sınıf listesine ekle
$classes = [..., 'Yeni Sınıf'];

### Renk Teması Değiştirme
CSS değişkenlerini `style` bölümünde güncelleyin.

## 📱 Mobil Uyumluluk

- **Responsive Grid**: Otomatik kolon ayarlaması
- **Mobil Menü**: Sol sidebar mobilde gizli
- **Touch Events**: Dokunma optimizasyonu  
- **Viewport**: Meta tag ile optimize
- **Navigation**: Alt sabit navigasyon butonu

## 🔒 Güvenlik Özellikleri

- **SQL Injection**: PDO prepared statements
- **XSS Protection**: Sanitized input/output
- **File Upload**: Güvenli dosya yükleme
- **Session Management**: Secure session handling
- **Form Validation**: Client & server side

## 🚦 Performans

- **Lazy Loading**: Resim yükleme optimizasyonu
- **Minified CSS**: Optimize edilmiş stil dosyaları
- **Efficient DB**: Index'lenmiş sorgular
- **Caching**: Browser cache headers

## 📋 TODO / Gelecek Özellikler

- [ ] **Dark Mode**: Karanlık tema desteği
- [ ] **Toplu İşlemler**: Çoklu ürün seçimi
- [ ] **Export/Import**: Excel entegrasyonu  
- [ ] **Loglama**: Sistem aktivite kayıtları
- [ ] **API**: RESTful API endpoints
- [ ] **Bildirimleri**: Real-time notifications
- [ ] **Raporlama**: Satış ve envanter raporları
- [ ] **Multi-dil**: Çoklu dil desteği

## 🤝 Katkıda Bulunma

1. Bu repo'yu fork edin
2. Feature branch oluşturun (`git checkout -b feature/yeni-ozellik`)
3. Değişikliklerinizi commit edin (`git commit -am 'Yeni özellik eklendi'`)
4. Branch'i push edin (`git push origin feature/yeni-ozellik`)
5. Pull Request oluşturun

## 📄 Lisans

Bu proje MIT lisansı altında lisanslanmıştır. Detaylar için `LICENSE` dosyasını inceleyiniz.

## 👨‍💻 Geliştirici

**Proje Ekibi** - Gebze Hisar Store Admin Panel
- Modern UI/UX tasarım
- Full-stack development
- Performans optimizasyonu

## 📞 İletişim ve Destek

Sorularınız için:
- 📧 Email: destek@gebzehisarstore.com  
- 🐛 Issues: GitHub Issues bölümü
- 📖 Wiki: Detaylı dokümantasyon

---

⭐ **Bu projeyi beğendiyseniz yıldızlamayı unutmayın!**
