<?php

declare(strict_types=1);

namespace MidoriKocak;

use \Exception;

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
        } else {
            throw new \Exception('User not found');
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

    public function addEntry(string $yesterday, string $today, string $blocker, ?string $createdAt)
    {
        if (!$this->isLoggedIn()) {
            throw new \Exception('Unauthorized');
        }

        $entry = new Entry($yesterday, $today, $blocker, $createdAt);
        $entry->setUser($this->getLoggedUser());
        $this->entries->store($entry);

        return $entry;
    }

    private function checkLogin()
    {
        if (!$this->isLoggedIn()) {
            throw new \Exception('Unauthorized');
        }
    }

    public function editEntry(string $id, string $yesterday, string $today, string $blocker, ?string $createdAt)
    {
        $this->checkLogin();
        $entry = $this->entries->show($id);
        $entry->yesterday = $yesterday;
        $entry->today = $today;
        $entry->blocker = $blocker;
        $entry->createdAt = $createdAt ?? $entry->createdAt;

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
        throw new \Exception('NotFound');
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

        throw new \Exception('NotFound');
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

    public function getWeekEntries(): array
    {
        $this->checkLogin();

        $userId = $this->getLoggedUser()->id;

        $entries = $this->entries->week($userId);

        if (empty($entries)) {
            return [];
        }

        return array_map(fn (Entry $entry) => $entry->toArray(), $entries);
    }

    public function getMonthEntries(): array
    {
        $this->checkLogin();

        $userId = $this->getLoggedUser()->id;

        $entries = $this->entries->month($userId);

        if (empty($entries)) {
            return [];
        }

        return array_map(fn (Entry $entry) => $entry->toArray(), $entries);
    }

    public function getYesterdayEntries(): array
    {
        $this->checkLogin();

        $userId = $this->getLoggedUser()->id;

        $entries = $this->entries->yesterday($userId);

        if (empty($entries)) {
            return [];
        }

        return array_map(fn (Entry $entry) => $entry->toArray(), $entries);
    }

    public function getTodayEntries(): array
    {
        $this->checkLogin();

        $userId = $this->getLoggedUser()->id;

        $entries = $this->entries->today($userId);

        if (empty($entries)) {
            return [];
        }

        return array_map(fn (Entry $entry) => $entry->toArray(), $entries);
    }

    public function search($term): array
    {
        $this->checkLogin();

        $userId = $this->getLoggedUser()->id;

        $entries = $this->entries->search($term, $userId);

        if (empty($entries)) {
            return [];
        }

        return array_map(fn (Entry $entry) => $entry->toArray(), $entries);
    }

    public function getSettings()
    {
        $this->checkLogin();

        $user = $this->getLoggedUser();

        if ($user) {
            return $user->toArray();
        }

        return [];
    }

    public function setSettings(array $inputData): void {


        $user = $this->getLoggedUser();

        if(array_key_exists('password',$inputData)){
            $password = $inputData['password'];
            $passwordCheck = $inputData['passwordCheck'] ?? null;

            if ($password !== $passwordCheck) {
                throw new \Exception('Bad Request');
            }
        }


        if ($user) {
            $userData = $user->toArray();

            $data = $userData;

            if (array_key_exists('username', $inputData) && $inputData['username'] != $userData['username']) {
                $data['username'] = $inputData['username'];
            }

            if (array_key_exists('email', $inputData) && $inputData['email'] != $userData['email']) {
                $data['email'] = $inputData['email'];
            }

            if (array_key_exists('password', $inputData) && $inputData['password'] != '') {
                $data['password'] = password_hash($inputData['password'] , PASSWORD_DEFAULT);
            }
        }

        if (!empty($data)) {
            $user->fromArray($data);
            $user->save();
        }
    }
}
