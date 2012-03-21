<?php
//this file is designed to be used as an include that is part of a loop.
//If a valid match is found, it will give $caller_id a value
//available variables for use are: $thenumber

/* Revision History */
// v0.1.0: Initial Release Version by myitguy
// v0.1.1: Minor bug fix by bushbomb

//configuration / display parameters
//The description cannot contain "a" tags, but can contain limited HTML. Some HTML (like the a tags) will break the UI.
$source_desc = "Look up data in your ConnectWise CRM, local or remote.<br>Fill in the appropriate ConnectWise configuration information to make the connection to the ConnectWise database.";
$source_param = array();
$source_param['DB_Site']['desc'] = 'ConnectWise Site URL not including the initial https://';
$source_param['DB_Site']['type'] = 'text';

$source_param['DB_Company']['desc'] = 'ConnectWise Company ID';
$source_param['DB_Company']['type'] = 'text';

$source_param['DB_User']['desc'] = 'Integration Username to connect to ConnectWise.  This ID should have access to the Company, Contact and Reporting APIs';
$source_param['DB_User']['type'] = 'text';

$source_param['DB_Password']['desc'] = 'Integration Password to connect to ConnectWise';
$source_param['DB_Password']['type'] = 'password';

$source_param['Search_Type']['desc'] = 'The ConnectWise type of entries that should be used to match the number';
$source_param['Search_Type']['type'] = 'select';
$source_param['Search_Type']['option'][1] = 'Companies Only';
$source_param['Search_Type']['option'][2] = 'Contacts Only';
$source_param['Search_Type']['option'][3] = 'Companies --> Contacts';
$source_param['Search_Type']['default'] = 3;
$source_param['Filter_Length']['desc'] = 'The number of rightmost digits to check for a match. Enter false to disable this setting';
$source_param['Filter_Length']['type']='number';
$source_param['Filter_Length']['default']= 10;


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
        print "Searching ConnectWise ... <br>";
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
            print "Searching Companies ... ";
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
//            print $wresult_caller_name;
        }
        else if($debug)
        {
            print "not found<br>\n";
        }
    }






    // Search Contacts

    if($run_param['Search_Type'] == 2 || $run_param['Search_Type'] == 3 && $wresult_caller_name =="")
    {
        if($debug)
        {
            print "Searching Contacts ... ";
        }

    $integration_xml = <<<EOT
            <RunReportQueryAction xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">
            <CompanyName>$integration_company</CompanyName>
            <IntegrationLoginId>$integration_user</IntegrationLoginId>
            <IntegrationPassword>$integration_password</IntegrationPassword>
            <ReportName>ContactCommunication</ReportName>
            <Conditions>Contact_Communication_Desc like "$thenumber"</Conditions>
            <Limit>1</Limit>
            </RunReportQueryAction>
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

        $pattern = '/<Value Name="Contact_RecID" Type="Numeric" IsNullable="false">(.*?)<\/Value>/';
        preg_match($pattern, $value, $match_ContactID);
        if (count($match_ContactID)>0){
            $integration_xml = <<<EOT
            <FindPartnerContactsAction xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xmlns:xsd="http://www.w3.org/2001/XMLSchema">
            <CompanyName>$integration_company</CompanyName>
            <IntegrationLoginId>$integration_user</IntegrationLoginId>
            <IntegrationPassword>$integration_password</IntegrationPassword>
            <Conditions>ContactRecID = $match_ContactID[1]</Conditions>
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
                $wresult_caller_name = $match_FirstName[1]." ".$match_LastName[1];
//                print $wresult_caller_name;
            }
            else if($debug)
            {
                print "not found<br>\n";
            }
        }
    }

    if($debug)
    {
        print "<br>Result: ";
    }


    if(strlen($wresult_caller_name) > 0)
    {
        $caller_id = strip_tags($wresult_caller_name);
    }
    else if($debug)
    {
        print "not found<br>\n";
    }
}
