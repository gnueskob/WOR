<?php

print_r($_FILES);

$localFile = $_FILES['building']['tmp_name'];
$type = $_FILES['building']['type'];
$name = $_FILES['building']['name'];
$data = [
    'building' => '@' . $localFile .
        ';type=' . $type .
        ';filename=' . $name
];

$url = 'localhost/test';
$headers[] = "Content-Type:multipart/form-data";
$options = [
    CURLOPT_HTTPHEADER => $headers,
    CURLOPT_URL => $url,
    CURLOPT_POST => true,
    CURLOPT_HEADER => true,
    CURLOPT_POSTFIELDS => $data,
    CURLOPT_RETURNTRANSFER => true
];

// Connecting to website.
$ch = curl_init();
curl_setopt_array($ch, $options);
$res = curl_exec($ch);

var_dump($res);

if (curl_errno($ch)) {
    $msg = curl_error($ch);
} else {
    $msg = 'File uploaded successfully.';
}

curl_close($ch);

$return = ['msg' => $msg];

echo json_encode($return);
