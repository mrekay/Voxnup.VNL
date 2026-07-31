# Proje sınıfları

Bu dizin **projenindir**. Çekirdeğin sınıfları `vnl/classes/` altındadır (Request,
Session, Language, TableModel, VnlTable ailesi).

Buraya bir çekirdek sınıfıyla **aynı adı** verirseniz sizinki geçerli olur ve
çekirdekteki hiç yüklenmez — çekirdeğe dokunmadan ezmenin yolu budur.

Otomatik yükleme dosya adına bakar: `Urun` → `classes/Urun.php`. Alt dizin için ad
alt çizgiyle bölünür: `rapor_Ozet` → `classes/rapor/rapor_Ozet.php`.

## Model sözleşmesi

`TableModel`'i genişletin. **`SelectList` tek soyut metottur** — yazmazsanız sınıf
örneklenemez. `Select()` zaten `TableModel`'den gelir, yeniden yazmayın.

```php
class Urun extends TableModel
{
    use \Illuminate\Database\Eloquent\SoftDeletes;

    protected $table = 'tbl_urunler';
    protected $fillable = ['ad', 'fiyat', 'kategori_id'];

    // Bu bir sütun LİSTESİ DEĞİL, filtre HARİTASIDIR.
    // anahtar = dışarıdan gelen filtre adı, 'name' = gerçek sütun
    protected static $columns = [
        'id'       => ['name' => 'tbl_urunler.id'],
        'ad'       => ['name' => 'tbl_urunler.ad', 'operator' => 'like'],
        'kategori' => ['name' => 'tbl_urunler.kategori_id'],
    ];

    public static function SelectList($filters = [], $extras = [])
    {
        return self::selectListQuery(self::$columns, $filters, $extras)
            ->select('tbl_urunler.*', 'k.ad as kategori_ad')
            ->leftJoin(Kategori::tableName('k'), 'kategori_id', '=', 'k.id');
    }
}
```

`selectListQuery()` **`protected static` ve üç argümanlıdır**; hazır sorgu geçirmeye
çalışmayın, kendi builder'ını kurar ve zincirlemeyi siz devam ettirirsiniz.

## Kullanım

```php
$urun  = Urun::Select(['id' => 42]);                       // anahtar = filtre adı
$liste = Urun::SelectList(['ad' => 'bisiklet'],
                          ['limit' => 20, 'begin' => 0,
                           'order' => 'ad', 'order_by' => 'asc'])->get();
$sayi  = Urun::SelectList($filters)->count();
```

**`->get()` zorunludur** — `SelectList` builder döndürür, veri değil.

`$extras` varsayılanları: `limit -1` (limit uygulanmaz), `begin 0`, `order ''`
(sıralama eklenmez), `order_by 'asc'`.

## Kolay yanlış yapılan üç yer

**Filtre anahtarı `$columns`'ta yoksa istek düşer** (`Geçerli Filtre Gelmedi`).
Bilinçlidir: dışarıdan gelen ad doğrudan sütuna çevrilseydi filtre listesi bir
enjeksiyon yüzeyi olurdu. Değeri boş olan filtreler ise sessizce atılır.

**`order` ham sütun adı değil, `$columns` anahtarıdır.** Orada olmayan bir değer
**sessizce yok sayılır** ve sorgu sırasız kalır — sıralama çalışmıyorsa ilk buraya bakın.

**Sorgular burada durur, action'da değil.** Bkz. `actions/CLAUDE.md`.
