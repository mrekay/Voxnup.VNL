<?php
/*!
 * ============================================================
 *  Voxnup Not Laravel (VNL) — Action Kimlik Kapısı
 *  Action'ların giriş ve yetki denetimi.
 *  Authors: Voxnup Authors
 * ============================================================
 *
 * PROJENİNDİR: bu dosya kökte durur, güncelleme ona dokunmaz. "Giriş
 * yapılmış mı" sorusunun cevabı yine de çekirdeğindir — vnl_logged_in()
 * ile vnl/kernel.php'nin uyguladığı ölçütün aynısı sorulur, iki yer
 * birbirinden kaymasın diye.
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
     * VNL 3.1'e kadar ölçüt `!empty(self::User())` idi: PHP'de bir nesne
     * hiçbir zaman empty olmadığı ve $CurrentUser koşulsuz kurulduğu için
     * denetim HER ZAMAN true dönüyordu — yani hiç denetlemiyordu.
     * Dokümantasyon (/docs/request-handling §5) onu "giriş yapılmamışsa
     * hata fırlatır" diye tarif ettiğinden action'lar bu satıra güveniyordu.
     * Ölçüt artık kimliğin kendisi.
     *
     * Durum kodu 400'de bırakıldı: vnl.js'in 401 dalı oturumu DÜŞMÜŞ
     * kullanıcı için yazılmıştır ve VNL_USER_ID sıfırken hiçbir şey
     * göstermez — girişsiz kullanıcı ekranda sessizlik görürdü.
     */
    public static function CheckLogin($throwError = true)
    {
        $isLogined = vnl_logged_in();

        if (!$isLogined && $throwError) throwErrorMessage(lang('ERR_LOGIN_FIRST'));

        return $isLogined;
    }

    /**
     * Kullanıcının perm_id'si verilen listede mi?
     *
     * Girişsiz istek burada da elenir: boş modelin null perm_id'si gevşek
     * karşılaştırmada listedeki 0 ya da '' ile eşleşebiliyordu.
     */
    public static function HasPermis(array $perms, $throwError = true)
    {
        $perm_result = vnl_logged_in() && in_array(self::User()->perm_id, $perms);

        if (!$perm_result && $throwError) throwErrorMessage(lang('ERR_PERM'));

        return $perm_result;

    }
}
