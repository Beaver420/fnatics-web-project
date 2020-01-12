<?php
require_once ("Database.php");
require_once("Schedule.php");
require_once ("UserData.php");


class ScheduleData
{
    protected $_dbHandle, $_dbInstance, $_userData;

    public function __construct() {
        $this->_dbInstance = Database::getInstance();
        $this->_dbHandle = $this->_dbInstance->getConnection();
        $this->_userData = new UserData();
    }

    public function getRota($id) {

    }

    public function getAllRotas() {
        $sqlQuery = "SELECT R.dateFrom, R.dateTo, A.username as devA, B.username as devB
                     FROM Rota R
                        JOIN Users A on R.devA = A.userID
                        JOIN Users B ON R.devB = B.userID
                     ORDER BY R.dateFrom";

        $statement = $this->_dbHandle->prepare($sqlQuery);

        $statement->execute();

        $data = [];
        while ($dbRow = $statement->fetch()) {
            $data[] = Schedule::fromRow($dbRow);
        }
        return $data;
    }
    public function getRotas($from, $to) {

    }

    public function createRota($from, $to, $devA, $devB) {
        $sqlQuery = "INSERT INTO Rota (dateFrom, dateTo, devA, devB)
                     VALUES (NOW(), DATE_ADD(NOW(), INTERVAL 14 DAY), :devA, :devB)";

        $statement = $this->_dbHandle->prepare($sqlQuery);

        $statement->bindValue(":devA", $devA, PDO::PARAM_INT);
        $statement->bindValue(":devB", $devB, PDO::PARAM_INT);

        $statement->execute();

        $this->_dbInstance->destruct();

        // TODO: maybe add a proper check on this
        return true;
    }

    public function updateRota() {

    }

    public function deleteRota() {

    }

    public function generateRota() {
        $nonAdmins = $this->_userData->getAllNonAdmins();

        // Pick two users to be on support rota

        // pick devA from the array and remove them as to prevent the chance of them being chosen again as devB
        $indexA = array_rand($nonAdmins, 1);

        $devA =  $nonAdmins[$indexA];
        unset($nonAdmins[$indexA]);

        // Remove 1st dev
        unset($nonAdmins[$indexA]);

        $indexB = array_rand($nonAdmins, 1);
        $devB =  $nonAdmins[$indexB];

        $tempRota = Schedule::fromString(date("d/m/Y"), date("d/m/Y"), $devA, $devB);

        return $tempRota;
    }
}