<?php
/*
 * Licensed to the Apache Software Foundation (ASF) under one
* or more contributor license agreements.  See the NOTICE file
* distributed with this work for additional information
* regarding copyright ownership.  The ASF licenses this file
* to you under the Apache License, Version 2.0 (the
* "License") +  you may not use this file except in compliance
* with the License.  You may obtain a copy of the License at
*
*   http://www.apache.org/licenses/LICENSE-2.0
*
* Unless required by applicable law or agreed to in writing,
* software distributed under the License is distributed on an
* "AS IS" BASIS, WITHOUT WARRANTIES OR CONDITIONS OF ANY
* KIND, either express or implied.  See the License for the
* specific language governing permissions and limitations
* under the License.
*/

/**
* Openmeetings repository object plugin
*
* @author Uwe Kohnle <kohnle@internetlehrer-gmbh.de>
* based on openmeetings_gateway.php in apache-openmeetings-moodle-plugin-1.5
* with additional functions for ILIAS
* @version $Id$
*
*/

include_once('./Customizing/global/plugins/Services/Repository/RepositoryObject/Openmeetings/lib/openmeetings_rest_service.php');

class ilOpenmeetingsREST {

	function __construct() {
		global $CFG;
		include_once("./Customizing/global/plugins/Services/Repository/RepositoryObject/Openmeetings/classes/class.ilOpenmeetingsConfig.php");
		$settings = ilOpenmeetingsConfig::getInstance();
		$CFG = (object) array(
			"openmeetings_openmeetingsAdminUser"=>$settings->getSvrUsername(),
			"openmeetings_red5host"=>$settings->getSvrUrl(),
			"openmeetings_red5port"=>$settings->getSvrPort(),
			"openmeetings_webappname"=>$settings->getSvrAppname(),
			"openmeetings_version_2_x"=>$settings->getAllowUpdate("om2x"),
			"openmeetings_openmeetingsAdminUserPass"=>$settings->getSvrPassword(),
			"openmeetings_openmeetingsModuleKey"=>$this->getModuleKey()
		);
	}

	var $session_id = "";

	function getModuleKey() {
		$iliasDomain = substr(ILIAS_HTTP_PATH,7);
		if (substr($iliasDomain,0,1) == "\/") $iliasDomain = substr($iliasDomain,1);
		if (substr($iliasDomain,0,4) == "www.") $iliasDomain = substr($iliasDomain,4);
		return $iliasDomain.';'.CLIENT_ID;
	}
	
	function getUrl() {
		global $CFG;
		$host = $CFG->openmeetings_red5host;
		if (substr($host,0,5) == "https") {
			$port = $CFG->openmeetings_red5port == 443 ? '' : ":" . $CFG->openmeetings_red5port;
		}
		else if (substr($host,0,4) == "http") {
			$port = $CFG->openmeetings_red5port == 80 ? '' : ":" . $CFG->openmeetings_red5port;
		}
		else if ($CFG->openmeetings_red5port == 443) {
			$host = 'https://'.$host;
			$port = '';
		}
		else {
			$host = 'http://'.$host;
			$port = $CFG->openmeetings_red5port == 80 ? '' : ":" . $CFG->openmeetings_red5port;
		}
		return $host . $port . "/" . $CFG->openmeetings_webappname;
	}

	function var_to_str($in)
	{
		if(is_bool($in))
		{
			if($in)
			return "true";
			else
			return "false";
		}
		else
		return $in;
	}


	/**
	* TODO: Get Error Service and show detailed Error Message
	*/

