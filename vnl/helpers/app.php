<?php

/**
 * Kademeli dosya çözümlemesi: önce proje, bulunamazsa çekirdek.
 *
 * GÖRÜNÜMLER VE DENETLEYİCİLER PROJENİNDİR — çekirdek tek bir sayfa bile
 * taşımaz, 401/404 dahil. Her proje düzenini nasılsa kendine göre kuruyor;
 * herkesin ezdiği bir "çekirdek varsayılanı" sahte sahiplik olurdu.
 *
 * Mekanizma yine de burada duruyor: eklentiler kendi görünümlerini
 * getirebilir ve sınıf/modül/dil tarafında sınır zaten işliyor.
 *
 * Hiçbirinde yoksa PROJE yolu döner; çağıran taraf zaten file_exists ile
 * bakıyor ve hata mesajında görmek isteyeceği yol kendi tarafındaki olur.
 */
function vnl_resolve(string $relative): string
{
	$relative = '/' . ltrim($relative, '/');

	foreach ([VNL_ROOT, VNL_PATH] as $base) {
		if (is_file($base . $relative)) {
			return $base . $relative;
		}
	}

	return VNL_ROOT . $relative;
}

/** Kademeli çözümlemenin dizin karşılığı: gruplu denetleyiciler için. */
function vnl_resolve_dir(string $relative): ?string
{
	$relative = '/' . ltrim($relative, '/');

	foreach ([VNL_ROOT, VNL_PATH] as $base) {
		if (is_dir($base . $relative)) {
			return $base . $relative;
		}
	}

	return null;
}

function controller($controllerName)
{
	return vnl_resolve('/controller/' . $controllerName . '.php');
}

function view($viewName)
{
	/* setting('theme') tanımlıysa görünümler view/<tema>/ altındadır. Tanımsız
	   olduğunda eskiden yola boş bir parça giriyor ve "view//index.php" gibi
	   çift eğik çizgili yollar oluşuyordu. */
	$theme = setting('theme');
	$prefix = $theme ? $theme . '/' : '';

	return VnlTemplate::resolve(vnl_resolve('/view/' . $prefix . $viewName . '.php'));
}

function modal($modalName, $directory = null)
{
	$path = '/view/includes/modals/' . ($directory ? $directory . '/' : '') . $modalName . '.php';

	return VnlTemplate::resolve(vnl_resolve($path));
}

function route($index)
{
	global $route;

	return isset($route[$index]) ? $route[$index] : false;
}

/* ------------------------------------------------------------------ *
 *  Kimlik ve yetki
 * ------------------------------------------------------------------ */

/**
 * Oturum açılmış mı?
 *
 * $CurrentUser her istekte KOŞULSUZ kurulur (config/database.php'deki
 * `new User()`); girişsiz istekte bile elde boş bir model durur. PHP'de
 * nesne asla empty olmadığı için `!empty($CurrentUser)` bir giriş denetimi
 * DEĞİLDİR — her zaman doğru döner. Tek geçerli ölçüt kimliğin kendisidir;
 * vnl/kernel.php girişsizi ($CurrentUser->id <= 0) zaten böyle tanıyor,
 * bu fonksiyon aynı ölçütü tek isim altında toplar.
 */
function vnl_logged_in(): bool
{
	global $CurrentUser;

	return isset($CurrentUser) && !empty($CurrentUser->id) && (int)$CurrentUser->id > 0;
}

/**
 * Oturum açmış kullanıcı — girişsizde null.
 *
 * $CurrentUser ile farkı boşluğun ayıklanmış olmasıdır: vnl_user()?->id
 * yazan kod girişsiz istekte kimlik yerine null görür, boş bir model değil.
 */
function vnl_user()
{
	global $CurrentUser;

	return vnl_logged_in() ? $CurrentUser : null;
}

