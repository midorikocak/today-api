<?php

declare(strict_types=1);

namespace MidoriKocak;

 class Users
 {
     /**
      * @var Database
      */
     private Database $db;

     public function __construct(Database $db)
     {
         $this->db = $db;
     }

     public function index()
     {
         return $this->db->index('users');
     }


     public function findById(string $id): ?User
     {
         $foundUser = $this->db->show($id, 'users');

         if ($foundUser == false) {
             return null;
         }

         $user = new User($this->db);
         $user->fromArray($foundUser);
         return $user;
     }


     public function findByEmail(string $email): ?User
     {
         $foundUser = $this->db->findOne($email, 'email', 'users');

         if ($foundUser == false) {
             return null;
         }

         $user = new User($this->db);
         $user->fromArray($foundUser);
         return $user;
     }


     public function findByUsername(string $username): ?User
     {
         $foundUser = $this->db->findOne($username, 'username', 'users');
         if ($foundUser == false) {
             return null;
         }
         $user = new User($this->db);
         $user->fromArray($foundUser);
         return $user;
     }
 }
