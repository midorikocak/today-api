<?php
declare(strict_types=1);

use MidoriKocak\App;
use MidoriKocak\Database;
use MidoriKocak\Entry;
use PHPUnit\Framework\TestCase;

final class AppTest extends TestCase
{
    /**
     * @var App
     */
    private App $app;

    private array $userData;

    private array $firstEntryData;
    private array $secondEntryData;

    /**
     * @var Database
     */
    private Database $db;

    protected function setUp(): void
    {
        parent::setUp();
        $this->db = new Database('localhost', 'today_test', 'root', 'turgut');
        $this->clearDatabase();
        $this->app = new App($this->db);

        $this->userData = [
            'email' => 'mtkocak@gmail.com',
            'username' => 'midorikocak',
            'password' => password_hash('12345678', PASSWORD_DEFAULT)
        ];

        $this->firstEntryData = [
            'yesterday' => 'çok çalıştım',
            'today' => 'wrote syllabus',
            'blocker' => 'heartbroken'
        ];

        $this->secondEntryData = [
            'yesterday' => 'baya uğraştım',
            'today' => 'created example UI',
            'blocker' => 'better'
        ];
    }

    protected function clearDatabase(): void
    {
        $this->db->query('TRUNCATE table users');
        $this->db->query('TRUNCATE table entries');
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        $this->clearDatabase();
        unset($this->app, $this->db);

    }

    public function testRegister()
    {
        $this->app->register($this->userData['email'],
            $this->userData['username'], $this->userData['password'], $this->userData['password']);

        $foundUser = $this->db->find($this->userData['email'], 'email', 'users');

        $this->assertNotEmpty($foundUser);
    }

    public function testLogin()
    {
        $this->db->insert($this->userData, 'users');
        $this->app->login('mtkocak@gmail.com', '12345678');
        $this->assertNotNull($this->app->getLoggedUser());
    }

    public function testLoginNoUser()
    {
        $this->expectException(\Exception::class);
        $this->db->insert($this->userData, 'users');
        $this->app->login('mtkocak@xx.com', '12345678');
    }

    public function testGetMyEntries()
    {

        $this->db->insert($this->userData, 'users');
        $this->app->login('mtkocak@gmail.com', '12345678');

        $firstEntry = new Entry('yesterday', 'today', 'blocker');
        $firstEntry->setUser($this->app->getLoggedUser());

        $secondEntry = new Entry('yesterday2', 'today2', 'blocker2');
        $secondEntry->setUser($this->app->getLoggedUser());

        $this->app->entries->store($firstEntry);
        $this->app->entries->store($secondEntry);

        $this->assertNotEmpty($this->app->getLoggedUser()->getEntries());
    }

    public function testGetMyEntriesWhenNotLoggedIn()
    {
        $this->db->insert($this->userData, 'users');
        $user = $this->app->users->findById($this->db->lastInsertId());

        $firstEntry = new Entry('yesterday', 'today', 'blocker');
        $firstEntry->setUser($user);

        $secondEntry = new Entry('yesterday2', 'today2', 'blocker2');
        $secondEntry->setUser($user);

        $this->app->entries->store($firstEntry);
        $this->app->entries->store($secondEntry);

        $this->expectException(\Exception::class);
        $this->app->getAllEntries();
    }

    public function testRegisterBadEmail()
    {
        $this->db->insert($this->userData, 'users');

        $this->app->register('mtkocak@',
            $this->userData['username'], $this->userData['password'], $this->userData['password']);

        $foundUser = $this->db->find('mtkocak@', 'email', 'users');

        self::assertEmpty($foundUser);
    }
}

