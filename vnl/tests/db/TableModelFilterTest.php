<?php

use Illuminate\Database\Capsule\Manager as Capsule;

/**
 * TableModel filtre güvenliği (4.0.0):
 *  - operatör yalnız $columns'tan gelir, istemci ezemez;
 *  - like/ilike'ta % ve _ düz metindir;
 *  - dizi değer TypeError değil, tanımsız anahtar gibi reddedilir.
 *
 * Reddetme testleri sorgu kurulmadan biter, veritabanı istemez. Arama
 * testleri geçici bir tablo açar ve tearDown'da siler.
 */
final class VnlTableModelFilterRow extends TableModel
{
    protected $table = 'tbl_vnl_test_tablemodel';
    public $timestamps = false;

    public static array $columns = [
        'id'    => ['name' => 'tbl_vnl_test_tablemodel.id'],
        'name'  => ['name' => 'tbl_vnl_test_tablemodel.name', 'operator' => 'like'],
        'range' => ['name' => 'tbl_vnl_test_tablemodel.id', 'operator' => 'between'],
    ];

    public static function SelectList($filters = [], $extras = [])
    {
        return self::selectListQuery(self::$columns, $filters, $extras);
    }
}

final class TableModelFilterTest extends VnlTestCase
{
    private bool $tableCreated = false;

    public function tearDown(): void
    {
        if ($this->tableCreated) {
            Capsule::schema()->dropIfExists('tbl_vnl_test_tablemodel');
            $this->tableCreated = false;
        }
    }

    private function seed(array $names): void
    {
        $this->requireDatabase();

        Capsule::schema()->dropIfExists('tbl_vnl_test_tablemodel');
        Capsule::schema()->create('tbl_vnl_test_tablemodel', function ($t) {
            $t->increments('id');
            $t->string('name');
        });
        $this->tableCreated = true;

        foreach ($names as $name) {
            Capsule::table('tbl_vnl_test_tablemodel')->insert(['name' => $name]);
        }
    }

    private function names(array $filters): array
    {
        $names = VnlTableModelFilterRow::SelectList($filters, ['order' => 'id'])->get()->pluck('name')->all();
        sort($names);

        return $names;
    }

    private function assertRejected(array $filters, string $label): void
    {
        $this->assertThrows(fn() => VnlTableModelFilterRow::SelectList($filters, []), VnlHttpException::class, $label);
    }

    /* ---------------- operatör ---------------- */

    public function testClientCannotOverrideOperator(): void
    {
        $this->assertRejected(['name' => ['value' => '.*', 'operator' => 'regexp']], 'regexp');
        $this->assertRejected(['name' => ['value' => 'x', 'operator' => '<>']], '<> (like yerine)');
        $this->assertRejected(['id' => ['value' => 1, 'operator' => '>']], '> (= yerine)');
        $this->assertRejected(['id' => ['value' => 1, 'operator' => ['=']]], 'dizi operatör');
    }

    public function testRepeatingDeclaredOperatorIsAllowed(): void
    {
        $this->seed(['bisiklet', 'kask']);

        $this->assertSame(['bisiklet'], $this->names(['name' => ['value' => 'bis', 'operator' => 'LIKE']]));
        $this->assertSame(['kask'], $this->names(['id' => ['value' => 2, 'operator' => '=']]));
        $this->assertSame(['bisiklet', 'kask'], $this->names(['range' => ['value' => '1|2', 'operator' => 'between']]));
    }

    /* ---------------- dizi değer ---------------- */

    public function testArrayValueIsRejectedNotTypeError(): void
    {
        $this->assertRejected(['name' => ['a', 'b']], 'like + dizi (?name[]=a&name[]=b)');
        $this->assertRejected(['name' => ['value' => ['a']]], 'like + uzun biçimde dizi');
        $this->assertRejected(['range' => ['value' => ['1', '2']]], 'between + dizi');
        $this->assertRejected(['id' => [5]], '= + dizi');
    }

    /* ---------------- LIKE kaçışı ---------------- */

    public function testLikeWildcardsAreLiteral(): void
    {
        $this->seed(['50% indirim', '500 TL', 'a_b', 'axb', 'c\\d', 'cd', 'x!y', 'xzy', 'k[a]z', 'kaz']);

        $this->assertSame(['50% indirim'], $this->names(['name' => '50%']));
        $this->assertSame(['a_b'], $this->names(['name' => 'a_b']));
        $this->assertSame(['c\\d'], $this->names(['name' => 'c\\d']));
        $this->assertSame(['x!y'], $this->names(['name' => 'x!y']));
        $this->assertSame(['k[a]z'], $this->names(['name' => 'k[a]z']));   // SQL Server'da [a] karakter kümesidir (4.1.0)
        $this->assertSame(['50% indirim'], $this->names(['name' => '%']));   // kaçışsız: hepsi
    }

    public function testLikeIsStillCaseInsensitiveSubstring(): void
    {
        $this->seed(['Bisiklet Kaski', 'Kask']);

        $this->assertSame(['Bisiklet Kaski', 'Kask'], $this->names(['name' => 'KASK']));
    }

    /* ---------------- VNL Table koşulları (4.1.0) ---------------- */

    /** VnlTableRequest::applyFilters ile metin koşulu; eşleşen adlar. */
    private function tableNames(string $op, string $value): array
    {
        $query = Capsule::table('tbl_vnl_test_tablemodel');
        (new VnlTableRequest())->applyFilters($query, ['ad' => 'name'], [
            'ad' => ['kind' => 'text', 'cond' => ['op' => $op, 'value' => $value]],
        ]);

        $names = $query->pluck('name')->all();
        sort($names);

        return $names;
    }

    /**
     * Önceden ters bölüyle kaçırılıyordu: SQL Server ve SQLite'ta ters bölü
     * LIKE'ta düz karakterdir, "50%" araması orada hiçbir şey bulmuyordu.
     */
    public function testTableTextConditionsTreatWildcardsAsLiteral(): void
    {
        $this->seed(['50% indirim', '500 TL', 'a_b', 'axb', 'c\\d', 'cd', 'x!y', 'k[a]z', 'kaz']);

        $this->assertSame(['50% indirim'], $this->tableNames('contains', '50%'));
        $this->assertSame(['a_b'], $this->tableNames('contains', 'a_b'));
        $this->assertSame(['c\\d'], $this->tableNames('contains', 'c\\d'));
        $this->assertSame(['x!y'], $this->tableNames('contains', 'x!y'));
        $this->assertSame(['k[a]z'], $this->tableNames('contains', '[a]'));
        $this->assertSame(['50% indirim'], $this->tableNames('startsWith', '50%'));
        $this->assertSame(['a_b'], $this->tableNames('endsWith', '_b'));
        $this->assertSame(
            ['500 TL', 'a_b', 'axb', 'c\\d', 'cd', 'k[a]z', 'kaz', 'x!y'],
            $this->tableNames('notContains', '50%')
        );
    }
}
