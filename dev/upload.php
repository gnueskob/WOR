<?php

$data = [];
foreach ($_FILES as $key => $FILE) {
    if ($FILE['error'] === 0) {
        $data[$key] = new CURLFile($FILE['tmp_name'], $FILE['type'], $FILE['name']);
    }
}

$url = 'localhost/wor/plan/upload';
$headers = ["Content-Type:multipart/form-data"];
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
