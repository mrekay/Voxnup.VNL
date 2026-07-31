<?php
class FileRequest
{
    /**
     * Tüm dosya verilerinin temizlenmiş ve düzenlenmiş halini tutar.
     * Artık $_FILES globaline dışarıdan erişmeye gerek kalmayacak.
     */
    private $files = array();

    function __construct()
    {
        $this->normalize_and_sanitize_files();
    }

    /**
     * PHP'nin karmaşık $_FILES yapısını mantıklı bir formata dönüştürür
     * ve zararlı girdileri temizleyerek $this->files içine hapseder.
     */
    private function normalize_and_sanitize_files()
    {
        if (empty($_FILES)) return;

        foreach ($_FILES as $key => $file) {
            // Eğer çoklu dosya yüklemesi yapılmışsa (name="resimler[]")
            if (is_array($file['name'])) {
                $this->files[$key] = array();
                foreach ($file['name'] as $i => $name) {
                    // Boş gönderilen inputları yoksay
                    if ($file['error'][$i] === UPLOAD_ERR_NO_FILE) continue;

                    $this->files[$key][] = array(
                        'name'     => $this->sanitize_file_name($name),
                        'type'     => $file['type'][$i],
                        'tmp_name' => $file['tmp_name'][$i],
                        'error'    => $file['error'][$i],
                        'size'     => $file['size'][$i]
                    );
                }

                // Eğer dizi boş kaldıysa key'i tamamen sil
                if (empty($this->files[$key])) {
                    unset($this->files[$key]);
                }
            }
            // Tekli dosya yüklemesi yapılmışsa
            else {
                if ($file['error'] !== UPLOAD_ERR_NO_FILE) {
                    $file['name'] = $this->sanitize_file_name($file['name']);
                    $this->files[$key] = $file;
                }
            }
        }
    }

    /**
     * Dosya ismini XSS, Null Byte ve Path Traversal saldırılarına karşı temizler.
     */
    private function sanitize_file_name($name)
    {
        $name = str_replace(chr(0), '', $name); // Null Byte
        $name = basename($name); // Dizin atlama (Path Traversal)
        $name = preg_replace("/[^a-zA-Z0-9\._-]/", "", $name); // Sadece güvenli karakterler
        return $name;
    }

    /**
     * İlgili key'e ait dosya verisini döndürür. Çoklu yüklemeyse dizi döner.
     */
    public function get($key)
    {
        return isset($this->files[$key]) ? $this->files[$key] : null;
    }

    /**
     * Sınıfa yüklenmiş tüm dosyaları (temizlenmiş ve düzenlenmiş haliyle) döndürür.
     */
    public function all()
    {
        return $this->files;
    }

    /**
     * İlgili key ile bir dosya yüklenip yüklenmediğini kontrol eder.
     */
    public function has($key)
    {
        return isset($this->files[$key]);
    }

    /**
     * Yüklenen dosyanın çoklu (multiple) olup olmadığını kontrol eder.
     */
    public function isMultiple($key)
    {
        if ($this->has($key)) {
            // Dizi içindeki ilk elemanın key'i sayısal ise bu çoklu yüklemedir.
            reset($this->files[$key]);
            return is_int(key($this->files[$key]));
        }
        return false;
    }

    /**
     * Yüklenen dosyanın sterilize edilmiş orijinal adını döndürür.
     * (Çoklu yüklemelerde ilk dosyanın adını döner)
     */
    public function getName($key, $index = 0)
    {
        if ($this->has($key)) {
            return $this->isMultiple($key) ? $this->files[$key][$index]['name'] : $this->files[$key]['name'];
        }
        return null;
    }

    /**
     * Güvenli, rastgele benzersiz bir isim üretir.
     */
    public function generateRandomName($key, $prefix = '', $index = 0)
    {
        if ($this->has($key)) {
            $ext = $this->getExtension($key, $index);
            return uniqid($prefix, true) . ($ext ? '.' . $ext : '');
        }
        return null;
    }

    public function getTmpName($key, $index = 0)
    {
        if ($this->has($key)) {
            return $this->isMultiple($key) ? $this->files[$key][$index]['tmp_name'] : $this->files[$key]['tmp_name'];
        }
        return null;
    }

    public function getSize($key, $index = 0)
    {
        if ($this->has($key)) {
            return $this->isMultiple($key) ? $this->files[$key][$index]['size'] : $this->files[$key]['size'];
        }
        return 0;
    }

    public function getExtension($key, $index = 0)
    {
        $name = $this->getName($key, $index);
        if ($name) {
            return strtolower(pathinfo($name, PATHINFO_EXTENSION));
        }
        return null;
    }

    /**
     * Sunucu tarafında dosyanın GERÇEK mime türünü okur (Spoofing'e karşı).
     */
    public function getRealMimeType($key, $index = 0)
    {
        $tmpName = $this->getTmpName($key, $index);
        if ($tmpName && file_exists($tmpName)) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mime = finfo_file($finfo, $tmpName);
            finfo_close($finfo);
            return $mime;
        }
        return null;
    }

    public function getError($key, $index = 0)
    {
        if ($this->has($key)) {
            return $this->isMultiple($key) ? $this->files[$key][$index]['error'] : $this->files[$key]['error'];
        }
        return UPLOAD_ERR_NO_FILE;
    }

    public function isUploadedFile($key, $index = 0)
    {
        $tmpName = $this->getTmpName($key, $index);
        if ($tmpName) {
            return is_uploaded_file($tmpName);
        }
        return false;
    }

    /**
     * Yüklenen dosyayı kalıcı hedefe taşır.
     * Hedef tam yol olmalıdır (örneğin: /var/www/uploads/resim.jpg)
     */
    public function move($key, $destination, $index = 0)
    {
        if ($this->isUploadedFile($key, $index) && $this->getError($key, $index) === UPLOAD_ERR_OK) {
            return move_uploaded_file($this->getTmpName($key, $index), $destination);
        }
        return false;
    }
}

?>