<?php

$new =[
    'id' => '1',
    'title' => 'neo',
    'content' => 'matrix'
];

//open or read json data
$data_results = file_get_contents('./data.json');
$tempArray = json_decode($data_results, true);

//append additional json to json file
$base[] = $tempArray;
$base[] = $new;
$jsonData = json_encode($base);
file_put_contents('./data.json', $jsonData); 