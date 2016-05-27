<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

require '../vendor/autoload.php';

require_once('../getui/' . 'IGt.Push.php');
require_once('../getui/' . 'igetui/template/IGt.NotificationTemplate.php');

//http的域名
define('HOST','http://sdk.open.api.igexin.com/apiex.htm');

//https的域名
//define('HOST','https://api.getui.com/apiex.htm');

define('APPKEY','VeESGoxCJo6CTT7jxOBAE6');
define('APPID','ffk7IuDdFn71eHw2yfkuh9');
define('MASTERSECRET','pnqpnUaK5D7YtbQfoXq7x');

$app = new \Slim\App;
$app->post('/push', function (Request $request, Response $response) {
    $targets = [];
    $options = [];
    $notification = [];
    $data = [];

    $payload = $request->getParsedBody();

    if ($payload) {

        if (array_key_exists("targets", $payload)) {
            $targets = $payload["targets"];
        }

        if (array_key_exists("options", $payload)) {
            $options = $payload["options"];
        }

        if (array_key_exists("notification", $payload)) {
            $notification = $payload["notification"];
        }

        if (array_key_exists("data", $payload)) {
            $data = $payload["data"];
        }

        $android_deviceids = [];
        $ios_deviceids = [];
        foreach ($targets as $target) {
            if ($target[1] == "android") {
                $android_deviceids[] = $target[0];
            } else if ($target[1] == "ios") {
                $ios_deviceids[] = $target[0];
            }
        }

        if (count($android_deviceids) > 0) {
            if (!$notification) {
                if ($data) {
                    var_dump($data);
                    $template = new IGtTransmissionTemplate();
                    $template->set_appId(APPID);
                    $template->set_appkey(APPKEY);
                    $template->set_transmissionType(2);
                    $template->set_transmissionContent(json_encode($data));

                    $message = new IGtListMessage();
                    $message->set_isOffline(true);                                      //是否离线
                    if ($options) {
                        if (array_key_exists("expired", $options)) {
                            $message->set_offlineExpireTime($options["expired"] * 1000);       //离线时间
                        }
                    }
                    $message->set_data($template);                                      //设置推送消息类型
                    $message->set_PushNetWorkType(0);                                   //设置是否根据WIFI推送消息，2为4G/3G/2G，1为wifi推送，0为不限制推送
                    $igt = new IGeTui(HOST,APPKEY,MASTERSECRET);
                    $contentId = $igt->getContentId($message);

                    $target_list = [];
                    foreach ($android_deviceids as $deviceid) {
                        $target = new IGtTarget();
                        $target->set_appId(APPID);
                        $target->set_clientId($deviceid);
                        //$target->set_alias(Alias);
                        $target_list[] = $target;
                    }

                    try {
                        $resp = $igt->pushMessageToList($contentId, $target_list);
                        $response->getBody()->write(json_encode($resp));
                    }catch(RequestException $e){
                        $response->getBody()->write(json_encode($e));
                    }
                }
            } else {
                $template = new IGtNotificationTemplate();
                $template->set_appId(APPID);                    //应用appid
                $template->set_appkey(APPKEY);                  //应用appkey
                if (array_key_exists("title", $payload["notification"])) {
                    $template->set_title($payload["notification"]["title"]);                   //通知栏标题
                }
                if (array_key_exists("body", $payload["notification"])) {
                    $template->set_text($payload["notification"]["body"]);       //通知栏内容
                }
                if (array_key_exists("icon", $payload["notification"])) {
                    $template->set_logo($payload["notification"]["icon"]);//通知栏logo
                }
                $template->set_isRing(true);                    //是否响铃
                $template->set_isVibrate(true);                 //是否震动
                $template->set_isClearable(true);               //通知栏是否可清除
                //$template->set_duration(BEGINTIME,ENDTIME);   //展示时段

                $message = new IGtListMessage();
                $message->set_isOffline(true);                                      //是否离线
                if ($options) {
                    if (array_key_exists("expired", $options)) {
                        $message->set_offlineExpireTime($options["expired"]);       //离线时间
                    }
                }
                $message->set_data($template);                                      //设置推送消息类型
                $message->set_PushNetWorkType(0);                                   //设置是否根据WIFI推送消息，2为4G/3G/2G，1为wifi推送，0为不限制推送

                $igt = new IGeTui(HOST,APPKEY,MASTERSECRET);
                $contentId = $igt->getContentId($message);

                $target_list = [];
                foreach ($android_deviceids as $deviceid) {
                    $target = new IGtTarget();
                    $target->set_appId(APPID);
                    $target->set_clientId($deviceid);
                    //$target->set_alias(Alias);
                    $target_list[] = $target;
                }

                try {
                    $resp = $igt->pushMessageToList($contentId, $target_list);
                    $response->getBody()->write(json_encode($resp));
                }catch(RequestException $e){
                    $response->getBody()->write(json_encode($e));
                }
            }
        }

        if (count($ios_deviceids) > 0) {
            if (!$notification) {
                if ($data) {
                    $template = new IGtAPNTemplate();

                    $apn = new IGtAPNPayload();
                    $apn->sound = $apn->APN_SOUND_SILENCE;
                    $apn->add_customMsg("data", json_encode($data));
                    $apn->contentAvailable = 1;

                    $template->set_apnInfo($apn);

                    $message = new IGtListMessage();
                    $message->set_data($template);                                      //设置推送消息类型

                    $igt = new IGeTui(HOST,APPKEY,MASTERSECRET);
                    $contentId = $igt->getAPNContentId(APPID, $message);
                    try {
                        $resp = $igt->pushAPNMessageToList(APPID, $contentId, $ios_deviceids);
                        $response->getBody()->write(json_encode($resp));
                    }catch(RequestException $e){
                        $response->getBody()->write(json_encode($e));
                    }
                }
            } else {
                $template = new IGtAPNTemplate();

                $alertmsg = new DictionaryAlertMsg();
                if (array_key_exists("title", $payload["notification"])) {
                    $alertmsg->title = $payload["notification"]["title"];
                }
                if (array_key_exists("body", $payload["notification"])) {
                    $alertmsg->body = $payload["notification"]["body"];
                }

                $apn = new IGtAPNPayload();
                $apn->alertMsg=$alertmsg;

                $template->set_apnInfo($apn);

                $message = new IGtListMessage();
                $message->set_data($template);                                      //设置推送消息类型

                $igt = new IGeTui(HOST,APPKEY,MASTERSECRET);
                $contentId = $igt->getAPNContentId(APPID, $message);
                try {
                    $resp = $igt->pushAPNMessageToList(APPID, $contentId, $ios_deviceids);
                    $response->getBody()->write(json_encode($resp));
                }catch(RequestException $e){
                    $response->getBody()->write(json_encode($e));
                }
            }
        }
    }

    return $response;
});

$app->run();
