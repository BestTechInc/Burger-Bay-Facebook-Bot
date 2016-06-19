<?php
/**
 * This file is a middleman between internal system and Facebook Messenger
 *
 * @author Adnan Siddiqi
 */

ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/../vendor/autoload.php';

$access_token = 'EAALZAvN8VjcEBAN1WfzKNVjmpUV5KYuLodHriThDixZA6qXBWrNvob4WpsDdPH5xj3HfTkWxYi6nnghKdZAg05PkKowRHGZAHoOZBMSxrZB2GMFOcXv3QaFa0O0mVs6zQNpLQ0NFaqCmokQioqDKVAJHDlx5MeuQvTH6PSeMjHnQZDZD';
$verify_token = 'burger_bay';


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


    }

    $query_internal = false;
    if ($message == 'CURRENT_MENU') {
        $url = '../site/pull.php?mode=' . $message;
        $query_internal = true;
    } else {
        $url = '../site/pull.php?mode=' . $message;
        $query_internal = true;
    }
    /**
     * Querying Internel Database about items
     */
    $request = Requests::get('http://0e6eb01a.ngrok.io/bots/Burger-Bay-Facebook-Bot/site/pull.php?mode=CURRENT_MENU',
        $headers);

    //print_r($request->body);
    $elements = $request->body;
    $elements = json_decode($elements,true);
    if ($query_internal) {
        $menu_message = [];
        $menu_message = [
            'type'    => 'template',
            'payload'       => ['template_type' => 'generic', 'elements' => $elements]
        ];

        $return_message['message'] = ['attachment' => $menu_message];
    }


    /**
     * Prepare Message to be Send to User
     */

    $j = '{
  "recipient":{
    "id":"1006627549374064"
  },
  "message":{
    "attachment":{
      "type":"template",
      "payload":{
        "template_type":"generic",
        "elements":[
          {
            "title":"Classic White T-Shirt",
            "image_url":"http://petersapparel.parseapp.com/img/item100-thumb.png",
            "subtitle":"Soft white cotton t-shirt is back in style",
            "buttons":[
              {
                "type":"web_url",
                "url":"https://petersapparel.parseapp.com/view_item?item_id=100",
                "title":"View Item"
              },
              {
                "type":"web_url",
                "url":"https://petersapparel.parseapp.com/buy_item?item_id=100",
                "title":"Buy Item"
              },
              {
                "type":"postback",
                "title":"Bookmark Item",
                "payload":"USER_DEFINED_PAYLOAD_FOR_ITEM100"
              }
            ]
          },
          {
            "title":"Classic Grey T-Shirt",
            "image_url":"http://petersapparel.parseapp.com/img/item101-thumb.png",
            "subtitle":"Soft gray cotton t-shirt is back in style",
            "buttons":[
              {
                "type":"web_url",
                "url":"https://petersapparel.parseapp.com/view_item?item_id=101",
                "title":"View Item"
              },
              {
                "type":"web_url",
                "url":"https://petersapparel.parseapp.com/buy_item?item_id=101",
                "title":"Buy Item"
              },
              {
                "type":"postback",
                "title":"Bookmark Item",
                "payload":"USER_DEFINED_PAYLOAD_FOR_ITEM101"
              }
            ]
          }
        ]
      }
    }
  }
}';

    //print_r($return_message);

//    print_r($menu_message);
    //echo nl2br(json_encode($return_message));

//    //API Url
    $url = 'https://graph.facebook.com/v2.6/me/messages?access_token=' . $access_token;
    $request = Requests::post($url, $headers,json_encode($return_message));
    print_r($request->body);

}
