<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"/>
    <link rel="stylesheet" href="http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.10/themes/ui-darkness/jquery-ui.css"/>
	<link rel="stylesheet" href="rffbaddies.css"/>
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.5.1/jquery.min.js"></script>
    <script src="https://ajax.googleapis.com/ajax/libs/jqueryui/1.8.18/jquery-ui.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-cookie/1.4.1/jquery.cookie.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/handlebars.js/1.0.0.beta6/handlebars.min.js"></script>
    <title>The Awesome RFF baddy list updater</title>
	
</head>
<body>
<img src="bg.jpg" class="bg">
<div id="page-wrap">Processing... please wait.</div>
<?php
$auth_code = $_GET["access_token"];
$chname = trim($_GET["chname"]);
$keyID = trim($_GET["keyID"]);
$vCode = trim($_GET["vCode"]);

function clearwrap() {
	echo "<script type=\"text/javascript\">";
	echo 'document.getElementById("page-wrap").innerHTML = "";';
	echo "</script>";
}
function prettify($uzi) {
	echo "<script type=\"text/javascript\">";
	echo "document.getElementById('page-wrap').innerHTML += '".$uzi."';";
	echo "</script>";
}

function makeApiRequest($url) {

    // Initialize a new request for this URL
    $ch = curl_init($url);

    // Set the options for this request
    curl_setopt_array($ch, array(
        CURLOPT_FOLLOWLOCATION => true, // Yes, we want to follow a redirect
        CURLOPT_RETURNTRANSFER => true, // Yes, we want that curl_exec returns the fetched data
        CURLOPT_SSL_VERIFYPEER => false, // Do not verify the SSL certificate
    ));

    // Fetch the data from the URL
    $data = curl_exec($ch);

    // Close the connection
    curl_close($ch);

    // Return a new SimpleXMLElement based upon the received data
    return new SimpleXMLElement($data);
}

function getContactType($kt) {
	if ($kt == '2') {
		return('https://public-crest.eveonline.com/corporations/');
	} elseif ($kt == '16159') {
		return ('https://public-crest.eveonline.com/alliances/');
	} else {
		return ('https://public-crest.eveonline.com/characters/');
	}
}

function curl_post ($kontaktlink, $auth_code, $batch)
{
$mh = curl_multi_init();
$handles = array();
$headers = array(
"Authorization: Bearer ".$auth_code,
"Content-Type: application/json",
);
foreach ($batch as $baddy) {
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $kontaktlink);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($ch, CURLOPT_POST, 1);
	$post["standing"] = $baddy[0];
	$post["contact"] = array();
	$post["contact"]["href"] = $baddy[1];
/*
The only required part of a contact create call is contact - href, so we set only that.
Reference here: http://jimpurbrick.com/crestmatic/ContactCreate-v1.html
I don't really know what would happen if say for a given valid href we would set
an "invalid" name for example, so in effect we would try "renaming" a contact. I guess
nothing would happen, no contact would be written to the contact list or maybe
the universe would just spontaneously explode. Anyway, let's just stick to only setting
the href for now, it works that way, so let's not try and fix it. :-)
*/
	$post = json_encode($post,JSON_UNESCAPED_UNICODE);
	curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
	curl_multi_add_handle($mh,$ch);
	$handles[] = $ch;
	unset($post);
	}
$active = null;
do {
    $mrc = curl_multi_exec($mh, $active);
} while ($mrc == CURLM_CALL_MULTI_PERFORM);

while ($active && $mrc == CURLM_OK) {
    if (curl_multi_select($mh) != -1) {
        do {
            $mrc = curl_multi_exec($mh, $active);
        } while ($mrc == CURLM_CALL_MULTI_PERFORM);
    }
}

for($i=0; $i<count($handles); $i++) {
	curl_multi_remove_handle($mh,$handles[$i]);
}

curl_multi_close($mh);
}

function curl_get($url,$auth_code,$headers)
{
 $ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
// Set so curl_exec returns the result instead of outputting it.
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
// Does not verify peer
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
// Get the response and close the channel.
// auth header
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
$response = curl_exec($ch);
curl_close($ch);
$response = json_decode($response);
return $response;
}

//Get the characterID of the RFF CA
$url = 'https://api.eveonline.com/eve/CharacterID.xml.aspx';
$url .= '?names=' . urlencode($chname);

$xml = makeApiRequest($url);

if ($xml->error) {
    $msg = (string) $xml->error;
	$uzi = "Whoops, there seems to be an error. :-( The error message I got is this:<br />";
	$uzi = $uzi . $msg;
	$uzi = $uzi . "<br />Click the link below to go back and try again.<br />";
	$uzi = $uzi . "<br />Make sure the form is filled exactly as requested. Character name especially, but also double check if you correctly copy - pasted the API key elements and nothing is missing from them.<br />";
	$uzi = $uzi . '<a href="index.html">Take me back to the start.</a>';
	clearwrap();
	prettify($uzi);
	exit;
} elseif ($xml->result->rowset->row[0]) {
    $karID = (int) $xml->result->rowset->row[0]->attributes()->characterID;
	if ($karID == 0) {
		$uzi = "Whoops, I can&#39;t find your Red Frog Contract Alt by the name you&#39;ve given anywhere in the EVE universe. :-(";
		$uzi = $uzi . "<br />Click the link below to go back and try again.<br />";
		$uzi = $uzi . "<br />Make sure the form is filled exactly as requested. It might help if you copy paste your character name into the relevant form field, maybe by copying it from in-game directly. Also make sure your API key can access your Red Frog contract alt and the needed permission is set. Maybe try making a completely new API key by following the link given where you fill in the form.<br />";
		$uzi = $uzi . '<a href="index.html">Take me back to the start.</a>';
		clearwrap();
		prettify($uzi);
		exit;
	}
} else {
    $uzi = "Whoops, I can&#39;t find your Red Frog Contract Alt by the name you&#39;ve given anywhere in the EVE universe. :-(";
	$uzi = $uzi . "<br />Click the link below to go back and try again.<br />";
	$uzi = $uzi . "<br />Make sure the form is filled exactly as requested. It might help if you copy paste your character name into the relevant form field, maybe by copying it from in-game directly. Also make sure your API key can access your Red Frog contract alt and the needed permission is set. Maybe try making a completely new API key by following the link given where you fill in the form.<br />";
	$uzi = $uzi . '<a href="index.html">Take me back to the start.</a>';
	clearwrap();
	prettify($uzi);
	exit;
}

