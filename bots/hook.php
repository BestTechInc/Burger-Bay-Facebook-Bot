<?php
/**
 * This file is a middleman between internal system and Facebook Messenger
 *
 * @author Adnan Siddiqi
 */
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/../vendor/autoload.php';
require_once 'config.php';


$access_token = 'EAALZAvN8VjcEBAN1WfzKNVjmpUV5KYuLodHriThDixZA6qXBWrNvob4WpsDdPH5xj3HfTkWxYi6nnghKdZAg05PkKowRHGZAHoOZBMSxrZB2GMFOcXv3QaFa0O0mVs6zQNpLQ0NFaqCmokQioqDKVAJHDlx5MeuQvTH6PSeMjHnQZDZD';
$verify_token = 'burger_bay';
$query_internal = false;
$selected_items = [];

$db = new mysqli($config['SERVER'], $config['USER'], $config['PASSWORD'], $config['DATABASE']);

if ($db->connect_errno > 0) {
    die('Unable to connect to database [' . $db->connect_error . ']');
}

function getTextMessage($message, $recipient_id)
{
    $return_message = [];
    $return_message['recipient'] = ['id' => $recipient_id];
    $return_message['message'] = ['text' => $message];

    return $return_message;
}

if (!empty($_REQUEST['hub_mode']) && $_REQUEST['hub_mode'] == 'subscribe' && $_REQUEST['hub_verify_token'] == $verify_token) {
    // Webhook setup request
    echo $_REQUEST['hub_challenge'];
} else {

    $input = json_decode(file_get_contents("php://input"), true, 512, JSON_BIGINT_AS_STRING);
    $sender = $input['entry'][0]['messaging'][0]['sender']['id'];
    $message = '';
    if (isset($input['entry'][0]['messaging'][0]['message'])) {
        $message = $input['entry'][0]['messaging'][0]['message']['text'];
    } elseif (isset($input['entry'][0]['messaging'][0]['postback'])) {
        $message = $input['entry'][0]['messaging'][0]['postback']['payload'];
    }

    /**
     * Message returned from User
     */

    $message_to_reply = '';

    $return_message = [];
    $return_message['recipient'] = ['id' => $sender];
    $return_message['message'] = ['text' => $message_to_reply];

    $headers = array('Content-Type' => 'application/json');

    /**
     * Asking for help, send Menu
     */

    if ($message == 'help') {

        $return_message['recipient'] = ['id' => $sender];
        $menu_message = [];
        $buttons = [];
        $buttons[] = ['type' => 'postback', 'title' => 'Menu', 'payload' => 'CURRENT_MENU'];
        $buttons[] = ['type' => 'postback', 'title' => 'Todays deals', 'payload' => 'TODAY_DEALS'];
        $menu_message = [
            'type'    => 'template',
            'payload' => ['template_type' => 'button', 'text' => 'Select one of the option', 'buttons' => $buttons]
        ];

        $return_message['message'] = ['attachment' => $menu_message];


    } elseif ($message == 'CURRENT_MENU') {
        $url = '../site/pull.php?mode=' . $message;
        $query_internal = true;
    } elseif (strpos($message, 'ID') !== false) {
        //print "GET THE ITEM";
        $item = json_decode($message, true);
        //print_r($item);
        $return_message = getTextMessage('You selected ' . $item['title'], $sender);
        $selected_items[] = $item['title'];
        $_SESSION['BB_ORDER'] = $selected_items;
        $query = "INSERT INTO orders(item_id,status,sender_id) VALUES ('{$item['ID']}',-1,$sender)";
        $res = $db->query($query) or die(mysqli_error($db));

        $query_internal = false;
    } elseif ($message == 'AM_DONE') {

        $return_message['recipient'] = ['id' => $sender];
        $menu_message = [];
        $buttons = [];
        $buttons[] = ['type' => 'postback', 'title' => 'Yes', 'payload' => 'ORDER_YES'];
        $buttons[] = ['type' => 'postback', 'title' => 'No', 'payload' => 'ORDER_NO'];
        $menu_message = [
            'type'    => 'template',
            'payload' => [
                'template_type' => 'button',
                'text'          => 'Do you want to see your order?',
                'buttons'       => $buttons
            ]
        ];

        $return_message['message'] = ['attachment' => $menu_message];
    } elseif ($message == 'ORDER_YES') {
        $query = "select title from items i inner join orders o ON i.id = o.item_id where sender_id = '$sender'";
        $res = $db->query($query) or die(mysqli_error($db));
        $selected_items = '';

        while($row = $res->fetch_assoc()) {
            $selected_items.= $row['title'].',';
            $return_message = getTextMessage($selected_items, $sender);
        }

    } elseif ($message == 'ORDER_NO') {
        $return_message = getTextMessage("Alright! Hope you'd try our meal someday.", $sender);
    }
    else {
        $return_message = getTextMessage("huh I did not get you. Type 'help' to learn further about how can I serve you.",
            $sender);
    }
    /**
     * Querying Internal Database about items
     */


    if ($query_internal) {

        $request = Requests::get('http://c323735f.ngrok.io/bots/Burger-Bay-Facebook-Bot/site/pull.php?mode=CURRENT_MENU',
            $headers);
        $elements = $request->body;
        $elements = json_decode($elements, true);
        $menu_message = [];
        $menu_message = [
            'type'    => 'template',
            'payload' => ['template_type' => 'generic', 'elements' => $elements]
        ];

        $return_message['message'] = ['attachment' => $menu_message];

    }

    /**
     * Prepare Message to be Send to User
     */


    if (isset($return_message['message']['text']) && $return_message['message']['text'] != '') {
        $url = 'https://graph.facebook.com/v2.6/me/messages?access_token=' . $access_token;
        $request = Requests::post($url, $headers, json_encode($return_message));
        print_r($request->body);
    } else {
        $url = 'https://graph.facebook.com/v2.6/me/messages?access_token=' . $access_token;
        $request = Requests::post($url, $headers, json_encode($return_message));
        print_r($request->body);
    }

}
