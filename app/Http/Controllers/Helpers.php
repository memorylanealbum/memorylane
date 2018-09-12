<?php
function cleanErrors($errors)
{
    $errors_messages = [];
    foreach($errors->getMessages() as $key => $message)
    {
        $errors_messages[$key] = $message[0];
    }
    $response = ["errors" => $errors_messages,"status" => 0];
    return response()
           ->json($response)
           ->header('Content-Type', 'application/json');
}
function success($data=[])
{
    $response = ["data" => $data,"status" => 1];
    return response()
           ->json($response)
           ->header('Content-Type', 'application/json');
}
function failure($errors=[])
{
    if(!App::isLocal())
        $errors = ["error" => "Something went wrong."];
    $response = ["errors" => $errors,"status" => 0];
    return response()
           ->json($response)
           ->header('Content-Type', 'application/json');
}