	public function openmeetings_loginuser() {
		global $CFG;

		$restService = new openmeetings_rest_service();

		$call = $this->getUrl()."/services/UserService/getSession";
		$response = $restService->call($call,"session_id");
		$GLOBALS['ilLog']->write(__METHOD__.': call: '.$call.' result: '.$response);

		if ($restService->getError()) {
			echo '<h2>Fault (Expect - The request contains an invalid SOAP body)</h2><pre>'; print_r($result); echo '</pre>';
		} else {
			$err = $restService->getError();
			if ($err) {
				echo '<h2>Error</h2><pre>' . $err . '</pre>';
			} else {
				$this->session_id = $response;
				$call = $this->getUrl()."/services/UserService/loginUser?"
						. "SID=".$this->session_id
						. "&username=" . urlencode($CFG->openmeetings_openmeetingsAdminUser)
						. "&userpass=" . urlencode($CFG->openmeetings_openmeetingsAdminUserPass);
				$result = $restService->call($call);
//				$GLOBALS['ilLog']->write(__METHOD__.': call: '.$call.' result: '.$result);

				if ($restService->getError()) {
					echo '<h2>Fault (Expect - The request contains an invalid REST body)</h2><pre>'; print_r($result); echo '</pre>';
				} else {
					$err = $restService->getError();
					if ($err) {
						echo '<h2>Error</h2><pre>' . $err . '</pre>';
					} else {
						$returnValue = $result;
					}
				}
			}
		}

		$GLOBALS['ilLog']->write(__METHOD__.': returns: '.$returnValue);

		if ($returnValue>0){
			return true;
		} else {
			return false;
		}
	}


	function openmeetings_updateRoomWithModeration($openmeetings) {

		//global $CFG;

		$restService = new openmeetings_rest_service();

		$err = $restService->getError();
		if ($err) {
			echo '<h2>Constructor error</h2><pre>' . $err . '</pre>';
			echo '<h2>Debug</h2><pre>' . htmlspecialchars($client->getDebug(), ENT_QUOTES) . '</pre>';
			exit();
		}
		// $course_name = 'MOODLE_COURSE_ID_'.$openmeetings->course.'_NAME_'.$openmeetings->name;
			
		// $isModeratedRoom = false;
		// if ($openmeetings->is_moderated_room == 1) {
			// $isModeratedRoom = true;
		// }
		$call = $this->getUrl()."/services/RoomService/updateRoomWithModeration?" .
				"SID=".$this->session_id.
				"&room_id=".$openmeetings->rooms_id.
				"&name=".urlencode($openmeetings->name).
				"&roomtypes_id=".urlencode($openmeetings->roomtype->roomtypes_id).
				"&comment=".urlencode($openmeetings->comment) .
				"&numberOfPartizipants=".$openmeetings->numberOfPartizipants.
				'&ispublic='.$this->var_to_str($openmeetings->ispublic).
				'&appointment='.$this->var_to_str($openmeetings->appointment).
				'&isDemoRoom='.$this->var_to_str($openmeetings->isDemoRoom).
				'&demoTime='.$openmeetings->demoTime.
				"&isModeratedRoom=".$this->var_to_str($openmeetings->isModeratedRoom);
		//$GLOBALS['ilLog']->write(__METHOD__.': '.$call);
		$result = $restService->call($call);

		if ($restService->fault()) {
			echo '<h2>Fault (Expect - The request contains an invalid SOAP body)</h2><pre>'; print_r($result); echo '</pre>';
		} else {
			$err = $restService->getError();
			if ($err) {
				echo '<h2>Error</h2><pre>' . $err . '</pre>';
			} else {
				//echo '<h2>Result</h2><pre>'; print_r($result["return"]); echo '</pre>';
				return $result;
			}
		}
		return -1;
	}

	/*
	 * public String setUserObjectAndGenerateRecordingHashByURL(String SID, String username, String firstname, String lastname,
					Long externalUserId, String externalUserType, Long recording_id)
	 */
	 function openmeetings_setUserObjectAndGenerateRecordingHashByURL($username, $firstname, $lastname, 
						$userId, $systemType, $recording_id) {
	    $restService = new openmeetings_rest_service();
	 	$result = $restService->call($this->getUrl().'/services/UserService/setUserObjectAndGenerateRecordingHashByURL?'.
			'SID='.$this->session_id .
			'&username='.urlencode($username) .
			'&firstname='.urlencode($firstname) .
			'&lastname='.urlencode($lastname) .
			'&externalUserId='.$userId .
			'&externalUserType='.urlencode($systemType) .
			'&recording_id='.$recording_id,
			'return'
			);
		
		if ($client_roomService->fault) {
			echo '<h2>Fault (Expect - The request contains an invalid SOAP body)</h2><pre>'; print_r($result); echo '</pre>';
		} else {
			$err = $restService->getError();
			if ($err) {
				echo '<h2>Error</h2><pre>' . $err . '</pre>';
			} else {
				return $result;
			}
		}   
		return -1;
	}