/**
 * Yetki kapısı — menü ve sayfa aynı soruyu buradan sorar.
 *
 * Yetki ŞEMASI projenindir: VNL'in tbl_permissions'ı bir örnektir, gerçek
 * kurulumlar rol, kiracı, ürün gibi kendi eksenlerini katar. O yüzden
 * çekirdek burada karar vermez, PROJEYE SORAR:
 *
 *   // helpers/yetki.php  —  vnl_load_dir('helpers') kendiliğinden yükler
 *   function vnl_project_permission(string $code, $user): bool
 *   {
 *       return $user !== null && UserPermissions::CanViewByCode($user->id, $code);
 *   }
 *
 * Proje böyle bir fonksiyon TANIMLAMADIYSA kapı açıktır ve davranış VNL 3.1
 * ile birebir aynıdır — yeni bir çekirdek sürümü kimsenin menüsünü
 * boşaltmaz. Tanımlandığı anda kenar çubuğu onu her menü öğesi için çağırır.
 *
 * Kanca istisna fırlatırsa yutulmaz: sessizce "yetkisi yok" saymak, yetki
 * sorgusundaki bir hatayı boş bir menüye çevirip saklardı.
 */
function vnl_can(?string $permission): bool
{
	if ($permission === null || trim($permission) === '') {
		return true;
	}

	if (!function_exists('vnl_project_permission')) {
		return true;
	}

	return (bool)vnl_project_permission($permission, vnl_user());
}

/**
 * Uygulama içi tam adres.
 *
 * VNL 3.0'a kadar burada tanımsız bir URL sabiti kullanılıyordu; site_url()
 * ve public_url() çağrıldıkları anda ölümcül hata veriyordu. Çağıran
 * olmadığı için fark edilmemişti.
 */
function site_url($url = '')
{
	return rtrim(SITE_URL, '/') . '/' . ltrim((string)$url, '/');
}

/** PROJENİN kendi varlıkları: assets/ altı. */
function public_url($url = '')
{
	global $cDefinitions;

	return rtrim($cDefinitions->getAssetsUrl(), '/') . '/' . ltrim((string)$url, '/');
}

/** VNL'İN varlıkları: vnl/assets/ altı. Proje bunları barındırmaz, taşır. */
function vnl_asset($url = '')
{
	return rtrim(VNL_ASSETS_URL, '/') . '/' . ltrim((string)$url, '/');
}

/**
 * Çekirdeğin yüklenmesi gereken stil dosyaları, doğru sırayla.
 *
 * Görünümler projenindir — head-standard.php'yi VNL güncellemesi değiştirmez.
 * Ama HANGİ dosyaların yükleneceği çekirdeğin bilgisidir: VNL 3.1 yeni bir
 * çekirdek CSS'i eklerse liste burada değişir ve her projeye kendiliğinden
 * ulaşır. Aksi halde her türev projenin <head>'ini elle düzeltmek gerekirdi.
 *
 * Sıra rastgele değil: skin, vnl.min.css'in üstüne biner; vnltable de
 * renklerini skin'den sonra okur.
 */
function vnl_core_styles(?string $skin = null): array
{
	$styles = [vnl_asset('theme/css/vnl.min.css')];

	if (!empty($skin)) {
		$styles[] = vnl_asset('theme/css/skins/' . basename($skin) . '.min.css');
	}

	$styles[] = vnl_asset('theme/css/bi.min.css');
	$styles[] = vnl_asset('theme/css/fa.min.css');
	$styles[] = vnl_asset('css/vnltable.css');
	$styles[] = vnl_asset('css/vnl-date.css');

	return $styles;
}

/**
 * Çekirdeğin script'leri, doğru sırayla. @see vnl_core_styles()
 *
 * flatpickr, VNL Date'in taşıyıcısıdır ve vnl-date.js ondan sonra gelmek
 * zorundadır. Tema paketine (vendors.min.js) konmadı: o dosya VNL-THEME
 * deposunda derlenir, çekirdek sürümü tema derlemesine bağlanmasın.
 */
