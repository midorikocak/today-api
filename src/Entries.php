<?php

declare(strict_types=1);

namespace MidoriKocak;

use function array_key_exists;

class Entries
{
    /**
     * @var Database
     */
    private Database $db;

    private ?array $entries = null;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    public function index()
    {
        if ($this->entries) {
            return $this->entries;
        }

        $this->entries = array_map(
            fn ($entryData) => Entry::fromArray($entryData),
            $this->db->index('entries')
        );

        return $this->entries;
    }

    public function store(Entry $entry): Entry
    {
        $this->db->insert($entry->toArray(), 'entries');
        $entry->id = $this->db->lastInsertId();
        return $entry;
    }

    public function show(string $id): Entry
    {
        return $this->findById($id);
    }

    public function update(string $id, Entry $entry): void
    {
        $this->db->update($id, $entry->toArray(), 'entries');
    }

    public function delete(string $id): void
    {
        $this->db->delete($id, 'entries');
    }

    public function findByUserId(string $userId): array
    {
        $entriesData = $this->db->find($userId, 'user_id', 'entries');

        if (!$entriesData) {
           return [];
        }

        $entries = array_map(
            fn ($entryData) => Entry::fromArray($entryData),
            $entriesData
        );

        return $entries;
    }

    public function week($userId)
    {
        $query = new Query();
        $sevenDaysAgo = date('Y-m-d H:i:s', strtotime('-7 days'));
        $query->select('entries')->where('user_id', $userId)->and(['created_at' => '>='.$sevenDaysAgo]);

        $entriesData = $this->db->useQuery($query, true);

        if (!$entriesData) {
            return [];
        }

        $entries = array_map(
            fn ($entryData) => Entry::fromArray($entryData),
            $entriesData
        );

        return $entries;
    }

    public function month($userId)
    {
        $query = new Query();
        $monthAgo = date('Y-m-d H:i:s', strtotime('-31 days'));
        $query->select('entries')->where('user_id', $userId)->and(['created_at' => '>='.$monthAgo]);

        $entriesData = $this->db->useQuery($query, true);

        if (!$entriesData) {
            return [];
        }

        $entries = array_map(
            fn ($entryData) => Entry::fromArray($entryData),
            $entriesData
        );

        return $entries;
    }

    public function yesterday($userId)
    {
        $query = new Query();
        $yesterday = date('Y-m-d H:i:s', strtotime('yesterday'));
        $today = date('Y-m-d H:i:s', strtotime('today'));
        $query->select('entries')->where('user_id', $userId)
            ->and(['created_at'=> '>'.$yesterday])
            ->and(['created_at'=> '<'.$today]);

        $entriesData = $this->db->useQuery($query, true);

        if (!$entriesData) {
            return [];
        }

        $entries = array_map(
            fn ($entryData) => Entry::fromArray($entryData),
            $entriesData
        );

        return $entries;
    }

    public function today($userId)
    {
        $query = new Query();
        $today = date('Y-m-d H:i:s', strtotime('today'));
        $query->select('entries')->where('user_id', $userId)
            ->and(['created_at' => '>='.$today]);
        $entriesData = $this->db->useQuery($query, true);

        if (!$entriesData) {
            return [];
        }

        $entries = array_map(
            fn ($entryData) => Entry::fromArray($entryData),
            $entriesData
        );

        return $entries;
    }

    public function search($term, $userId)
    {
        return [];
    }

    public function findById(string $id): ?Entry
    {
        if ($this->entries) {
            return $this->entries[$id];
        }

        $entryData = $this->db->show($id, 'entries');
        if (!$entryData) {
            throw new \Exception('NotFound');
        }

        $entry = Entry::fromArray($entryData);

        if (array_key_exists('user_id', $entryData)) {
            $users = new Users($this->db);
            $user = $users->findById((string)$entryData['user_id']);
            $entry->setUser($user);
        }

        return $entry;
    }
}
