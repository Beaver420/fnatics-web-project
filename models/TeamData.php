<?php
require_once ("Database.php");
require_once ("Team.php");
require_once ("User.php");

class TeamData
{
    protected  $_dbHandle, $_dbInstance;

    // Establish a connection to the DB
    public function __construct()
    {
        $this->_dbInstance = Database::getInstance();
        $this->_dbHandle = $this->_dbInstance->getConnection();
    }

    //Fetch team by ID
    public function fetchTeam($teamID){
        $sqlQuery = "SELECT * FROM Teams WHERE teamID = :teamID";
        $statement = $this->_dbHandle->prepare($sqlQuery);

        $statement->bindValue(':teamID', $teamID, PDO::PARAM_INT);

        $statement->execute();
        $this->_dbInstance->destruct();

        $dataSet = [];
        while ($dbRow = $statement->fetch(PDO::FETCH_ASSOC)) {
            $dataSet[] = new Team($dbRow);
        }
        return $dataSet;
    }

    //Fetches all teams
    public function fetchAllTeams(){
        $sqlQuery = "SELECT teamID, teamID, teamName, dateCreated, lastUpdate, isBusy, (select count(userID)
                      FROM Users where Users.teamID = Teams.teamID) as'memberCount'        
                     FROM Teams";
        $statement = $this->_dbHandle->prepare($sqlQuery);
        $statement->execute();

        $dataSet = [];
        while ($dbRow = $statement->fetch(PDO::FETCH_ASSOC)) {
            $dataSet[] = new Team($dbRow);

        }

        $this->_dbInstance->destruct();
        return $dataSet;
    }

    //Creates a team
    public function createTeam($teamName, $isBusy) {
        $sqlQuery = "INSERT INTO Teams (teamName, dateCreated, lastUpdate, isBusy)
                     VALUES (:teamName, NOW(), NOW(), :isBusy)"
        ;
        $statement = $this->_dbHandle->prepare($sqlQuery);

        $statement->bindValue(":teamName", $teamName, PDO::PARAM_STR);
        $statement->bindValue(":isBusy", $isBusy, PDO::PARAM_INT);


        $statement->execute();

        $this->_dbInstance->destruct();

    }

    public function updateTeam($teamID, $teamName, $isBusy){
        $statement = $this->_dbHandle->prepare("UPDATE Teams SET teamName = :teamName, isBusy = :isBusy WHERE teamID = :teamID");


        $statement->bindValue(":teamID", $teamID, PDO::PARAM_INT);
        $statement->bindValue(":teamName", $teamName, PDO::PARAM_STR);
        $statement->bindValue(":isBusy", $isBusy, PDO::PARAM_BOOL);

        $statement->execute();
        $this->_dbInstance->destruct();

        $dataSet = [];
        while ($dbRow = $statement->fetch()) {
            $dataSet[] = new Team($dbRow);
        }
        return $dataSet;
    }

    //Deletes a team
    public function deleteTeam($id){
        $sqlQuery = "DELETE FROM Teams WHERE teamID = :teamID";
        $statement = $this->_dbHandle->prepare($sqlQuery);

//        TODO: Investigate why statement doesn't work when value is bound
        $statement->bindValue(":teamID", $id, PDO::PARAM_INT);
        $statement->execute();
        $this->_dbInstance->destruct();

        return true;
    }

    public function getTeamNameByID($id){
        $sqlQuery = "SELECT teamName FROM Teams T
                     WHERE T.teamID = :teamID";

        $statement = $this->_dbHandle->prepare($sqlQuery);
        $statement->bindValue(":teamID", $id, PDO::PARAM_INT);
        $statement->execute();
        $this->_dbInstance->destruct();

        $r = '';
        while ($row = $statement->fetch(PDO::FETCH_ASSOC)){
            $r = $row['teamName'];
        }

        return $r;
    }

    //Check Team Exists
    public function checkTeamNameExists($teamName){
        $sqlQuery = "SELECT * FROM Teams
                     WHERE teamName = :teamName";

        $statement = $this->_dbHandle->prepare($sqlQuery);
        $statement->bindValue(":teamName", $teamName, PDO::PARAM_STR);
        $statement->execute();
        $this->_dbInstance->destruct();

        return ($statement->fetch() == null);
    }

    //Check Team Exists, Ignore Current Team Name
    public function checkTeamNameExistsIgnore($newTeamName, $id){
        $sqlQuery = "SELECT teamName FROM Teams
                     WHERE teamID = :teamID"; // Get user's old email.

        $statement = $this->_dbHandle->prepare($sqlQuery);
        $statement->bindValue(":teamID", $id, PDO::PARAM_STR);
        $statement->execute();
        $this->_dbInstance->destruct();

        $r = $statement->fetch();

        if (!$this->checkTeamNameExists($newTeamName)){ //If new team name exists
            if ($r['teamName'] == $newTeamName){ //If new team name == old team name
                return true; //New team name == old username
            } else {
                return false; //New team name already exists.
            }
        } else {
            return true; //New team name is new.
        }
    }

    public function getTeamMembers($teamID){
        $sqlQuery = "SELECT U.firstName, U.lastName FROM Users U
                     WHERE U.teamID = :teamID";

        $statement = $this->_dbHandle->prepare($sqlQuery);
        $statement->bindValue(":teamID", $teamID, PDO::PARAM_STR);
        $statement->execute();
        $this->_dbInstance->destruct();

        $data = [];
        while ($row = $statement->fetch(PDO::FETCH_ASSOC)){
            $firstName = $row['firstName'];
            $lastName = $row['lastName'];
            $data[] = $firstName . ' '. $lastName;
        }

        return $data;
    }

    public function getTeamMembersNew($teamID) {
        $sqlQuery = "SELECT U.userID, T.teamName, U.username, U.password, U.firstName, U.lastName, U.dateCreated, U.lastUpdate, U.isAdmin 
                     FROM Users U
                     JOIN Teams T on U.teamID = T.teamID
                     WHERE U.teamID = :teamID";

        $statement = $this->_dbHandle->prepare($sqlQuery);
        $statement->bindValue(":teamID", $teamID, PDO::PARAM_STR);
        $statement->execute();


        $data = [];
        while ($dbRow = $statement->fetch(PDO::FETCH_ASSOC)){
            $data[] = new User($dbRow);
        }

        $this->_dbInstance->destruct();
        return $data;
    }

    // TODO: maybe un-needed
    public function isTeamEmpty($teamID) {
        $sqlQuery = "SELECT U.userID
                     FROM Users U
                     JOIN Teams T on U.teamID = :teamID";

        $statement = $this->_dbHandle->prepare($sqlQuery);

        $statement->bindValue(":teamID", $teamID, PDO::PARAM_INT);

        $statement->execute();

        $members = $statement->rowCount();

        $this->_dbInstance->destruct();

        return $members > 0;
    }
}