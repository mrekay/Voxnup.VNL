<?php

/**
 * Şablon derleyicisi (vnl/template.php) ve çıktı kaçışı.
 *
 * Felsefe 5: kaçış çıkışta yapılır. {{ }} ve vnl_e() kaçışlıdır;
 * {!! !!} ve <?= ?> bilerek kaçışsızdır.
 */
final class TemplateTest extends VnlTestCase
{
    private const XSS = '<script>alert("x")</script>&\'';

    public function testEscapeHelper(): void
    {
        $this->assertSame('&lt;script&gt;alert(&quot;x&quot;)&lt;/script&gt;&amp;&#039;', vnl_e(self::XSS));
        $this->assertSame('', vnl_e(null));
        $this->assertSame('0', vnl_e(0));
        $this->assertSame('[&quot;&lt;b&gt;&quot;]', vnl_e(['<b>']));
    }

    public function testEscapeHelperSubstitutesInvalidUtf8(): void
    {
        $this->assertNotSame('', vnl_e("a\xC3\x28b"), 'geçersiz UTF-8 boş dizgeye dönmemeli');
    }

    public function testCompileEchoForms(): void
    {
        $this->assertSame('<?= vnl_e($x) ?>', VnlTemplate::compile('{{ $x }}'));
        $this->assertSame('<?= $x ?>', VnlTemplate::compile('{!! $x !!}'));
        $this->assertSame('<?= $x ?>', VnlTemplate::compile('<?= $x ?>'), 'ham PHP olduğu gibi kalmalı');
    }

    public function testCompileEscapes(): void
    {
        $this->assertSame('{{ $x }}', VnlTemplate::compile('@{{ $x }}'));
        $this->assertSame('{!! $x !!}', VnlTemplate::compile('@{!! $x !!}'));
        $this->assertSame('@if', VnlTemplate::compile('@@if'));
    }

    public function testCommentsKeepLineCount(): void
    {
        $source = "a\n{{-- bir\niki\nüç --}}\nb";

        $compiled = VnlTemplate::compile($source);

        $this->assertNotContains('bir', $compiled);
        $this->assertSame(substr_count($source, "\n"), substr_count($compiled, "\n"));
    }

    public function testDirectives(): void
    {
        $compiled = VnlTemplate::compile("@if (\$a)\nA\n@elseif (\$b)\nB\n@else\nC\n@endif\n@foreach (\$xs as \$x)\n{{ \$x }}\n@endforeach");

        foreach (['if ($a):', 'elseif ($b):', 'else:', 'endif;', 'foreach ($xs as $x):', 'endforeach;'] as $piece) {
            $this->assertContains($piece, $compiled);
        }
    }

    public function testHasSyntax(): void
    {
        $this->assertTrue(VnlTemplate::hasSyntax('<p>{{ $x }}</p>'));
        $this->assertTrue(VnlTemplate::hasSyntax("@if (\$x)\n@endif"));
        $this->assertFalse(VnlTemplate::hasSyntax('<p><?= $x ?></p>'));
        $this->assertFalse(VnlTemplate::hasSyntax('e-posta: ad@site.test'));
    }

    /** Derlenmiş şablon gerçekten çalıştırıldığında kaçış devrede mi? */
    public function testRenderedOutputEscapesOnlyWhereExpected(): void
    {
        $compiled = VnlTemplate::compile('[{{ $x }}][{!! $x !!}]');
        $file     = tempnam(sys_get_temp_dir(), 'vnltpl');
        file_put_contents($file, $compiled);

        try {
            $render = static function (string $__file, $x): string {
                ob_start();
                include $__file;

                return (string)ob_get_clean();
            };
            $html = $render($file, self::XSS);
        } finally {
            @unlink($file);
        }

        /* Beklenen değer sabit yazılır: vnl_e() ile üretilseydi vnl_e bozulduğunda
           bu test de onunla birlikte bozulup hatayı göremezdi. */
        $this->assertSame('[&lt;script&gt;alert(&quot;x&quot;)&lt;/script&gt;&amp;&#039;][' . self::XSS . ']', $html);
    }
}
