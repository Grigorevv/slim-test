<?php

$cart = [
    '1' => [
        "id" => "1",
        "name" => "One",
        "count" => 1
    ],
    '2' => [
        "id" => "2",
        "name" => "Two",
        "count" => 1
        ]

];



//if(!isset($item['count'])) $item['count'] = 1;
//else $item['count']++;

print_r($cart['1']['count']);