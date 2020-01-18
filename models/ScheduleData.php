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
        $query = "SELECT * FROM Rota WHERE rotaID = :rotaID";
        $statement = $this->_dbHandle->prepare($query);
        $statement->bindValue(":rotaID", $id, PDO::PARAM_INT);

        $statement->execute();
        $this->_dbInstance->destruct();

        $dataSet = [];
        while ($dbRow = $statement->fetch(PDO::FETCH_ASSOC)) {
            $dataSet[] = new Schedule($dbRow);
        }
        return $dataSet;
    }

    public function getAllRotas() {
        $sqlQuery = "SELECT R.rotaID, R.dateFrom, R.dateTo, CONCAT(A.firstName, ' ', A.lastName) as devA, CONCAT(B.firstName, ' ', B.lastName)as devB
                     FROM Rota R
                        JOIN Users A on R.devA = A.userID
                        JOIN Users B ON R.devB = B.userID
                     ORDER BY R.dateFrom";

        $statement = $this->_dbHandle->prepare($sqlQuery);

        $statement->execute();

        $data = [];
        while ($dbRow = $statement->fetch(PDO::FETCH_ASSOC)) {
            $data[] = Schedule::fromRow($dbRow);
        }

        $this->_dbInstance->destruct();
        return $data;
    }

    public function getRotas($from, $to) {
        $sqlQuery = "SELECT R.rotaID, R.dateFrom, R.dateTo, CONCAT(A.firstName, ' ', A.lastName) as devA, CONCAT(B.firstName, ' ', B.lastName)as devB
                     FROM Rota R
                        JOIN Users A on R.devA = A.userID
                        JOIN Users B ON R.devB = B.userID
                     WHERE R.dateFrom >= :dateFrom AND R.dateTo <= :dateTo 
                     ORDER BY R.dateFrom";

        $statement = $this->_dbHandle->prepare($sqlQuery);

        $statement->bindValue(":dateFrom", $from, PDO::PARAM_STR);
        $statement->bindValue(":dateTo", $to, PDO::PARAM_STR);

        $statement->execute();

        $data = [];

        while ($dbRow = $statement->fetch(PDO::FETCH_ASSOC)) {
            $data[] = Schedule::fromRow($dbRow);
        }

        $this->_dbInstance->destruct();

        return $data;
    }

    public function isRotaValid($id, $from, $to) {
        $sqlQuery = "SELECT DISTINCT U.username
                     FROM Unavailable A
                        JOIN Users U ON A.userID = :userID
                     WHERE (:dateTo > A.dateFrom) or (:dateFrom > A.dateTo)";

        $statement = $this->_dbHandle->prepare($sqlQuery);

        $statement->bindValue(":userID", $id, PDO::PARAM_INT);
        $statement->bindValue(":dateTo", $to, PDO::PARAM_STR);
        $statement->bindValue(":dateFrom", $from, PDO::PARAM_STR);

        $statement->execute();

        $this->_dbInstance->destruct();

        return $statement->rowCount() != 0;
    }

    public function getUserSchedules($id) {
        $sqlQuery = "SELECT R.rotaID, R.dateFrom, R.dateTo, CONCAT(A.firstName, ' ', A.lastName) as devA, CONCAT(B.firstName, ' ', B.lastName)as devB
                     FROM Rota R
                        JOIN Users A on R.devA = A.userID
                        JOIN Users B on R.devB = B.userID
                     WHERE R.devA = :userID OR R.devB = :userID
                     ORDER BY R.dateFrom";

        $statement = $this->_dbHandle->prepare($sqlQuery);

        $statement->bindValue(":userID", $id, PDO::PARAM_INT);

        $statement->execute();

        $data = [];

        while ($dbRow = $statement->fetch(PDO::FETCH_ASSOC)) {
            $data[] = Schedule::fromRow($dbRow);
        }

        $this->_dbInstance->destruct();

        return $data;
    }

    public function scheduleAlreadyExists($from, $to) {
        $sqlQuery = "SELECT * FROM Rota R
                     WHERE R.dateFrom = :dateFrom AND R.dateTo = :dateTo";

        $statement = $this->_dbHandle->prepare($sqlQuery);

        $statement->bindValue(":dateFrom", $from, PDO::PARAM_STR);
        $statement->bindValue(":dateTo", $to, PDO::PARAM_STR);

        $statement->execute();

        $schedulesFound = $statement->rowCount() > 0;

        $this->_dbInstance->destruct();

        return $schedulesFound;
    }

    public function createRota($from, $to, $devA, $devB) {

        $scheduleExists = $this->scheduleAlreadyExists($from, $to);

        if ($scheduleExists) {
            $this->updateRota($from, $to, $devA, $devB);
        }
        else {
            $sqlQuery = "INSERT INTO Rota (dateFrom, dateTo, devA, devB)
                         VALUES (:dateFrom, :dateTo, :devA, :devB)";

            $statement = $this->_dbHandle->prepare($sqlQuery);
            $this->_dbHandle->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_WARNING);
            $statement->bindValue(":dateFrom", $from);
            $statement->bindValue(":dateTo", $to);
            $statement->bindValue(":devA", $devA, PDO::PARAM_INT);
            $statement->bindValue(":devB", $devB, PDO::PARAM_INT);

            $statement->execute();

            $this->_dbInstance->destruct();
        }


        // TODO: maybe add a proper check on this
        return true;
    }

    public function updateRota($from, $to, $devA, $devB) {
        $sqlQuery = "UPDATE Rota R
                     SET R.dateFrom = :dateFrom,
                         R.dateTo = :dateTo,
                         R.devA = :devA,
                         R.devB = :devB
                     WHERE R.dateFrom = :dateFrom AND R.dateTo = :dateTo";

        $statement = $this->_dbHandle->prepare($sqlQuery);

        $statement->bindValue(":dateFrom", $from, PDO::PARAM_STR);
        $statement->bindValue(":dateTo", $to, PDO::PARAM_STR);
        $statement->bindValue(":devA", $devA, PDO::PARAM_INT);
        $statement->bindValue(":devB", $devB, PDO::PARAM_INT);

        $statement->execute();

        $this->_dbInstance->destruct();

        return true;
    }

    public function deleteRota() {

    }

