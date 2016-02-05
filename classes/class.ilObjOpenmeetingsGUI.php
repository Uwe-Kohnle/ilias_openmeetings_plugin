<?php
include_once("./Services/Repository/classes/class.ilObjectPluginGUI.php");

/**
* User  class for Openmeetings repository object.
*
* User  classes process GET and POST parameter and call
* application classes to fulfill certain tasks.
*
* @author Paul <ilias@gdconsulting.it>
* @author Uwe Kohnle <kohnle@internetlehrer-gmbh.de>
*
* $Id$
*
* Integration into control structure:
* - The GUI class is called by ilRepositoryGUI
* - GUI classes used by this class are ilPermissionGUI (provides the rbac
*   screens) and ilInfoScreenGUI (handles the info screen).
*
* @ilCtrl_isCalledBy ilObjOpenmeetingsGUI: ilRepositoryGUI, ilAdministrationGUI, ilObjPluginDispatchGUI
* @ilCtrl_Calls ilObjOpenmeetingsGUI: ilPermissionGUI, ilInfoScreenGUI, ilObjectCopyGUI, ilCommonActionDispatcherGUI
*
*/
class ilObjOpenmeetingsGUI extends ilObjectPluginGUI
{
	/**
	* Initialisation
	*/
	protected function afterConstructor()
	{
		// anything needed after object has been constructed
		// - Openmeetings: append my_id GET parameter to each request
		//   $ilCtrl->saveParameter($this, array("my_id"));
		//$this->deactivateCreationForm(ilObject2GUI::CFORM_IMPORT);
		//$this->deactivateCreationForm(ilObject2GUI::CFORM_CLONE);
	}
	
	/**
	* Get type.
	*/
	final function getType()
	{
		return "xomv";
	}
	
	/**
	* Handles all commmands of this class, centralizes permission checks
	*/
	function performCommand($cmd)
	{
		$next_class = $this->ctrl->getNextClass($this);
		switch($next_class)
		{
			case 'ilcommonactiondispatchergui':
				require_once 'Services/Object/classes/class.ilCommonActionDispatcherGUI.php';
				$gui = ilCommonActionDispatcherGUI::getInstanceFromAjaxCall();
				return $this->ctrl->forwardCommand($gui);
				break;
		}

		switch ($cmd)
		{
			case "editProperties":		// list all commands that need write permission here
			case "updateProperties":
				$this->checkPermission("write");
				$this->$cmd();
				break;
			
			case "showContent":			// list all commands that need read permission here
			//case "...":
			//case "...":
				$this->checkPermission("read");
				$this->$cmd();
				break;
		}
	}
	/**
	 * init create form
	 * @param  $a_new_type
	 */
	public function initCreateForm($a_new_type)
	{
		$form = parent::initCreateForm($a_new_type);
		// online
		$cb = new ilCheckboxInputGUI($this->lng->txt("online"), "online");
		$form->addItem($cb);

		// room type
		include_once("./Customizing/global/plugins/Services/Repository/RepositoryObject/Openmeetings/classes/class.ilOpenmeetingsREST.php");
		$this->omr = new ilOpenmeetingsREST();
		$this->omr->openmeetings_loginuser();
		$options=array();
		$a_r = $this->omr->openmeetings_getRoomTypes();
		for ($i=0; $i<count($a_r); $i++) {
			$options[key($a_r[$i])] = $a_r[$i][key($a_r[$i])];
		}
		$si = new ilSelectInputGUI($this->lng->txt('rep_robj_xomv_type'), 'rmtypes');
		$si->setOptions($options);
		$form->addItem($si);

		return $form;
	}

	/**
	 *
	 * @global <type> $ilCtrl
	 * @global <type> $ilUser
	 * @param ilObj $newObj
	 */
	public function afterSave($newObj)
	{
		global $ilCtrl, $ilUser;
		$form = $this->initCreateForm('xomv');
		$form->checkInput();
		$newObj->createRoom($form->getInput("rmtypes"),$form->getInput("online"));
		parent::afterSave($newObj);
	}
	/**
	* After object has been created -> jump to this command
	*/
	function getAfterCreationCmd()
	{
		return "editProperties";
	}

