<?php
require_once ("models/UserData.php");

//$id = $_GET['userID'];

if (isset($_POST['submit'])){

    $teamName = htmlentities($_POST['teamName']);
    $lastUpdate = htmlentities($_POST['lastUpdate']);
    $isBusy = htmlentities($_POST['isBusy']);
}

$teamData = new TeamData();
$teamData->updateTeam($id);

header("Location: index.php");