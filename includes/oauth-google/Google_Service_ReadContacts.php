<?php
/*
* Copyright 2010 Google Inc.
*
* Licensed under the Apache License, Version 2.0 (the "License" );
* you may not use this file except in compliance with the License.
* You may obtain a copy of the License at
*
* http://www.apache.org/licenses/LICENSE-2.0
*
* Unless required by applicable law or agreed to in writing, software
* distributed under the License is distributed on an "AS IS" BASIS,
* WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
* See the License for the specific language governing permissions and
* limitations under the License.
*/

if (!function_exists('str_contains')) {
        function str_contains($haystack, $needle) {
                return $needle !== '' && mb_strpos($haystack, $needle) !== false;
        }
}

require_once 'Google/Service.php';
require_once 'Google/Service/Resource.php';

#[AllowDynamicProperties]
class Google_Service_ReadContacts {
        // Expected by Superfecta
        const SCOPE_CONTACTS = "https://www.googleapis.com/auth/contacts";
        const SCOPE_CONTACTS_READONLY = "https://www.googleapis.com/auth/contacts.readonly";

        const BASE_URL = "https://people.googleapis.com/v1/people:searchContacts";

        private $query;
        private $gam;
        private $access_token;

        // Behavior flags
        private $stopAfterFirstMatch = false;

        // Debug control
        private $debugEnabled = true;
        private $last_http_code = null;

        public function __construct(GoogleAuthManager $authManager) {
                $this->gam = $authManager;

                try {
                        if (isset($this->gam->debug_level)) {
                                $lvl = intval($this->gam->debug_level);
                                $this->debugEnabled = ($lvl > 0);
                        } elseif (isset($this->gam->debug)) {
                                $this->debugEnabled = ($this->gam->debug === 'on' || $this->gam->debug === 1 || intval($this->gam->debug) > 0);
                        }
                } catch (\Throwable $e) {}

                try {
                        if (isset($this->gam->stop_after_first_match) && $this->gam->stop_after_first_match === 'on') {
                                $this->stopAfterFirstMatch = true;
                        }
                } catch (\Throwable $e) {}
        }

        public function setAccessToken($at) {
                $this->access_token = $at;
        }

        public function getContactsForNumberStarting($query) {
                $appendPhoneTypes = true;
                $useNickNames = true;
                $displayLastNameFirstName = false;

                try { $appendPhoneTypes = ($this->gam->append_phone_types === 'on'); } catch (\Throwable $e) {}
                try { $useNickNames = ($this->gam->use_nicknames === 'on'); } catch (\Throwable $e) {}
                try { $displayLastNameFirstName = ($this->gam->display_lastname_firstname === 'on'); } catch (\Throwable $e) {}

                $this->debugEcho($appendPhoneTypes ? "Appending Phone Types to names.<br>" : "Not Appending Phone Types to names.<br>");
                $this->debugEcho($useNickNames ? "Using Nicknames when available.<br>" : "Not using Nicknames.<br>");
                $this->debugEcho($displayLastNameFirstName ? "Displaying Last Name, First Name format<br>" : "Displaying Default Name format.<br>");

                $this->setAccessToken($this->gam->getAccessToken());

                // 1) E.164 first
                $queryE164 = $this->normalizeToE164($query);
                $this->debugEcho("Normalized query number (E.164): {$queryE164}<br>");
                $result_json = $this->performQueryAndDecode($queryE164);

                // 2) NANP format
                if (!$this->hasResults($result_json)) {
                        $this->debugEcho("No results with E.164, retrying with NANP format<br>");
                        $queryNANP = $this->formatNANP($query);
                        $result_json = $this->performQueryAndDecode($queryNANP);
                }

                // 3) Raw digits
                if (!$this->hasResults($result_json)) {
                        $this->debugEcho("No results with NANP, retrying with raw digits<br>");
                        $queryDigits = preg_replace('/\D+/', '', $query);
                        $result_json = $this->performQueryAndDecode($queryDigits);
                }

                if (isset($result_json->error)) {
                        $this->debugEcho('Error getting result:<br>' . htmlspecialchars(json_encode($result_json->error)) . '<br>');
                        return ['success' => 'no', 'data' => json_encode($result_json->error)];
                }

                $output = [];
                $counter = 0;

                if (isset($result_json->results)) {
                        foreach ($result_json->results as $entry) {
                                try {
                                        $name = "";
                                        if ($useNickNames && isset($entry->person->nicknames)) {
                                                $name = $entry->person->nicknames[0]->value;
                                                $this->debugEcho("<br>Nickname: " . htmlspecialchars($name));
                                        } elseif (isset($entry->person->names)) {
                                                $name = $displayLastNameFirstName ?
                                                        $entry->person->names[0]->displayNameLastFirst :
                                                        $entry->person->names[0]->displayName;
                                                $this->debugEcho("<br>Name: " . htmlspecialchars($name));
                                        } elseif (isset($entry->person->organizations)) {
                                                $name = $entry->person->organizations[0]->name;
                                                $this->debugEcho("<br>Organization: " . htmlspecialchars($name));
                                        }

                                        if (!empty($name) && isset($entry->person->phoneNumbers)) {
                                                foreach ($entry->person->phoneNumbers as $phoneNumber) {
                                                        $nameWithType = $name;
                                                        if ($appendPhoneTypes && isset($phoneNumber->formattedType)) {
                                                                $nameWithType = $name . ' (' . $phoneNumber->formattedType . ')';
                                                        }

                                                        if (isset($phoneNumber->canonicalForm)) {
                                                                $no = $phoneNumber->canonicalForm;
                                                                $this->debugEcho($this->h("$nameWithType, Canonical Form: $no") . "<br>");
                                                                $score = $this->subStringScore($no, $queryE164);
                                                                if ($score > 0) {
                                                                        $output[$counter] = ['name' => $nameWithType, 'number' => $no, 'score' => $score];
                                                                        $counter++;
                                                                        $this->debugEcho($this->h("$counter. $nameWithType, $no, Score:$score") . "<br>");
                                                                        if ($this->stopAfterFirstMatch) {
                                                                                $this->debugEcho("Stopping after first match (flag enabled)<br>");
                                                                                return ['success' => 'yes', 'data' => $output];
                                                                        }
                                                                }
                                                        }

                                                        if (isset($phoneNumber->value)) {
                                                                $noRaw = $this->cleanNumber($phoneNumber->value);
                                                                $this->debugEcho($this->h("$nameWithType, Value: $noRaw") . "<br>");
                                                                $score = $this->subStringScore($noRaw, $queryE164);
                                                                if ($score > 0) {
                                                                        $output[$counter] = ['name' => $nameWithType, 'number' => $noRaw, 'score' => $score];
                                                                        $counter++;
                                                                        $this->debugEcho($this->h("$counter. $nameWithType, $noRaw, Score:$score") . "<br>");
                                                                        if ($this->stopAfterFirstMatch) {
                                                                                $this->debugEcho("Stopping after first match (flag enabled)<br>");
                                                                                return ['success' => 'yes', 'data' => $output];
                                                                        }
                                                                }
                                                        }
                                                }
                                        }
                                } catch (\Throwable $e) {
                                        $this->debugEcho('Message: ' . $this->h($e->getMessage()) . '<br>');
                                }
                        }
                }

                $this->debugEcho('Found ' . $counter . ' matches<br>');
                return ['success' => 'yes', 'data' => $output];
        }

