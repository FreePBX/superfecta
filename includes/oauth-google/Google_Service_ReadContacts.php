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

// based on original work from the PHP Laravel framework
if (!function_exists('str_contains')) {
    function str_contains($haystack, $needle) {
        return $needle !== '' && mb_strpos($haystack, $needle) !== false;
    }
}

require_once 'Google/Service.php';
require_once 'Google/Service/Resource.php';

class Google_Service_ReadContacts
{
    const SCOPE_CONTACTS_READONLY = "https://www.googleapis.com/auth/contacts.readonly";
    const BASE_URL = "https://people.googleapis.com/v1/people:searchContacts";

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

    public function getContactsForNumberStarting($query) {
        $appendPhoneTypes = true;
        $useNickNames = true;
        try{
//            echo '<br><br><br><br>in getContactsForNumberStarting: '.json_encode($this).'<br><br><br>';
            $append_phone_types = $this->gam->append_phone_types;
            $use_nicknames = $this->gam->use_nicknames;

            if ($append_phone_types === 'on'){
              $appendPhoneTypes = true;
            }
            else{
              $appendPhoneTypes = false;
            }
            if ($this->gam->use_nicknames === 'on'){
              $useNickNames = true;
            }
            else{
              $useNickNames = false;
            }
        }
        catch(Exception $e){
          //echo 'Unable to get phone Type Flag: '.$e->getMessage().'<br>';
        }

        if ($appendPhoneTypes){
          echo 'Appending Phone Types to names.<br>';
        }
        else{
          echo 'Not Appending Phone Types to names.<br>';
        };

        if ($useNickNames){
          echo 'Using Nicknames when available.<br>';
        }
        else{
          echo 'Not using Nicknames.<br>';
        };
        $counter = 0;
        $output = array();

        $this->setAccessToken($this->gam->getAccessToken());
        $query_number = $this->cleanNumber($query);
        $len_query = strlen($query_number);
        $this->query = $query_number;

        $result = $this->curl_file_get_contents($this->constructFinalUrl());
        //echo 'result:<br>'.$result.'<br>';
        $result_json = json_decode($result);
        dbug($result_json);

        if( isset( $result_json->error) ){
            echo 'Error getting result:<br>'.$result.'<br>';
            $results = array('success' => 'no', 'data' => json_encode($result_json->error));
            return $results;
        }
        
        try{
          $count=0;
          if( isset( $result_json->results) ){
            foreach ($result_json->results as $entry){
              $count++;
              $name="";
              try{
                if( $useNickNames and isset( $entry->person->nicknames) ){
                  $name = $entry->person->nicknames[0]->value;
                  dbug($name);
                  echo '<br>Nickname: '.$name;
                }elseif( isset( $entry->person->names) ){
                  $name = $entry->person->names[0]->displayName;
                  dbug($name);
                  echo '<br>Name: '.$name;
                }elseif(isset( $entry->person->organizations) ){
                  $name = $entry->person->organizations[0]->name;
                  dbug($name);
                  echo '<br>Organization: '.$name;
                }
                echo '<br>';


                if (!empty($name)){
                  if( isset( $entry->person->phoneNumbers) ){
                    foreach ($entry->person->phoneNumbers as $phoneNumber){
                      $nameWithType = $name;
                      try{
                        if( $appendPhoneTypes and isset( $phoneNumber->formattedType) ){
                          $nameWithType = $name.' ('.$phoneNumber->formattedType.')';
                        }
                      }
                      catch(Exception $e){
                        echo 'Unable to get phone number type for '.$name.': '.$e->getMessage().'<br>';
                      }

                      try{
                        if( isset( $phoneNumber->canonicalForm) ){
                          $no = $phoneNumber->canonicalForm;
                          echo $nameWithType.', Canonical Form: '.$no.'<br>';
                          $score = $this->subStringScore($no, $query_number);
                          if ($score > 0){
                            $output[$counter] = $option = array('name' => $nameWithType, 'number' => $no, 'score' => $score);
                            $counter++;
                            echo $counter.'. '.$nameWithType.', '.$no.', Score:'.$score.'<br>';
                          }
                        }
                      }
                      catch(Exception $e){
                        echo 'Error Parsing Canonical phone number for '.$nameWithType.': ' .$e->getMessage().'<br>';
                      }

                      try{
                        if( isset( $phoneNumber->value) ){
                          $no = $this->cleanNumber($phoneNumber->value);
                          echo $nameWithType.', Value: '.$no.'<br>';
                          $score = $this->subStringScore($no, $query_number);
                          if ($score > 0){
                            $counter++;
                            $output[$counter] = $option = array('name' => $nameWithType, 'number' => $no, 'score' => $score);
                            echo $counter.'. '.$nameWithType.', '.$no.', Score:'.$score.'<br>';
                          }
                        }
                      }
                      catch(Exception $e){
                        echo 'Error Parsing phone number for '.$nameWithType.': ' .$e->getMessage().'<br>';
                      }
                    }
                  }
                }
              }
              catch(Exception $e){
                echo 'Message 2: ' .$e->getMessage().'<br>';
              }
            }
          }
        }
        catch(Exception $e){
          echo 'Message 3: ' .$e->getMessage().'<br>';
        }

        echo 'found '.$counter.' matches<br>';
        $results = array('success' => 'yes', 'data' => $output);
        if (sizeof($results['data']) > 0){
          return $results;
        }

        if (substr($query, 0, 1) == '+'){
          // No matches
          return $results;
        }

        // For US Numbers try to prefix number with 1
        if (substr($query, 0, 1) != '1'){
          $query1 = '1'.$query;
          echo '<br><b>Searching Google Contacts for number with a 1 appended: '.$query1.'</b><br>';
          $results = $this->getContactsForNumberStarting($query1);
          if (sizeof($results['data']) > 0){
            //we have some matches
            return $results;
          }
          // We didn't find a match with a number starting with 1
        }

        // Try to prefix + sign
        $query1 = '+'.$query;
        echo '<br><b>Searching Google Contacts for number with a + sign appended: '.$query1.'</b><br>';
        $results = $this->getContactsForNumberStarting($query1);
        //echo sizeof($results['data']).'<br>';
        if (sizeof($results['data']) > 0){
          //we have some matches
          return $results;
        }
        // No Matches
        return $results;
    }

