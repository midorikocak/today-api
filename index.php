<?php
declare(strict_types=1);

require __DIR__.'/vendor/autoload.php';

use MidoriKocak\Api;
use MidoriKocak\App;
use MidoriKocak\Database;

$db = new Database('localhost', 'today', 'root', 'turgut');
$app = new App($db);
$api = new Api();


$api->setAuthenticator($app);


$api->get('/', function () {
    echo json_encode("welcome to api");
});

$api->post('/register', function () use (&$app, &$api) {
    $input = (array)json_decode(file_get_contents('php://input'), true);
    if (empty($input['email']) ||
        empty($input['password']) ||
        empty($input['passwordCheck']) ||
        empty($input['username'])
    ) {
        $api->responseCode(400);
    } else {
        try {
            $response = $app->register(
                $input['email'],
                $input['username'],
                $input['password'],
                $input['passwordCheck']
            );
            $api->responseCode(201);
            echo json_encode($response);
        } catch (Exception $e) {
            echo json_encode($e->getMessage());
            $api->responseCode(400);
        }
    }
});

$api->post('/login', function () use (&$app, &$api) {
    $input = (array)json_decode(file_get_contents('php://input'), true);

    if (empty($input['email']) || empty($input['password'])) {
        echo json_encode('Invalid Credentials');
        $api->responseCode(401);
    } else {
        $app->login($input['email'], $input['password']);
        if ($app->isLoggedIn()) {
            echo json_encode(base64_encode($input['email'].':'.$input['password']));
            $api->responseCode(200);
        }
    }
});


$api->auth(function () use (&$app, &$api) {

    $api->get('/entries', function () use (&$app) {
        echo json_encode($app->getAllEntries());
    });

    $api->get('/settings', function () use (&$app) {
        echo json_encode($app->getSettings());
    });

    $api->put('/settings', function () use (&$app) {

        $input = (array)json_decode(file_get_contents('php://input'), true);

        $app->setSettings($input);
    });

    $api->get('/week', function () use (&$app) {
        echo json_encode($app->getWeekEntries());
    });

    $api->get('/month', function () use (&$app) {
        echo json_encode($app->getMonthEntries());
    });

    $api->get('/yesterday', function () use (&$app) {
        echo json_encode($app->getYesterdayEntries());
    });

    $api->get('/today', function () use (&$app) {
        echo json_encode($app->getTodayEntries());
    });

    $api->get('/entries/{id}', function ($id) use (&$app, &$api) {
        try {
            echo json_encode($app->entries->show($id));
            $api->responseCode(200);
        } catch (Exception $e) {
            echo json_encode($e->getMessage());
            $api->responseCode(404);
        }
    });

    $api->get('/search/{term}', function ($term) use (&$app, &$api) {
        try {
            if (!empty($term)) {
                echo json_encode($app->search($term));
                $api->responseCode(200);
            }
        } catch (Exception $e) {
            echo json_encode($e->getMessage());
            $api->responseCode(404);
        }
    });

    $api->put('/entries/{id}', function ($id) use (&$app) {

        $input = (array)json_decode(file_get_contents('php://input'), true);
        $app->editEntry($id,
            $input['yesterday'],
            $input['today'],
            $input['blocker'],
            $input['createdAt'] ?? null,
            );

    });

    $api->delete('/entries/{id}', function ($id) use (&$app, &$api) {
        $app->deleteEntry($id);
        $api->responseCode(204);
    });

    $api->post('/entries', function () use (&$app, &$api) {
        $input = (array)json_decode(file_get_contents('php://input'), true);
        if (empty($input['yesterday']) ||
            empty($input['today']) ||
            empty($input['blocker'])
        ) {
            $api->responseCode(400);
        } else {
            $response = $app->addEntry(
                $input['yesterday'],
                $input['today'],
                $input['blocker'],
                $input['createdAt'] ?? null,
                );

            echo json_encode($response);
        }
        $api->responseCode(201);
    });
});
