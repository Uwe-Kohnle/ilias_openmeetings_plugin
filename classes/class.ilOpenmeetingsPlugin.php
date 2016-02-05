<?php

include_once("./Services/Repository/classes/class.ilRepositoryObjectPlugin.php");
 
/**
* Openmeetings repository object plugin
*
* @author Paul <ilias@gdconsulting.it>, Uwe Kohnle <kohnle@internetlehrer-gmbh.de>
* @version $Id$
*
*/
class ilOpenmeetingsPlugin extends ilRepositoryObjectPlugin
{
	function getPluginName()
	{
		return "Openmeetings";
	}

	protected function uninstallCustom() {

		global $ilDB;

		if ($ilDB->tableExists('rep_robj_xomv_data')) {
			$ilDB->dropTable('rep_robj_xomv_data');
		}
		if ($ilDB->tableExists('rep_robj_xomv_conn')) {
			$ilDB->dropTable('rep_robj_xomv_conn');
		}

	}

}
?>