	function openmeetings_setUserObjectAndGenerateRoomHashByURLAndRecFlag($username, $firstname, $lastname,
					$profilePictureUrl, $email, $userId, $systemType, $room_id, $becomeModerator, $allowRecording) {
		//global $CFG;

		$restService = new openmeetings_rest_service();
		//echo $restService."<br/>";
		$err = $restService->getError();
		if ($err) {
			echo '<h2>Constructor error</h2><pre>' . $err . '</pre>';
			echo '<h2>Debug</h2><pre>' . htmlspecialchars($client->getDebug(), ENT_QUOTES) . '</pre>';
			exit();
		}
		$call = $this->getUrl()."/services/UserService/setUserObjectAndGenerateRoomHashByURLAndRecFlag?" .
				"SID=".$this->session_id.
				"&username=".urlencode($username).
				"&firstname=".urlencode($firstname).
				"&lastname=".urlencode($lastname).
				"&profilePictureUrl=".urlencode($profilePictureUrl).
				"&email=".urlencode($email).
				"&externalUserId=".urlencode($userId).
				"&externalUserType=".urlencode($systemType).
				"&room_id=".urlencode($room_id).
				"&becomeModeratorAsInt=".$becomeModerator.
				"&showAudioVideoTestAsInt=1".
				"&allowRecording=".$this->var_to_str($allowRecording);
		//$GLOBALS['ilLog']->write(__METHOD__.': '.$call);
		$result = $restService->call($call);

		if ($restService->fault) {
			echo '<h2>Fault (Expect - The request contains an invalid SOAP body)</h2><pre>'; print_r($result); echo '</pre>';
		} else {
			$err = $restService->getError();
			if ($err) {
				echo '<h2>Error</h2><pre>' . $err . '</pre>';
			} else {
				//echo '<h2>Result</h2><pre>'; print_r($result["return"]); echo '</pre>';
				return $result;
			}
		}
		return -1;
	}

	function deleteRoom($rooms_id) {
		//global $CFG;

		$call = $this->getUrl()."/services/RoomService/deleteRoom?" .
				"SID=".$this->session_id.
				"&rooms_id=".$rooms_id;
		//$GLOBALS['ilLog']->write(__METHOD__.': '.$call);

		$restService = new openmeetings_rest_service();
		$err = $restService->getError();
		if ($err) {
			echo '<h2>Constructor error</h2><pre>' . $err . '</pre>';
			echo '<h2>Debug</h2><pre>' . htmlspecialchars($client->getDebug(), ENT_QUOTES) . '</pre>';
			exit();
		}
		$result = $restService->call($call);

		if ($restService->fault()) {
			echo '<h2>Fault (Expect - The request contains an invalid SOAP body)</h2><pre>'; print_r($result); echo '</pre>';
		} else {
			$err = $restService->getError();
			if ($err) {
				echo '<h2>Error</h2><pre>' . $err . '</pre>';
			} else {
				//echo '<h2>Result</h2><pre>'; print_r($result["return"]); echo '</pre>';
				//return $result["return"];
				return $result;
			}
		}
		return -1;
	}