	/**
	* Get standard command
	*/
	function getStandardCmd()
	{
		return "showContent";
	}
	
//
// DISPLAY TABS
//
	
	/**
	* Set tabs
	*/
	function setTabs()
	{
		global $ilTabs, $ilCtrl, $ilAccess;
		
		// tab for the "show content" command
		if ($ilAccess->checkAccess("read", "", $this->object->getRefId()))
		{
			$ilTabs->addTab("content", $this->txt("content"), $ilCtrl->getLinkTarget($this, "showContent"));
		}

		// standard info screen tab
		$this->addInfoTab();

		// a "properties" tab
		if ($ilAccess->checkAccess("write", "", $this->object->getRefId()))
		{
			$ilTabs->addTab("properties", $this->txt("properties"), $ilCtrl->getLinkTarget($this, "editProperties"));
		}

		// standard epermission tab
		$this->addPermissionTab();
	}

	/**
	* Edit Properties. This commands uses the form class to display an input form.
	*/
	function editProperties()
	{
		global $tpl, $ilTabs;

		$ilTabs->activateTab("properties");
		$this->initPropertiesForm();
		$this->getPropertiesValues();
		$tpl->setContent($this->form->getHTML());
	}
	
	function selectItemCheck($item) {
		include_once("./Customizing/global/plugins/Services/Repository/RepositoryObject/Openmeetings/classes/class.ilOpenmeetingsConfig.php");
		$settings = ilOpenmeetingsConfig::getInstance();
		if ($settings->getAllowUpdate($item) && ($item == "isDemoRoom" || $item == "ispublic" || $settings->getAllowUpdate("om2x") == true)) {
			$hd = new ilCheckboxInputGUI($this->lng->txt("rep_robj_xomv_rm".$item), "rm".$item);
			$hd->setInfo($this->lng->txt("rep_robj_xomv_info_".$item));
		} else {
			$hd = new ilHiddenInputGUI("rm".$item);
		}
		$hd->setValue(1);
		return $hd;
	}
	
	/**
	* Init  form.
	*
	* @param        int        $a_mode        Edit Mode
	*/
	public function initPropertiesForm()
	{
		global $ilCtrl;
		include_once("./Customizing/global/plugins/Services/Repository/RepositoryObject/Openmeetings/classes/class.ilOpenmeetingsREST.php");
		$this->omr = new ilOpenmeetingsREST();
		$this->omr->openmeetings_loginuser();
		include_once("Services/Form/classes/class.ilPropertyFormGUI.php");
		$this->form = new ilPropertyFormGUI();

		// title
		$ti = new ilTextInputGUI($this->txt("title"), "title");
		$ti->setRequired(true);
		$this->form->addItem($ti);
				
		// description
		$ta = new ilTextAreaInputGUI($this->txt("description"), "desc");
		$this->form->addItem($ta);
		
		// online
		$cb = new ilCheckboxInputGUI($this->lng->txt("online"), "online");
		$this->form->addItem($cb);
		
		// rmid
		$hd = new ilHiddenInputGUI("rmid");
		$hd->setValue(30);
		$this->form->addItem($hd);
		
		// room type
		$options=array();
		$a_r = $this->omr->openmeetings_getRoomTypes();
		for ($i=0; $i<count($a_r); $i++) {
			$options[key($a_r[$i])] = $a_r[$i][key($a_r[$i])];//[(string)key($a_r[$i]) => $a_r[$i][key($a_r[$i])];
		}
		$si = new ilSelectInputGUI($this->lng->txt('rep_robj_xomv_type'), 'rmtypes');
		$si->setOptions($options);
		$this->form->addItem($si);
		
		// rmcomment
		//$hd = new ilHiddenInputGUI("rmcomment");
		//$this->form->addItem($hd);
		
		
		
		// rmparticipants
		$options_ref = array(2,4,6,8,10,12,14,16,20,25,32,50,100,150,200,500,1000);
		$options = array();
		for($i = 2; $i <= 1000; $i++)
		{
			if (in_array($i, $options_ref)){
			$options[$i] = $i; }
		}	
		$hd = new ilSelectInputGUI($this->lng->txt('rep_robj_xomv_rmparticipants'), 'rmparticipants');
		$hd->setOptions($options);
		$this->form->addItem($hd);
		
		// ismoderated
		$hd = new ilCheckboxInputGUI($this->lng->txt("rep_robj_xomv_rmismoderated"), "rmismoderated");
		$hd->setInfo($this->lng->txt("rep_robj_xomv_info_ismoderated"));
		$hd->setValue(1);
		$this->form->addItem($hd);

		// rmispublic
		$this->form->addItem($this->selectItemCheck("ispublic"));

		// rmappointment - not available in this version
		$hd = new ilHiddenInputGUI("rmappointment");
		$hd->setValue(0);
		$this->form->addItem($hd);

		// rmisDemoRoom
		$this->form->addItem($this->selectItemCheck("isDemoRoom"));

		// rmdemotime
		$hd = new ilHiddenInputGUI("rmdemotime");
		$hd->setValue(600);
		$this->form->addItem($hd);

		$this->form->addItem($this->selectItemCheck("allowUserQuestions"));
		$this->form->addItem($this->selectItemCheck("isAudioOnly"));
		$this->form->addItem($this->selectItemCheck("hideTopBar"));
		$this->form->addItem($this->selectItemCheck("hideChat"));
		$this->form->addItem($this->selectItemCheck("hideActivitiesAndActions"));
		$this->form->addItem($this->selectItemCheck("hideFilesExplorer"));
		$this->form->addItem($this->selectItemCheck("hideActionsMenu"));
		$this->form->addItem($this->selectItemCheck("hideScreenSharing"));
		$this->form->addItem($this->selectItemCheck("hideWhiteboard"));

		$this->form->addCommandButton("updateProperties", $this->txt("save"));
	                
		$this->form->setTitle($this->txt("edit_properties"));
		$this->form->setFormAction($ilCtrl->getFormAction($this));
	}
	
