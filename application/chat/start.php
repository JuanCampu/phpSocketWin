<?php
 use Workerman\Worker;
use Workerman\WebServer;
use Workerman\Autoloader;
use PHPSocketIO\SocketIO;


// composer autoload

require_once __DIR__ . '/../../vendor/autoload.php';

$io = new SocketIO(2000);
$usernames = array();
$userRooms = array();
$clients = array();

$io->on('connection', function($socket){
    $socket->addedUser = false;
   
    // when the client emits 'new message', this listens and executes
    $socket->on('message', function ($data)use($socket){
        global $usernames, $clients,$userRooms;
        // we tell the client to execute 'new message'
       print_R($userRooms);
        
        if( $socket->room == $userRooms[$data['reciverId']]){
            //Entra si el usuario emisor ya se unio a una sala de conversación con el receptor y si el receptor también se encuentra en la sala
            print_r(1);
            $socket->broadcast->to($userRooms[$data['reciverId']])->emit('message',$data["msg"]);
            $msgInfo = [
                "msg" => $data["msg"],
                "crt" => false,
                "receptorId" => $data['userId'],
                "actualId" =>$data['reciverId']
            ];
            $socket->broadcast->to($userRooms[$data['reciverId']])->emit('addMessage',$msgInfo);
        }else{
            //Entra si el usuario emisor va uniser a la sala del usuario receptor.
            print_r(2);
            $socket->leave($socket->room); 
            $socket->room = $userRooms[$data['reciverId']];
            $socket->join($userRooms[$data['reciverId']]);
            $userRooms[$data['userId']] = $userRooms[$data['reciverId']];
            $socket->broadcast->to($userRooms[$data['reciverId']])->emit('message',$data["msg"]);
            $msgInfo = [
                "msg" => $data["msg"],
                "crt" => false,
                "receptorId" =>$data['userId'],
                "actualId" =>$data['reciverId']
            ];
            $socket->broadcast->to($userRooms[$data['reciverId']])->emit('addMessage',$msgInfo);
        }
        
    });

    $socket->on('isLoggedIn', function ($data)use($socket){
        global $usernames;
        // we tell the client to execute 'new message'
        if(array_key_exists($data['reciverId'],$usernames)){
            $socket->emit('isLoggedIn', true);
            print_r("userLoging");
        }else{
            print_r("usuario no conectado");
            $socket->emit('isLoggedIn', false);
            print_r("usernotLoging");
        } 
    });
   
    // when the client emits 'add user', this listens and executes
    $socket->on('add user', function ($data) use($socket){
        global $usernames,$clients,$io, $userRooms;
       if($socket->username == $data['userId']){
        $socket->emit('added', "usuario agregado al socket");
       }else{
          // we store the username in the socket session for this client
          $socket->username = $data['userId'];
          // add the client's username to the global list
          $usernames[$data['userId']]=$data['userId'];
          $socket->addedUser = true;
          $clients[$data['userId']] = $socket->id;
          $socket->room = $data['userId'];
          $socket->join($data['userId']);
          $userRooms[$data['userId']]=$data['userId'];
          echo $socket->id;
          print_r($userRooms);
          $socket->emit('added', "usuario agregado al socket");
       }
    });

    // when the client emits 'typing', we broadcast it to others
    $socket->on('typing', function () use($socket) {
        $socket->broadcast->emit('typing', array(
            'username' => $socket->username
        ));
    });

    // when the client emits 'stop typing', we broadcast it to others
    $socket->on('stop typing', function () use($socket) {
        $socket->broadcast->emit('stop typing', array(
            'username' => $socket->username
        ));
    });

    // when the user disconnects.. perform this
    $socket->on('disconnect', function () use($socket) {
        global $usernames, $numUsers;
        // remove the username from global usernames list
        if($socket->addedUser) {
            unset($usernames[$socket->username]);
            --$numUsers;

           // echo globally that this client has left
           $socket->broadcast->emit('user left', array(
               'username' => $socket->username,
               'numUsers' => $numUsers
            ));
        }
   });
   
});

if (!defined('GLOBAL_START')) {
    Worker::runAll();
} 
