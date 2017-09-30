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
        global $usernames, $clients;
        // we tell the client to execute 'new message'
       
        if( $socket->room == $data['userRoom'] && !empty($socket->adapter->sids[$clients[$data['reciverId']]][$data['userRoom']])){
            print_r(1);
            $socket->broadcast->to($data['userRoom'])->emit('message',$data["msg"]);
            $msgInfo = [
                "msg" => $data["msg"],
                "room" => $data['userId'],
            ];
            $socket->broadcast->to($data['userRoom'])->emit('addMessage',$msgInfo);
        }else{
            print_r(2);
            $socket->leave($socket->room);
            if(empty($socket->adapter->sids[$clients[$data['reciverId']]][$data['userRoom']])){
                print_r(3);
                $socket->room = $data['reciverId'];
                $socket->join($data['reciverId']);
                $socket->broadcast->to($data['reciverId'])->emit('message',$data["msg"]);
                $msgInfo = [
                    "msg" => $data["msg"],
                    "room" =>$data['userId'],
                ];
                $socket->broadcast->to($data['reciverId'])->emit('addMessage',$msgInfo);
                $socket->leave($socket->room);
                $socket->room = $data['userRoom'];
                $socket->join($data['userRoom']);
                $userRooms[$data['userRoom']]=$data['userRoom'];
     
                
            }else{
                print_r(4);
                $socket->room = $data['userRoom'];
                $socket->join($data['userRoom']);
                $userRooms[$data['userRoom']]=$data['userRoom'];
                $socket->broadcast->to($data['userRoom'])->emit('message',$data["msg"]);
                $msgInfo = [
                    "msg" => $data["msg"],
                    "room" => $data['userId'],
                ];
                $socket->broadcast->to($data['userRoom'])->emit('addMessage',$msgInfo);
            }
        }
        
    });

    $socket->on('isLoggedIn', function ($data)use($socket){
        global $usernames;
        // we tell the client to execute 'new message'
        if(array_key_exists($data['reciverId'],$usernames)){
            $socket->emit('isLoggedIn', true);
          
        }else{
            print_r("usuario no conectado");
            $socket->emit('isLoggedIn', false);
        } 
    });
   
    // when the client emits 'add user', this listens and executes
    $socket->on('add user', function ($data) use($socket){
        global $usernames,$clients,$io;
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
          echo $socket->id;
          print_r($socket->rooms);
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