function vnl_core_scripts(): array
{
	return [
		vnl_asset('theme/js/vendors.min.js'),
		vnl_asset('theme/js/vnl.min.js'),
		vnl_asset('js/vnl.js'),
		vnl_asset('js/vnltable.js'),
		vnl_asset('js/flatpickr.min.js'),
		vnl_asset('js/vnl-date.js'),
	];
}

function setting($name)
{
	global $settings;

	return isset($settings[$name]) ? $settings[$name] : false;
}

function permalink($str, $options = array())
{
	$str = mb_convert_encoding((string)$str, 'UTF-8', mb_list_encodings());
	$defaults = array(
		'delimiter'     => '-',
		'limit'         => null,
		'lowercase'     => true,
		'replacements'  => array(),
		'transliterate' => true
	);
	$options = array_merge($defaults, $options);
	$char_map = array(
		// Latin
		'À' => 'A', 'Á' => 'A', 'Â' => 'A', 'Ã' => 'A', 'Ä' => 'A', 'Å' => 'A', 'Æ' => 'AE', 'Ç' => 'C',
		'È' => 'E', 'É' => 'E', 'Ê' => 'E', 'Ë' => 'E', 'Ì' => 'I', 'Í' => 'I', 'Î' => 'I', 'Ï' => 'I',
		'Ð' => 'D', 'Ñ' => 'N', 'Ò' => 'O', 'Ó' => 'O', 'Ô' => 'O', 'Õ' => 'O', 'Ö' => 'O', 'Ő' => 'O',
		'Ø' => 'O', 'Ù' => 'U', 'Ú' => 'U', 'Û' => 'U', 'Ü' => 'U', 'Ű' => 'U', 'Ý' => 'Y', 'Þ' => 'TH',
		'ß' => 'ss',
		'à' => 'a', 'á' => 'a', 'â' => 'a', 'ã' => 'a', 'ä' => 'a', 'å' => 'a', 'æ' => 'ae', 'ç' => 'c',
		'è' => 'e', 'é' => 'e', 'ê' => 'e', 'ë' => 'e', 'ì' => 'i', 'í' => 'i', 'î' => 'i', 'ï' => 'i',
		'ð' => 'd', 'ñ' => 'n', 'ò' => 'o', 'ó' => 'o', 'ô' => 'o', 'õ' => 'o', 'ö' => 'o', 'ő' => 'o',
		'ø' => 'o', 'ù' => 'u', 'ú' => 'u', 'û' => 'u', 'ü' => 'u', 'ű' => 'u', 'ý' => 'y', 'þ' => 'th',
		'ÿ' => 'y',
		// Latin symbols
		'©' => '(c)',
		// Greek
		'Α' => 'A', 'Β' => 'B', 'Γ' => 'G', 'Δ' => 'D', 'Ε' => 'E', 'Ζ' => 'Z', 'Η' => 'H', 'Θ' => '8',
		'Ι' => 'I', 'Κ' => 'K', 'Λ' => 'L', 'Μ' => 'M', 'Ν' => 'N', 'Ξ' => '3', 'Ο' => 'O', 'Π' => 'P',
		'Ρ' => 'R', 'Σ' => 'S', 'Τ' => 'T', 'Υ' => 'Y', 'Φ' => 'F', 'Χ' => 'X', 'Ψ' => 'PS', 'Ω' => 'W',
		'Ά' => 'A', 'Έ' => 'E', 'Ί' => 'I', 'Ό' => 'O', 'Ύ' => 'Y', 'Ή' => 'H', 'Ώ' => 'W', 'Ϊ' => 'I',
		'Ϋ' => 'Y',
		'α' => 'a', 'β' => 'b', 'γ' => 'g', 'δ' => 'd', 'ε' => 'e', 'ζ' => 'z', 'η' => 'h', 'θ' => '8',
		'ι' => 'i', 'κ' => 'k', 'λ' => 'l', 'μ' => 'm', 'ν' => 'n', 'ξ' => '3', 'ο' => 'o', 'π' => 'p',
		'ρ' => 'r', 'σ' => 's', 'τ' => 't', 'υ' => 'y', 'φ' => 'f', 'χ' => 'x', 'ψ' => 'ps', 'ω' => 'w',
		'ά' => 'a', 'έ' => 'e', 'ί' => 'i', 'ό' => 'o', 'ύ' => 'y', 'ή' => 'h', 'ώ' => 'w', 'ς' => 's',
		'ϊ' => 'i', 'ΰ' => 'y', 'ϋ' => 'y', 'ΐ' => 'i',
		// Turkish
		'Ş' => 'S', 'İ' => 'I', 'Ç' => 'C', 'Ü' => 'U', 'Ö' => 'O', 'Ğ' => 'G',
		'ş' => 's', 'ı' => 'i', 'ç' => 'c', 'ü' => 'u', 'ö' => 'o', 'ğ' => 'g',
		// Russian
		'А' => 'A', 'Б' => 'B', 'В' => 'V', 'Г' => 'G', 'Д' => 'D', 'Е' => 'E', 'Ё' => 'Yo', 'Ж' => 'Zh',
		'З' => 'Z', 'И' => 'I', 'Й' => 'J', 'К' => 'K', 'Л' => 'L', 'М' => 'M', 'Н' => 'N', 'О' => 'O',
		'П' => 'P', 'Р' => 'R', 'С' => 'S', 'Т' => 'T', 'У' => 'U', 'Ф' => 'F', 'Х' => 'H', 'Ц' => 'C',
		'Ч' => 'Ch', 'Ш' => 'Sh', 'Щ' => 'Sh', 'Ъ' => '', 'Ы' => 'Y', 'Ь' => '', 'Э' => 'E', 'Ю' => 'Yu',
		'Я' => 'Ya',
		'а' => 'a', 'б' => 'b', 'в' => 'v', 'г' => 'g', 'д' => 'd', 'е' => 'e', 'ё' => 'yo', 'ж' => 'zh',
		'з' => 'z', 'и' => 'i', 'й' => 'j', 'к' => 'k', 'л' => 'l', 'м' => 'm', 'н' => 'n', 'о' => 'o',
		'п' => 'p', 'р' => 'r', 'с' => 's', 'т' => 't', 'у' => 'u', 'ф' => 'f', 'х' => 'h', 'ц' => 'c',
		'ч' => 'ch', 'ш' => 'sh', 'щ' => 'sh', 'ъ' => '', 'ы' => 'y', 'ь' => '', 'э' => 'e', 'ю' => 'yu',
		'я' => 'ya',
		// Ukrainian
		'Є' => 'Ye', 'І' => 'I', 'Ї' => 'Yi', 'Ґ' => 'G',
		'є' => 'ye', 'і' => 'i', 'ї' => 'yi', 'ґ' => 'g',
		// Czech
		'Č' => 'C', 'Ď' => 'D', 'Ě' => 'E', 'Ň' => 'N', 'Ř' => 'R', 'Š' => 'S', 'Ť' => 'T', 'Ů' => 'U',
		'Ž' => 'Z',
		'č' => 'c', 'ď' => 'd', 'ě' => 'e', 'ň' => 'n', 'ř' => 'r', 'š' => 's', 'ť' => 't', 'ů' => 'u',
		'ž' => 'z',
		// Polish
		'Ą' => 'A', 'Ć' => 'C', 'Ę' => 'e', 'Ł' => 'L', 'Ń' => 'N', 'Ó' => 'o', 'Ś' => 'S', 'Ź' => 'Z',
		'Ż' => 'Z',
		'ą' => 'a', 'ć' => 'c', 'ę' => 'e', 'ł' => 'l', 'ń' => 'n', 'ó' => 'o', 'ś' => 's', 'ź' => 'z',
		'ż' => 'z',
		// Latvian
		'Ā' => 'A', 'Č' => 'C', 'Ē' => 'E', 'Ģ' => 'G', 'Ī' => 'i', 'Ķ' => 'k', 'Ļ' => 'L', 'Ņ' => 'N',
		'Š' => 'S', 'Ū' => 'u', 'Ž' => 'Z',
		'ā' => 'a', 'č' => 'c', 'ē' => 'e', 'ģ' => 'g', 'ī' => 'i', 'ķ' => 'k', 'ļ' => 'l', 'ņ' => 'n',
		'š' => 's', 'ū' => 'u', 'ž' => 'z'
	);
	$str = preg_replace(array_keys($options['replacements']), $options['replacements'], $str);
	if ($options['transliterate']) {
		$str = str_replace(array_keys($char_map), $char_map, $str);
	}
	$str = preg_replace('/[^\p{L}\p{Nd}]+/u', $options['delimiter'], $str);
	$str = preg_replace('/(' . preg_quote($options['delimiter'], '/') . '){2,}/', '$1', $str);
	$str = mb_substr($str, 0, ($options['limit'] ? $options['limit'] : mb_strlen($str, 'UTF-8')), 'UTF-8');
	$str = trim($str, $options['delimiter']);

	return $options['lowercase'] ? mb_strtolower($str, 'UTF-8') : $str;
}

