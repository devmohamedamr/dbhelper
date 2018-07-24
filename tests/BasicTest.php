<?php
use vielhuber\dbhelper\dbhelper;

class BasicTest extends \PHPUnit\Framework\TestCase
{

    protected $db;

    protected function setUp()
    {
        $this->db = new dbhelper();
        $this->db->connect('pdo', 'mysql', '127.0.0.1', 'root', 'root', 'dbhelper', 3306);
        $this->db->clear('dbhelper');
        $this->db->query('
            CREATE TABLE test
            (
              id int(255) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
              col1 varchar(255),
              col2 varchar(255),
              col3 varchar(255)
            )
        ');
    }

    protected function tearDown()
    {
        $this->db->clear('dbhelper');
        $this->db->disconnect();
    }

    function test__insert()
    {
        $id = $this->db->insert('test', ['col1' => 'foo']);
        $this->assertSame($this->db->fetch_var('SELECT col1 FROM test WHERE id = ?', $id), 'foo');
        $id = $this->db->insert('test', ['id' => 2, 'col1' => 'foo', 'col2' => 'bar', 'col3' => null]);
        $this->assertSame($this->db->fetch_var('SELECT col3 FROM test WHERE id = ?', $id), null);
    }

    function test__update()
    {
        $id = $this->db->insert('test', ['col1' => 'foo']);
        $this->db->update('test', ['col1' => 'bar'], ['col1' => 'foo']);
        $this->assertSame( $this->db->fetch_var('SELECT COUNT(*) FROM test WHERE col1 = ?', 'foo'), 0 );
        $this->assertSame( $this->db->fetch_var('SELECT COUNT(*) FROM test WHERE col1 = ?', 'bar'), 1 );
    }

    function test__delete()
    {
        $id = $this->db->insert('test', ['col1' => 'foo']);
        $this->assertSame( $this->db->fetch_var('SELECT COUNT(*) FROM test WHERE col1 = ?', 'foo'), 1 );
        $this->db->delete('test', ['col1' => 'foo']);
        $this->assertSame( $this->db->fetch_var('SELECT COUNT(*) FROM test WHERE col1 = ?', 'foo'), 0 );
    }

    function test__fetch_all()
    {
        $id1 = $this->db->insert('test', ['col1' => 'foo']);
        $id2 = $this->db->insert('test', ['col1' => 'bar']);
        $this->assertSame( $this->db->fetch_all('SELECT * FROM test WHERE col1 = ?', 'foo'), [['id' => $id1, 'col1' => 'foo', 'col2' => null, 'col3' => null]] );
        $this->assertSame( $this->db->fetch_all('SELECT * FROM test WHERE col1 = ? OR col1 = ?', 'foo', 'bar'), [['id' => $id1, 'col1' => 'foo', 'col2' => null, 'col3' => null],['id' => $id2, 'col1' => 'bar', 'col2' => null, 'col3' => null]] );
    }

    function test__fetch_row()
    {
        $id = $this->db->insert('test', ['col1' => 'foo']);
        $this->assertSame( $this->db->fetch_row('SELECT * FROM test WHERE id = ?', $id), ['id' => $id, 'col1' => 'foo', 'col2' => null, 'col3' => null] );
    }

    function test__fetch_col()
    {
        $this->db->insert('test', ['col1' => 'foo']);
        $this->db->insert('test', ['col1' => 'bar']);
        $this->assertSame( $this->db->fetch_col('SELECT col1 FROM test'), ['foo', 'bar'] );
    }

    function test__fetch_var()
    {
        $id1 = $this->db->insert('test', ['col1' => 'foo']);
        $id2 = $this->db->insert('test', ['col1' => 'bar']);
        $this->assertSame( $this->db->fetch_var('SELECT col1 FROM test WHERE id = ?', $id1), 'foo' );
        $this->assertSame( $this->db->fetch_var('SELECT col1 FROM test WHERE id = ?', $id2), 'bar' );
    }

    function test__flattened_args()
    {
        $id = $this->db->insert('test', ['col1' => 'foo', 'col2' => 'bar', 'col3' => 'baz']);
        $this->assertSame( $this->db->fetch_row('SELECT * FROM test WHERE col1 = ? AND col2 = ? AND col3 = ?', 'foo', 'bar', 'baz'), ['id' => $id, 'col1' => 'foo', 'col2' => 'bar', 'col3' => 'baz'] );
        $this->assertSame( $this->db->fetch_row('SELECT * FROM test WHERE col1 = ? AND col2 = ? AND col3 = ?', ['foo'], ['bar'], ['baz']), ['id' => $id, 'col1' => 'foo', 'col2' => 'bar', 'col3' => 'baz'] );
        $this->assertSame( $this->db->fetch_row('SELECT * FROM test WHERE col1 = ? AND col2 = ? AND col3 = ?', ['foo', 'bar'], 'baz'), ['id' => $id, 'col1' => 'foo', 'col2' => 'bar', 'col3' => 'baz'] );
        $this->assertSame( $this->db->fetch_row('SELECT * FROM test WHERE col1 = ? AND col2 = ? AND col3 = ?', 'foo', ['bar', 'baz']), ['id' => $id, 'col1' => 'foo', 'col2' => 'bar', 'col3' => 'baz'] );
        $this->assertSame( $this->db->fetch_row('SELECT * FROM test WHERE col1 = ? AND col2 = ? AND col3 = ?', ['foo', 'bar', 'baz']), ['id' => $id, 'col1' => 'foo', 'col2' => 'bar', 'col3' => 'baz'] );
    }

    function test__in_expansion()
    {
        $this->db->insert('test', [
            ['id' => 1, 'col1' => 'foo', 'col2' => 'bar', 'col3' => 'baz'],
            ['id' => 2, 'col1' => 'foo', 'col2' => 'baz', 'col3' => 'foo'],
            ['id' => 3, 'col1' => 'foo', 'col2' => 'foo', 'col3' => 'bar'],
        ]);
        $this->assertSame(
            $this->db->fetch_all('SELECT * FROM test WHERE col1 = ? AND col2 IN (?)', 'foo', ['bar','baz']),
            [['id' => 1, 'col1' => 'foo', 'col2' => 'bar', 'col3' => 'baz'],['id' => 2, 'col1' => 'foo', 'col2' => 'baz', 'col3' => 'foo']]
        );
        $this->assertSame(
            $this->db->fetch_all('SELECT * FROM test WHERE col1 = ? AND col2 NOT IN (?)', 'foo', ['bar','baz']),
            [['id' => 3, 'col1' => 'foo', 'col2' => 'foo', 'col3' => 'bar']]
        );
        $this->assertSame(
            $this->db->fetch_all('SELECT * FROM test WHERE col1 IN (?)', ['foo','bar','baz']),
            [['id' => 1, 'col1' => 'foo', 'col2' => 'bar', 'col3' => 'baz'], ['id' => 2, 'col1' => 'foo', 'col2' => 'baz', 'col3' => 'foo'], ['id' => 3, 'col1' => 'foo', 'col2' => 'foo', 'col3' => 'bar']]
        );
    }

    function test__null_values()
    {
        $id = $this->db->insert('test', ['col1' => 'foo', 'col2' => null, 'col3' => 'bar']);
        $this->db->query('UPDATE test SET col1 = NULL WHERE col2 IS NULL AND col3 IS NOT NULL');
        $this->assertSame(
            $this->db->fetch_row('SELECT * FROM test WHERE id = ?', $id),
            ['id' => $id, 'col1' => null, 'col2' => null, 'col3' => 'bar']
        );
        $id = $this->db->insert('test', ['col1' => 'foo', 'col2' => null, 'col3' => 'bar']);
        $this->db->query('UPDATE test SET col1 = ? WHERE col2 = ? AND col3 != ?', null, null, null);
        $this->assertSame(
            $this->db->fetch_row('SELECT * FROM test WHERE id = ?', $id),
            ['id' => $id, 'col1' => null, 'col2' => null, 'col3' => 'bar']
        );
    }

    function test__batch()
    {
        $this->db->insert('test', [
            ['id' => 1, 'col1' => 'foo'],
            ['id' => 2, 'col1' => 'bar'],
            ['id' => 3, 'col1' => 'baz']
        ]);
        $this->assertSame(
            $this->db->fetch_all('SELECT * FROM test'),
            [
                ['id' => 1, 'col1' => 'foo', 'col2' => null, 'col3' => null],
                ['id' => 2, 'col1' => 'bar', 'col2' => null, 'col3' => null],
                ['id' => 3, 'col1' => 'baz', 'col2' => null, 'col3' => null]
            ]
        );
        $this->db->update('test', [
            [['col1' => 'foo1'], ['id' => 1]],
            [['col1' => 'bar1'], ['id' => 2]],
            [['col1' => 'baz1'], ['id' => 3]]
        ]);
        $this->assertSame(
            $this->db->fetch_all('SELECT * FROM test'),
            [
                ['id' => 1, 'col1' => 'foo1', 'col2' => null, 'col3' => null],
                ['id' => 2, 'col1' => 'bar1', 'col2' => null, 'col3' => null],
                ['id' => 3, 'col1' => 'baz1', 'col2' => null, 'col3' => null]
            ]
        );
        $this->db->delete('test', [
            ['id' => 1],
            ['id' => 2],
            ['id' => 3]
        ]);
        $this->assertSame($this->db->fetch_all('SELECT * FROM test'), []);
    }

    function test__found_rows()
    {
        $id = $this->db->insert('test', ['col1' => 'foo']);
        $id = $this->db->insert('test', ['col1' => 'foo']);
        $id = $this->db->insert('test', ['col1' => 'foo']);
        $id = $this->db->insert('test', ['col1' => 'foo']);
        $id = $this->db->insert('test', ['col1' => 'foo']);
        $this->db->query('SELECT SQL_CALC_FOUND_ROWS * FROM test LIMIT 3;');
        $this->assertSame($this->db->found_rows(), 5);
    }

    function test__last_insert_id()
    {
        $id = $this->db->insert('test', ['col1' => 'foo']);
        $this->assertSame($id, $this->db->last_insert_id());
    }

    function test__get_tables()
    {
        $this->assertSame( $this->db->get_tables(), ['test'] );
    }

    function test__get_columns()
    {
        $this->assertSame( $this->db->get_columns('test'), ['id', 'col1', 'col2', 'col3'] );
    }

    function test__has_column()
    {
        $this->assertSame( $this->db->has_column('test', 'col1'), true );
        $this->assertSame( $this->db->has_column('test', 'col0'), false );
    }

    function test__get_datatype()
    {
        $this->assertSame( $this->db->get_datatype('test', 'col1'), 'varchar' );
        $this->assertSame( $this->db->get_datatype('test', 'col0'), null );
    }

    function test__get_primary_key()
    {
        $this->assertSame( $this->db->get_primary_key('test'), 'id' );
        $this->assertSame( $this->db->get_primary_key('test0'), null );
    }

    function test__errors()
    {
        try
        {
            $this->db->insert('test', ['id' => 1, 'col1' => (object)['foo' => 'bar']]);
        }
        catch(Exception $e)
        {
            $this->assertTrue(true);
        }
        try
        {
            $this->db->query('SELCET * FROM test');
        }
        catch(Exception $e)
        {
            $this->assertTrue(true);
        }
        $this->assertSame($this->db->fetch_var('SELECT COUNT(*) FROM test'), 0);
    }

}