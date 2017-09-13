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
  $d->data = array_values(array_filter($d->data, function($v) {
    return $v->job_id == $_GET['filter_id'];
  }));
  $r = json_encode($d);
} elseif ($api === 'change_position') {
  $request_body = file_get_contents('php://input');
  $responses = json_decode(file_get_contents('./responses.json'));
  $post = json_decode($request_body);
  foreach ($post->response_ids as $response_id) {
    foreach ($responses->data as $response) {
      if ($response->id == $response_id) {
        $response->job_id = $post->new_position_id;
      }
    }
  }
  file_put_contents('./responses.json', json_encode($responses));
  $responses->data = array_values(array_filter($responses->data, function ($v) use ($post) {
    return in_array($v->id, $post->response_ids);
  }));
  print json_encode($responses);
  die();
} elseif ($api === 'change_status') {
  $request_body = file_get_contents('php://input');
  $post = json_decode($request_body);
  file_put_contents('./data/status_id', $post->status_id);
}

print $r;

