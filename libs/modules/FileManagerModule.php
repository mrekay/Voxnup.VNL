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
     * $Allows = 'pdf,xls' or 'all'
     * */
    static function UploadFile($Destination, $File, $Allows = 'all', $saveNewName = '')
    {
        if (@$File['name']) {
            if (!file_exists($Destination)) {
                mkdir($Destination, 0755, true);
            }
            //echo 'p1';
            $FileName = @$File['name'];

            $FileName2 = pathinfo($FileName);
            $NewName = sefUrl($FileName2['filename']);

            if (!empty($saveNewName)) {

                $NewName = sefUrl($saveNewName);
                $NewName = mb_convert_encoding($NewName, "UTF-8");
                $NewName = sefUrl($NewName);
            }
            //print_r($NewName);
            //$NewName= empty($NewName) ? 'belirsiz' :$NewName;

            $Extension = mb_strtolower($FileName2['extension']);
            $NewFileName = $NewName . '.' . $Extension;

            $exts = str_replace(' ', '', explode(',', $Allows));
            if ($Allows != 'all' && !in_array($Extension, $exts)) {
                //echo 'p2';
                //echo $tip;
                return '';
            }

//		$fileCounter = 0;
//		while (file_exists($Destination . $NewFileName)) {
//				$NewFileName = $NewName . '_' . $fileCounter . '.' . $Extension;
//				$fileCounter++;
//		}

            $NewFileName = IFExistsChangeFileName($Destination, $NewFileName);

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