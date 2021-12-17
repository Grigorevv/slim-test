<?php

use Slim\Factory\AppFactory;
use DI\Container;
use Slim\Middleware\MethodOverrideMiddleware;

require __DIR__ . '/../vendor/autoload.php';

class Validator
{
    public function validate(array $user)
    {
        $errors = [];
        if (empty($user['name'])) {
            $errors['name'] = "Can't be blank";
         }
        if (empty($user['email'])) {
            $errors['email'] = "Can't be blank";
         }
     return $errors;
    }
}

session_start();

$container = new Container();
$container->set('renderer', function () {
    return new Slim\Views\PhpRenderer(__DIR__ . '/../templates');
});
$container->set('flash', function () {
    return new Slim\Flash\Messages();
});

$app = AppFactory::createFromContainer($container);
$app->add(MethodOverrideMiddleware::class);
$app->addErrorMiddleware(true, true, true);

$router = $app->getRouteCollector()->getRouteParser();

function getUser($users, $id) {
   return array_values(array_filter($users, fn($user) => $user['id'] == $id))[0];
}

//1
$app->get('/', function ($request, $response) {
    return $this->get('renderer')->render($response, 'index.phtml');
});

//2
$app->get('/users', function ($request, $response) {
  $flash = $this->get('flash')->getMessages();
  $users = json_decode($request->getCookieParam('users', json_encode([])), true);

    $params = [
        'flash' => $flash,
        'users' => $users
    ];
    return $this->get('renderer')->render($response, 'users/index.phtml', $params);
})->setName('users');

//3
$app->get('/users/new', function ($request, $response) {
    $params = [
        'userData' => [],
        'errors' => []
    ];
    return $this->get('renderer')->render($response, 'users/new.phtml', $params);
});

//4
$app->post('/users', function ($request, $response) use ($router) {
    $userData = $request->getParsedBodyParam('user'); 
    $users = json_decode($request->getCookieParam('users', json_encode([])), true);
    $validator = new Validator();
    $errors = $validator->validate($userData);
    if (count($errors) === 0) {
        $this->get('flash')->addMessage('success', 'User has been created');
        $userId = mt_rand(0, 999999);
        $userData['id'] = $userId;
        $users[$userId] = $userData;
        $encodedUsers = json_encode($users);
        return $response->withHeader('Set-Cookie', "users={$encodedUsers}")->withRedirect($router->urlFor('users'));
    }
    $params = [
        'userData' => $userData,
        'errors' => $errors
    ];
    return $this->get('renderer')->render($response->withStatus(422), 'users/new.phtml', $params);
});

//5
$app->get('/users/{id}/edit', function ($request, $response, array $args) {
    $id = $args['id'];
    $users = json_decode($request->getCookieParam('users', json_encode([])), true);
    $user = getUser($users, $id);                 
    $params = [
        'user' => $user,
        'errors' => [],
        'userData' => $user
    ];
    return $this->get('renderer')->render($response, 'users/edit.phtml', $params);
})->setName('editUser');

//6

$app->patch('/users/{id}', function ($request, $response, array $args) use ($router)  {
    $id = $args['id'];
    $userData = $request->getParsedBodyParam('user'); 
    $users = json_decode($request->getCookieParam('users', json_encode([])), true);
    $user = getUser($users, $id); 
    $validator = new Validator();
    $errors = $validator->validate($userData);

    if (count($errors) === 0) {
        $user['name'] = $userData['name'];
        $user['email'] = $userData['email'];
        $users[$id] = $user;
        $encodedUsers = json_encode($users);
        $this->get('flash')->addMessage('success', 'User has been updated');
        return $response->withHeader('Set-Cookie', "users={$encodedUsers}")->withRedirect($router->urlFor('users'));
    }

    $params = [
        'user' => $user,
        'errors' => $errors,
        'userData' => $user
    ];

    $response = $response->withStatus(422);
    return $this->get('renderer')->render($response, 'users/edit.phtml', $params);
});

//7
$app->delete('/users/{id}', function ($request, $response, array $args) use ($router) {
    $id = $args['id'];
    $users = json_decode($request->getCookieParam('users', json_encode([])), true);
    unset($users[$id]);
    $encodedUsers = json_encode($users);
    $this->get('flash')->addMessage('success', 'User has been removed');
    return $response->withHeader('Set-Cookie', "users={$encodedUsers}")->withRedirect($router->urlFor('users'));
});

$app->run();
