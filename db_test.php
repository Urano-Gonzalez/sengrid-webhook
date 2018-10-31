<?php

$namefile = "test.json";
$data = file_get_contents($namefile);
if (!$data){
    echo "no existe el archivo $namefile";
    exit();
}
$mysql = new mysqli ('localhost', 'user', 'password', 'database');
if ($mysql->connect_errno) {
    echo "no pude conectar a la BD ";
    echo "Fallo al conectar a MySQL: (" . $mysql->connect_errno . ") " . $mysql->connect_error;
    exit;
}
  
echo "Conectado a la BD <br>";

$events=json_decode ($data);
/*echo "<br>";
print_r($events);
echo "<br>";*/
$i = 0;
foreach ($events as $event) {
    //echo $i . '<br>';
    echo "event <br>";
    print_r($event);
    echo "event <br>";
	echo $event->email . '<br>';
    echo $event->event . '<br>';
    $email = strtolower ($event->email);
    $tmp_qry = "select id from emails where email = '$email'";
    //echo "Query a ejecutar: ($tmp_qry)<br>";
    $resultado = $mysql->query ($tmp_qry);
    $tmp =  $resultado->fetch_object();
    $id_de_email = $tmp->id;
                
    if(!$id_de_email){
        echo "el correo ($email)  no estaba registrado, agregandolo...<br>";
        $tmp_qry = "insert  into emails (email,status) values ('$email', 'init')";
        //echo "Query a ejecutar: ($tmp_qry)<br>";
        $resultado = $mysql->query ($tmp_qry);
        if (!$resultado) {
            echo "no pude insertar en la BD <br>";
            echo "Fallo al conectar a MySQL:  " . $mysql->error. "<br>";
            exit;
        }else{
            $id_de_email = $mysql->insert_id;
            echo "el correo se registró con el id ($id_de_email)<br>";    
        }
    }else{
        echo "el correo ya estaba registrado con el id ($id_de_email)<br>";
    }
        // Buscamos el Id del evento en cat_eventos
        $resultado = $mysql->query ("select id from cat_eventos where EventName= '$event->event';");
        $tmp =  $resultado->fetch_object();
        $evento = $tmp->id;
        if (!$evento){
            $evento = 99;
        }
        echo "<br>";
        echo "El evento correspondiente a {$event->event} es {$evento}<br>";
        //agregamos el registro en la tabla log
        $msg_id = $event->sg_message_id; 
        $event_id = $event->sg_event_id;
        $time_stamp = $event->timestamp;
        $values_str = "";
        switch ($event->event){
            case "processed":
            break;
            case "dropped":
            $values_str .= "Reason: $event->reason";
            break;
            case "delivered":
            $values_str .= "Response: $event->response";
            break;
            case "deferred":
            $values_str .= "Response: $event->response";
            break;
            case "bounce":
            $values_str .= "Reason: $event->reason, Status: $event->status";
            break;
            case "open":
            $values_str .= "UserAgent: $event->useragent, IP: $event->ip";
            break;
            case "click":
            $values_str .= "UserAgent: $event->useragent, IP: $event->ip, URL: $event->url";
            break;
            case "spamreport":
            $tmp = "update emails set status = 'Spam Report'";
            break;
            case "unsubscribe":
            break;
            case "group_unsubscribe":
            $values_str .= "UserAgent: $event->useragent, IP: $event->ip, URL: $event->url";
            break;
            case "group_resubscribe":
            $values_str .= "UserAgent: $event->useragent, IP: $event->ip, URL: $event->url";
            break;
        }
        $tmp = "insert into log (EventTimeStamp, IdEmail, IdEventSG, IdMsg, Event, Values_Str) values (FROM_UNIXTIME({$time_stamp}),{$id_de_email},'{$event_id}','{$msg_id}',{$evento},'{$values_str}')";
        echo "query a ejecutar: <br>" . $tmp ."<br>";
        $resultado = $mysql->query ("insert ignore into log (EventTimeStamp, IdEmail, IdEventSG, IdMsg, Event, Values_Str) values (FROM_UNIXTIME({$time_stamp}),{$id_de_email},'{$event_id}','{$msg_id}',{$evento},'{$values_str}')");
        if (!$resultado) {
            echo "no pude insertar en la BD <br>";
            echo "Fallo al conectar a MySQL:  " . $mysql->error. "<br>";
            exit;
        }
    
	$i++;
}
$mysql->close();
echo "ok";

