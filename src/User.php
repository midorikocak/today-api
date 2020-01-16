<?php

declare(strict_types=1);

namespace MidoriKocak;

use function array_key_exists;

class User
{
    private Database $db;

    public ?string $id = null;

    private string $email;

    private string $username;

    private string $password;

    /**
     * @var Entry[]|null
     */
    private ?array $entries = null;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function setPassword(string $password): void
    {
        $this->password = $password;
    }

    public function getEntries(): array
    {
        if (!$this->entries) {
            $entries = new Entries($this->db);
            $this->entries = $entries->findByUserId($this->id);
        }

        return $this->entries;
    }

    public function getPassword(): string
    {
        return $this->password;
    }

    public function setEmail(string $email): void
    {
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->email = $email;
        }
    }

    public function setUsername(string $username): void
    {
        $this->username = $username;
    }

    public function save()
    {
        if ($this->id) {
            $this->db->update($this->id, $this->toArray(), 'users');

            $entries = new Entries($this->db);

            $this->entries = $entries->findByUserId($this->id);
        } else {
            $this->db->insert($this->toArray(), 'users');
            $this->id = $this->db->lastInsertId();
        }
    }

    public function delete(): void
    {
        $this->db->delete($this->id, 'users');
    }

    public function toArray(): array
    {
        $data = [
            'username' => $this->username,
            'email' => $this->email,
            'password' => $this->password,
        ];

        if ($this->id) {
            $data['id'] = (string)$this->id;
        }

        return $data;
    }

    public function fromArray(array $data): void
    {
        if (array_key_exists('id', $data)) {
            $this->id = (string)$data['id'];
        }

        $this->setEmail($data['email']);
        $this->setUsername($data['username']);
        $this->setPassword($data['password']);
    }
}
