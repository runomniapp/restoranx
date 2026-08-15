# RestoranX — Tezgah Burger

Restoranlar için dijital menü, QR ile masaya sipariş ve canlı sipariş yönetim paneli.
Saf PHP + PDO ile yazıldı; framework veya build adımı gerektirmez.

## Özellikler

**Misafir tarafı**
- Dijital menü: büyük ürün görselleri, görsel üzerinde indirim oranı ve fiyat rozeti
- Ürüne tıklayınca açıklama, alerjen ve kalori bilgisini gösteren detay penceresi
- Tek dokunuşla sepete ekleme, canlı arama ve kategori filtresi
- Üç servis tipi: **masaya servis**, **adrese teslim**, **gel-al paket**
- `qr.php?masa=5` ile gelindiğinde masa numarası otomatik seçilir

**Yönetim paneli**
- Canlı sipariş ekranı; yeni sipariş geldiğinde **alarm sesi**, ekran bildirimi, masaüstü bildirimi ve sayaç rozeti
- Bildirim melodisi `notification/` klasöründen seçilebilir (klasöre atılan yeni dosyalar otomatik listelenir)
- Sipariş durumu akışı: Yeni → Hazırlanıyor → Hazır → Teslim Edildi / İptal
- Ürün, kategori ve kampanya yönetimi; alerjen ve kalori tanımlama
- Ürün / kategori / tüm menü bazında yüzdesel zam aracı ve canlı simülasyon
- QR kod ve masa kartı tasarımcısı, PDF dışa aktarma

## Kurulum

1. Proje klasörünü bir PHP sunucusuna (XAMPP, Laragon, vb.) kopyalayın — PHP 7.4+ ve PDO gerekir.
2. **MySQL kullanacaksanız:** `tezgah_burger` adında bir veritabanı oluşturup `tezgah_burger.sql` dosyasını içe aktarın, ardından `config/db.php` içindeki bağlantı bilgilerini düzenleyin.
3. **MySQL kurmak istemiyorsanız:** hiçbir şey yapmanıza gerek yok — bağlantı kurulamazsa proje otomatik olarak `config/database.sqlite` dosyasına düşer ve tabloları kendisi oluşturur.
4. Tarayıcıdan `index.php` (site), `qr.php` (dijital menü) veya `admin/` (yönetim paneli) adreslerini açın.

## Dizin yapısı

```
admin/            Yönetim paneli sayfaları (inc/ altında ortak header, sidebar, footer)
api/orders.php    Sipariş JSON API'si (create, poll, list, status, seen)
config/db.php     Veritabanı bağlantısı ve tablo kurulumu
includes/         Ortak fonksiyonlar, site başlık/altlık, sipariş penceresi
notification/     Yeni sipariş bildirim melodileri (mp3/wav/ogg/m4a)
public/assets/    CSS, JS ve görseller
qr.php            QR ile açılan dijital menü
```

## Güvenlik notu

Yönetim paneli ve sipariş API'si şu anda **kimlik doğrulaması içermiyor**. Projeyi herkese açık bir sunucuya almadan önce `admin/` dizinini ve `api/orders.php` içindeki `create` dışındaki işlemleri bir oturum kontrolü ile korumanız gerekir.
