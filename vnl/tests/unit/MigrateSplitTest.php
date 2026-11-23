<?php

/**
 * Migration dosyasını ifadelere bölen tarayıcı, lehçeye göre (4.1.0).
 * MySQL kuralları değişmedi; öbür lehçelerde #, ters bölü ve backtick
 * özel değildir, SQL Server'da [köşeli] ve PostgreSQL'de $$ gövdeleri tek
 * parça okunur.
 */
final class MigrateSplitTest extends VnlTestCase
{
    public function setUp(): void
    {
        require_once VNL_PATH . '/tools/migrate.php';
    }

    public function testMysqlRulesUnchanged(): void
    {
        $sql = "# yorum\nSELECT 'a;b';\nSELECT `x;y` FROM t; -- son\n/*!40101 SET NAMES utf8 */;\nSELECT 'it\\'s;';";

        $this->assertSame([
            "SELECT 'a;b'",
            'SELECT `x;y` FROM t',
            '/*!40101 SET NAMES utf8 */',
            "SELECT 'it\\'s;'",
        ], VnlMigrate::split($sql));
    }

    public function testPgsqlDollarQuotedBodyIsOneStatement(): void
    {
        $sql = "DO \$\$\nBEGIN\n  IF true THEN\n    ALTER TABLE t ADD c INT;\n  END IF;\nEND\n\$\$;\nSELECT 1;\n"
             . "CREATE FUNCTION f() RETURNS int AS \$body\$ SELECT 1; \$body\$ LANGUAGE sql;";

        $out = VnlMigrate::split($sql, 'pgsql');

        $this->assertSame(3, count($out));
        $this->assertContains('END IF;', $out[0]);
        $this->assertSame('SELECT 1', $out[1]);
        $this->assertContains('$body$ SELECT 1; $body$', $out[2]);
    }

    public function testBackslashIsLiteralOutsideMysql(): void
    {
        /* PostgreSQL / SQL Server / SQLite'ta 'C:\' tam bir dizgidir. */
        foreach (['pgsql', 'sqlsrv', 'sqlite'] as $dialect) {
            $this->assertSame(["SELECT 'C:\\'", 'SELECT 2'], VnlMigrate::split("SELECT 'C:\\';\nSELECT 2;", $dialect), $dialect);
        }
    }

    public function testHashIsNotACommentOutsideMysql(): void
    {
        $this->assertSame(['SELECT * INTO #gecici FROM t', 'SELECT 1'],
            VnlMigrate::split("SELECT * INTO #gecici FROM t;\nSELECT 1;", 'sqlsrv'));
    }

    public function testSqlsrvBracketIdentifiers(): void
    {
        $this->assertSame(['SELECT [a;b], [c]]d] FROM t', "SELECT N'x'"],
            VnlMigrate::split("SELECT [a;b], [c]]d] FROM t;\nSELECT N'x';", 'sqlsrv'));
    }

    public function testCommentOnlyFileHasNoStatements(): void
    {
        $this->assertSame([], VnlMigrate::split("-- yalniz yorum\n/* blok */\n", 'sqlite'));
    }

    /** Çekirdeğin her sürücü dosyası temiz bölünür (boş ifade, yarım dizgi yok). */
    public function testCoreDriverFilesSplitCleanly(): void
    {
        foreach (glob(VNL_PATH . '/migrations/*.sql') as $file) {
            $dialect = preg_match('/\.(pgsql|sqlsrv|sqlite)\.sql$/', $file, $m) ? $m[1] : 'mysql';

            foreach (VnlMigrate::split((string)file_get_contents($file), $dialect) as $statement) {
                $this->assertSame(0, substr_count($statement, "'") % 2, basename($file) . ': tek sayida tirnak');
                $this->assertNotSame('', trim($statement));
            }
        }
    }

    /** Her çekirdek numarasının dört sürücüde de hâli var ve adları tutuyor. */
    public function testEveryCoreMigrationHasEveryDriver(): void
    {
        $byNumber = [];
        foreach (glob(VNL_PATH . '/migrations/*.sql') as $file) {
            if (preg_match('/^(\d{4})_(.+?)(?:\.(pgsql|sqlsrv|sqlite))?\.sql$/', basename($file), $m)) {
                $byNumber[$m[1]][$m[3] ?? 'mysql'] = $m[2];
            }
        }

        foreach ($byNumber as $number => $variants) {
            ksort($variants);
            $this->assertSame(['mysql', 'pgsql', 'sqlite', 'sqlsrv'], array_keys($variants), "{$number}: eksik surucu");
            $this->assertSame(1, count(array_unique($variants)), "{$number}: adlar farkli");
        }
    }
}
