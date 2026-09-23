<?php
/*!
 * ============================================================
 *  Voxnup Not Laravel (VNL) — Action Kimlik Kapısı
 *  Action'ların giriş ve yetki denetimi.
 *  Authors: Voxnup Authors
 * ============================================================
 *
 * PROJENİNDİR: bu dosya kökte durur, güncelleme ona dokunmaz. "Giriş
 * yapılmış mı" ve "yetkisi var mı" sorularının cevabı yine de çekirdeğindir —
 * vnl_logged_in() ve vnl_can() sorulur, sayfa ile action aynı ölçütü
 * kullansın diye.
 *
 * 3.4.0: dağıtıcı (VnlDispatcher) girişi VARSAYILAN olarak zorunlu kılar;
 * PUBLIC_METHODS'ta olmayan metoda girişsiz istek hiç ulaşmaz. Yine de her
 * action'ın ilk satırı yetki denetimidir:
 *
 *   Auth::CheckPermission('Domains', 'edit');
 */

namespace ActionGlobal;

class Auth
{

    /**
     * Aktif $CurrentUser nesnesi.
     *
     * DİKKAT: bu global her istekte KOŞULSUZ kurulur
     * (config/database.php'deki `new User()`), yani girişsiz istekte de
     * elde boş bir model durur — null DEĞİLDİR. "Giriş yapılmış mı"
     * sorusunu buna bakarak yanıtlamayın; CheckLogin() ya da
     * vnl_logged_in() kullanın. Girişsizde null isteyen için: vnl_user().
     */
    public static function User()
    {
        global $CurrentUser;

        return $CurrentUser;
    }

    public static function UserPermis()
    {
        return self::User()->perm_id;
    }

    /**
     * Giriş zorunluluğu. Girişsizse hata fırlatır ve isteği durdurur.
     *
     * VNL 3.1'e kadar ölçüt `!empty(self::User())` idi ve HER ZAMAN true
     * dönüyordu. 3.2'de ölçüt kimliğin kendisi oldu.
     *
     * 3.4.0: durum kodu 401 (VNL_AUTH). 3.2'de 400'de bırakılmıştı çünkü
     * vnl.js'in 401 dalı girişsiz kullanıcıya hiçbir şey göstermiyordu;
     * 3.4'ün AJAX kancası mesajı gösterip /login'e yönlendiriyor.
     */
    public static function CheckLogin($throwError = true)
    {
        $isLogined = \vnl_logged_in();

        if (!$isLogined && $throwError) \throwErrorMessage(\lang('ERR_LOGIN_FIRST'), 401, 'VNL_AUTH');

        return $isLogined;
    }

    /**
     * Kullanıcının perm_id'si (rol) verilen listede mi? Değilse 403.
     *
     * Girişsiz istek burada da elenir: boş modelin null perm_id'si gevşek
     * karşılaştırmada listedeki 0 ya da '' ile eşleşebiliyordu.
     */
    public static function HasPermis(array $perms, $throwError = true)
    {
        $perm_result = \vnl_logged_in() && in_array(self::User()->perm_id, $perms);

        if (!$perm_result && $throwError) \throwErrorMessage(\lang('ERR_PERM'), 403, 'VNL_PERM');

        return $perm_result;

    }

    /**
     * Kod tabanlı yetki kapısı (3.4.0) — her action'ın ilk satırı.
     *
     *   Auth::CheckPermission('Domains');            // görüntüleme
     *   Auth::CheckPermission('Domains', 'insert');  // insert | edit | delete
     *
     * Girişsiz → 401 VNL_AUTH, yetkisiz → 403 VNL_PERM; ikisi de isteği
     * KOŞULSUZ bitirir (istisna değil: geniş bir try/catch kapıyı yutamaz).
     * Karar vnl_can() → projenin vnl_project_permission() kancası
     * (helpers/permissions.php) üzerinden verilir; menü, sayfa ve action
     * aynı soruyu sorar.
     */
    public static function CheckPermission(string $code, string $ability = 'view'): void
    {
        if (!\vnl_logged_in()) {
            self::deny(\lang('ERR_LOGIN_FIRST'), 401, 'VNL_AUTH');
        }

        if (!\vnl_can($code, $ability)) {
            self::deny(\lang('ERR_PERM'), 403, 'VNL_PERM');
        }
    }

    /** CheckPermission'ın durdurmayan hali: yetki var mı? */
    public static function Can(string $code, string $ability = 'view'): bool
    {
        return \vnl_logged_in() && \vnl_can($code, $ability);
    }

    /** JSON hata yazar ve betiği bitirir (PHPEXCELMODE'dan bağımsız). */
    private static function deny(string $message, int $status, string $devcode): never
    {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        http_response_code($status);
        \vnl_json_header();
        echo json_encode(['message' => $message, 'devcode' => $devcode]);

        exit;
    }
}