//    function compareUserID($a, $b)
//    {
//        $aID = $a->getUserID();
//        $bID = $b->getUserID();
//
//        return $aID - $bID;
//    }

    public function generateRotas($from, $to) {

        /*
         * REFINED ALGORITHM:
         * For each schedule:
         *      Get a list of all non-admins who are available for the current range (no absences ranging FROM => TO) And who are not busy
         *      Let devA = random selection from available users
         *          Remove devA from consideration
         *      Let devB = random selection from available users
         *          If devB is on the same team as devA
         *              Re-select devB
         *          Else
         *              select devB
         *      Create provisional Schedule(From, To, devA, devB)
         */


        $rotas = [];
        $dateFrom = date_create($from);
        $dateTo = date_create($to);


        $n = ceil($dateFrom->diff($dateTo)->days / 14) ;

        for ($i = 0; $i < $n; $i++) {


            $add = ($i * 14);

            $from = date("d-m-Y", strtotime($dateFrom->format("d-m-Y"). ' + ' . $add . ' days'));
            $to = date("d-m-Y", strtotime($from. ' + 14 days'));


            $dateDB = date('Y-m-d', strtotime($from));
            $nonAdmins = $this->_userData->getAllNonAdmins();
            $unavailable = $this->_userData->getAllUnavailableUsers($dateDB);

            $availableUsers = array_udiff($nonAdmins, $unavailable, function($obj_A, $obj_B) {
                return ($obj_A->getUserID() - $obj_B->getUserID());
            });

            $indexA = array_rand($availableUsers, 1);

            $devA =  $availableUsers[$indexA];

            unset($availableUsers[$indexA]);

            $indexB = array_rand($availableUsers, 1);
            $devB =  $availableUsers[$indexB];

            while ($devA->getTeamName() == $devB->getTeamName()) {
                $indexB = array_rand($availableUsers, 1);
                $devB =  $availableUsers[$indexB];
            }

            $rotas[] = Schedule::fromString($from, $to, $devA, $devB);
        }
        return $rotas;
    }
}