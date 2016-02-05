<#1>
<?php
$fields_data = array(
	'id' => array(
		'type' => 'integer',
		'length' => 8,
		'notnull' => true
	),
	'is_online' => array(
		'type' => 'integer',
		'length' => 1,
		'notnull' => false
	),
	'rmid' => array(
		'type' => 'integer',
		'length' => 8,
		'notnull' => false
	),
	'rmtypes' => array(
		'type' => 'integer',
		'length' => 1,
		'notnull' => false
	),
	'rmcomment' => array(
		'type' => 'text',
		'length' => 256,
		'fixed' => false,
		'notnull' => false
	),
	'rmparticipants' => array(
		'type' => 'integer',
		'length' => 4,
		'notnull' => false
	),
	'rmispublic' => array(
		'type' => 'integer',
		'length' => 1,
		'notnull' => false
	),
	'rmappointment' => array(
		'type' => 'integer',
		'length' => 1,
		'notnull' => false
	),
	'rmisdemo' => array(
		'type' => 'integer',
		'length' => 1,
		'notnull' => false
	),
	'rmdemotime' => array(
		'type' => 'integer',
		'length' => 8,
		'notnull' => false
	),
	'rmismoderated' => array(
		'type' => 'integer',
		'length' => 1,
		'notnull' => false
	)
	
);

$ilDB->createTable("rep_robj_xomv_data", $fields_data);
$ilDB->addPrimaryKey("rep_robj_xomv_data", array("id"));

$fields_conn = array(
        'id' => array(
                'type' => 'integer',
                'length' => 4,
                'notnull' => true
        ),
        'svrurl' => array(
                'type' => 'text',
                'length' => 256,
                'notnull' => true
        ),
        'svrport' => array(
                'type' => 'integer',
                'length' => 8,
                'notnull' => true
        ),
        'svrusername' => array(
                'type' => 'text',
                'length' => 256,
                'notnull' => true
        ),
        'svrpassword' => array(
                'type' => 'text',
                'length' => 256,
                'notnull' => true
        )
);

$ilDB->createTable("rep_robj_xomv_conn", $fields_conn);
$ilDB->addPrimaryKey("rep_robj_xomv_conn", array("id"));
?>
<#2>
<?php
if ( !$ilDB->tableColumnExists('rep_robj_xomv_conn', 'isdemoroom') ) {
	$ilDB->addTableColumn('rep_robj_xomv_conn', 'isdemoroom', array(
		'type' => 'integer',
		'length'  => 1,
		'notnull' => true,
		'default' => 0
	));
}
if ( !$ilDB->tableColumnExists('rep_robj_xomv_conn', 'ispublic') ) {
	$ilDB->addTableColumn('rep_robj_xomv_conn', 'ispublic', array(
		'type' => 'integer',
		'length'  => 1,
		'notnull' => true,
		'default' => 0
	));
}
if ( !$ilDB->tableColumnExists('rep_robj_xomv_conn', 'om2x') ) {
	$ilDB->addTableColumn('rep_robj_xomv_conn', 'om2x', array(
		'type' => 'integer',
		'length'  => 1,
		'notnull' => true,
		'default' => 0
	));
}
if ( !$ilDB->tableColumnExists('rep_robj_xomv_conn', 'allowuserquestions') ) {
	$ilDB->addTableColumn('rep_robj_xomv_conn', 'allowuserquestions', array(
		'type' => 'integer',
		'length'  => 1,
		'notnull' => true,
		'default' => 1
	));
}
if ( !$ilDB->tableColumnExists('rep_robj_xomv_conn', 'isaudioonly') ) {
	$ilDB->addTableColumn('rep_robj_xomv_conn', 'isaudioonly', array(
		'type' => 'integer',
		'length'  => 1,
		'notnull' => true,
		'default' => 1
	));
}
if ( !$ilDB->tableColumnExists('rep_robj_xomv_conn', 'hidetopbar') ) {
	$ilDB->addTableColumn('rep_robj_xomv_conn', 'hidetopbar', array(
		'type' => 'integer',
		'length'  => 1,
		'notnull' => true,
		'default' => 1
	));
}
if ( !$ilDB->tableColumnExists('rep_robj_xomv_conn', 'hidechat') ) {
	$ilDB->addTableColumn('rep_robj_xomv_conn', 'hidechat', array(
		'type' => 'integer',
		'length'  => 1,
		'notnull' => true,
		'default' => 1
	));
}
if ( !$ilDB->tableColumnExists('rep_robj_xomv_conn', 'hideactivitiesandactions') ) {
	$ilDB->addTableColumn('rep_robj_xomv_conn', 'hideactivitiesandactions', array(
		'type' => 'integer',
		'length'  => 1,
		'notnull' => true,
		'default' => 1
	));
}
if ( !$ilDB->tableColumnExists('rep_robj_xomv_conn', 'hidefilesexplorer') ) {
	$ilDB->addTableColumn('rep_robj_xomv_conn', 'hidefilesexplorer', array(
		'type' => 'integer',
		'length'  => 1,
		'notnull' => true,
		'default' => 1
	));
}
if ( !$ilDB->tableColumnExists('rep_robj_xomv_conn', 'hideactionsmenu') ) {
	$ilDB->addTableColumn('rep_robj_xomv_conn', 'hideactionsmenu', array(
		'type' => 'integer',
		'length'  => 1,
		'notnull' => true,
		'default' => 1
	));
}
if ( !$ilDB->tableColumnExists('rep_robj_xomv_conn', 'hidescreensharing') ) {
	$ilDB->addTableColumn('rep_robj_xomv_conn', 'hidescreensharing', array(
		'type' => 'integer',
		'length'  => 1,
		'notnull' => true,
		'default' => 1
	));
}
if ( !$ilDB->tableColumnExists('rep_robj_xomv_conn', 'hidewhiteboard') ) {
	$ilDB->addTableColumn('rep_robj_xomv_conn', 'hidewhiteboard', array(
		'type' => 'integer',
		'length'  => 1,
		'notnull' => true,
		'default' => 1
	));
}
?>
<#3>
<?php
if ( !$ilDB->tableColumnExists('rep_robj_xomv_conn', 'svrappname') ) {
	$ilDB->addTableColumn('rep_robj_xomv_conn', 'svrappname', array(
		'type' => 'text',
		'length'  => 32,
		'notnull' => true,
		'default' => 'openmeetings'
	));
}
?>