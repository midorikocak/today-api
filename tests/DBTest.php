<?php
declare(strict_types=1);

namespace MidoriKocak;

use PHPUnit\Framework\TestCase;

class DBTest extends TestCase
{
    private Database $db;
    private array $firstEntry;
    private array $secondEntry;
    private string $table;

    protected function setUp(): void
    {
        parent::setUp();

        $this->table = 'entries';

        $this->firstEntry = [
            'user_id' => '1',
            'yesterday' => 'çok çalıştım',
            'today' => 'wrote syllabus',
            'blocker' => 'heartbroken'
        ];

        $this->secondEntry = [
            'user_id' => '2',
            'yesterday' => 'baya uğraştım',
            'today' => 'created example UI',
            'blocker' => 'better'
        ];

        $this->db = new Database('localhost', 'today_test', 'root', 'turgut');
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->db->query('TRUNCATE table entries');
        unset($this->db);
    }

    public function testInsert(): void
    {
        $this->db->insert($this->firstEntry, $this->table);
        $lastEntry = $this->db->show($this->db->lastInsertId(), $this->table);
        $this->assertEquals($this->firstEntry, array_intersect($this->firstEntry, $lastEntry));
    }

    public function testIndex(): void
    {
        $this->db->insert($this->firstEntry, $this->table);
        $allEntries = $this->db->index($this->table);
        $lastEntry = reset($allEntries);
        $this->assertEquals($this->firstEntry, array_intersect($this->firstEntry, $lastEntry));
    }

    public function testUpdate(): void
    {
        $this->db->insert($this->firstEntry, $this->table);
        $lastId = $this->db->lastInsertId();
        $this->db->update($lastId, $this->secondEntry, $this->table);
        $lastEntry = $this->db->show($lastId, $this->table);
        $this->assertEquals($this->secondEntry, array_intersect($this->secondEntry, $lastEntry));
    }

    public function testShow(): void
    {
        $this->db->insert($this->secondEntry, $this->table);
        $lastEntry = $this->db->show($this->db->lastInsertId(), $this->table);
        $this->assertEquals($this->secondEntry, array_intersect($this->secondEntry, $lastEntry));
    }

    public function testUseQuery(): void
    {

        $this->db->insert($this->secondEntry, $this->table);

        $query = new Query();
        $query->select('entries')->where('id', $this->db->lastInsertId());

        $lastEntry = $this->db->useQuery($query);
        $this->assertEquals($this->secondEntry, array_intersect($this->secondEntry, $lastEntry));
    }

    public function testFind(): void
    {
        $this->db->insert($this->secondEntry, $this->table);
        $foundEntries =
            $this->db->find(
                $this->secondEntry['yesterday'],
                'yesterday',
                $this->table);
        $lastEntry = reset($foundEntries);
        $this->assertEquals($this->secondEntry, array_intersect($this->secondEntry, $lastEntry));
    }

    public function testDelete(): void
    {
        $this->db->insert($this->secondEntry, $this->table);
        $lastId = $this->db->lastInsertId();
        $this->db->delete($lastId, $this->table);
        $allEntries = $this->db->index($this->table);
        $this->assertEmpty($allEntries);
    }
}
