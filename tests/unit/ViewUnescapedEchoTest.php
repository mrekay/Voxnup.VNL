<?php

/**
 * 4.0.0: view/ altında kaçışsız değişken basımı (<?= $x ?>) için mandal.
 *
 * <?= $x ?> kaçışsızdır (CLAUDE.md, felsefe 5); kaçışlı olan {{ $x }} ve
 * vnl_e($x). Aşağıdaki liste bugünkü kullanımların DOSYA BAŞINA sayısıdır —
 * çoğu tanımdan gelen sabit (logo yolu, uygulama adı, menü) ya da bilerek
 * HTML basan yer (contentHeader'ın 'custom' alanı). Satır numarası yerine
 * sayı tutulur ki başka bir düzenleme testi kırmasın.
 *
 * Sayı ARTARSA test düşer: yeni basım {{ }} ya da vnl_e() ile yazılmalı;
 * gerçekten HTML basılacaksa listeye gerekçesiyle eklenir. Sayı AZALIRSA da
 * düşer: listeyi indirin ki kazanılan yer geri kaybedilmesin.
 *
 * Bu test projenin (tests/), çünkü view/ projenin — çekirdek testi projenin
 * görünümlerine kural koyamaz.
 */
final class ViewUnescapedEchoTest extends VnlTestCase
{
    private const ALLOWED = [
        'view/401.php'                                             => 1,
        'view/403.php'                                             => 1,
        'view/404.php'                                             => 1,
        'view/docs/theme-components.php'                           => 2,
        'view/docs/theme-modals.php'                               => 1,
        'view/docs/theme.php'                                      => 4,
        'view/includes/footer/project-app/script-view-project.php' => 1,
        'view/includes/static/head-standard.php'                   => 2,
        'view/includes/static/script-standard.php'                 => 1,
        'view/login.php'                                           => 1,
        'view/logout.php'                                          => 1,
        'view/static/contentHeader.php'                            => 25,
        'view/static/footer.php'                                   => 2,
        'view/static/header.php'                                   => 4,
        'view/static/leftSidebar.php'                              => 8,
        'view/static/topNavbar.php'                                => 5,
    ];

    public function testUnescapedVariableEchoesDoNotGrow(): void
    {
        $root  = rtrim(str_replace('\\', '/', VNL_ROOT), '/');
        $found = [];

        $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root . '/view', FilesystemIterator::SKIP_DOTS));
        foreach ($it as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }
            $n = preg_match_all('/<\?=\s*\$/', (string)file_get_contents($file->getPathname()));
            if ($n > 0) {
                $found[substr(str_replace('\\', '/', $file->getPathname()), strlen($root) + 1)] = $n;
            }
        }
        ksort($found);

        $errors = [];
        foreach ($found + self::ALLOWED as $path => $_) {
            $have = $found[$path] ?? 0;
            $want = self::ALLOWED[$path] ?? 0;
            if ($have > $want) {
                $errors[] = "$path: $have kaçışsız <?= \$ (izin $want) — {{ }} ya da vnl_e() kullanın";
            } elseif ($have < $want) {
                $errors[] = "$path: $have kaçışsız <?= \$ (liste $want) — ALLOWED'ı $have'e indirin";
            }
        }

        $this->assertSame([], $errors);
    }
}
