<?php

class FileManagerModule
{
    static function IFExistsChangeFileName($Destination, $File)
    {
        if (empty($Destination) || empty($File)) return $File;

        $ext = explode('.', $File);

        if (count($ext) > 1) {
            $temp = array_keys($ext);
            $lastKey = end($temp);
            $extension = end($ext);
            unset($ext[$lastKey]);
            $fileName = implode('.', $ext);
            $fName = $fileName;
            $fileName = $fileName . '.' . $extension;
            $fileCounter = 0;
            while (file_exists($Destination . $fileName)) {
                $fileName = $fName . '-' . $fileCounter . '.' . $extension;
                $fileCounter++;
            }

            return $fileName;

        } else {
            return $File;
        }
    }

    /**
     * Web sunucusunun ÇALIŞTIRABİLECEĞİ ya da tarayıcının aynı kökende
     * BETİK olarak açacağı uzantılar. $Allows ne derse desin reddedilir
     * (3.5.2) — 'all' dahil. Adın HERHANGİ bir parçasında geçmesi yeter:
     * "fatura.php.jpg" yanlış yapılandırılmış Apache'de PHP olarak çalışır.
     */
    public const BLOCKED_EXTENSIONS = [
        'php', 'php3', 'php4', 'php5', 'php7', 'php8', 'phtml', 'pht', 'phar', 'phps', 'pgif',
        'cgi', 'pl', 'py', 'asp', 'aspx', 'jsp', 'shtml', 'shtm',
        'htaccess', 'htpasswd', 'ini',
        'html', 'htm', 'xhtml', 'xht', 'svg', 'svgz',
    ];

    /**
     * Yüklenen dosyanın ADI kabul edilebilir mi? Uzantısız, nokta ile
     * başlayan ve engelli bir uzantıyı herhangi bir parçasında taşıyan ad
     * reddedilir.
     */
    public static function IsSafeUploadName(string $name): bool
    {
        $name = trim(str_replace('\\', '/', $name));
        $name = basename($name);

        if ($name === '' || $name[0] === '.' || !str_contains($name, '.') || str_contains($name, "\0")) {
            return false;
        }

        $parts = explode('.', strtolower($name));
        array_shift($parts);   // dosya adının kendisi

        foreach ($parts as $part) {
            if (in_array(trim($part), self::BLOCKED_EXTENSIONS, true)) {
                return false;
            }
        }

        return end($parts) !== '';
    }

    /**
     * $Allows = 'pdf,xls' ya da 'all'.
     *
     * 3.5.2: 'all' artık "her şey" değil: BLOCKED_EXTENSIONS ve uzantısız /
     * nokta ile başlayan adlar her durumda reddedilir. 3.5.1'e kadar 'all'
     * (varsayılan) .php ve .htaccess kabul ediyordu; files/ altında PHP
     * çalıştığı için bu uzaktan kod çalıştırma demekti. Yine de yalnız
     * gereken uzantıları açıkça yazın: UploadFile($d, $f, 'pdf,png,jpg').
     * */
    static function UploadFile($Destination, $File, $Allows = 'all', $saveNewName = '')
    {
        if (!self::IsSafeUploadName((string)($File['name'] ?? ''))) {
            return '';
        }

        if (@$File['name']) {
            if (!file_exists($Destination)) {
                mkdir($Destination, 0755, true);
            }
            //echo 'p1';
            $FileName = @$File['name'];

            $FileName2 = pathinfo($FileName);
            $NewName = TextGeneralModule::SafeURL($FileName2['filename']);

            if (!empty($saveNewName)) {

                $NewName = TextGeneralModule::SafeURL($saveNewName);
                $NewName = mb_convert_encoding($NewName, "UTF-8");
                $NewName = TextGeneralModule::SafeURL($NewName);
            }
            //print_r($NewName);
            //$NewName= empty($NewName) ? 'belirsiz' :$NewName;

            $Extension = mb_strtolower($FileName2['extension'] ?? '');
            if ($NewName === '' || $NewName === null) {
                $NewName = 'dosya';
            }
            $NewFileName = $NewName . '.' . $Extension;

            $exts = array_map('strtolower', str_replace(' ', '', explode(',', $Allows)));
            if (!self::IsSafeUploadName($NewFileName) || ($Allows != 'all' && !in_array($Extension, $exts, true))) {
                //echo 'p2';
                //echo $tip;
                return '';
            }

//		$fileCounter = 0;
//		while (file_exists($Destination . $NewFileName)) {
//				$NewFileName = $NewName . '_' . $fileCounter . '.' . $Extension;
//				$fileCounter++;
//		}

            $NewFileName = self::IFExistsChangeFileName($Destination, $NewFileName);

            if (move_uploaded_file($File['tmp_name'], $Destination . $NewFileName)) {
                //echo 'p3';
//			echo $Destination . $NewFileName;

                return $NewFileName;
            } else {
                return '';
            }
        }

        return '';
    }

    static function DeleteFile($location)
    {
        if (@unlink($location)) {
            return true;
        }

        return false;
    }

    static function GetDiskSpaceText($disk_alani_mb)
    {

        // Eğer disk alanı 1 GB'den büyükse GB cinsine dönüştür
        if ($disk_alani_mb >= 1024) {
            $disk_alani_gb = $disk_alani_mb / 1024;
            return round($disk_alani_gb, 2) . ' GB';
        } else {
            return round($disk_alani_mb, 2) . ' MB';
        }
    }

}