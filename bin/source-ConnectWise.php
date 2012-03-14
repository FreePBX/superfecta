<?php
//this file is designed to be used as an include that is part of a loop.
//If a valid match is found, it should give $caller_id a value
//available variables for use are: $thenumber
//retreive website contents using get_url_contents($url);

/* BUG FIXES */
// v0.1.0: Initial Release Version by myitguy
// v0.2.0: Minor bug fixes by bushbomb


//configuration / display parameters
//The description cannot contain "a" tags, but can contain limited HTML. Some HTML (like the a tags) will break the UI.
$source_desc = "Look up data in your ConnectWise CRM, local or remote.<br>Fill in the appropriate ConnectWise configuration information to make the connection to the ConnectWise database.";
$source_param = array();
$source_param['DB_Site']['desc'] = 'ConnectWise Site URL';
$source_param['DB_Site']['type'] = 'text';

$source_param['DB_Company']['desc'] = 'ConnectWise Company ID';
$source_param['DB_Company']['type'] = 'text';

$source_param['DB_User']['desc'] = 'Integration Username to connect to ConnectWise';
$source_param['DB_User']['type'] = 'text';

$source_param['DB_Password']['desc'] = 'Integration Password to connect to ConnectWise';
$source_param['DB_Password']['type'] = 'password';

$source_param['Search_Type']['desc'] = 'The ConnectWise type of entries that should be used to match the number';
$source_param['Search_Type']['type'] = 'select';
$source_param['Search_Type']['option'][1] = 'Companies Only';
$source_param['Search_Type']['option'][2] = 'Contacts Only';
$source_param['Search_Type']['option'][3] = 'Companies --> Contacts';
$source_param['Search_Type']['default'] = 1;
$source_param['Filter_Length']['desc']='The number of rightmost digits to check for a match';
$source_param['Filter_Length']['type']='number';
$source_param['Filter_Length']['default']= 9;



//run this if the script is running in the "get caller id" usage mode.
if($usage_mode == 'get caller id')
{
$integration_site = $run_param['DB_Site'];
$integration_company = $run_param['DB_Company'];
$integration_user = $run_param['DB_User'];
$integration_password = $run_param['DB_Password'];
$varSearchType = $run_param['Search_Type'];

    if($debug)
    {
        print "<br>\nSearching ConnectWise ... <br>\n";
//        print "Site: " . $integration_site . "<br>\n";
//        print "Company: " . $integration_company. "<br>\n";
//        print "User: " . $integration_user. "<br>\n";
//        print "Password: " . $integration_password. "<br>\n";
//        print "SearchType: " . $varSearchType. "<br>\n";
    }

    $wquery_input = "";
    $wquery_string = "";
    $wquery_result = "";
    $wresult_caller_name = "";

    if ($run_param['Filter_Length'] != false)
        {
            if (strlen($wquery_input) > $run_param['Filter_Length']) $wquery_input = substr($wquery_input, -$run_param['Filter_Length']); // keep only the filter_length rightmost digits
        }

    // Search Companies
    if($run_param['Search_Type'] == 1 || $run_param['Search_Type'] == 3)
    {
        if($debug)
        {
            print "Searching Companies ... <br>\n";
        }
        $integration_xml = <<<EOT
            <FindPartnerCompaniesAction xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">
            <CompanyName>$integration_company</CompanyName>
            <IntegrationLoginId>$integration_user</IntegrationLoginId>
            <IntegrationPassword>$integration_password</IntegrationPassword>
            <Conditions>PhoneNumber like "$thenumber"</Conditions>
            <OrderBy>CompanyID</OrderBy>
            <Limit>1</Limit>
            </FindPartnerCompaniesAction>
EOT;

        // Order of replacement
        $order  = array("\r\n", "\n", "\r");
        $replace = '';

        // Processes \r\n's first so they aren't converted twice.
        $integration_xml = str_replace($order, $replace, $integration_xml);

        $url="https://$integration_site/v4_6_release/services/system_io/integration_io.asmx/ProcessClientAction?actionString=".urlencode($integration_xml);
        if($debug)

        //$value = get_url_contents($url);
        $ch = curl_init();
        $timeout = 5; // set to zero for no timeout
        curl_setopt ($ch, CURLOPT_URL, $url);
        curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        $value = htmlspecialchars_decode(curl_exec($ch));
        curl_close($ch);

        $pattern = "/<CompanyName>(.*?)<\/CompanyName>/";
        preg_match_all($pattern, $value, $matches);
        if (count($matches[0])>1) {
            $wresult_caller_name = $matches[0][1];
        }
    }

    // Search Contacts

    if($run_param['Search_Type'] == 2 || $run_param['Search_Type'] == 3 && $wresult_caller_name =="")
    {
        if($debug)
        {
            print "Searching Contacts ... <br>\n";
        }
        $integration_xml = <<<EOT
            <FindPartnerContactsAction xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">
            <CompanyName>$integration_company</CompanyName>
            <IntegrationLoginId>$integration_user</IntegrationLoginId>
            <IntegrationPassword>$integration_password</IntegrationPassword>
            <Conditions>Phone like "$thenumber"</Conditions>
            <OrderBy>LastName</OrderBy>
            <Limit>10</Limit>
            </FindPartnerContactsAction>
EOT;

        // Order of replacement
        $order  = array("\r\n", "\n", "\r");
        $replace = '';

        // Processes \r\n's first so they aren't converted twice.
        $integration_xml = str_replace($order, $replace, $integration_xml);

        $url="https://$integration_site/v4_6_release/services/system_io/integration_io.asmx/ProcessClientAction?actionString=".urlencode($integration_xml);
        //$value = get_url_contents($url);
        $ch = curl_init();
        $timeout = 5; // set to zero for no timeout
        curl_setopt ($ch, CURLOPT_URL, $url);
        curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        $value = htmlspecialchars_decode(curl_exec($ch));
        curl_close($ch);

        $pattern = "/<LastName>(.*?)<\/LastName>/";
        preg_match($pattern, $value, $match_LastName);

        $pattern = "/<FirstName>(.*?)<\/FirstName>/";
        preg_match($pattern, $value, $match_FirstName);
        if (count($match_LastName)>0 && count($match_FirstName)>0) {
            $wresult_caller_name = $match_FirstName[0]." ".$match_LastName[0];
        }
    }

    if(strlen($wresult_caller_name) > 0)
    {
        $caller_id = $wresult_caller_name;
    }
    else if($debug)
    {
        print "Number not found in ConnectWise<br>\n";
    }
}