	/**
	 * Generate a new room hash for entering a conference room
	 */
	function openmeetings_setUserObjectAndGenerateRoomHash($username,
									$firstname,
									$lastname,
									$profilePictureUrl,
									$email,
									$externalUserId,
									$externalUserType,
									$room_id,
									$becomeModeratorAsInt,
									$showAudioVideoTestAsInt) {

		//global $CFG;

		$restService = new openmeetings_rest_service();

		$result = $restService->call($this->getUrl()."/services/UserService/setUserObjectAndGenerateRoomHash?" .
					"SID=".$this->session_id.
					"&username=".urlencode($username).
					"&firstname=".urlencode($firstname).
					"&lastname=".urlencode($lastname).
					"&profilePictureUrl=".urlencode($profilePictureUrl).
					"&email=".urlencode($email).
					"&externalUserId=".urlencode($externalUserId).
					"&externalUserType=".urlencode($externalUserType).
					"&room_id=".$room_id.
					"&becomeModeratorAsInt=".$becomeModeratorAsInt.
					"&showAudioVideoTestAsInt=".$showAudioVideoTestAsInt);


		$err = $restService->getError();
		if ($err) {
			echo '<h2>Constructor error</h2><pre>' . $err . '</pre>';
			echo '<h2>Debug</h2><pre>' . htmlspecialchars($client->getDebug(), ENT_QUOTES) . '</pre>';
			exit();
		}

		if ($restService->getError()) {
			echo '<h2>Fault (Expect - The request contains an invalid SOAP body)</h2><pre>'; print_r($result); echo '</pre>';
		} else {
			$err = $restService->getError();
			if ($err) {
				echo '<h2>Error</h2><pre>' . $err . '</pre>';
			} else {
				//echo '<h2>Result</h2><pre>'; print_r($result["return"]); echo '</pre>';
				return $result;

			}
		}
		return -1;
	}

	/**
	 * Create a new conference room
	 */
	function openmeetings_createRoomWithModAndType($openmeetings) {
		global $CFG;

		$restService = new openmeetings_rest_service();

		$url = $this->getUrl().'/services/RoomService/addRoomWithModerationAndExternalType?' .
						'SID='.$this->session_id .
						'&name='.urlencode($openmeetings->name).
						'&roomtypes_id='.$openmeetings->type .
						'&comment='.urlencode($openmeetings->description) .
						'&numberOfPartizipants='.$openmeetings->max_user .
						'&ispublic=false'.
						'&appointment=false'.
						'&isDemoRoom=false'.
						'&demoTime=0' .
						'&isModeratedRoom='.$this->var_to_str($openmeetings->isModeratedRoom) .
						'&externalRoomType='.urlencode($CFG->openmeetings_openmeetingsModuleKey)
						;
		
	 	$result = $restService->call($url, "return");
		
		if ($restService->fault()) {
			echo '<h2>Fault (Expect - The request contains an invalid SOAP body)</h2><pre>'; print_r($result); echo '</pre>';
		} else {
			$err = $restService->getError();
			if ($err) {
				echo '<h2>Error</h2><pre>' . $err . '</pre>';
			} else {
				return $result;
			}
		}   
		return -1;
	}

	/**
	 * Get list of available recordings made by this ILIAS instance
	 */
	function openmeetings_getRecordingsByExternalRooms() {
	
		global $CFG;

		$restService = new openmeetings_rest_service();
		
		$url = $this->getUrl()."/services/RoomService/getFlvRecordingByExternalRoomType?" .
					"SID=".$this->session_id .
					"&externalRoomType=".urlencode($CFG->openmeetings_openmeetingsModuleKey);

		$result = $restService->call($url,"");
					
		return $result;		
					
	}

	//=============================================================
	//new functions for ILIAS
	
