<?php

declare(strict_types=1);

namespace MidoriKocak;

use MidoriKocak\Query;
use PHPUnit\Framework\TestCase;

final class QueryTest extends TestCase
{
    private Query $query;

    protected function setUp(): void
    {
        $this->query = new Query();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        unset($this->query);
    }

    public function testSelect()
    {
        $this->query->select('users');
        $this->assertEquals('SELECT * FROM users', $this->query->getQuery());
        $this->assertEquals('SELECT * FROM users', $this->query->getStatement());
    }

    public function testSelectFields()
    {
        $this->query->select('users', ['id', 'email']);
        $this->assertEquals('SELECT id, email FROM users', $this->query->getQuery());
        $this->assertEquals('SELECT id, email FROM users', $this->query->getStatement());
    }

    public function testSelectFieldsWhere()
    {
        $this->query->select('users', ['id', 'email'])->where('id', 3);
        $this->assertEquals('SELECT id, email FROM users WHERE id=\'3\'', $this->query->getQuery());
        $this->assertEquals('SELECT id, email FROM users WHERE id=:id', $this->query->getStatement());
    }

    public function testUpdate()
    {
        $this->query->update('users', ['email' => 'mtkocak@gmail.com', 'username' => 'midorikocak'])->where('id', 3);
        $this->assertEquals("UPDATE users SET email='mtkocak@gmail.com', username='midorikocak' WHERE id='3'",
            $this->query->getQuery());
        $this->assertEquals("UPDATE users SET email=:email, username=:username WHERE id=:id", $this->query->getStatement());
    }

    public function testWhereAnd()
    {
        $this->query->select('users', ['id', 'email'])->where('id', 3)->and(['username' => 'midori']);
        $this->assertEquals("SELECT id, email FROM users WHERE id='3' AND username='midori'", $this->query->getQuery());
        $this->assertEquals("SELECT id, email FROM users WHERE id=:id AND username=:username",
            $this->query->getStatement());
    }

    public function testWhereOr()
    {
        $this->query->select('users', ['id', 'email'])->where('id', 3)->or(['username' => 'midori']);
        $this->assertEquals("SELECT id, email FROM users WHERE id='3' OR username='midori'", $this->query->getQuery());
        $this->assertEquals("SELECT id, email FROM users WHERE id=:id OR username=:username", $this->query->getStatement());

    }

    public function testWhereAndOr()
    {
        $this->query->select('users', ['id', 'email'])->where('id',
            3)->and(['email' => 'mtkocak@gmail.com'])->or(['username' => 'midori']);
        $this->assertEquals("SELECT id, email FROM users WHERE id='3' AND email='mtkocak@gmail.com' OR username='midori'",
            $this->query->getQuery());
        $this->assertEquals("SELECT id, email FROM users WHERE id=:id AND email=:email OR username=:username",
            $this->query->getStatement());
    }

}