function session($name)
{
	return isset($_SESSION[$name]) ? $_SESSION[$name] : false;
}


function GetCurrentPageURL()
{
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 'https' : 'http';

    return $protocol . '://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
}

function getSubFolderName(): string
{
    return dirname($_SERVER['SCRIPT_NAME']);
}

/**
 * Proje kökü.
 *
 * Eskiden realpath('.') idi — yani sürecin çalışma dizini. Web'de bu tesadüfen
 * doğruydu; CLI'da ya da chdir() sonrasında değildi. Artık ön denetleyicinin
 * bulunduğu dizin sabitlenmiş durumda.
 */
function getPath(): string
{
    return VNL_ROOT;
}

function throwErrorMessage($errorMessage, $status = 400)
{
    http_response_code($status);
    throw new Exception($errorMessage);
}

/**
 * PHPEXCELMODE hakkında.
 *
 * Aşağıdaki dört yer bu sabite bakıp betiği sonlandırmaktan vazgeçiyor:
 * niyet, Excel çıktısı üretilirken yanıt fonksiyonlarının akışı kesmemesi.
 * Sabiti tanımlayan TEK yer action_datatableMultipleSelect.php idi ve o
 * dosya VNL 3.0'da silindi (DataTables kalıntısıydı, hiçbir yerden
 * çağrılmıyordu). Yani VNL artık bu sabiti hiç tanımlamıyor.
 *
 * Denetimler yine de duruyor: kendi ihracat akışında yanıt fonksiyonunun
 * die etmesini istemeyen bir proje sabiti kendisi tanımlayabilir. VNL'in
 * kendi Excel çıktısı (VnlTableExport) buna ihtiyaç duymaz, kendi exit'ini
 * çağırır.
 */
function returnErrorMessage($message, $error_code = 400,$devcode=""){
    http_response_code($error_code);
    $json = json_encode(['message' => $message, 'devcode' => $devcode]);
    echo $json;
    if (!defined("PHPEXCELMODE"))  die;
}

function returnWarningMessage($message, $code = 406){
    http_response_code($code);
    $json = json_encode(['message' => $message]);
    echo $json;
    if (!defined("PHPEXCELMODE"))  die;
}
function returnInfoMessage($message, $code = 410){
    http_response_code($code);
    $json = json_encode(['message' => $message]);
    echo $json;
    if (!defined("PHPEXCELMODE")) die;
}

function returnSuccessMessage($message, $data = [])
{
    http_response_code(200);
    $json = json_encode(['message' => $message, 'data' => $data]);
    echo $json;
}

function returnSuccessDataOnly($data = [])
{
    http_response_code(200);
    $json = json_encode($data, JSON_NUMERIC_CHECK);
    echo $json;
    if (!defined("PHPEXCELMODE")) die;
}

function returnAjaxError($result, $message, $data)
{
    http_response_code(401);
    $jsondata = array('message' => $message, 'result' => $result, 'data' => $data);
    $json = json_encode($jsondata);
    echo $json;
}
