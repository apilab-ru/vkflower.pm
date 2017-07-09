<?php

ob_start();
//error_reporting(E_ALL);

$id = "5331985";//"6097674";
$token = "KriUD6iDh3GFmG0ZM5in";//"xk1wy68QbftBu39JPbiQ";
$secret = "054cc9337ba878dfe4";
$service = "dedde5c3dedde5c3dedde5c360de80eec9ddedddedde5c387854ebd2f3a49d158fa48c7";
$api = '5744092dc26b80513a9a1279e3cd10be6deeb7904d97006e94b40caf4ecbb145491dc2773b768716c4117';

include 'vkontacte.php';

file_put_contents('test.txt', print_r($_FILES,true));
rename($_FILES['file']['tmp_name'],"test.png");

$vk = new vkontacte(array(
    'access_token' => $api,
    'secret'       => $secret
),$id);

$user = json_decode($_REQUEST['user'],1);

print_r($user);

$res = $vk->uploadPhoto(array(
    'path'    => $_SERVER['DOCUMENT_ROOT']. "/test.png",
    'title'   => 'test.png',
    'caption' => "Заказ : " . $_REQUEST['order']
),"243785804","145491189");

print_r($res);

$res = $vk->sendMessage(
        $user['id'],
        "Вы оформили заказ букета: " . $_REQUEST['order'] . PHP_EOL . "На сумму: " . $_REQUEST['price'] . "руб.",
        ['photo'.$res['owner_id']."_".$res['id']]
);

print_r($res);

$log = ob_get_clean();

$file = fopen('log.txt', 'a+');
fwrite($file, PHP_EOL . "####" . PHP_EOL);
fwrite($file, $log);
fclose($file);