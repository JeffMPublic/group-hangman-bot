<?php
   //Set your mysql host/username/password here
    DEFINE("MYSQLHOST","");
    DEFINE("MYSQLUSER","");
    Define("MYSQLPASSWORD","");
/*
Function    :   gameOver
            This closes all open games for user. We should never have more than 1 open game for a user
Parameters  :   (string) $userid - groupme user id 
                (int)    $gameid - id of the hangman game
Return      : We should return a true or false incase something fails

TODO        : Allow to just close 1 game given a gameid
              Better Error checking and a return
*/
function gameOver($userid,$gameid = 0){
	$setGameOver = new mysqli('mysql.dontburnthepig.com',MYSQLUSER,MYSQLPASSWORD);
        $setGameOver->select_db("dontburnthepig_hangman");
        $query = "update `dontburnthepig_hangman`.`games` set finished = NOW() where user_id = ? and finished is NULL";
        $stmt = $setGameOver->prepare($query);
        $stmt->bind_param("s",$userid);
        $stmt->execute();

}
/*
Function    :   validGuess
            This function will return true or false depening on if a user has already guessed a letter for this game
Parameters  :   (string) $userid - groupme user id 
                (string) $guess -  a guess 
Return      :(boolean) True if valid guess
                       False is invalide guess
*/
function validGuess($userid,$guess){
 	$gameData = new mysqli('mysql.dontburnthepig.com',MYSQLUSER,MYSQLPASSWORD);
    	if($session_check -> connect_error){
        	printf("Connection to DB failed",$mysqli->connect-error);
        	die();           
    	}
    	$gameData->select_db("dontburnthepig_hangman");
    	$query = "select if(guesses like ?,false,true) as guesses from games where finished is NULL and user_id = ? limit 1";
    	$stmt = $gameData->prepare($query);
    	$formatGuess = "%$guess%";
	$stmt->bind_param("ss",$formatGuess,$userid);
    	$stmt->execute();
    	$result = $stmt->get_result();
    	while ($row = $result->fetch_array(MYSQLI_ASSOC)){
        	$validGuess = $row['guesses'];
    	}
	return $validGuess;
}
/*
Function    :   makeGuess
            This function will make a guess for the game the user is currently playing
Parameters  :   (string) $userid - groupme user id 
                (string) $guess -  a guess 
Return      :We also need to return (boolean) True if valid guess
                                               False is invalide guess
TODO        :Error checking and return error is something has happened
*/
function makeGuess($userid,$guess){
	if(opengame($userid)){
	$guess_maker = new mysqli('mysql.dontburnthepig.com',MYSQLUSER,MYSQLPASSWORD);
    	$guess_maker->select_db("dontburnthepig_hangman");
    	$query = "update `dontburnthepig_hangman`.`games` set `guesses` = CONCAT(`guesses`, ?),wrong_count = IF(word like ? ,wrong_count,  wrong_count +1) where finished is NULL and user_id = ?"; 
        $stmt = $guess_maker->prepare($query);
	    $preparedGuess = "%$guess%";
        $stmt->bind_param("sss",$guess,$preparedGuess,$userid);
        $stmt->execute();

	}
}
/*
Function    :   displayGame
            This function will display the current game for a user id, after a guess is made this function is called
            and this will check if the game is won or if the game  has been lost
Parameters  :   (string) $userid - groupme user id 
                (int) $gameid -  id of a game
Return      :(string) $rtstr the the groupme api can output in chat to show the game state
TODO        :Mostly more error checkin and display hangman images off of the server
*/
function displayGame($userid,$gameid = 0){
    $gameData = new mysqli('mysql.dontburnthepig.com',MYSQLUSER,MYSQLPASSWORD);
    if($session_check -> connect_error){
        printf("Connection to DB failed",$mysqli->connect-error);
        die();           
    }
    $gameData->select_db("dontburnthepig_hangman");
    $query = "select * from games where user_id = ? and finished is NULL limit 1";
    $stmt = $gameData->prepare($query);
    $stmt->bind_param("s",$userid);
    $stmt->execute();
    $result = $stmt->get_result();
    while ($row = $result->fetch_array(MYSQLI_ASSOC)){
	$rows[] = $row;
    }
	$test = $rows[0]['guesses'];
	$replaced = preg_replace('/\s/',"\n",$rows[0]['word']); 
	if($rows[0]['wrong_count'] < 6){
		$rtstr = "Topic : ".$rows[0]['topic']."\n\n";
		$replaced = preg_replace('/[^'.$test.'\s0-9\&\(\)\^\'\#\@\"\:\,]/i',"_ ",$replaced);
		if(strpos($replaced,"_") === FALSE){
			gameOver($userid);
			$rtstr .="Congrats You Won!!!\n";
		}
			$rtstr .= preg_replace('/([a-zA-Z])/',"$1 ",$replaced)."\n---------------------------------------------\n"; 
			foreach (str_split($rows[0]['guesses']) as $letter){
				if( strpos(strtolower($rows[0]['word']),$letter) === FALSE){
					$rtstr .= "[".$letter."]";
				}
			}
		
	}else{
		gameOver($userid);
		$rtstr .= "GAME OVER!!!\n\n";
		$rtstr .= $replaced."\n---------------------------------------------\n";
		 foreach (str_split($rows[0]['guesses']) as $letter){
                        if( strpos(strtolower($rows[0]['word']),$letter) === FALSE){
                                $rtstr .= "[".$letter."]";
                        }
                }

	}
	return $rtstr;

}
/*
Function    :   opengame
            This function will check to see if a groupme userid has a current game open
Parameters  :   (string) $userid - groupme user id 
                
Return      :(boolean) if there is an open game it will return true, false otherwise

*/
function opengame($userid){
    $session_check = new mysqli('mysql.dontburnthepig.com',MYSQLUSER,MYSQLPASSWORD);
    if($session_check -> connect_error){
        printf("Connection to DB failed",$mysqli->connect-error);
        die();           
    }
    $session_check->select_db("dontburnthepig_hangman");
    $query = "select * from games where user_id = ? and finished is NULL";
     $stmt = $session_check->prepare($query);
    $stmt->bind_param("s",$userid);
    $stmt->execute();
    $stmt->store_result();

    printf("Number of rows: %d.\n", $stmt->num_rows);		
	if($stmt->num_rows > 0){
        echo "true";
	return true;
    }else return false;
	return false;

}

