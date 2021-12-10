<?php

// Подключение автозагрузки через composer
require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use DI\Container;

session_start();
$app = AppFactory::create();

$container = new Container();
$container->set('renderer', function () {
    return new \Slim\Views\PhpRenderer(__DIR__ . '/../templates');
});

$container->set('flash', function () {
    return new \Slim\Flash\Messages();
});

//$app = AppFactory::createFromContainer($container);
//$app->addErrorMiddleware(true, true, true);

AppFactory::setContainer($container);
$app = AppFactory::create();

$app->addErrorMiddleware(true, true, true);


$dataBase = file_get_contents('./data.json');
$users = json_decode($dataBase, true);

$app->get('/', function ($request, $response) {
    $response->getBody()->write('Welcome to Slim!');
    return $response;
});

$app->get('/users/new', function ($request, $response) {
    $this->get('flash')->addMessage('success', 'User added!!!!!!!!!');
    $params = [
        'user' => ['name' => '', 'email' => '', 'id' => '']];
    return $this->get('renderer')->render($response, "users/new.phtml", $params);
});


$app->get('/users', function ($request, $response) use ($users) {
    $term = $request->getQueryParam('term');
    $messages = $this->get('flash')->getMessages();
    
    if ($term !== null) {
    $res = array_filter($users, fn($user) =>  str_contains($user['name'], $term));

    $params = ['flash' => $messages, 'users' => $res];
    }   else {
            $params = ['flash' => $messages, 'users' => $users, 'term' => $term];
    }
    return $this->get('renderer')->render($response, 'users/index.phtml', $params);
})->setName('users');


$app->post('/users', function ($request, $response) {
    $user = $request->getParsedBodyParam('user');

    $dataBase = file_get_contents('./data.json');
    $temp = json_decode($dataBase, true);
    if ($temp !== null) $tempArray = $temp;
    $tempArray[] = $user;
    $jsonData = json_encode($tempArray);
    file_put_contents('./data.json', $jsonData); 
    return $response->withRedirect('/users');
});


$app->get('/users/{id}', function ($request, $response, array $args) {
    $id = $args['id'];
    $data = file_get_contents('./data.json');
    $data2 = json_decode($data, true);

    $result = array_values(array_filter($data2, fn($user) => $user['id'] === $id));
    if (empty($result)) {
        return $response->withStatus(404);
    }
    $params = ['user' => $result];
    return $this->get('renderer')->render($response, 'users/show.phtml', $params);
})->setName('users');

$app->run();