$headers = array(
"User-Agent: rffbaddies application (rpotor@gmail.com)",
"Authorization: Bearer ".$auth_code
);

//Get the contact list resource href of the auth'd character
$result = curl_get("https://crest-tq.eveonline.com/decode/",$auth_code,$headers);
$kontaktlink = $result->character->href."contacts/";

// Leaving this code here, it can be used to get the token expiration if we need it
// in the future for some reason. Document here:
// https://eveonline-third-party-documentation.readthedocs.org/en/latest/sso/obtaincharacterid/

/*
$headers = array(
"User-Agent: rffbaddies application (rpotor@gmail.com)",
"Authorization: Bearer ".$auth_code,
"Host: login.eveonline.com"
);

$result = curl_get("https://login.eveonline.com/oauth/verify",$auth_code,$headers);
$tokenexp = $result->ExpiresOn;
*/

//We don't need to read the contact list, so commenting out this function call for now
//but leaving it in place, because this is good stuff and can be used later if needed.
//curl_get($kontaktlink,$auth_code,$headers);

$url = 'https://api.eveonline.com/char/ContactList.xml.aspx';
$url .= '?keyID=' . $keyID;
$url .= '&vCode=' . $vCode;
$url .= '&characterID=' . $karID;

$xml = makeApiRequest($url);
if ($xml->error) {
    $msg = (string) $xml->error;
	$uzi = "Whoops, there seems to be an error. :-( The error message I got is this:<br />";
	$uzi = $uzi . $msg;
	$uzi = $uzi . "<br />Click the link below to go back and try again.<br />";
	$uzi = $uzi . "<br />Make sure the form is filled exactly as requested. Character name especially, but also double check if you correctly copy - pasted the API key elements and nothing is missing from them.<br />";
	$uzi = $uzi . '<a href="index.html">Take me back to the start.</a>';
	clearwrap();
	prettify($uzi);
	exit;
} elseif ($xml->result->rowset[4]) {
    $allicontpc = $xml->result->rowset[4]->count();
	if ($allicontpc == 0) {
	$uzi = "Whoops, seems like you have no alliance contacts. In that case I got no soup for your. :-(<br />";
	$uzi = $uzi . "<br />Click the link below to go back and try again.<br />";
	$uzi = $uzi. "<br />Make sure the character name you use on the form is the name of your Red Frog contract alt. If you accidentally give there the name of a character who is not in any alliance (or in an alliance with no alliance contacts set), then this application won&#39;t work, because then there are no alliance contacts at all to copy.<br />";
	$uzi = $uzi . '<a href="index.html">Take me back to the start.</a>';
	clearwrap();
	prettify($uzi);
	exit;
	}
} else {
    $uzi = "Whoops, seems like you have no alliance contacts. In that case I got no soup for your. :-(<br />";
	$uzi = $uzi . "<br />Click the link below to go back and try again.<br />";
	$uzi = $uzi. "<br />Make sure the character name you use on the form is the name of your Red Frog contract alt. If you accidentally give there the name of a character who is not in any alliance (or in an alliance with no alliance contacts set), then this application won&#39;t work, because then there are no alliance contacts at all to copy.<br />";
	$uzi = $uzi . '<a href="index.html">Take me back to the start.</a>';
	clearwrap();
	prettify($uzi);
	exit;
}

if (isset($allicontpc)) {
	foreach ($xml->result->rowset[4]->row as $row) {
		$kt = getContactType($row{'contactTypeID'});
		$baddies[] = array((int)$row{'standing'},$kt . $row{'contactID'} . "/");
	}
}

//Below code is not needed but leaving here, because that's how we can
//output lots stuff to the prettified div element.
/*if ($baddies) {
	echo "<script type=\"text/javascript\">";
	echo 'document.getElementById("page-wrap").innerHTML = "";';
	foreach ($baddies as $baddy) {
		echo "document.getElementById('page-wrap').innerHTML += '".$baddy[0]." ".$baddy[1]."<br />"."';";
	}
	echo "</script>";
}*/

$numbads = count($baddies);
$maradek = fmod($numbads, 20);
$hanykor = floor($numbads / 20);

for($i=0; $i<$hanykor; $i++) {
	usleep(1000000);
	unset($batch);
	$batch = array_slice($baddies, $i * 20, 20);
	curl_post($kontaktlink, $auth_code, $batch);
	}

unset($batch);
$batch = array_slice($baddies, $hanykor * 20, $maradek);
curl_post($kontaktlink, $auth_code, $batch);

$uzi = "Okay, everything is done, your contact list is now synchronized and up to date with the Red Frog alliance contact list.";
$uzi = $uzi . "<br />If you want to copy the alliance contact list to another character, then click the link below to go back to the beginning. Otherwise you can close this browser window. See you at the next update. Fly safe! :-)<br />";
$uzi = $uzi . '<a href="index.html">Take me back to the start.</a>';
clearwrap();
prettify($uzi);
?>
</body>
</html>