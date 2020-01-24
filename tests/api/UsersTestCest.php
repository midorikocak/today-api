<?php

use Codeception\Configuration;
use MidoriKocak\Database;

class UsersTestCest
{
    private $db;
    private $userId;

    private $email;
    private $username;
    private $password;

    private $email2;
    private $username2;
    private $password2;

    public function _before(ApiTester $I)
    {
        $config = Configuration::config();
        $apiSettings = Configuration::suiteSettings('api', $config);
        $this->email = $apiSettings['params']['email'];
        $this->username = $apiSettings['params']['username'];
        $this->password = $apiSettings['params']['password'];

        $this->email2 = $apiSettings['params']['email2'];
        $this->username2 = $apiSettings['params']['username2'];
        $this->password2 = $apiSettings['params']['password2'];

        $this->db = new Database($apiSettings['params']['dbhost'],$apiSettings['params']['dbname'],$apiSettings['params']['dbuser'],$apiSettings['params']['dbpass']);
        $this->db->query('TRUNCATE TABLE users');
        $this->db->query('TRUNCATE TABLE entries');
    }

    // Login Test

    public function registerTest(ApiTester $I)
    {
        $userData = [
            'username' => $this->username2,
            'email' => $this->email2,
            'password' => $this->password2,
            'passwordCheck' => $this->password2,
        ];

        $userJson = json_encode($userData);
        $I->sendPOST('/register', $userJson);
        $I->seeResponseCodeIs(\Codeception\Util\HttpCode::CREATED); // 201
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson();
        $response = json_decode($I->grabResponse(), true);
        $id = $response['id'];
        $this->db->delete($id, 'users');
    }

}
