<?php

declare(strict_types=1);

namespace MidoriKocak;

use DateTime;
use function array_key_exists;

class Entry
{
    public ?string $id = null;

    public ?string $userId = null;

    private ?User $user = null;

    /**
     * @var string
     */
    public ?string $createdAt = null;
    /**
     * @var string
     */
    public ?string $updatedAt = null;

    /**
     * @var string
     */
    public string $yesterday;
    /**
     * @var string
     */
    public string $today;
    /**
     * @var string
     */
    public string $blocker;

    public function __construct(
        string $yesterday,
        string $today,
        string $blocker,
        ?string $createdAt = null,
        ?string $updatedAt = null
    ) {
        if (!$createdAt || $createdAt == '') {
            $createdAt = date('Y-m-d H:i:s');
        }

        if (!$updatedAt || $updatedAt == '') {
            $updatedAt = date('Y-m-d H:i:s');
        }

        $this->yesterday = $yesterday;
        $this->today = $today;
        $this->blocker = $blocker;

        $this->createdAt = $createdAt;
        $this->updatedAt = $updatedAt;
    }

    public static function fromArray(array $data): Entry
    {
        $entry = new Entry($data['yesterday'], $data['today'], $data['blocker']);

        if (array_key_exists('id', $data)) {
            $entry->id = (string)$data['id'];
        }

        if (array_key_exists('user_id', $data)) {
            $entry->userId = (string)$data['user_id'];
        }

        if (array_key_exists('created_at', $data)) {
            $entry->createdAt = $data['created_at'];
        }

        if (array_key_exists('updated_at', $data)) {
            $entry->updatedAt = $data['updated_at'] ?? date('Y-m-d H:i:s');
        }

        return $entry;
    }

    public function setUser(User $user)
    {
        $this->user = $user;
        $this->userId = $user->id;
    }

    public function toArray(): array
    {
        $data = [
            'today' => $this->today,
            'yesterday' => $this->yesterday,
            'blocker' => $this->blocker,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt
        ];

        $userId = $this->user->id ?? $this->userId;

        if ($userId) {
            $data['user_id'] = (string)$userId;
        }

        if ($this->id) {
            $data['id'] = (string)$this->id;
        }

        return $data;
    }
}
