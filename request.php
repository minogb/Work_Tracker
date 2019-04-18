<?php
	$success= 'success';
	$reason= 'reason';
	$internalServerError = "Internal server error. Please try again latter";
	$inputError = "Input not valid";
	$requestError = "Invalid Request";
	$couldNotGetOnDate = "Could not get entries for user on: ";
	$couldNotSave = "Could not save/create entry";
	$array=[
		$success=> false,
		$reason=>$internalServerError
	];
	if($_SERVER["REQUEST_METHOD"] == "POST" 
	&& isset($_POST["request"])){
		$request = $_POST["request"];
		$var = $request["method"];
		switch($request["method"]){
			case "getAllEntries":
				$var = GetAllEntries($request["userId"],$request["day"]);
				if($var){
					$array["entries"] = $var;
					$array["success"] = true;
					unset($array["reason"]);
				}
			break;
			case "saveEntry":
				
				if(!isset($request["userId"])){
					$array[$reason] = "bad userId";	
					break;
				}
				$var = SaveEntry($request);
				if($var){
					$array["id"] = $var;
					$array["uniqueId"] = $request['uniqueId'];
					$array["success"] = true;
					unset($array[$reason]);
				}
				else{
					$array[$reason] = $couldNotSave;	
				}
			break;
			case "removeEntry":
			if(RemoveEntry($request["id"])){
				$array["uniqueId"] = $request['uniqueId'];
				$array["success"] = true;
				unset($array[$reason]);
			}
			break;
			default:
			$array[$reason] = $requestError;
			break;		
		}
	}
	else{
		$array[$reason] = $inputError;
	}
	echo(json_encode ($array));
	
	function GetAllEntries($userId,$day){
		//return ["bitch"=>"bitches"];
		
        $db = new WorkDb();
        if(!$db->connected){
            return true;
        }
		$retVal = $db->GetEntries($userId,$day);
        return $retVal ? $retVal: true;
	}
	//returns id of saved
	function SaveEntry($array){
        $db = new WorkDb();
        if(!$db->connected){
            return false;
        }
		//update existing
		if(isset($array["id"])){
			if($db->UpdateEntry($array))
				return $array["id"];
		}
		//create new entry
		else{
			return $db->SaveEntry($array);
		}
		return false;
	
	}
	function RemoveEntry($id){
        $db = new WorkDb();
        if(!$db->connected){
            return true;
        }
		return $db->DeleteEntry($id);
	}
	class WorkDb {
		private $UserId = "WorkTracker";
		private $Password = "jFWYcNOZp3KLSmK3";
		private $Servername = "localhost";
		private $UserDb = "WorkTracker";
		private $DeleteEntry = "DELETE FROM `work` WHERE `id` = ?" ;
		private $GetEntries = "SELECT * FROM `work` WHERE `userId` = ? AND `day` = ?" ;
		private $SaveEntry = "INSERT INTO `work`(`userId`, `customerName`, `billing`, `file`, `project`, `location`, `day`, `start`, `end`) VALUES (?,?,?,?,?,?,?,?,?)" ;
		private $UpdateEntry = "UPDATE `work` SET `id`=?,`userId`=?, `customerName`=?,`billing`=?,`file`=?,`project`=?,`location`=?,`day`=?,`start`=?,`end`=? WHERE `id` = ?" ;
		private $serverConnection = null;
		public $connected = false;
		public $connectionError = '';
		function Connect(){
			
			try{
				if($this->serverConnection)
					mysqli_close($this->serverConnection);
				$this->connected=false;
			}
			catch(Exception $e){
				$this->connected=false;
			}
			$this->serverConnection = @mysqli_connect($this->Servername, $this->UserId, $this->Password, $this->UserDb);
			if(!$this->serverConnection){
				$this->connectionError = "" . mysqli_connect_errno();
				$this->connected = false;
			}
			else{
				$this->connected = true;
			}
		}
		function __construct(){
			try{
			  $this->Connect();
			}
			catch(Exception $e){
				$this->connectionError = "Fatel error: " . $e;
				$this->connected = false;
			}
		}
		function SaveEntry($array){
			//return id
			$stmt = $this->serverConnection->prepare($this->SaveEntry);
			if($stmt){
			//"INSERT INTO `work`(`userId`, `billing`, `file`, `project`, `location`, `day`, `start`, `end`) VALUES (?,?,?,?,?,?,?,?)" 
				$stmt->bind_param("sssssssss",$array["userId"],$array["customerName"],$array["billing"],$array["file"],$array["project"],$array["location"],$array["day"],$array["start"],$array["end"]);
				if($stmt->execute()){
					return $stmt->insert_id;
				}
			}
			return 0;
		}
		function UpdateEntry($array){
            $stmt  = $this->serverConnection->prepare($this->UpdateEntry);
            if($stmt){
                $stmt->bind_param("sssssssssss", $array["id"], $array["userId"],$array["customerName"], $array["billing"], $array["file"], $array["project"], $array["location"], $array["day"], $array["start"], $array["end"], $array["id"]);

				return $stmt->execute();
			}
		}
		function GetEntries($userId, $day){
			$this->Connect();
			if(!$this->connected){
				return null;
			}
			if($stmt  = $this->serverConnection->prepare($this->GetEntries)){
				$stmt->bind_param("ss", $userId,$day);
				$stmt->execute();
				$result = $stmt->get_result();
				$stmt->close();
				if(count($result)> 0){
					$output=[];
					while($row = $result->fetch_assoc()){
						$output[] = $row;
					}
					return $output;
				}
			}
        }
		function DeleteEntry($id){
            $stmt  = $this->serverConnection->prepare($this->DeleteEntry);
            if($stmt){
                $stmt->bind_param("s", $id);

				return $stmt->execute();
			}
		
		}
	}
?>