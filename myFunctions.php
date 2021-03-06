<?php
set_include_path('./');
require_once ('MySQLDB.php');
require_once('User.php');
include_once ('myFunctions.php');
include_once ('db.php');


function checkSessionActive($oldTimer){
	$sessionLength = 600; // website remains active for 10 minutes or 600 seconds
	$currentTime = time();
	if($oldTimer + $sessionLength <= $currentTime){
		return false;	
	}
	return true;
}

function getUserID($db, $email, $password){
	$sql = "select password from user where email = '$email';";
	$queryResult = $db->query($sql);
	$aRow = $queryResult->fetch();
	$hash = $aRow['password'];
	if(password_verify($password, $hash)){
		$sql = "select user_id from user where email = '$email';";
		$queryResult = $db->query($sql);
		$aRow = $queryResult->fetch();		
		var_dump($aRow);		
		return $aRow['user_id'];
	}	
	return false;
}

function newGetID($db, $email){
		$sql = "select user_id from user where email = '$email';";
		$queryResult = $db->query($sql);
		$aRow = $queryResult->fetch();		
		return $aRow['user_id'];
}


function getUser($db, $user_id){
      $sql = "select * from user where user_id = '$user_id';";
		$queryResult = $db->query($sql);
		$aRow = $queryResult->fetch();		
     return $aRow;
}
function initUser($db, $user_id){
	global $currentUser;
	$userData = getUser($db, $user_id);
	$nickname = $userData['nickname'];
	$email = $userData['email'];
	$password = $userData['password'];
	$lang = $userData['language'];
	$currentUser = new User($db, $nickname, $email, $password, $user_id, $lang, false);
	return $currentUser;
}


function addUser($db, $nickname, $email, $password, $lang){
	$sql ="insert into user (nickname, email, password, language) values ('$nickname','$email','$password', '$lang')";
	$queryResult = $db->query($sql);
	$sql = "select user_id from user where email = '$email';";
	$queryResult = $db->query($sql);
	$aRow = $queryResult->fetch();
	$user_id = $aRow['user_id'];	
	return $user_id;

}

function addNewForum($db, $user_id, $forName, $forSubject){
	$sql = "insert into forum ";
	$sql .= "(user_id, name, subject) values ";
	$sql .= "($user_id, '$forName', '$forSubject'); ";	
	$queryResult = $db->query($sql);
	$sql = "select * from forum where user_id = $user_id and name = '$forName' and subject = '$forSubject';"; 
	$queryResult = $db->query($sql);
	if($queryResult){
		$aRow = $queryResult->fetch();
		return $aRow;
	}
	
	return false;
}

function getForums($db, $user_id){
	$sql = "select * from forum where user_id = '$user_id' ;";
	$queryResult = $db->query($sql);
	$forumArr = [];
	if($queryResult != false){
		
		while( $aRow = $queryResult->fetch() ){	
			$forumArr[] = $aRow;
		}
	}
	return $forumArr;
}
function getAllForums($db, $user_id){
	$sql = "select * from forum where user_id != '$user_id' ;";
	$queryResult = $db->query($sql);
	$forumArr = [];
	if($queryResult != false){
		while( $aRow = $queryResult->fetch() ){	
			$forumArr[] = $aRow;
		}
	}
	return $forumArr;
}


function displayForums($forumArr){
	if ($forumArr == null){ return null;}
	foreach( $forumArr as $aRow ){
		$forum_id = $aRow['forum_id'];
		$link = "./main.php?$forum_id";
		echo '<li> <a href = "'. $link . '">' .$aRow["name"].': '.$aRow["subject"].'</a></li>'	;
	}
}

function getForum($db, $forum_id){
	$sql = "select * from forum where forum_id = '$forum_id';";
	$queryResult = $db->query($sql);
	$aRow = $queryResult->fetch();
	return $aRow;
}

