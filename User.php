<?php
set_include_path('./');
require_once ('MySQLDB.php');
include_once ('myFunctions.php');
include_once ('db.php');

class User{
	private $email;
	private $nickname;
	public  $lang;
	private $password;
	private $user_id;
	private $allMyForums = [];
	private $allMyMessages = [];

	public function __construct($db, $nickname, $email, $password, $user_id, $lang, $newUser){
		$this->nickname = $nickname;
		$this->email = $email;
		$this->lang = $lang;
		$this->password = $password;
		if($newUser){
			$this->user_id = $this->addNewUsertoDB($db);
		}else {
			$this->user_id = $user_id;			
			$this->setReturningUserData($db);	
		}
	}
	
	private function setReturningUserData($db){
		$this->loadForums($db);
		$this->loadMessages($db);
		
	}
	
	private function loadForums($db){
		$this->allMyForums = getForums($db, $this->user_id);
	}
	
	private function loadMessages($db){
		$this->allMyMessages = getMessages($db, $this->user_id);
	}
	
	private function addNewUsertoDB($db){
		$this->user_id = addUser($db, $this->nickname, $this->email, $this->password, $this->lang);
		return $this->user_id;
	}

	public function setUserID($db){
		$this->user_id = newGetID($db, $this->email);
		return $this->user_id;	
	}
	
	
	public function getUserID($db){
		return $this->user_id;	
	}
	
	public function getEmail(){
		return $this->email;
	}
	public function setEmail($newEmail){
		$this->email = $newEmail;	
	}
	
	public function getNickname(){
		return $this->nickname;	
	}
	public function getAllMyForums(){
		return $this->allMyForums;	
	}
	public function getAllMyMessages(){
		return $this->allMyMessages;
	}

	public function addNewForum($db, $newForumName, $newForumSubject){
		$newForum = addNewForum($db, $this->user_id, $newForumName, $newForumSubject);
		$this->allMyForums[] = $newForum;
		$forum_id = $newForum['forum_id'];
		return $forum_id;	
	}
	
	public function addMessage($db, $forum_id, $messagetxt)	{
		$message = addMessage($db, $this->user_id, $forum_id, $messagetxt);
		$this->allMyMessages[] = $message;
		$message_id = $message['message_id'];
		addRating($db, $this->user_id, $message_id);	
		return $message_id;
	}
	
	public function toString(){
		return "$this->nickname, $this->email, $this->user_id, $this->lang";
	}
}

