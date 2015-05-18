<?php
/*
 * Copyright 2010 Google Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License"); you may not
 * use this file except in compliance with the License. You may obtain a copy of
 * the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS, WITHOUT
 * WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the
 * License for the specific language governing permissions and limitations under
 * the License.
 */

/**
 * Service definition for Admin (email_migration_v2).
 *
 * <p>
 * Email Migration API lets you migrate emails of users to Google backends.
 * </p>
 *
 * <p>
 * For more information about this service, see the API
 * <a href="https://developers.google.com/admin-sdk/email-migration/v2/" target="_blank">Documentation</a>
 * </p>
 *
 * @author Google, Inc.
 */

require_once 'Google/Service.php';
require_once 'Google/Service/Resource.php';

class Google_Service_ReadContacts
{
    const SCOPE_CONTACTS_READONLY = "https://www.googleapis.com/auth/contacts.readonly"; // for readony, v=3 is not needed on the scope, I think!
    const BASE_URL = "https://www.google.com/m8/feeds/contacts";

    private $max_results = '10';
    private $query;

  /**
   * Constructs the internal representation of the Admin service.
   *
   * @param Google_Client $client
   */
    public function __construct(GoogleAuthManager $authManager)
    {
        $this->gam = $authManager;
    }

    public function setAccessToken($at) {
        $this->access_token = $at;
    }

    public function getContactsForNumberEnding($query) {
        $this->setAccessToken($this->gam->getAccessToken());
        $query_number = $this->cleanNumber($query);
        $len_query = strlen($query_number);
        if ($len_query <= 6) $this->query = $query_number;
        else                 $this->query = substr($query_number, $len_query-6, 6);

        $result = $this->curl_file_get_contents($this->constructFinalUrl());

//        echo $result;

        $doc = new DOMDocument;
        $doc->recover = true;
        $doc->loadXML($result);

        // Errors!
        $errors = $doc->getElementsByTagName('error');
        foreach ($errors as $error) {
            return array('success' => 'no', 'data' => 'AUTH NEEDED');
        }

        // Results!
        $result = $doc->getElementsByTagName('entry');
        $counter = 1;
        $output = array();
        foreach ($result as $entry) {
            $name = $entry->getElementsByTagName('title')->item(0)->textContent;
            $phoneNos = $entry->getElementsByTagName('phoneNumber');
            foreach ($phoneNos as $number) {
                $no = $this->cleanNumber($number->textContent);
                $score = $this->endMatchScore($no, $query_number);
                if ($score > 1) {
                    $output[$counter] = $option = array('name' => $name, 'number' => $no, 'score' => $score);
                    $counter++;
                }
            }
        }
        return array('success' => 'yes', 'data' => $output);
    }

    private function cleanNumber($number) {
        $result = preg_replace('/[^0-9+]*/', '', $number);
        return $result;
    }

    private function endMatchScore($number, $end) {
        $result = 0;
        $len_end = strlen($end) - 1;
        $len_no  = strlen($number) - 1;
        while (($len_end >= 0) && ($len_no >= 0)) {
             if ($number{$len_no} == $end{$len_end}) $result++;
             else return $result;
             $len_end--;
             $len_no--;
        }
        return $result;
    }

    private function constructFinalUrl() {
        $result  = Google_Service_ReadContacts::BASE_URL;
        $result .= "/default/full";
        $result .= '?v=3&oauth_token='.$this->access_token;

        if (isset($this->query)) $result .= '&q='.urlencode($this->query);

        return $result;
    }

    private function curl_file_get_contents($url) {
        $curl = curl_init();
        $userAgent = 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; .NET CLR 1.1.4322)';

        curl_setopt($curl, CURLOPT_URL, $url);	//The URL to fetch. This can also be set when initializing a session with curl_init().
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);	//TRUE to return the transfer as a string of the return value of curl_exec() instead of outputting it out directly.
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5);	//The number of seconds to wait while trying to connect.	

        curl_setopt($curl, CURLOPT_USERAGENT, $userAgent);	//The contents of the "User-Agent: " header to be used in a HTTP request.
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, TRUE);	//To follow any "Location: " header that the server sends as part of the HTTP header.
        curl_setopt($curl, CURLOPT_AUTOREFERER, TRUE);	//To automatically set the Referer: field in requests where it follows a Location: redirect.
        curl_setopt($curl, CURLOPT_TIMEOUT, 10);	//The maximum number of seconds to allow cURL functions to execute.
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);	//To stop cURL from verifying the peer's certificate.
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);

        $contents = curl_exec($curl);
        curl_close($curl);
        return $contents;
    }
}
