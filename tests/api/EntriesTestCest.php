<?php

use Codeception\Configuration;
use Codeception\Util\HttpCode;
use MidoriKocak\Database;

class EntriesTestCest
{
    private $email;
    private $username;
    private $password;

    private $entryData;
    private $db;
    private $userId;


    public function _before(ApiTester $I)
    {
        $config = Configuration::config();
        $apiSettings = Configuration::suiteSettings('api', $config);

        $this->entryData = [
            'userId' => 1,
            'yesterday' => 'çok çalıştım',
            'today' => 'wrote syllabus',
            'blocker' => 'heartbroken'
        ];

        $this->email = $apiSettings['params']['email'];
        $this->username = $apiSettings['params']['username'];
        $this->password = $apiSettings['params']['password'];

        $userData = [
            'username' => $this->username,
            'email' => $this->email,
            'password' => $this->password,
            'passwordCheck' => $this->password,
        ];

        $userJson = json_encode($userData);
        $I->sendPOST('/register', $userJson);
        $response = json_decode($I->grabResponse(), true);
        $id = $response['id'];

        $db = new Database($apiSettings['params']['dbhost'],$apiSettings['params']['dbname'],$apiSettings['params']['dbuser'],$apiSettings['params']['dbpass']);
        $this->db = $db;
        $this->userId = $id;
    }



    public function addEntryAndDeleteTest(ApiTester $I)
    {
        $I->amHttpAuthenticated($this->email, $this->password);

        $entryJson = json_encode($this->entryData);
        $I->sendPOST('/entries', $entryJson);
        $I->seeResponseCodeIs(\Codeception\Util\HttpCode::CREATED); // 201
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson(
            $this->entryData
        );
        $response = json_decode($I->grabResponse(), true);
        $id = $response['id'];

        $I->sendDELETE('/entries/'.$id);


    }

    public function getEntryAndDeleteTest(ApiTester $I)
    {
        $I->amHttpAuthenticated($this->email, $this->password);

        $entryJson = json_encode($this->entryData);
        $I->sendPOST('/entries', $entryJson);
        $I->seeResponseCodeIs(\Codeception\Util\HttpCode::CREATED); // 201
        $I->seeResponseIsJson();
        $response = json_decode($I->grabResponse(), true);
        $id = $response['id'];

        $I->sendGET('/entries/'.$id);
        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseContainsJson(
            $this->entryData
        );

        $I->sendDELETE('/entries/'.$id);

        $I->sendGET('/entries/'.$id);
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND); // 404

    }

    public function getNonExistentEntryTest(ApiTester $I)
    {
        $I->amHttpAuthenticated($this->email, $this->password);
        $I->sendGET('/entries/osman');
        $I->seeResponseCodeIs(\Codeception\Util\HttpCode::NOT_FOUND); // 404
    }

    public function getEntriesTest(ApiTester $I)
    {
        $I->amHttpAuthenticated($this->email, $this->password);

        $entryJson = json_encode($this->entryData);
        $I->sendGET('/entries');
        $I->seeResponseCodeIs(\Codeception\Util\HttpCode::OK); // 200
        $I->seeResponseIsJson();
    }

    public function editEntryTest(ApiTester $I)
    {
        $I->amHttpAuthenticated($this->email, $this->password);

        $entryJson = json_encode($this->entryData);
        $I->sendPOST('/entries', $entryJson);
        $I->seeResponseCodeIs(\Codeception\Util\HttpCode::CREATED); // 201
        $I->seeResponseIsJson();
        $response = json_decode($I->grabResponse(), true);
        $id = $response['id'];

        $updatedEntryData = [
            'userId' => '1',
            'yesterday' => 'change yesterday',
            'today' => 'If I could',
            'blocker' => 'I\'d erase pain'
        ];

        $I->sendPUT('/entries/'.$id, json_encode($updatedEntryData));
        $I->seeResponseCodeIs(HttpCode::OK); // 200

        $I->sendGET('/entries/'.$id);

        $I->seeResponseCodeIs(HttpCode::OK); // 200
        $I->seeResponseContainsJson(
            $updatedEntryData
        );

        $I->sendDELETE('/entries/'.$id);

        $I->sendGET('/entries/'.$id);
        $I->seeResponseCodeIs(HttpCode::NOT_FOUND); // 404

    }
}
