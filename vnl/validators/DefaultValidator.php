<?php

class DefaultValidator
{

    /**
     * Değerin boş olmadığını doğrular.
     *
     * VNL 3.0'a kadar bu ad yoktu; action_users ve stripe eklentisi
     * validateEmpty() çağırıyor ama sınıfta yalnızca validateEmptyString /
     * validateEmptyData vardı — yani o çağrıların hepsi fatal veriyordu.
     * İki eski ad birebir aynı gövdeye sahipti; ikisi de buraya bağlandı.
     */
    static function validateEmpty($param, $region = '')
    {
        if (empty($param)) {
            throw new StandartException(empty($region) ? lang('PLEASE_FILL_THE_REQUIRED_FIELD') : lang('PLEASE_FILL_IN_FIELD', $region));
        }
    }

    /** @see validateEmpty() */
    static function validateEmptyString($param, $region = '')
    {
        self::validateEmpty($param, $region);
    }

    /**
     * Parola kuralı (4.0.0: ayarlanabilir).
     *
     *   VNL_PASSWORD_MIN_LENGTH=8                         en az karakter (çok baytlı harf 1 sayılır)
     *   VNL_PASSWORD_REQUIRE=upper,lower,digit,symbol     istenen sınıflar; boş = hiçbiri
     *
     * Varsayılan 3.x'in sabit kuralıdır: 8 karakter, dört sınıfın hepsi.
     *
     * @return array{min:int,require:string[]}
     */
    static function passwordPolicy(): array
    {
        $min = (string)VnlEnv::get('VNL_PASSWORD_MIN_LENGTH', '8');
        $req = VnlEnv::get('VNL_PASSWORD_REQUIRE', 'upper,lower,digit,symbol');
        $req = $req === false || $req === null ? '' : (string)$req;

        $require = array_values(array_intersect(
            ['upper', 'lower', 'digit', 'symbol'],
            array_map(static fn($r) => strtolower(trim($r)), explode(',', $req))
        ));

        return [
            'min'     => ctype_digit($min) && (int)$min > 0 ? (int)$min : 8,
            'require' => $require,
        ];
    }

    static function validateValidPassword($password, $region = '')
    {
        if (empty($password)) {
            throw new StandartException(empty($region) ? lang('PLEASE_FILL_THE_REQUIRED_FIELD') : lang('PLEASE_FILL_IN_FIELD', $region));
        }

        $password = (string)$password;
        $policy   = self::passwordPolicy();
        $length   = function_exists('mb_strlen') ? mb_strlen($password, 'UTF-8') : strlen($password);

        if ($length < $policy['min']) {
            throw new StandartException("Şifre en az {$policy['min']} karakter uzunluğunda olmalıdır.");
        }

        $rules = [
            'upper'  => ['/\p{Lu}/u', 'Şifre en az bir büyük harf içermelidir.'],
            'lower'  => ['/\p{Ll}/u', 'Şifre en az bir küçük harf içermelidir.'],
            'digit'  => ['/[0-9]/', 'Şifre en az bir rakam içermelidir.'],
            'symbol' => ['/[^\p{L}\p{N}]/u', 'Şifre en az bir özel karakter içermelidir.'],
        ];

        foreach ($policy['require'] as $rule) {
            if (!preg_match($rules[$rule][0], $password)) {
                throw new StandartException($rules[$rule][1]);
            }
        }
    }

    /**
     * validateValidPassword ile aynı kural. Web isteğinde ihlal 400 yanıtıyla
     * biter (StandartException'ın kurucusu yazar). Voxnup'ın kullandığı ad;
     * proje validators/ ezmesi olmadan çağırabilsin diye çekirdekte.
     */
    static function requireValidPassword($password, $region = '')
    {
        self::validateValidPassword($password, $region);
    }

    static function validateEmptyOption($param, $region = '')
    {
        if (empty($param)) {
            throw new StandartException(empty($region) ? lang('PLEASE_SELECT_VALID_OPTION') : lang('PLEASE_SELECT_VALID_OPTION_IN_FIELD', $region));
        }
    }

    static function validateEmptyPhone($param, $region = '')
    {
        if (empty($param)) {
            throw new StandartException(empty($region) ? lang('PLEASE_ENTER_VALID_PHONE_NUMBER') : lang('PLEASE_FILL_IN_FIELD', $region));
        }
        self::validatePhone($param, $region);

    }

    static function validatePhone($param, $region = '')
    {
        $param = str_replace([' ', '-', '_', '(', ')'], '', $param);
        if (empty($param)) return;
        if (!TextGeneralModule::AsInt($param) || strlen($param) < 10) {
            throw new StandartException(empty($region) ? lang('PLEASE_ENTER_VALID_PHONE_NUMBER') : lang('PLEASE_ENTER_VALID_PHONE_NUMBER_IN_FIELD', $region));
        }
    }


    static function validateEmptyMail($param)
    {
        if (empty($param)) throw new StandartException(lang('EMAIL_ADDRESS_CANNOT_BE_EMPTY'));
        if (!MailModule::isValidEmail($param)) {
            throw new StandartException(lang('EMAIL_ADDRESS_INVALID'));
        }
    }

    /** @see validateEmpty() */
    static function validateEmptyData($param, $region = '')
    {
        self::validateEmpty($param, $region);
    }

