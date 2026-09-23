# Dil parçaları (tr)

`languages/tr.json`'dan sonra bu dizindeki her `*.json` ad sırasıyla okunur
ve sözlüğe eklenir (VNL 3.4.0). Modül başına bir dosya: `domains.json`.
Biçim ana dosyayla aynıdır (`[ { "ANAHTAR": "metin" } ]` ya da `{ ... }`).
Aynı anahtar iki yerde varsa sonraki dosya kazanır ve günlüğe uyarı düşer.
Her anahtarın `languages/en/` altında da karşılığı olmalıdır.
