<?php
declare(strict_types=1);

require __DIR__.'/vendor/autoload.php';

use MidoriKocak\Api;
use MidoriKocak\App;
use MidoriKocak\Database;

$db = new Database('localhost', 'today', 'root', 'turgut');
$app = new App($db);
$api = new Api();

$api->get('/', function () use ($app, $api) {
    echo json_encode("welcome to api");
});

$api->post('/register', function () use ($app, $api) {
    $input = (array)json_decode(file_get_contents('php://input'), true);
    $response = $app->register(
        $input['email'],
        $input['username'],
        $input['password'],
        $input['passwordCheck']
    );

    echo json_encode($response);
    http_response_code(201);
});

$api->post('/login', function () use ($app, $api) {
    $input = (array)json_decode(file_get_contents('php://input'), true);

    $app->login($input['email'], $input['password']);
    if ($app->isLoggedIn()) {
        echo json_encode(base64_encode($input['email'].':'.$input['password']));
    }
});

$api->get('/entries', function () use ($app, $api) {
    $api->auth(function () use ($app) {
        echo json_encode($app->getAllEntries());
    }, $app);
});

$api->get('/entries/{id}', function ($id) use ($app, $api) {
    $api->auth(function () use ($app, $id) {
        try {
            echo json_encode($app->entries->show($id));
        } catch (Exception $e) {
            http_response_code(404);
        }
    }, $app);
});

$api->put('/entries/{id}', function ($id) use ($app, $api) {
    $api->auth(function () use ($app, $id) {

        $input = (array)json_decode(file_get_contents('php://input'), true);
        $app->editEntry($id,
            $input['yesterday'],
            $input['today'],
            $input['blocker'],
            );

    }, $app);

});

$api->delete('/entries/{id}', function ($id) use ($app, $api) {
    $api->auth(function () use ($app, $id) {
        try {
            $app->deleteEntry($id);
            http_response_code(204);
        } catch (Exception $e) {
            http_response_code(404);
        }
    }, $app);
});

$api->post('/entries', function () use ($app, $api) {
    $api->auth(function () use ($app) {
        $input = (array)json_decode(file_get_contents('php://input'), true);
        $response = $app->addEntry(
            $input['yesterday'],
            $input['today'],
            $input['blocker'],
            );

        echo json_encode($response);
    }, $app);
    http_response_code(201);
});

//$app->register('mtkocak@gmail.com', 'midorikocak', 'turgut', 'turgut');

//$app->login('mtkocak@gmail.com', 'turgut');

//$app->addEntry('worked hard', 'cool', 'nothing');
//$app->addEntry('worked less', 'anything', 'nothing again');

//echo json_encode($app->getAllEntries());
