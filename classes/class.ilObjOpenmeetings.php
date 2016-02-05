<?php
/**
* Application class for Openmeetings repository object.
*
* @author Paul <ilias@gdconsulting.it>
* @author  Uwe Kohnle <kohnle@internetlehrer-gmbh.de>
*
* @version $Id$
*/

include_once("./Services/Repository/classes/class.ilObjectPlugin.php");


class ilObjOpenmeetings extends ilObjectPlugin
{
	/**
	* Constructor
	*
	* @access	public
	*/
	function __construct($a_ref_id = 0)
	{
		parent::__construct($a_ref_id);
	}
	

	/**
	* Get type.
	*/
	final function initType()
	{
		$this->setType("xomv");
	}
	
	/**
	* Create object
	*/
		
	function doCreate()
	{
	}
	
	function createRoom($rmtype,$online)
	{
		include_once("./Customizing/global/plugins/Services/Repository/RepositoryObject/Openmeetings/classes/class.ilOpenmeetingsREST.php");
		$this->WSDL = new ilOpenmeetingsREST();
		$this->WSDL->openmeetings_loginuser();

		$isModeratedRoom = "false";
		$max_user = 150;
		if ($rmtype == 1) $max_user = 25;
		else if ($rmtype == 3) $isModeratedRoom = "true";
		else if ($rmtype == 4) $max_user = 2;
		$input = (object) array(
			"name"=>$this->getTitle().' ('.$this->WSDL->getModuleKey().';'.$this->getRefId().')',
			"comment"=>$this->getDescription(),
			"isModeratedRoom"=>$isModeratedRoom,
			"type"=>$rmtype,
			"max_user"=>$max_user
		);
		$rmNum = $this->WSDL->openmeetings_createRoomWithModAndType($input);
		$this->setrmId($rmNum);
		$this->setOnline($online);
		global $ilDB;
		$ilDB->manipulate("INSERT INTO rep_robj_xomv_data ".
			"(id, is_online , rmid) VALUES (".
			$ilDB->quote($this->getId(), "integer").",". // id
			$ilDB->quote($online, "integer").",". //is_online
			$ilDB->quote($rmNum, "integer"). //rmid
			")");
	}
	
	/**
	* Read data from db
	*/
	function doRead()
	{
		global $ilDB;
		
		$set = $ilDB->query("SELECT is_online,rmid FROM rep_robj_xomv_data ".
			" WHERE id = ".$ilDB->quote($this->getId(), "integer")
			);
		while ($rec = $ilDB->fetchAssoc($set))
		{
			$this->setOnline($rec["is_online"]);
			$this->setrmId($rec["rmid"]);
		}
	}
	/**
	* Read data from openmeetings
	*/
	function omRead()
	{
		include_once("./Customizing/global/plugins/Services/Repository/RepositoryObject/Openmeetings/classes/class.ilOpenmeetingsREST.php");
		$this->WSDL = new ilOpenmeetingsREST();
		$this->WSDL->Openmeetings_loginuser();
		$om = $this->WSDL->openmeetings_getRoomById($this->getrmId());
		if ($om !=-1) {
			$this->setOpenmeetingsObject($om);
		} else {
			echo "failure reading data for openmeetings room";
			die();
		}
		return true;
	}
	/**
	* Update data
	*/
	function doUpdate()
	{
		global $ilDB;
 
		include_once("./Customizing/global/plugins/Services/Repository/RepositoryObject/Openmeetings/classes/class.ilOpenmeetingsREST.php");

		$this->WSDL = new ilOpenmeetingsREST();
		$this->WSDL->Openmeetings_loginuser();
		$this->openmeetingsObject->rooms_id = $this->getrmId(); //because of clone
		$this->openmeetingsObject->name = $this->getTitle().' ('.$this->WSDL->getModuleKey().';'.$this->getRefId().')';//$this->getId()
		$this->openmeetingsObject->comment = $this->getDescription();
//		$ret = $this->WSDL->openmeetings_updateRoomWithModeration($this->openmeetingsObject);
		$ret = $this->WSDL->openmeetings_updateRoomWithModerationQuestionsAudioTypeAndHideOptions($this->openmeetingsObject);
		if ($ret == -1) {echo "failure updating room";die();}
		$up = "UPDATE rep_robj_xomv_data SET ".
			" is_online = ".$ilDB->quote($this->getOnline(), "integer")
			.", rmid = ".$ilDB->quote($this->getrmId(), "integer")
			." WHERE id = ".$ilDB->quote($this->getId(), "integer");
		$ilDB->manipulate($up);
	}
	