	public function openmeetings_getRoomTypes() {
		//global $CFG;

		$a_name=array();
		$a_id=array();
		$a_roomTypes=array();
		$restService = new openmeetings_rest_service();

		$call = $this->getUrl()."/services/RoomService/getRoomTypes?SID=".$this->session_id;
		$result = $restService->call($call,"");
		$GLOBALS['ilLog']->write(__METHOD__.': call: '.$call);
		if ($restService->getError()) {
			echo '<h2>Fault : Rest Service Error</h2><pre>'; print_r($result); echo '</pre>';
		} else {
			$err = $restService->getError();
			if ($err) {
				echo '<h2>Error</h2><pre>' . $err . '</pre>';
			} else {
				$dom = $result;
				$returnNodeList = $dom->getElementsByTagName("name");
				foreach ($returnNodeList as $returnNode) {
					if ($returnNode->nodeValue != "conference_room_type") array_push($a_name,$returnNode->nodeValue);
				}
				$returnNodeList = $dom->getElementsByTagName("roomtypes_id");
				foreach ($returnNodeList as $returnNode) {
					array_push($a_id,$returnNode->nodeValue);
				}
				for($i=0;$i<count($a_id);$i++) {
					$a_roomTypes[$i]=array( (string)$a_id[$i] => (string)$a_name[$i] );
				}
				return $a_roomTypes;
			}
		}
		return -1;
	}
	
	function getObjectsFromOpenmeetingsDOM($dom){
		// var_dump($dom);
		$xml = new SimpleXMLElement ($dom->saveXML());
		$spaceNames = $xml->getNameSpaces (true);
		$spaceName = "";
		foreach ($spaceNames as $key => $value){
			if (strcmp ($key, "ns") == 0) continue;
			if (strcmp ($key, "xsi") == 0) continue;
			if ($spaceName == "") $spaceName = $key;
		}
		$ret = $xml->children('ns', true)->return[0]->children($spaceName, true);
		//check for version 3.0.3
		if($spaceName=="ax215") return $ret;
		//newer version
		$itemsRequestedBy_openmeetings_updateRoomWithModerationQuestionsAudioTypeAndHideOptions='<ns>
		<allowUserQuestions>true</allowUserQuestions>
		<appointment>false</appointment>
		<comment></comment>
		<demoTime>0</demoTime>
		<hideActionsMenu>false</hideActionsMenu>
		<hideActivitiesAndActions>false</hideActivitiesAndActions>
		<hideChat>false</hideChat>
		<hideFilesExplorer>false</hideFilesExplorer>
		<hideScreenSharing>false</hideScreenSharing>
		<hideTopBar>false</hideTopBar>
		<hideWhiteboard>false</hideWhiteboard>
		<isAudioOnly>false</isAudioOnly>
		<isDemoRoom>false</isDemoRoom>
		<isModeratedRoom>false</isModeratedRoom>
		<ispublic>false</ispublic>
		<name></name>
		<numberOfPartizipants>25</numberOfPartizipants>
		<rooms_id></rooms_id>
		<roomtype>
			<name>conference</name>
			<roomtypes_id>1</roomtypes_id>
		</roomtype>
		</ns>';
		$omObj = new SimpleXMLElement($itemsRequestedBy_openmeetings_updateRoomWithModerationQuestionsAudioTypeAndHideOptions);

		//3.0.4
		$omObj->appointment = $ret->appointment; 
		$omObj->comment = $ret->comment; 
		$omObj->rooms_id = $ret->id; 
		$omObj->name = $ret->name; 
		$omObj->numberOfPartizipants = $ret->numberOfPartizipants; 
		$omObj->roomtype->name = $xml->children('ns', true)->return[0]->children($spaceName, true)->roomtype[0]->children("ax215", true)->name;
		$omObj->roomtype->roomtypes_id = $xml->children('ns', true)->return[0]->children($spaceName, true)->roomtype[0]->children("ax215", true)->roomtypes_id;
		//3.0.6 to test 3.0.5
		$omObj->allowUserQuestions = $ret->allowUserQuestions;
		$omObj->demoTime = $ret->demoTime;
		$omObj->hideActionsMenu = $ret->actionsMenuHidden;
		$omObj->hideActivitiesAndActions = $ret->activitiesHidden;
		$omObj->hideChat = $ret->chatHidden;
		$omObj->hideFilesExplorer = $ret->filesExplorerHidden;
		$omObj->hideScreenSharing = $ret->screenSharingHidden;
		$omObj->hideTopBar = $ret->topBarHidden;
		$omObj->hideWhiteboard = $ret->whiteboardHidden;
		$omObj->isAudioOnly = $ret->audioOnly;
		$omObj->isDemoRoom = $ret->demo;
		$omObj->isModeratedRoom = $ret->moderated;
		$omObj->ispublic = $ret->public;
		// $omObj-> = $ret->; 

		return $omObj;
	}
	