/*
Function    :   buildnewgame
            This function will build a group me user id a new game. 
Parameters  :   (string) $userid - groupme user id 
                
Return      :(boolean) TRUE
TODO        :We need to handle more error and also need to handle more topics right now we only have tv show's from wiki
*/
function buildnewgame($userid){
     if(opengame($userid)){
	echo "open";
	return false;
	}
    $game_builder = new mysqli('mysql.dontburnthepig.com',MYSQLUSER,MYSQLPASSWORD);
    if($session_recorder -> connect_error){
        printf("Connection to DB failed",$mysqli->connect-error);
        die();           
    }
    $game_builder->select_db("dontburnthepig_hangman");
    $query = "INSERT INTO `dontburnthepig_hangman`.`games` (`ID`, `user_id`, `guesses`, `wrong_count`, `topic`, `start_time`, `last_updated`, `finished`, `word`) VALUES (NULL,?,?,?,?,NOW(),NOW(),NULL,?);";
    $stmt = $game_builder->prepare($query);
	$t = 0;
        $sp = "";
	$st = "TV Shows";
	$word = "mysql database";
	$f_contents = file("newfile.txt");
	$line = $f_contents[array_rand($f_contents)];
	$data = $line;
	$stmt->bind_param("ssdss",$userid,$sp,$t,$st,$data);
        $stmt->execute();

	
	return TRUE;
}
?>
