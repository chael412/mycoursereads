<?php
$page = isset($_GET['page']) ? $_GET['page'] : 1;
$apiUrl = "https://me.vivliotek.com/api/books?page=".$page;
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$response = curl_exec($ch);
if (curl_errno($ch)) {
    echo json_encode(["error" => curl_error($ch)]);
} else {
    echo $response;
}
curl_close($ch);
?>
