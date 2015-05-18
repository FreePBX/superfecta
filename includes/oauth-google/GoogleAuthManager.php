<?php
require_once 'Google/Client.php';

class GoogleAuthManager
{
    var $dataDir = '/tmp';
    var $redirect_uri = 'urn:ietf:wg:oauth:2.0:oob';
//    var $redirect_uri = 'urn:ietf:wg:oauth:2.0:oob:auto'; // Requires ability to read title from on a web page served from google
//    var $redirect_uri = 'localhost'; // Requires ability to receive a return message

    var $users_id;
    var $client;
    var $client_id;
    var $client_secret;
    var $scope;
    var $access_token_json;
    var $access_token_only;

    var $needCode = true;

    public function __construct() {
        $this->client = new Google_Client();
    }

    public function configure($params) {
        if ((!isset($this->client_id))     && (!isset($params['client_id']))) return null;
        if ((!isset($this->client_secret)) && (!isset($params['client_secret']))) return null;
        if ((!isset($this->users_id))      && (!isset($params['user_id']))) return null;
        if ((!isset($this->scope))         && (!isset($params['scope']))) return null;

        if (isset($params['client_id']))     $this->client_id     = $params['client_id'];
        if (isset($params['client_secret'])) $this->client_secret = $params['client_secret'];
        if (isset($params['user_id']))       $this->users_id      = $params['user_id'];
        if (isset($params['scope']))         $this->scope         = $params['scope'];

        $this->client->setClientId($this->client_id);
        $this->client->setClientSecret($this->client_secret);
        $this->client->setRedirectUri($this->redirect_uri);
//        $this->client->setLoginHint($user_google_id);
        $this->client->setAccessType("offline"); // So we can keep going when the user is not at a browser
        $this->client->addScope($this->scope);

        if (!isset($params['code']) && !isset($params['access_token_json'])) {
             if ($this->accessTokenIsSet()) return $this->getAccessTokenJson();
             return null;
        }

        if (isset($params['code'])) {
            $this->authenticateFromCode($params['code']);
        }
	else if (isset($params['access_token_json'])) {
            $this->useGivenJsonAccessToken($params['access_token_json']);
        }

        return $this->RetrieveToken();
    }

    public function needAuthentication() {
        return $this->needCode;
    }

    public function codeGettingUrl() {
        return $this->client->createAuthUrl();
    }

    public function authRedirect() {
        header("Location: ".$this->client->createAuthUrl());
    }

    public function getAuthIframe() {
       $result  = $this->getJavascript();
       $result .= '<a href="javascript:void(0);" onclick="gcg();">Click to start Google Process</a>';
        return $result;
    }

    public function getAccessToken() {
        if (!isset($this->access_token_only)) return NULL;
        return $this->access_token_only;
    }

    private function authenticateFromCode($new_code) {
        $this->new_code = $new_code;
    }

    private function useGivenJsonAccessToken($at) {
        $this->setAccessTokenJson($at);
        $this->client->SetAccessToken($at);
    }

    private function RetrieveToken() {
        // If not set, we need a code and from it get the access token
        if (!$this->accessTokenIsSet() || isset($this->new_code)) {
            if ($this->authenticateGoogleCode($this->new_code)) {
                $this->setAccessTokenJson($this->client->getAccessToken());
            }
            unset($this->new_code);
        }

        // If we have access token, is it still valid?
        if ($this->accessTokenIsExpired()) {
            $this->refreshAccessToken();
        }

        if ($this->accessTokenIsSet()) {
            $this->client->setAccessToken($this->getAccessTokenJson());
            $this->needCode = false;
        }

        return $this->getAccessTokenJson();
    }

    private function refreshAccessToken() {
        $rt = $this->client->getRefreshToken();
        if ($rt != NULL) {
            $this->client->refreshToken($rt);
            $new_token = $this->client->getAccessToken();
            $this->setAccessTokenJson($new_token);
        }
    }

    private function accessTokenIsSet() {
        if (isset($this->access_token_json)) return true;
        return false;
    }

    private function accessTokenIsExpired() {
        // IF there is no access token, by definition it's expried!
        if (!$this->accessTokenIsSet()) return true;
        // If it has less than 30 seconds to run (or none at all) it's expired.
        $atinfo = json_decode($this->access_token_json, true);
        if (($atinfo['created'] + $atinfo['expires_in']) < (time() - 30)) return true;
        return false;
    }

    private function getAccessTokenJson() {
        return $this->access_token_json;
    }

    private function setAccessTokenJson($new_token) {
        if ($new_token == NULL) return;
        if (strlen($new_token) < 5) return;
        $this->access_token_json = $new_token;
        $data = json_decode($new_token, true);
        $this->access_token_only = $data['access_token'];
    }

    private function resetAccessToken() {
        unset($this->access_token_json);
        unset($this->access_token_only);
    }

    private function authenticateGoogleCode($new_code) {
        try {
            $this->client->authenticate($new_code);
        } catch (Exception $e) {
            return false;
        }
        return true;
    }

    private function getJavascript() {
        echo <<< EOT
<h3>Google Authorisation necessary</h3>
To do that, click on the link below. A new page will open. When you have given your consent,
copy the code you are given into this box:
EOT;
        echo '<form method="GET" action="'.$_SERVER['PHP_SELF'].'"><input type="text" id="cert" name="cert"><p>';
echo <<< EOT
Then, click this button:<p>
<input type="submit" value="Submit google code entered in the box above">
</form>
EOT;
        echo "<script>\n";
        echo "var u='".$_SERVER['PHP_SELF']."?r=x';\n";
echo <<< EOT
var codeWindow = null;
var codeInput = document.getElementById('cert');
function gcg() {
    codeWindow = window.open(u);
    return false;
    }
</script>
EOT;
    }
}
