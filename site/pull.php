<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);
require_once 'config.php';


function get_buttons($id, $title)
{
    $buttons[] = ['type'    => 'postback',
                  'title'   => 'Select item',
                  'payload' => json_encode(['ID' => $id, 'title' => $title])
    ];
    $buttons[] = ['type' => 'postback', 'title' => 'I am done', 'payload' => "AM_DONE"];

    return $buttons;
}


$db = new mysqli($config['SERVER'], $config['USER'], $config['PASSWORD'], $config['DATABASE']);

if ($db->connect_errno > 0) {
    die('Unable to connect to database [' . $db->connect_error . ']');
}
$items = [];
$query = "SELECT * from items";
$res = $db->query($query) or die(mysqli_error($db));
$buttons = [];

while ($row = $res->fetch_assoc()) {
    $items[] = [
        'title'     => $row['title'],
        'image_url' => 'http://c323735f.ngrok.io/bots/Burger-Bay-Facebook-Bot/site/images/' . $row['image_url'],
        'subtitle'  => substr($row['description'], 0, 100),
        'buttons'   => get_buttons($row['id'],$row['title'])
    ];
}

echo json_encode($items);

