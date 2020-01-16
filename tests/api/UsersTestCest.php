<?php

use Codeception\Configuration;
use MidoriKocak\Database;

class UsersTestCest
{
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

        $db = new Database('localhost','today','root','turgut');
        $db->delete($id, 'users');
    }

}