	/**
	* Delete data from db
	*/
	function doDelete()
	{
		global $ilDB;
		include_once("./Customizing/global/plugins/Services/Repository/RepositoryObject/Openmeetings/classes/class.ilOpenmeetingsREST.php");
		$this->WSDL = new ilOpenmeetingsREST();
		$this->WSDL->Openmeetings_loginuser();
		$ret = $this->WSDL->deleteRoom($this->getrmId());
		if ($ret == -1) {echo "failure deleting room";die();}
		$ilDB->manipulate("DELETE FROM rep_robj_xomv_data WHERE id = ".$ilDB->quote($this->getId(), "integer"));
	}
	
	public function doCloneObject($new_obj, $a_target_id, $a_copy_id)
	{
		$this->doClone($new_obj, $a_target_id, $a_copy_id);
	}
	/**
	* Do Cloning
	*/
	function doClone($new_obj, $a_target_id, $a_copy_id)
	{
		$this->omRead();
		$new_obj->setOpenmeetingsObject($this->getOpenmeetingsObject());
		$new_obj->createRoom(1,$this->getOnline());
		$new_obj->doUpdate();
	}
	
	/**
	* get cmdURL to show Content 
	*/
	function getCmdUrlToShowContent()
	{
		global $ilUser, $ilCtrl, $ilDB, $ilAccess;
		$rm_ID = $this->getrmId();
		if ($rm_ID == null) {echo "no room id";die();}
		$user_id=$ilUser->getID();
		$user_login = $ilUser->getlogin();
		$user_firstname = $ilUser->getFirstname();
		$user_lastname = $ilUser->getLastname();
		$user_language = $ilUser->getCurrentLanguage();
		$user_email = $ilUser->getEmail();
		$user_image = substr($ilUser->getPersonalPicturePath($a_size = "xsmall", $a_force_pic = true),2);
		if (substr($user_image,0,2) == './') $user_image = substr($user_image,2);
		$user_image = ILIAS_HTTP_PATH.'/'.$user_image;
		include_once("./Customizing/global/plugins/Services/Repository/RepositoryObject/Openmeetings/classes/class.ilOpenmeetingsREST.php");
		$this->WSDL = new ilOpenmeetingsREST();
		$this->WSDL->openmeetings_loginuser();
		$om = $this->WSDL->openmeetings_getRoomById($rm_ID);
		if ($om !=-1) {
			$this->setOpenmeetingsObject($om);
		} else {
			echo "failure reading data for openmeetings room";
			die();
		}
		$i_allowRecording = 0;
		$moderator=0;
		if ($this->getrmIsModerated() == 1) {
			if ($ilAccess->checkAccess("write", "", $this->getRefId())) {
				$moderator=1;
	//			$ilDB->query("INSERT INTO rep_robj_xomv_debug VALUES ('moderator: ".$moderator."')");
			}
		}
		
		$secHash = $this->WSDL->openmeetings_setUserObjectAndGenerateRoomHashByURLAndRecFlag($user_login,$user_firstname,$user_lastname,$user_image, $user_email,$user_id,"ILIAS",$rm_ID,$moderator,$i_allowRecording);
		
		$cmdURL = $this->WSDL->getUrl()."/?". "secureHash=" . $secHash;
		return $cmdURL;
	}
//
// Set/Get Methods for our Openmeetings properties
//
	function ilBoolToOm($a_val) {
		if ($a_val == 1) return "true";
		return "false";
	}
	function omBoolToIl($a_val) {
		if ($a_val == "true") return 1;
		return 0;
	}

	/**
	* Set online
	*
	* @param	boolean		online
	*/
	function setOnline($a_val)
	{
		$this->online = $a_val;
	}
	
	/**
	* Get online
	*
	* @return	boolean		online
	*/
	function getOnline()
	{
		return $this->online;
	}
			


	function setrmId($a_val){
		$this->rmid = $a_val;
	}

	function getrmId(){
		return $this->rmid;
	}

	function setrmTypes($a_val){
		$this->openmeetingsObject->roomtype->roomtypes_id = (int) $a_val;
	}
	function getrmTypes(){
		return $this->openmeetingsObject->roomtype->roomtypes_id;
	}