    private function cleanNumber($number) {
        $result = preg_replace('/[^0-9+]*/', '', $number);
        return $result;
    }

    private function subStringScore($number, $prefix) {
        if (str_contains($number, $prefix)){
          $result = strlen($prefix);
        }
        elseif (str_contains($prefix, $number)){
          $result = strlen($number);
        }
        else{
          $result = 0;
        }
        return $result;
    }

    private function constructFinalUrl() {
        $result  = Google_Service_ReadContacts::BASE_URL;
        $result .= '?readMask=names,nicknames,organizations,phoneNumbers';
        $result .= '&access_token='.$this->access_token;
        if (isset($this->query)) $result .= '&query='.$this->query;
        dbug($result);
        echo 'url: '.$result.'<br>';
        return $result;
    }

    private function curl_file_get_contents($url) {
        $curl = curl_init();
        $userAgent = 'Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1; .NET CLR 1.1.4322)';

        curl_setopt($curl, CURLOPT_URL, $url);  //The URL to fetch. This can also be set when initializing a session with curl_init().
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, TRUE);       //TRUE to return the transfer as a string of the return value of curl_exec() instead of outputting it out directly.
        curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5);  //The number of seconds to wait while trying to connect.

        curl_setopt($curl, CURLOPT_USERAGENT, $userAgent);      //The contents of the "User-Agent: " header to be used in a HTTP request.
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, TRUE);       //To follow any "Location: " header that the server sends as part of the HTTP header.
        curl_setopt($curl, CURLOPT_AUTOREFERER, TRUE);  //To automatically set the Referer: field in requests where it follows a Location: redirect.
        curl_setopt($curl, CURLOPT_TIMEOUT, 10);        //The maximum number of seconds to allow cURL functions to execute.
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);  //To stop cURL from verifying the peer's certificate.
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);

        $contents = curl_exec($curl);
        curl_close($curl);
        return $contents;
    }
}