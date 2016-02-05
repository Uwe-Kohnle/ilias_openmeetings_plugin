<?php
/**
 * Openmeetings configuration class
 * @author  Uwe Kohnle <kohnle@internetlehrer-gmbh.de>
 * @version $Id$
 *
 */
class ilOpenmeetingsConfig
{
	private static $instance = null;
	
	
	private $svrurl = 'http://yourserver.de';
	private $svrport = 5080;
	private $svrappname = 'openmeetings';
	private $svrusername = '';
	private $svrpassword = '';
	private $allowUpdate = array(
			"isDemoRoom" => false,
			"ispublic" => false,
			"om2x" => false,
			"allowUserQuestions" => true,
			"isAudioOnly" => true,
			"hideTopBar" => true,
			"hideChat" => true,
			"hideActivitiesAndActions" => true,
			"hideFilesExplorer" => true,
			"hideActionsMenu" => true,
			"hideScreenSharing" => true,
			"hideWhiteboard" => true
		);

	/**
	 * Constructor
	 */
	public function __construct()
	{
		$this->read();
	}
	
	/**
	 * Get singleton instance
	 * 
	 * @return ilOpenmeetingsConfig
	 */
	public static function getInstance()
	{
		if(self::$instance)
		{
			return self::$instance;
		}
		return self::$instance = new ilOpenmeetingsConfig();
	}

	public function getSvrUrl() {
		return $this->svrurl;
	}
	public function setSvrUrl($a_svrurl) {
		$this->svrurl = $a_svrurl;
	}

	public function getSvrPort() {
		return $this->svrport;
	}
	public function setSvrPort($a_svrport) {
		$this->svrport = $a_svrport;
	}
	
	public function getSvrAppname() {
		return $this->svrappname;
	}
	public function setSvrAppname($a_svrappname) {
		$this->svrappname = $a_svrappname;
	}

	public function getSvrUsername() {
		return $this->svrusername;
	}
	public function setSvrUsername($a_svrusername) {
		$this->svrusername = $a_svrusername;
	}
	
	public function getSvrPassword() {
		return $this->svrpassword;
	}
	public function setSvrPassword($a_svrpassword) {
		$this->svrpassword = $a_svrpassword;
	}

	public function getAllowUpdate($a_val) {
		return $this->allowUpdate[$a_val];
	}
	public function setAllowUpdate($a_item,$a_val) {
		$this->allowUpdate[$a_item] = $a_val;
	}

	/**
	* 
	*/
	public function save()
	{
		global $ilDB;
		// check if data exisits decide to update or insert
		$result = $ilDB->query("SELECT * FROM rep_robj_xomv_conn");
		$num = $ilDB->numRows($result);
		if($num == 0){
			$quer2 = "";
			$query = "INSERT INTO rep_robj_xomv_conn (id, svrurl, svrport, svrappname, svrusername, svrpassword";
			foreach($this->allowUpdate as $key => $value)  {
				$query.= ", ".strtolower($key);
				$quer2.= ",".$ilDB->quote($value, "integer");
			}
			$query.=") VALUES (".
			$ilDB->quote(1, "integer").",". // id from old versions
			$ilDB->quote($this->getSvrUrl(), "text").",".
			$ilDB->quote($this->getSvrPort(), "integer").",".
			$ilDB->quote($this->getSvrAppname(), "text").",".
			$ilDB->quote($this->getSvrUsername(), "text").",".
			$ilDB->quote($this->getSvrPassword(), "text").
			$quer2.")";
			$ilDB->manipulate($query);
		} else {
			$up = "UPDATE rep_robj_xomv_conn  SET ".
			" svrurl = ".$ilDB->quote($this->getSvrUrl(), "text").",".
			" svrport = ".$ilDB->quote($this->getSvrPort(), "integer").",".
			" svrappname = ".$ilDB->quote($this->getSvrAppname(), "text").",".
			" svrusername = ".$ilDB->quote($this->getSvrUsername(), "text").",".
			" svrpassword = ".$ilDB->quote($this->getSvrPassword(),"text");
			foreach($this->allowUpdate as $key => $value)  {
				$up.= ", ".strtolower($key). "=".$ilDB->quote($value, "integer");
			}
			$up .= " WHERE id = ".$ilDB->quote(1, "integer"); //not necessary now

			$ilDB->manipulate($up);
		}
	}

	/**
	*
	*/
	public function read()
	{
		global $ilDB;
		$result = $ilDB->query("SELECT * FROM rep_robj_xomv_conn");
		while ($record = $ilDB->fetchAssoc($result)) {
			$this->setSvrUrl($record["svrurl"]);
			$this->setSvrPort($record["svrport"]);
			$this->setSvrUsername($record["svrusername"]);
			$this->setSvrPassword($record["svrpassword"]);
			$this->setSvrAppname($record["svrappname"]);
			foreach($this->allowUpdate as $key => $value)  {
				$this->setAllowUpdate($key,$record[strtolower($key)]);
			}
		}
    }
}


?>
