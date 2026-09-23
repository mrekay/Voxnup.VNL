<?php


/**
 * Doğrulama / iş kuralı hatası.
 *
 * Web isteğinde kurucu JSON hata yanıtını yazar ve isteği bitirir (eski
 * davranış). 3.4.0: komut satırında (kuyruk işi, zamanlayıcı, proje komutu)
 * YANIT YAZILMAZ ve süreç öldürülmez; istisna normal fırlatılır. Aksi halde
 * bir işin içinde kullanılan DefaultValidator süreci çıkış kodu 0 ile
 * bitirir, iş --stale dolana kadar 'running' kalır ve yanıltıcı bir
 * "zaman aşımı" hatasıyla düşerdi.
 */
class StandartException extends Exception
{
	public function __construct($msg,$error = 400)
	{
		parent::__construct($msg);

		if (PHP_SAPI !== 'cli' && PHP_SAPI !== 'phpdbg') {
			returnErrorMessage($msg, $error);
		}
	}

}
