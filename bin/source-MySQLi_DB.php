<?php
//this file is designed to be used as an include that is part of a loop.
//If a valid match is found, it will give $caller_id a value
//available variables for use are: $thenumber
//retreive website contents using get_url_contents($url);
//created by candrews 25 Feb, 2011

//configuration / display parameters
//The description cannot contain "a" tags, but can contain limited HTML. Some HTML (like the a tags) will break the UI.
$source_desc = "Query a local or remote MySQL database using PHP MySQLi.";
$source_param = array();
$source_param['DB_Host']['desc'] = 'Host address of the database. (localhost if the database is on the same server as FreePBX)';
$source_param['DB_Host']['type'] = 'text';
$source_param['DB_Name']['desc'] = 'schema name of the database';
$source_param['DB_Name']['type'] = 'text';
$source_param['DB_User']['desc'] = 'Username used to connect to the database';
$source_param['DB_User']['type'] = 'text';
$source_param['DB_Password']['desc'] = 'Password used to connect to the database';
$source_param['DB_Password']['type'] = 'password';
$source_param['SQL_Query']['desc'] = 'SQL Query used to retrieve the Dialer Name. select result from table where telfield=?. ? will be replaced by the called number';
$source_param['SQL_Query']['type'] = 'text';


//run this if the script is running in the "get caller id" usage mode.
if($usage_mode == 'get caller id')
{
	$wresult_caller_name = "";
	if($debug)
	{
		print 'Searching MySQL Database... ';
	}
	
	$link = mysqli_connect($run_param['DB_Host'], $run_param['DB_User'], $run_param['DB_Password'], $run_param['DB_Name']);
	if(mysqli_connect_error()){
        print 'Connect Error (' . mysqli_connect_errno() . ') '. mysqli_connect_error();
	}else{
	    if($stmt = mysqli_prepare($link, $run_param['SQL_Query'])){
	        mysqli_stmt_bind_param($stmt, 's', $thenumber);
	        mysqli_stmt_execute($stmt);
	        mysqli_stmt_bind_result($stmt, $caller_id);
	        if(mysqli_stmt_fetch($stmt)){
	            if($debug) print 'Result found';
	        }else{
	            if($debug) print 'No result found';
	        }
	        mysqli_stmt_close($stmt);
	        mysqli_close($link);
        }else{
            if($debug) print 'Failed to prepare statement: ' . $run_param['SQL_Query'];
        }
	}
}