        /* -------------------- Helpers -------------------- */

        private function performQueryAndDecode($queryString) {
                $this->query = $queryString;
                $url = $this->constructFinalUrl();
                $this->debugEcho('URL: ' . $this->h($url) . '<br>');

                $result = $this->curl_file_get_contents($url);

                $this->debugEcho("cURL HTTP Code: " . $this->last_http_code . "<br>");
                $this->debugEcho("Raw API result:<br>" . $this->prettyOrRaw($result) . "<br>");

                $decoded = json_decode($result);
                return $decoded ?: (object)[];
        }

        private function hasResults($result_json) {
                return (isset($result_json->results) && !empty($result_json->results));
        }

        private function normalizeToE164($number) {
                // Always strip all non-digits except leading '+'
                $clean = preg_replace('/[^\d+]/', '', $number);

                if (substr($clean, 0, 1) === '+') {
                        return $clean;
                }
                if (strlen($clean) === 10) {
                        return '+1' . $clean;
                } elseif (strlen($clean) === 11 && substr($clean, 0, 1) === '1') {
                        return '+' . $clean;
                }
                return '+' . $clean;
        }

        private function formatNANP($number) {
                $digits = preg_replace('/\D+/', '', $number);
                if (strlen($digits) == 10) {
                        return '(' . substr($digits, 0, 3) . ') ' . substr($digits, 3, 3) . '-' . substr($digits, 6);
                } elseif (strlen($digits) == 11 && substr($digits, 0, 1) == '1') {
                        return '(' . substr($digits, 1, 3) . ') ' . substr($digits, 4, 3) . '-' . substr($digits, 7);
                }
                return $number;
        }

        private function cleanNumber($number) {
                return preg_replace('/[^0-9+]*/', '', $number);
        }

        private function subStringScore($number, $prefix) {
                if (str_contains($number, $prefix)) return strlen($prefix);
                if (str_contains($prefix, $number)) return strlen($number);
                return 0;
        }

        private function constructFinalUrl() {
                $url  = self::BASE_URL;
                $url .= '?readMask=names,nicknames,organizations,phoneNumbers';
                $url .= '&access_token=' . $this->access_token;
                if (isset($this->query)) $url .= '&query=' . urlencode($this->query);
                return $url;
        }

        private function curl_file_get_contents($url) {
                $curl = curl_init();
                $userAgent = 'Mozilla/5.0 (Superfecta GoogleContacts)';

                curl_setopt($curl, CURLOPT_URL, $url);
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5);
                curl_setopt($curl, CURLOPT_TIMEOUT, 10);
                curl_setopt($curl, CURLOPT_USERAGENT, $userAgent);
                curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($curl, CURLOPT_AUTOREFERER, true);
                curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
                curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
                curl_setopt($curl, CURLOPT_IPRESOLVE, CURL_IPRESOLVE_V4);

                $contents = curl_exec($curl);
                $this->last_http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);

                if ($contents === false) {
                        $err = curl_error($curl);
                        $this->debugEcho('cURL Error: ' . $this->h($err) . '<br>');
                }

                curl_close($curl);
                return $contents ?: '';
        }

        private function prettyOrRaw($result) {
                if (!$this->debugEnabled) return '';
                $decodedAssoc = json_decode($result, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                        $pretty = json_encode($decodedAssoc, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
                        $pretty = preg_replace_callback('/^( +)/m', function($m) {
                                $spaces = strlen($m[1]);
                                $groups = intdiv($spaces, 4);
                                return str_repeat('  ', max(1, $groups));
                        }, $pretty);
                        return '<pre style="white-space:pre-wrap;margin:0;">' . htmlspecialchars($pretty) . '</pre>';
                }
                return '<pre style="white-space:pre-wrap;margin:0;">' . htmlspecialchars($result) . '</pre>';
        }

        private function debugEcho($msg) {
                if ($this->debugEnabled) {
                        echo $msg;
                }
        }

        private function h($s) {
                return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }
}