function displayChatForum($db, $currentForum, $user_id){

	$forum_id = $currentForum['forum_id'];
	$sql = "select user.nickname as 'user',  maintxt as 'message', message.message_id as 'message_id', date as 'when' ";
	$sql .= " from message inner join user on message.user_id = user.user_id";
	$sql .= " inner join forum on message.forum_id = forum.forum_id";
	$sql .= " where forum.forum_id = '$forum_id' order by date desc ;";
	$queryResult = $db->query($sql);
	//var_dump($queryResult);
	if(!$queryResult){ return false;}
	if($queryResult->isEmpty()){ return true;}
	echo "<table> ";
	echo '<caption>' .$currentForum["name"].': '.$currentForum["subject"].'</caption>';
	echo "<thead> ";
	echo "<tr><th>User</th> <th>Message</th><th></th></tr>";
	echo "</thead>";	
	while( $aRow = $queryResult->fetch() ){
		$counterID = 'countID' . $aRow['message_id']  ;
		$message_id = $aRow['message_id'] ;
		$likes = getLikes($db, $message_id);
		$output = '';
		$output .= '<tr><td>'. $aRow['user'].'</td><td>'.$aRow['message'].'</td><td><div class = "'.'likeIt"'.'>';
		$output .= "<img src='./img/thumbs-down-small.png'alt = 'Thumbs down' title = 'Thumbs down' class = 'thumbsdown'";
		$output .= ' onclick = "doCount(document.getElementById(';
		$output .= "'$counterID').childNodes[0].nodeValue, -1, '$counterID', $message_id, $user_id)";
		$output .= '">';
		$output .= '<p><span id = "'.$counterID.'">'.$likes;
		$output .= " </span></p><img src='./img/thumbs-up-small.png' alt='Thumbs up' title='Thumbs up' class= 'thumbsup' ";
		$output .= 'onclick = "doCount(document.getElementById(';
		$output .= "'$counterID').childNodes[0].nodeValue, 1, '$counterID', $message_id, $user_id)";
		$output .= '">';
		$output .= " </div> </td></tr><br>";
		echo $output;
	}
	echo "</table> ";
		
	echo "<form action ='addMessage.php?$forum_id' method='post'>";
	echo "<input type='submit' value='Write message' />";
	echo "</form> ";
	

}
/*
function javascriptCounter(){
  $output = "<script>\n";
  $output .= "function doCount(count, incr, counterID, msgID, user_id){\n";
  $output .= "  xhttp = new XMLHttpRequest();\n";
  $output .= "  xhttp.onreadystatechange = function() {\n";
  $output .= "    if (this.readyState == 4 && this.status == 200) {\n";
  $output .= "    document.getElementById(counterID).innerHTML = this.responseText\n";
  $output .= "    }\n";
  $output .= "  }\n";
  $output .= "  let fileQuery = 'counter.php?q=' + count + ',' + incr + ',' + msgID + ',' + user_id;\n";
  $output .= "  xhttp.open('GET', fileQuery, true);\n";
  $output .= "  xhttp.send();\n";
  $output .= "} \n";
  $output .= "</script>\n";
  echo $output;
}
*/


function addLikes($db, $user_id, $message_id, $count, $incr){
	//check if entry is in rating table
	$newLikes = 0;
	$newDislikes = 0;	
	$sql = "select likes, dislikes from rating where user_id = '$user_id' and message_id = '$message_id';";
	$queryResult = $db->query($sql);
	//var_dump($queryResult);	
	
	if(!$queryResult || $queryResult->isEmpty()){
		//add entry to rating table
		if($incr > 0 ) {
			$sql = "insert into rating (user_id, message_id, likes, dislikes) values ('$user_id', '$message_id', '$incr', '0');";
			$queryResult = $db->query($sql);
			return getLikes($db, $message_id);
		} else {
			$newDislikes = $incr * -1;
			$sql = "insert into rating (user_id, message_id, likes, dislikes) values ('$user_id', '$message_id', '0', '$newDislikes');";
			$queryResult = $db->query($sql);
			return getLikes($db, $message_id);
		}
	} else{
		$aRow = $queryResult->fetch();
		if($aRow['likes'] > 0 || $aRow['dislikes'] > 0){
			return getLikes($db, $message_id);
		}		
		
		if($incr > 0) {
			$newLikes = $aRow['likes'] + $incr;
		
		}elseif($incr < 0){
			$newDislikes = $aRow['dislikes'] - $incr;  
		}			
						
		$sql = "update rating set likes = '$newLikes', dislikes = '$newDislikes' where user_id = '$user_id' and message_id = '$message_id';";
		$queryResult = $db->query($sql);
		return getLikes($db, $message_id);
	}
}

function getLikes($db, $message_id){
	$sql = "select sum(likes) - sum(dislikes) as 'likes' from rating where message_id = '$message_id';";	
	$queryResult = $db->query($sql);
	$aRow = $queryResult->fetch();
	return $aRow['likes'];

}

//($db, $this->user_id, $forum_id, $messagetxt
function addMessage($db,  $user_id, $forum_id, $message){
	$sql = "insert into message ";
	$sql .= "(forum_id, user_id, maintxt) values ";
	$sql .= "($forum_id, $user_id, '$message');";
	$queryResult = $db->query($sql);
	$sql = "select * from message where user_id = $user_id and maintxt = '$message';"; 
	$queryResult = $db->query($sql);
	if($queryResult){
		$aRow = $queryResult->fetch();
		return $aRow;
	}
	return;
}

function getMessages($db, $user_id){
	
	$sql = "select * from message where user_id = '$user_id';";
	$queryResult = $db->query($sql);
	$messageArr = [];
	if($queryResult != false){
		while( $aRow = $queryResult->fetch() ){	
			$messageArr[] = $aRow;
		}
	}	
	return $messageArr;
}

function displayMessages($messages){
	if ($messages == null){ return null;}
	foreach( $messages as $aRow ){	
		echo '<li>' .$aRow["maintxt"].'</li>';
	}
}

function addRating($db, $user_id, $message_id){
	$sql = "insert into rating ";
	$sql .="(user_id, message_id, likes, dislikes) values ";
	$sql .= "($user_id, $message_id, 0, 0);";	
	$queryResult = $db->query($sql);

}
