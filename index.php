<?php

function cors() {

    // Allow from any origin
    if (isset($_SERVER['HTTP_ORIGIN'])) {
        // Decide if the origin in $_SERVER['HTTP_ORIGIN'] is one
        // you want to allow, and if so:
        header("Access-Control-Allow-Origin: {$_SERVER['HTTP_ORIGIN']}");
        header('Access-Control-Allow-Credentials: true');
        header('Access-Control-Max-Age: 86400');    // cache for 1 day
    }

    // Access-Control headers are received during OPTIONS requests
    if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {

        if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']))
            // may also be using PUT, PATCH, HEAD etc
            header("Access-Control-Allow-Methods: GET, POST, OPTIONS");         

        if (isset($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']))
            header("Access-Control-Allow-Headers: {$_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']}");

        exit(0);
    }

}

cors();

$api = preg_replace('/([^\/]+)[\/?].*/', '$1', ltrim($_SERVER['REQUEST_URI'], '/'));
if ($api === 'responses' and strstr($_SERVER['REQUEST_URI'], 'available_statuses')) {
  $api = 'available_statuses';
}
if (!file_exists("$api.json")) {
  header("HTTP/1.0 404 Not Found");
  print "$api.json not exists";
  return;
}

$r = file_get_contents("$api.json");
if ($api === 'state') {
  $d = json_decode($r);
  $d->data[0]->statuses[2]->count = rand(0, 100);
  $r = json_encode($d);
} elseif ($api === 'responses') {
  $d = json_decode($r);
  $job_id = file_get_contents('./data/position_id');
  $d->data = array_filter($d->data, function($v) use ($job_id) {
    $v->job_id = $job_id;
    return $v->job_id == $_GET['filter_id'];
  });
  $r = json_encode($d);
} elseif ($api === 'change_position') {
  $request_body = file_get_contents('php://input');
  $post = json_decode($request_body);
  // print_r($post);
  file_put_contents('./data/position_id', $post->new_position_id);
}

print $r;

