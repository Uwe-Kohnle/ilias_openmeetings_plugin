<?php

include_once("./Services/Component/classes/class.ilPluginConfigGUI.php");
 
/**
* openMeetings configuration user interface class
*
* @author Paul <ilias@gdconsulting.it>
* @author Uwe Kohnle <kohnle@internetlehrer-gmbh.de>
* @version $Id$
*
* @ilCtrl_Calls ilOpenmeetingsConfigGUI: ilCommonActionDispatcherGUI
*/
class ilOpenmeetingsConfigGUI extends ilPluginConfigGUI
{
	/**
	* Handles all commmands, default is "configure"
	*/
	function performCommand($cmd)
	{

		switch ($cmd)
		{
			case "configure":
			case "save":
				$this->$cmd();
				break;

		}
	}

	/**
	 * Configure screen
	 */
	function configure()
	{
		global $tpl;

		$this->initConfigurationForm();
		$this->getValues();
		$tpl->setContent($this->form->getHTML());
		
	}
	
	protected $fields = array(
		"isDemoRoom"	=> array("type"=>"ilCheckboxInputGUI","info"=>"info_isDemoRoom","subelements"=>null),
		"ispublic"	=> array("type"=>"ilCheckboxInputGUI","info"=>"info_ispublic","subelements"=>null),
		"om2x"			=> array("type"=>"ilCheckboxInputGUI","info"=>"info_om2x","subelements"=>array(
				"allowUserQuestions"		=> array("type"=>"ilCheckboxInputGUI","info"=>"info_allowUserQuestions"),
				"isAudioOnly"				=> array("type"=>"ilCheckboxInputGUI","info"=>"info_isAudioOnly"),
				"hideTopBar"				=> array("type"=>"ilCheckboxInputGUI","info"=>"info_hideTopBar"),
				"hideChat"					=> array("type"=>"ilCheckboxInputGUI","info"=>"info_hideChat"),
				"hideActivitiesAndActions"	=> array("type"=>"ilCheckboxInputGUI","info"=>"info_hideActivitiesAndActions"),
				"hideFilesExplorer"			=> array("type"=>"ilCheckboxInputGUI","info"=>"info_hideFilesExplorer"),
				"hideActionsMenu"			=> array("type"=>"ilCheckboxInputGUI","info"=>"info_hideActionsMenu"),
				"hideScreenSharing"			=> array("type"=>"ilCheckboxInputGUI","info"=>"info_hideScreenSharing"),
				"hideWhiteboard"			=> array("type"=>"ilCheckboxInputGUI","info"=>"info_hideWhiteboard")
			),
		),
	);
	
	/**
	 * Init configuration form.
	 *
	 * @return object form object
	 */
	public function initConfigurationForm()
	{
		global $lng, $ilCtrl, $ilDB;

		$pl = $this->getPluginObject();
		$this->getPluginObject()->includeClass('class.ilOpenmeetingsConfig.php');
		$this->object = ilOpenmeetingsConfig::getInstance();

		include_once("Services/Form/classes/class.ilPropertyFormGUI.php");
		$this->form = new ilPropertyFormGUI();
		
		$this->form->setTitle($pl->txt("openmeetings_plugin_configuration"));
		$this->form->setFormAction($ilCtrl->getFormAction($this));
		$this->form->addCommandButton("save", $lng->txt("save"));
		
		// url (text)
		$ti = new ilTextInputGUI($pl->txt("url"), "frmurl");
		$ti->setRequired(true);
		$ti->setMaxLength(256);
		$ti->setSize(60);
		$this->form->addItem($ti);
		
		// port (text)
		$ti = new ilTextInputGUI($pl->txt("port"), "frmport");
		$ti->setRequired(true);
		$ti->setMaxLength(10);
		$ti->setSize(10);
		$this->form->addItem($ti);
		
		// appname
		$ti = new ilTextInputGUI($pl->txt("appname"), "frmappname");
		$ti->setRequired(true);
		$ti->setMaxLength(32);
		$ti->setSize(20);
		$this->form->addItem($ti);

		// username (text)
		$ti = new ilTextInputGUI($pl->txt("username"), "frmusername");
		$ti->setRequired(true);
		$ti->setMaxLength(256);
		$ti->setSize(20);
		$this->form->addItem($ti);
		
		// password (text)
		$ti = new ilPasswordInputGUI($pl->txt("password"), "frmpassword");
		$ti->setRequired(true);
		$ti->setMaxLength(256);
		$ti->setSize(20);
		$ti->setRetype(false);
		$this->form->addItem($ti);

		foreach($this->fields as $key => $item)
		{
			$field = new $item["type"]($this->plugin_object->txt('conf_'.$key), $key);
			$field->setInfo($this->plugin_object->txt($item["info"]));
			if(is_array($item["subelements"]))
			{
				foreach($item["subelements"] as $subkey => $subitem)
				{
					$subfield = new $subitem["type"]($this->plugin_object->txt('conf_'.$key . "_" . $subkey),$subkey);
					$subfield->setInfo($this->plugin_object->txt($subitem["info"]));
					$field->addSubItem($subfield);
				}
			}
			$this->form->addItem($field);
		}
		return $this->form;
	}
	
	public function getValues()
	{
		$values["frmurl"] = $this->object->getSvrUrl();
		$values["frmport"] = $this->object->getSvrPort();
		$values["frmappname"] = $this->object->getSvrAppname();
		$values["frmusername"] = $this->object->getSvrUsername();
		$values["frmpassword"] = $this->object->getSvrPassword();
		foreach($this->fields as $key => $item)
		{
			$values[$key] = $this->object->getAllowUpdate($key);
			if(is_array($item["subelements"]))
			{
				foreach($item["subelements"] as $subkey => $subitem)
				{
					$values[$subkey] = $this->object->getAllowUpdate($subkey);
				}
			}
		}
		$this->form->setValuesByArray($values);
	}

	/**
	 * Save form input
	 *
	 */
	public function save()
	{
		global $tpl, $lng, $ilCtrl;
	
		$pl = $this->getPluginObject();
		
		$form = $this->initConfigurationForm();
		if ($form->checkInput())
		{
			$this->object->setSvrUrl($form->getInput("frmurl"));
			$this->object->setSvrPort($form->getInput("frmport"));
			$this->object->setSvrAppname($form->getInput("frmappname"));
			$this->object->setSvrUsername($form->getInput("frmusername"));
			$this->object->setSvrPassword($form->getInput("frmpassword"));
			// Save Checkbox Values
			foreach($this->fields as $key => $item)
			{
				$this->object->setAllowUpdate($key, $this->form->getInput($key));
				if(is_array($item["subelements"]))
				{
					foreach($item["subelements"] as $subkey => $subitem)
					{
						$this->object->setAllowUpdate($subkey, $this->form->getInput($subkey));
					}
				}
			}
			$this->object->save();
			ilUtil::sendSuccess($pl->txt("saving_invoked"), true);
			$ilCtrl->redirect($this, "configure");
		}
		else
		{
			$form->setValuesByPost();
			$tpl->setContent($form->getHtml());
		}
	}
	


}
?>
