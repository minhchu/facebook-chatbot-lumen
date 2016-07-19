<?php

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
*/

function sendMessage($data) {
    $jsonData = json_encode($data);

    $ch = curl_init('https://graph.facebook.com/v2.6/me/messages?access_token='.env('PAGE_TOKEN'));
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_POSTFIELDS, $jsonData);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($jsonData)
    ]);
    $response = curl_exec($ch);
    curl_close($ch);

    return $response;
}

$app->group(['prefix' => 'messenger'], function() use ($app) {
    $app->get('webhook', function(\Illuminate\Http\Request $request) {
        if ($request->input('hub_mode') == 'subscribe' &&
            $request->input('hub_verify_token') == env('BOT_TOKEN')) {
            return $request->input('hub_challenge');
        }

        return response('You are not authorized', 403);
    });

    $app->post('webhook', function(\Illuminate\Http\Request $request) {
        $messageObject = $request->all();

        if ($messageObject['object'] != 'page') {
            return response('Message must be sent from page', 403);
        }

        // maybe you want to log the response to debug
        // app('log')->debug(json_encode($messageObject));
        $sender  = $messageObject['entry'][0]['messaging'][0]['sender']['id'];
        $message = $messageObject['entry'][0]['messaging'][0]['message'];
        $text    = isset($message['text']) ? $message['text'] : 'Huh ?';
        $data = [
            'recipient' => [
                'id' => $sender
            ],
            'message' => [
                'text' => $text
            ]
        ];

        $response = sendMessage($data);

        return response('ok');
     });
 });