	/**
	* Get values for edit properties form
	*/
	function getPropertiesValues()
	{
		$this->object->omRead();
		$values["title"] = $this->object->getTitle();
		$values["desc"] = $this->object->getDescription();
		$values["online"] = $this->object->getOnline();
		$values["rmid"] = $this->object->getrmId();
		$values["rmtypes"] = $this->object->getrmTypes();
		//$values["rmcomment"] = $this->object->getrmComment();
		$values["rmparticipants"] = $this->object->getrmParticipants();
		$values["rmispublic"] = $this->object->getrmIsPublic();
		$values["rmappointment"] = $this->object->getrmAppointment();
		$values["rmisDemoRoom"] = $this->object->getrmisDemoRoom();
		$values["rmdemotime"] = $this->object->getrmDemoTime();
		$values["rmismoderated"] = $this->object->getrmIsModerated();
		$values["rmallowUserQuestions"] = $this->object->getrm_allowUserQuestions();
		$values["rmisAudioOnly"] = $this->object->getrm_isAudioOnly();
		$values["rmhideTopBar"] = $this->object->getrm_hideTopBar();
		$values["rmhideChat"] = $this->object->getrm_hideChat();
		$values["rmhideActivitiesAndActions"] = $this->object->getrm_hideActivitiesAndActions();
		$values["rmhideFilesExplorer"] = $this->object->getrm_hideFilesExplorer();
		$values["rmhideActionsMenu"] = $this->object->getrm_hideActionsMenu();
		$values["rmhideScreenSharing"] = $this->object->getrm_hideScreenSharing();
		$values["rmhideWhiteboard"] = $this->object->getrm_hideWhiteboard();
		$this->form->setValuesByArray($values);
		
	}
	
