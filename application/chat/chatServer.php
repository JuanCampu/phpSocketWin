<?php
header('Access-Control-Allow-Origin: *');  
defined('BASEPATH');
require '../../vendor/autoload.php';
$io = new \PHPSocketIO\SocketIO(3000);
$io->on('connection', function($socket)use($io){
  print_r("User Connected");
  $socket->on('chat message', function($msg)use($io){
    $io->emit('chat message', $msg);
  });
  // cuando se ejecute en el cliente el evento new message
     $socket->on('message', function($message) use($socket)
     {
         print_r($message);
         //me notifico del mensaje que he escrito
         $socket->emit("message",$message);

         //notificamos al resto del mensaje que he escrito
         $socket->broadcast->emit("message", array(
             "action" => "chat",
             "message" => $socket->username . " dice: " . $message
         ));
     });

});



\Workerman\Worker::runAll();
