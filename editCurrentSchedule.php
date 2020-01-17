<?php

require_once ("models/UserData.php");
require_once ("models/LogsData.php");
require_once ("models/ScheduleData.php");

session_start();

$view = new stdClass();

$users = new UserData();
$schedule = new ScheduleData();

$devA = htmlentities($_POST['devA']);
$devA = htmlentities($_POST['devB']);

$view.$users = $users->getAllNonAdmins();

$view->schedules = $scheduleData->getAllRotas();

header("Location: rota.php");