	function setrmParticipants($a_val){
		$this->openmeetingsObject->numberOfPartizipants  = (int) $a_val;
	}
	function getrmParticipants(){
		return $this->openmeetingsObject->numberOfPartizipants;
	}	
	
	
	function setrmIsPublic($a_val){
		$this->openmeetingsObject->ispublic = $this->ilBoolToOm($a_val);
	}
	function getrmIsPublic(){
		return $this->omBoolToIl($this->openmeetingsObject->ispublic);
	}
	
	
	function setrmAppointment($a_val) {
		$this->openmeetingsObject->appointment = $this->ilBoolToOm($a_val);
	}
	function getrmAppointment() {
		return $this->omBoolToIl($this->openmeetingsObject->appointment);
	}
	
	
	function setrmisDemoRoom($a_val){
		$this->openmeetingsObject->isDemoRoom = $this->ilBoolToOm($a_val);
	}
	function getrmisDemoRoom(){
		return $this->omBoolToIl($this->openmeetingsObject->isDemoRoom);
	}
	
	
	function setrmDemoTime($a_val){
		$this->openmeetingsObject->demoTime = (int) $a_val;
	}
	function getrmDemoTime(){
		return $this->openmeetingsObject->demoTime;
	}


	function setrmIsModerated($a_val){
		$this->openmeetingsObject->isModeratedRoom = $this->ilBoolToOm($a_val);
	}
	function getrmIsModerated(){
		return $this->omBoolToIl($this->openmeetingsObject->isModeratedRoom);
	}

	function setrm_allowUserQuestions($a_val) {
		$this->openmeetingsObject->allowUserQuestions = $this->ilBoolToOm($a_val);
	}
	function setrm_isAudioOnly($a_val) {
		$this->openmeetingsObject->isAudioOnly = $this->ilBoolToOm($a_val);
	}
	function setrm_hideTopBar($a_val) {
		$this->openmeetingsObject->hideTopBar = $this->ilBoolToOm($a_val);
	}
	function setrm_hideChat($a_val) {
		$this->openmeetingsObject->hideChat = $this->ilBoolToOm($a_val);
	}
	function setrm_hideActivitiesAndActions($a_val) {
		$this->openmeetingsObject->hideActivitiesAndActions = $this->ilBoolToOm($a_val);
	}
	function setrm_hideFilesExplorer($a_val) {
		$this->openmeetingsObject->hideFilesExplorer = $this->ilBoolToOm($a_val);
	}
	function setrm_hideActionsMenu($a_val) {
		$this->openmeetingsObject->hideActionsMenu = $this->ilBoolToOm($a_val);
	}
	function setrm_hideScreenSharing($a_val) {
		$this->openmeetingsObject->hideScreenSharing = $this->ilBoolToOm($a_val);
	}
	function setrm_hideWhiteboard($a_val) {
		$this->openmeetingsObject->hideWhiteboard = $this->ilBoolToOm($a_val);
	}
	
	function getrm_allowUserQuestions(){
		return $this->omBoolToIl($this->openmeetingsObject->allowUserQuestions);
	}
	function getrm_isAudioOnly(){
		return $this->omBoolToIl($this->openmeetingsObject->isAudioOnly);
	}
	function getrm_hideTopBar(){
		return $this->omBoolToIl($this->openmeetingsObject->hideTopBar);
	}
	function getrm_hideChat(){
		return $this->omBoolToIl($this->openmeetingsObject->hideChat);
	}
	function getrm_hideActivitiesAndActions(){
		return $this->omBoolToIl($this->openmeetingsObject->hideActivitiesAndActions);
	}
	function getrm_hideFilesExplorer(){
		return $this->omBoolToIl($this->openmeetingsObject->hideFilesExplorer);
	}
	function getrm_hideActionsMenu(){
		return $this->omBoolToIl($this->openmeetingsObject->hideActionsMenu);
	}
	function getrm_hideScreenSharing(){
		return $this->omBoolToIl($this->openmeetingsObject->hideScreenSharing);
	}
	function getrm_hideWhiteboard(){
		return $this->omBoolToIl($this->openmeetingsObject->hideWhiteboard);
	}

	function setOpenmeetingsObject($a_val) {
		$this->openmeetingsObject = $a_val;
	}
	function getOpenmeetingsObject() {
		return $this->openmeetingsObject;
	}

}
?>