	public function openmeetings_getRoomById($roomId) {
		//global $CFG;

		$restService = new openmeetings_rest_service();

		$call = $this->getUrl()."/services/RoomService/getRoomById?SID=".$this->session_id. "&rooms_id=".$roomId;
		$result = $restService->call($call,"");
		// var_dump($call);
		// $GLOBALS['ilLog']->write(__METHOD__.': call: '.$call);
		if ($restService->getError()) {
			echo '<h2>Fault (Expect - The request contains an invalid REST body)</h2><pre>'; print_r($result); echo '</pre>';
		} else {
			$err = $restService->getError();
			if ($err) {
				echo '<h2>Error</h2><pre>' . $err . '</pre>';
			} else {
				return self::getObjectsFromOpenmeetingsDOM($result);
			}
		}
		return -1;
	}


	function openmeetings_updateRoomWithModerationQuestionsAudioTypeAndHideOptions($openmeetings) {
		global $CFG;
		if ($CFG->openmeetings_version_2_x == false) {
			$result = $this->openmeetings_updateRoomWithModeration($openmeetings);
			return $result;
		}
		$restService = new openmeetings_rest_service();
		$call = $this->getUrl()."/services/RoomService/updateRoomWithModerationQuestionsAudioTypeAndHideOptions?" .
				"SID=".$this->session_id.
				"&room_id=".$openmeetings->rooms_id.
				"&name=".urlencode($openmeetings->name).
				"&roomtypes_id=".urlencode($openmeetings->roomtype->roomtypes_id).
				"&comment=".urlencode($openmeetings->comment) .
				"&numberOfPartizipants=".$openmeetings->numberOfPartizipants.
				'&ispublic='.$this->var_to_str($openmeetings->ispublic).
				'&appointment='.$this->var_to_str($openmeetings->appointment).
				'&isDemoRoom='.$this->var_to_str($openmeetings->isDemoRoom).
				'&demoTime='.$openmeetings->demoTime.
				"&isModeratedRoom=".$this->var_to_str($openmeetings->isModeratedRoom) .
				"&allowUserQuestions=".$this->var_to_str($openmeetings->allowUserQuestions) .
				"&isAudioOnly=".$this->var_to_str($openmeetings->isAudioOnly) .
				"&hideTopBar=".$this->var_to_str($openmeetings->hideTopBar) .
				"&hideChat=".$this->var_to_str($openmeetings->hideChat) .
				"&hideActivitiesAndActions=".$this->var_to_str($openmeetings->hideActivitiesAndActions) .
				"&hideFilesExplorer=".$this->var_to_str($openmeetings->hideFilesExplorer) .
				"&hideActionsMenu=".$this->var_to_str($openmeetings->hideActionsMenu) .
				"&hideScreenSharing=".$this->var_to_str($openmeetings->hideScreenSharing) .
				"&hideWhiteboard=".$this->var_to_str($openmeetings->hideWhiteboard);
		// var_dump($call);
		// $GLOBALS['ilLog']->write(__METHOD__.': '.$call);
		$result = $restService->call($call);

		if ($restService->fault()) {
			echo '<h2>Fault (Expect - The request contains an invalid SOAP body)</h2><pre>'; print_r($result); echo '</pre>';
		} else {
			$err = $restService->getError();
			if ($err) {
				echo '<h2>Error</h2><pre>' . $err . '</pre>';
			} else {
				//echo '<h2>Result</h2><pre>'; print_r($result["return"]); echo '</pre>';
				return $result;
			}
		}
		return -1;
	}

}
?>
