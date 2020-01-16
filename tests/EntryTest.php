<?php
declare(strict_types=1);

use MidoriKocak\Database;
use MidoriKocak\Entries;
use MidoriKocak\Entry;
use MidoriKocak\User;
use MidoriKocak\Users;
use PHPUnit\Framework\TestCase;

/**
 *
 */
final class EntryTest extends TestCase
{

    /**
     * @var array
     */
    private array $firstEntry;
    /**
     * @var array
     */
    private array $secondEntry;

    /**
     * @var array
     */
    private array $userData;

    /**
     * @var Entries
     */
    private Entries $entries;

    /**
     * @var Database
     */
    private Database $db;

    /**
     * @return void
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->table = 'entries';

        $this->firstEntry = [
            'user_id' => 1,
            'yesterday' => 'çok çalıştım',
            'today' => 'wrote syllabus',
            'blocker' => 'heartbroken'
        ];

        $this->secondEntry = [
            'user_id' => 1,
            'yesterday' => 'baya uğraştım',
            'today' => 'created example UI',
            'blocker' => 'better'
        ];

        $this->userData = [
            'email' => 'mtkocak@gmail.com',
            'username' => 'midorikocak',
            'password' => '12345678'
        ];

        $this->db = new Database('localhost', 'today_test', 'root', 'turgut');
        $this->db->insert($this->userData, 'users');
        $this->entries = new Entries($this->db);
    }

    /**
     * @return void
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        $this->db->query('TRUNCATE table entries');
        $this->db->query('TRUNCATE table users');
        unset($this->entries, $this->db);
    }

    /**
     * @return void
     */
    public function testCreate(): void
    {
        $this->createEntry();

        $foundEntry = $this->db->show($this->db->lastInsertId(), 'entries');

        $this->assertNotNull($foundEntry);
    }

    public function createEntry()
    {
        $users = new Users($this->db);
        $id = (string)$this->firstEntry['user_id'];
        $user = $users->findById($id);

        $firstEntry = Entry::fromArray($this->firstEntry);
        $firstEntry->setUser($user);
        $this->entries->store($firstEntry);
        return $firstEntry;
    }

    /**
     * @return void
     */
    public function testShow(): void
    {
        $this->db->insert($this->secondEntry, 'entries');
        $id = $this->db->lastInsertId();
        $expectedEntry = $this->entries->show($id);

        $this->assertEquals($this->secondEntry, array_intersect($this->secondEntry, $expectedEntry->toArray()));

    }

    /**
     * @return void
     */
    public function testEdit(): void
    {
        $entry = $this->createEntry();
        $id = $this->db->lastInsertId();

        $entry->today = 'degistir';

        $this->entries->update($id, $entry);

        $updatedEntry = Entry::fromArray($this->db->show($id, 'entries'));

        $this->assertEquals($updatedEntry->toArray(), array_intersect($entry->toArray(), $updatedEntry->toArray()));
    }

    /**
     * @return void
     */
    public function testIndex(): void
    {
        $firstEntry = Entry::fromArray($this->firstEntry);
        $this->entries->store($firstEntry);

        $secondEntry = Entry::fromArray($this->secondEntry);
        $this->entries->store($secondEntry);

        $this->assertNotEmpty($this->entries->index());
    }

    /**
     * @return void
     */
    public function testDelete(): void
    {
        $firstEntry = Entry::fromArray($this->firstEntry);
        $this->entries->store($firstEntry);

        $this->entries->delete($firstEntry->id);

        $this->assertEmpty($this->entries->index());
    }

}

