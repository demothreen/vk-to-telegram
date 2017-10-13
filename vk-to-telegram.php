<?php

if (!isset($_REQUEST)) {
    return;
}

require_once('./vendor/autoload.php');

$dotenv = new \Dotenv\Dotenv(__DIR__);
$dotenv->load();

$vkGroupID = getenv('VK_GROUP_ID');
$serverAnswer = getenv('SERVER_ANSWER');
$telegramChatId = getenv('TELEGRAM_CHAT_ID');
$telegramToken = getenv('TELEGRAM_TOKEN');

//Строка для подтверждения адреса сервера из настроек Callback API
$answer = [
    $vkGroupID => $serverAnswer,
];


$file = 'vk_log.txt';
$data = json_decode(file_get_contents('php://input'));

//функция для записи в логи
function recordLog($file, $data) {
    file_put_contents($file, var_export($data, true) . "\n\n", FILE_APPEND);
}
recordLog($file, $data);

//Если пришло уведомление для подтверждения адреса
if ($data->type == 'confirmation') {
    if (array_key_exists($data->group_id, $answer)) {
        echo $answer[$data->group_id];
    }
    die();
}

$telegramMessage = '';

//парсим дату/время сообщения

function getDateTime($data) {
    return "Дата: " . date('d.m.Y H:i:s', $data->object->date);
}

//парсим в какую группу пришло сообшение

function getGroup($data) {
    return "\nГруппа: https://vk.com/club{$data->group_id}: ";
}
//парсим идентификатор сообщения

function getTextId($data) {
    return "\nИдентификатор сообщения: <<{$data->object->id}>>";
}

//парсим текст сообщения

function getBody($data) {
    return "\n\nТекст сообщения: '{$data->object->body}'";
}

//парсим текст комментария

function getComment($data) {
    return "\n\nТекст комментария: '{$data->object->text}'";
}

//парсим от кого пришло сообщение

function getUserMessage($data) {
    return "\n\nОт:  https://vk.com/id{$data->object->user_id}";
}

//парсим от кого комментарий

function getUserComment($data) {
    $from_id = $data->object->from_id;
    if ($from_id > 0) {
        return "\n\nОт:  https://vk.com/id{$from_id}";
    } elseif ($from_id < 0) {
        return "\n\nОт:  https://vk.com/club".abs($from_id)."";
    }
}

//парсим ссылку на пост

function getPostLink($data) {
    return "\nПост:  https://vk.com/club{$data->group_id}?w=wall-{$data->group_id}_{$data->object->post_id};";
}

//парсим ссылку на фото

function getPhotoLink($data) {
    return "\nК изображению:  https://vk.com/club{$data->group_id}?z=photo-{$data->group_id}_{$data->object->photo_id};";
}

//парсим ссылку на видео

function getVideoLink($data) {
    return "\nК видео:  https://vk.com/videos-{$data->group_id}?z=video-{$data->group_id}_{$data->object->video_id};";
}

//парсим кто удалил комментарий

function getUserCommentDel($data) {
    return "\n\nКто удалил комментарий:  https://vk.com/id{$data->object->deleter_id}";
}
//парсим ссылку на новосозданный пост

function getNewPostLink($data) {
    return "\nПост:  https://vk.com/club{$data->group_id}?w=wall-{$data->group_id}_{$data->object->id};";
}
//парсим создателя поста

function getUserPostCreate($data) {
    return "\n\nОпубликовал запись:  https://vk.com/id{$data->object->created_by}";
}
//парсим ссылку на обсуждение

function getTopicLink($data) {
    return "\nОбсуждение:  https://vk.com/topic-{$data->group_id}_{$data->object->topic_id};";
}
//парсим ссылку на товар

function getMarketLink($data) {
    return "\nТовар:  https://vk.com/market-{$data->group_id}?w=product-{$data->group_id}_{$data->object->item_id};";
}
//пришло новое сообщение в группу
if ($data->type == 'message_new') {
    $telegramMessage = getDateTime($data)
        . "\nПришло личное сообщение "
        . getGroup($data)
        . getTextId($data)
        . getBody($data)
        . getUserMessage($data);
}
//добавлен новый комментарий к фото
if ($data->type == 'photo_comment_new') {
    $telegramMessage = getDateTime($data)
        . "\nДобавлен комментарий "
        . getPhotoLink($data)
        . getGroup($data)
        . getTextId($data)
        . getComment($data)
        . getUserComment($data);
}
//добавлен комментарий на стене
if ($data->type == 'wall_reply_new') {
    $telegramMessage = getDateTime($data)
        . "\nДобавлен комментарий к посту "
        . getPostLink($data)
        . getGroup($data)
        . getTextId($data)
        . getComment($data)
        . getUserComment($data);
}
//изменен комментарий на стене
if ($data->type == 'wall_reply_edit') {
    $telegramMessage = getDateTime($data)
        . "\nИзменен комментарий "
        . getPostLink($data)
        . getGroup($data)
        . getTextId($data)
        . getComment($data)
        . getUserComment($data);
}
//удален комментарий на стене
if ($data->type == 'wall_reply_delete') {
    $telegramMessage =
         "\nУдален комментарий "
        . getPostLink($data)
        . getGroup($data)
        . getTextId($data)
        . getUserCommentDel($data);
}
//добавлен комментарий к видео
if ($data->type == 'video_comment_new') {
    $telegramMessage = getDateTime($data)
        . "\nДобавлен комментарий "
        . getVideoLink($data)
        . getGroup($data)
        . getTextId($data)
        . getComment($data)
        . getUserComment($data);
}
//добавлена новая запись на стене
if ($data->type == 'wall_post_new') {
    $telegramMessage = getDateTime($data)
        . "\nДобавлен новый пост "
        . getNewPostLink($data)
        . getGroup($data)
        . getTextId($data)
        . getComment($data)
        . getUserPostCreate($data);
}
//добавлен новый комментарий в обсуждениях
if ($data->type == 'board_post_new') {
    $telegramMessage = getDateTime($data)
        . "\nДобавлен новый комментарий к обсуждению "
        . getTopicLink($data)
        . getGroup($data)
        . getTextId($data)
        . getComment($data)
        . getUserComment($data);
}
//отредактирован новый комментарий в обсуждениях
if ($data->type == 'board_post_edit') {
    $telegramMessage = getDateTime($data)
        . "\nОтредактирован комментарий в обсуждении "
        . getTopicLink($data)
        . getGroup($data)
        . getTextId($data)
        . getComment($data)
        . getUserComment($data);
}
//Удален комментарий в обсуждениях
if ($data->type == 'board_post_delete') {
    $telegramMessage = getDateTime($data)
        . "\nУдален комментарий в обсуждении "
        . getTopicLink($data)
        . getGroup($data)
        . getTextId($data)
        . getUserComment($data);
}
//добавлен новый комментарий к товару
if ($data->type == 'market_comment_new') {
    $telegramMessage = getDateTime($data)
        . "\nДобавлен новый комментарий к товару "
        . getMarketLink($data)
        . getGroup($data)
        . getTextId($data)
        . getComment($data)
        . getUserComment($data);
}

//Функция для отправки сообщения в группу телеграмма

function sendMessage($chatId, $token, $msg) {
    $msg = urlencode($msg);
    $result = file_get_contents("https://api.telegram.org/bot{$token}/sendMessage?chat_id={$chatId}&text={$msg}");
}

sendMessage($telegramChatId, $telegramToken, $telegramMessage);

// А вдруг в сообщении ничего нет?
if ($telegramMessage) {
    recordLog($file, $telegramMessage);
}

//Возвращаем "ok" серверу Callback API

echo('ok');