    static function validateEmptyID($param, $region = '')
    {
        if (empty($param)) {
            throw new StandartException(empty($region) ? lang('PLEASE_SELECT_VALID_OPTION') : lang('PLEASE_SELECT_VALID_OPTION_IN_FIELD', $region));
        } else if (!is_int($param) || $param <= 0) {
            throw new StandartException(empty($region) ? lang('PLEASE_SELECT_VALID_OPTION') : lang('PLEASE_SELECT_VALID_OPTION_IN_FIELD', $region));
        }
    }

    static function validateEmptyIDArray($param, $region = '')
    {
        if (is_array($param)) {
            foreach ($param as $item) {
                self::validateEmptyID($item, $region);
            }
        } else {
            self::validateEmptyID($param, $region);
        }
    }

    static function validateInt($param, $region = '')
    {
        if ($param != '' && !is_int($param)) {
            throw new StandartException(empty($region) ? lang('PLEASE_ENTER_VALID_NUMBER') : lang('PLEASE_ENTER_VALID_NUMBER_IN_FIELD', $region));
        }
    }

    static function validateEmptyInt($param, $region = '')
    {
        if (!empty($param) && intval($param) <= 0) {
            throw new StandartException(empty($region) ? lang('PLEASE_ENTER_VALID_NUMBER') : lang('PLEASE_ENTER_VALID_NUMBER_IN_FIELD', $region));
        }
    }

    /* EMPTYID OLMAMASININ SEBEBİ VERDİĞİ HATA MESAJININ FARKLI OLMASIDIR */
    static function validateEmptyINT_ID($param, $region = '')
    {
        if ((!empty($param) && !TextGeneralModule::AsInt($param, false)) || $param <= 0) {
            throw new StandartException(empty($region) ? lang('PLEASE_ENTER_NUMBER_BIGGER_THAN_0') : lang('PLEASE_ENTER_NUMBER_BIGGER_THAN_0_IN_FIELD', $region));
        }
    }

    static function validateDate($date, $region = '', $format = 'Y-m-d')
    {
        $d = DateTime::createFromFormat($format, $date);

        // The Y ( 4 digits year ) returns TRUE for any integer with any number of digits so changing the comparison from == to === fixes the issue.
        if (($d && $d->format($format) === $date) == false) {
            throw new StandartException(empty($region) ? lang('PLEASE_ENTER_VALID_DATE') : lang('PLEASE_ENTER_VALID_DATE_IN_FIELD', $region));
        }
    }

    static function validateDateNow($date, $region = '', $format = 'Y-m-d')
    {

        $d = DateTime::createFromFormat($format, $date);

        // The Y ( 4 digits year ) returns TRUE for any integer with any number of digits so changing the comparison from == to === fixes the issue.
        if (($d && $d->format($format) === $date) == false) {
            throw new StandartException(empty($region) ? lang('PLEASE_ENTER_VALID_DATE') : lang('PLEASE_ENTER_VALID_DATE_IN_FIELD', $region));
        } else if ($date > date('Y-m-d')) {
            throw new StandartException(lang('YOU_CANT_ENTER_FEATURE_DATE'));
        }
    }

    static function validateEmptyDate($date, $region = '', $format = 'Y-m-d')
    {
        if (empty($date)) throw new StandartException(lang('PLEASE_ENTER_DATE'));
        $d = DateTime::createFromFormat($format, $date);

        // The Y ( 4 digits year ) returns TRUE for any integer with any number of digits so changing the comparison from == to === fixes the issue.
        if (($d && $d->format($format) === $date) == false) {
            throw new StandartException(empty($region) ? lang('PLEASE_ENTER_VALID_DATE') : lang('PLEASE_ENTER_VALID_DATE_IN_FIELD', $region));
        }
    }

    static function validateAge($dateOfBirth, $region = '', $format = 'Y-m-d')
    {
        // Doğum tarihi boş mu kontrolü
        if (empty($dateOfBirth)) throw new StandartException(lang('PLEASE_ENTER_DATE'));

        // Tarih formatını kontrol et
        $d = DateTime::createFromFormat($format, $dateOfBirth);
        if (($d && $d->format($format) === $dateOfBirth) == false) {
            throw new StandartException(empty($region) ? lang('PLEASE_ENTER_VALID_DATE') : lang('PLEASE_ENTER_VALID_DATE_IN_FIELD', $region));
        }

        // Yaş kontrolü: 16 yaşından küçükse hata fırlat
        $today = new DateTime();
        $age = $today->diff($d)->y; // Yaş hesaplama
        if ($age < 16) {
            throw new StandartException("Kayıt olmak için en az 16 yaşında olmalısınız.");
        }
    }


    static function validateTextLength($param, $maxlength = 255, $minlength = 3, $region = '')
    {
        $strlen = strlen($param);
        if ($strlen > $maxlength) {
            throw new StandartException(empty($region) ? lang('PLEASE_DO_NOT_PASS_X_CHAR_LIMIT', $maxlength) : lang('PLEASE_DO_NOT_PASS_X_CHAR_LIMIT_IN_FIELD', $region,$maxlength));
        }
        if ($strlen < $minlength) {
            throw new StandartException(empty($region) ? lang('PLEASE_DO_NOT_PASS_X_CHAR_MIN_LIMIT', $minlength) : lang('PLEASE_DO_NOT_PASS_X_CHAR_MIN_LIMIT_IN_FIELD', $region, $minlength));
        }

    }

}
