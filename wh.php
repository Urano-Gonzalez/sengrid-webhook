<?php
// Webhook para Sendgrid
// Documentación de sendgrid: https://sendgrid.com/docs/API_Reference/Event_Webhook/event.html
$data = file_get_contents("php://input");
$events=json_decode ($data);

$mysql = new mysqli ('localhost', 'user', 'password', 'database');
if ($mysql->connect_errno) {
    exit;
}

foreach ($events as $event) {
    $email = strtolower ($event->email);
    // Si email no está en la BD, lo agregamos
    $resultado = $mysql->query ("select id from emails where email = '$email'");
    $tmp =  $resultado->fetch_object();
    $id_de_email = $tmp->id;
    if(!$id_de_email){
        // no existe, lo agregamos a la tabla de emails
        $resultado = $mysql->query ("insert  into emails (email,status) values ('$email', 'init')");
        if (!$resultado) {
            exit;
        }else{
            $id_de_email = $mysql->insert_id;
        }
    }
    // Buscamos el Id del evento en cat_eventos
    $resultado = $mysql->query ("select id from cat_eventos where EventName= '$event->event';");
    $tmp =  $resultado->fetch_object();
    $evento = $tmp->id;
    if (!$evento){
        $evento = 99;
    }
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
    $resultado = $mysql->query ("insert ignore into log (EventTimeStamp, IdEmail, IdEventSG, IdMsg, Event, Values_Str) values (FROM_UNIXTIME({$time_stamp}),{$id_de_email},'{$event_id}','{$msg_id}',{$evento},'{$values_str}')");
    if (!$resultado) {
        exit;
    }
	$i++;
}

//fin de proceso
$mysql->close();
echo "ok";
?>