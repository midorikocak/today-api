<?php

declare(strict_types=1);

namespace MidoriKocak;

use RuntimeException;

class App
{
    /**
     * @var Database
     */
    private Database $db;

    /**
     * @var User|null
     */
    public ?User $user = null;
    public ?Entries $entries = null;
    public ?Users $users = null;

    public function __construct(Database $db)
    {
        $this->db = $db;

        $this->users = new Users($this->db);
        $this->entries = new Entries($this->db);
    }

    public function login(string $email, string $password)
    {
        $users = new Users($this->db);
        $user = $users->findByEmail($email);

        if ($user && password_verify($password, $user->getPassword())) {
            $this->user = $user;
        }
    }

    public function getLoggedUser()
    {
        return $this->user;
    }

    /**
     * @return bool
     */
    public function logout()
    {
        if ($this->user) {
            $this->user = null;
        }
    }

    public function isLoggedIn(): bool
    {
        return $this->user !== null;
    }

    /**
     * @param  string  $email
     * @param  string  $username
     * @param  string  $password
     * @param  string  $passwordCheck
     *
     * @return array
     */
    public function register(
        string $email,
        string $username,
        string $password,
        string $passwordCheck
    ) {
        if ($password == $passwordCheck && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $user = new User($this->db);

            $password = password_hash($password, PASSWORD_DEFAULT);

            $user->fromArray(compact('email', 'username', 'password'));
            $user->save();

            return $user->toArray();
        }

        return [];
    }

    public function addEntry(string $yesterday, string $today, string $blocker)
    {
        if (!$this->isLoggedIn()) {
            throw new RuntimeException('Unauthorized');
        }

        $entry = new Entry($yesterday, $today, $blocker);
        $entry->setUser($this->getLoggedUser());
        $this->entries->store($entry);

        return $entry;
    }

    private function checkLogin()
    {
        if (!$this->isLoggedIn()) {
            throw new RuntimeException('Unauthorized');
        }
    }

    public function editEntry(string $id, string $yesterday, string $today, string $blocker)
    {
        $this->checkLogin();
        $entry = $this->entries->show($id);
        $entry->yesterday = $yesterday;
        $entry->today = $today;
        $entry->blocker = $blocker;

        $this->entries->update($id, $entry);
    }

    public function getEntry(string $id)
    {
        $this->checkLogin();

        $entries = $this->getLoggedUser()->getEntries();

        /* @var Entry[] $entries */
        foreach ($entries as $entry) {
            // Bad OOP
            if ($entry->id == $id) {
                return $entry->toArray();
            }
        }
        throw new RuntimeException('NotFound');
    }

    public function deleteEntry(string $id)
    {
        $this->checkLogin();

        $entries = $this->getLoggedUser()->getEntries();

        /* @var Entry[] $entries */
        foreach ($entries as $entry) {
            // Bad OOP
            if ($entry->id == $id) {
                $this->entries->delete($id);
                return;
            }
        }

        throw new RuntimeException('NotFound');
    }

    public function getAllEntries(): array
    {
        $this->checkLogin();

        $entries = $this->getLoggedUser()->getEntries();

        if (empty($entries)) {
            return [];
        }

        return array_map(fn (Entry $entry) => $entry->toArray(), $entries);
    }
}