	/**
	* Update properties
	*/
	public function updateProperties()
	{
		global $tpl, $lng, $ilCtrl;
		$this->initPropertiesForm();
		if ($this->form->checkInput())
		{
			$demotime = $this->form->getInput("rmdemotime");
			if ($this->form->getInput("rmisDemoRoom") == 1 &&  $demotime == 0) $demotime = 1200;
			$this->object->omRead();
			$this->object->setTitle($this->form->getInput("title"));
			$this->object->setDescription($this->form->getInput("desc"));
			$this->object->setrmId($this->form->getInput("rmid"));
			$this->object->setrmTypes($this->form->getInput("rmtypes"));
			//$this->object->setrmComment($this->form->getInput("rmcomment"));
			$this->object->setrmParticipants($this->form->getInput("rmparticipants"));
			$this->object->setrmIsPublic($this->form->getInput("rmispublic"));
			$this->object->setrmAppointment($this->form->getInput("rmappointment"));
			$this->object->setrmisDemoRoom($this->form->getInput("rmisDemoRoom"));
			$this->object->setrmDemoTime($demotime);
			$this->object->setrmIsModerated($this->form->getInput("rmismoderated"));
			$this->object->setOnline($this->form->getInput("online"));
			$this->object->setrm_allowUserQuestions($this->form->getInput("rmallowUserQuestions"));
			$this->object->setrm_isAudioOnly($this->form->getInput("rmisAudioOnly"));
			$this->object->setrm_hideTopBar($this->form->getInput("rmhideTopBar"));
			$this->object->setrm_hideChat($this->form->getInput("rmhideChat"));
			$this->object->setrm_hideActivitiesAndActions($this->form->getInput("rmhideActivitiesAndActions"));
			$this->object->setrm_hideFilesExplorer($this->form->getInput("rmhideFilesExplorer"));
			$this->object->setrm_hideActionsMenu($this->form->getInput("rmhideActionsMenu"));
			$this->object->setrm_hideScreenSharing($this->form->getInput("rmhideScreenSharing"));
			$this->object->setrm_hideWhiteboard($this->form->getInput("rmhideWhiteboard"));

			$returnVal = $this->object->update();
			ilUtil::sendSuccess($lng->txt("msg_obj_modified"), true);
			$ilCtrl->redirect($this, "editProperties");
		}

		$this->form->setValuesByPost();
		$tpl->setContent($this->form->getHtml());
	}


	/**
	* Show content
	*/
	function showContent()
	{
		global $tpl, $ilTabs;
		$ilTabs->clearTargets();
		$ilTabs->activateTab("content");//necessary...
		
		$my_tpl = new ilTemplate("./Customizing/global/plugins/Services/Repository/RepositoryObject/Openmeetings/templates/tpl.Openmeetingsclient.html", true, true);
		$cmdURL = $this->object->getCmdUrlToShowContent();
		$my_tpl->setVariable("cmdURL", $cmdURL);
		$my_tpl->setVariable("omStartOption", $this->lng->txt('rep_robj_xomv_om_start_option'));
		$my_tpl->setVariable("omStartIframe", $this->lng->txt('rep_robj_xomv_om_start_iframe'));
		$my_tpl->setVariable("omStartIframeOnly", $this->lng->txt('rep_robj_xomv_om_start_iframe_only'));
		$my_tpl->setVariable("omStartWindow", $this->lng->txt('rep_robj_xomv_om_start_window'));
		$my_tpl->setVariable("omStartWindowOnly", $this->lng->txt('rep_robj_xomv_om_start_window_only'));
		$my_tpl->setVariable("omStartDisable", $this->lng->txt('rep_robj_xomv_om_start_disable'));
		$my_tpl->setVariable("omStartDisabled", $this->lng->txt('rep_robj_xomv_om_start_disabled'));
		$my_tpl->setVariable("omReload", $this->lng->txt('rep_robj_xomv_om_reload'));
		$my_tpl->setVariable("omStartIframeAuto", $this->lng->txt('rep_robj_xomv_om_start_iframe_auto'));
		$my_tpl->setVariable("omStartWindowAuto", $this->lng->txt('rep_robj_xomv_om_start_window_auto'));
		$my_tpl->setVariable("omStartStop", $this->lng->txt('rep_robj_xomv_om_start_stop'));
		$my_tpl->setVariable("omWindowBlocked", $this->lng->txt('rep_robj_xomv_om_window_blocked'));
		$my_tpl->setVariable("omWindowStarted", $this->lng->txt('rep_robj_xomv_om_window_started'));
		$my_tpl->setVariable("omWindowClosed", $this->lng->txt('rep_robj_xomv_om_window_closed'));
		$tpl->setContent($my_tpl->get());
	}

}
?>
