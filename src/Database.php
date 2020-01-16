<?php

declare(strict_types=1);

namespace MidoriKocak;

use PDO;
use PDOException;

class Database
{
    /**
     * @var PDO
     */
    private PDO $db;

    public function __construct($host, $dbname, $user, $pass)
    {
        try {
            $options = [
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ];

            $this->db = new PDO("mysql:host=$host;dbname=$dbname", $user, $pass, $options);

            # SQLite Database
            //$DBH = new PDO("sqlite:my/database/path/database.db");
        } catch (PDOException $e) {
            echo $e->getMessage();
        }
    }

    public function lastInsertId(): string
    {
        return $this->db->lastInsertId();
    }

    public function insert(array $data, string $table): void
    {
        $keys = array_keys($data);
        $params = array_map(fn ($key) => ':' . $key, $keys);

        $statementKeys = implode(',', $keys);
        $statementParams = implode(',', $params);
        $query = "INSERT INTO $table ($statementKeys) value ($statementParams)";

        $statement = $this->db->prepare($query);

        $statement->execute($data);
    }

    public function index(string $table): array
    {
        $statement = $this->db->query("SELECT * FROM $table");

        return $statement->fetchAll();
    }

    public function show(string $id, string $table)
    {
        $statement = $this->db->prepare("SELECT * from $table WHERE id=:id");
        $statement->execute(compact('id'));
        return $statement->fetch();
    }

    public function find(string $value, string $field, string $table): array
    {
        $statement = $this->db->prepare("SELECT * from $table WHERE $field=:value");
        $statement->execute(compact('value'));
        return $statement->fetchAll();
    }

    public function findOne(string $value, string $field, string $table)
    {
        $statement = $this->db->prepare("SELECT * from $table WHERE $field=:value");
        $statement->execute(compact('value'));
        return $statement->fetch();
    }

    public function update(string $id, array $data, string $table): void
    {
        if (isset($data['id'])) {
            $id = $data['id'];
            unset($data['id']);
        }

        $keys = array_keys($data);
        $params = array_map(fn ($key) => $key . '=:' . $key, $keys);

        $statementParams = implode(', ', $params);

        $statement = $this->db->prepare("UPDATE $table SET $statementParams WHERE id=:id");
        $statement->execute(array_merge(['id' => $id], $data));
    }

    public function delete(string $id, string $table): void
    {
        $statement = $this->db->prepare("DELETE FROM $table WHERE id=:id");
        $statement->execute(['id' => $id]);
    }

    public function query(string $query)
    {
        return $this->db->query($query)->fetchAll();
    }
}
