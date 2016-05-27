<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

require '../vendor/autoload.php';

define("APP_KEY", "9d984fb9e1683eec496467ef");
define("MASTER_SECRET", "7153127a3c85c5849c763907");

$app = new \Slim\App(array(
    'debug' => true
));

$app->post('/push', function (Request $request, Response $response) {
    try {
    $deviceids = null;
    $expired = null;
    $icon = null;
    $title = null;
    $body = null;
    $notification = null;
    $data = null;

    $payload = $request->getParsedBody();

    if ($payload) {

        if (array_key_exists("targets", $payload)) {
            foreach ($payload["targets"] as $target) {
                $deviceids[] = $target[0];
            }
        }

        if (array_key_exists("options", $payload)) {
            $options = $payload["options"];
            if (array_key_exists("expired", $options)) {
                $expired = $options["expired"];
            }
        }

        if (array_key_exists("notification", $payload)) {
            $notification = $payload["notification"];
            if (array_key_exists("title", $notification)) {
                $title = $notification["title"];
            }
            if (array_key_exists("body", $notification)) {
                $body = $notification["body"];
            }
        }

        if (array_key_exists("data", $payload)) {
            $data = $payload["data"];
        }

        $client = new JPush(APP_KEY, MASTER_SECRET);

        $payload = $client->push();
        $payload->setPlatform('ios', 'android')
                ->addRegistrationId($deviceids)
                ->setOptions(null, $expired);

        if ($notification) {
            $payload->addAndroidNotification($body, $title);
//        }
//        if ($data) {
//            $payload->addIosNotification("jpush require alert for ios", JPush::DISABLE_SOUND, null, true);
//        } else {
            $payload->addIosNotification($body);
        }
        if ($data) {
            $payload->setMessage(json_encode($data));
        }

        $result = $payload->send();
        $response->getBody()->write(json_encode($result));

    }
    }catch(Exception $e) {
        $response->getBody()->write(var_dump($e));
    }

    return $response;
});

$app->run();
