/*!
 * rffbaddies
 * https://github.com/rpotor/rffbaddies
 */

/*
 * An application to simplify adding those characters, corporations and alliances to the contact list
 * of Red Frog Freight pilots that are allegedly involved in bumping and suicide ganking activities
 * against freighters as determined by Red Frog management.
 * This code uses snippets of code and bucketloads of inspiration from the following sources:
 * 		- the awesome contactjs script located at https://github.com/jimpurbrick/contactjs
 * 		- the code posted by pilot Shegox Gabriel on the EVE forums in post #17 of this topic:
 *		  https://forums.eveonline.com/default.aspx?g=posts&t=463526&find=unread
 *		- the EVE API tutorial here:
 *		  http://www.evepanel.net/blog/eve-online/api/eve-api-tutorial-part-ii-requesting-data-from-the-api-and-save-it.html
 * However since I'm not such a hardcore coder to understand everything, some of the sophistication in 
 * the original sources might have been lost in transition. :-)
 * For that I do apologize and please if you are more knowledgeable than me, feel free to improve on this.
 */

// Configuration parameters
var server = "https://crest.eveonline.com/"; // API server
var redirectUri = "http://www.rpotor.com/EVE/rffbaddies/"; // client uri
var clientId = "2ed723a1060d4e6890f8e81a6263f8db"; // OAuth client id
var csrfTokenName = clientId + "csrftoken";
var token; // OAuth token
var authorizationEndpoint = "https://login.eveonline.com/oauth/authorize/"; // OAuth endpoint
var scopes = "publicData characterContactsRead characterContactsWrite";
var chname;
var keyID;
var vCode;

function uuidGen() {
	return 'xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx'.replace(/[xy]/g, function(c) {
	var r = Math.random()*16|0, v = c == 'x' ? r : (r&0x3|0x8);
	return v.toString(16);
    });
}

// Extract value from oauth formatted hash fragment.
function extractFromHash(name, hash) {
	var match = hash.match(new RegExp(name + "=([^&]+)"));
	return !!match && match[1];
}

function redir() {
	document.getElementById("page-wrap").innerHTML = "Processing... please wait.";
	window.location.assign("http://www.rpotor.com/EVE/rffbaddies/rffbaddies.php?access_token=" + token + "&chname=" + chname + "&keyID=" + keyID + "&vCode=" + vCode);
}

function getbasedata() {
	chname = document.getElementById("characterName").value;
	keyID = document.getElementById("keyID").value;
	vCode = document.getElementById("vCode").value;
	document.getElementById("page-wrap").innerHTML = "Okay thanks. Click on the link below to begin. The whole process should take about 40-50 seconds and you shouldn't close your web browser until the application says it's all done. The changes should take effect in-game immediately after the application is done with its thing, but you might have to open and close your contact list window if you have it currently open in game. In case you click the link below, then go for a coffee, walk the dog, eat your dinner and come back 30 minutes later to see that the application is still working, then it's safe to say something really went wrong and you might have to try again and start over. :-(<br /><br />";
	document.getElementById("page-wrap").innerHTML += '<a href="#" onclick="redir();">Click here</a> to update your contact list with the current Red Frog alliance contacts.<br />';
}

var hash = document.location.hash;
token = extractFromHash("access_token", hash);

if (token) {
	// Note to self: somehow the access token expiration should be handled by the app and it should only ask for a new login when the actual access token is expired or close to expiring. The php script could be used for this, we could check expiry before the curl requests calls. A curl get request could get the job done, we need to send the get request described here: https://eveonline-third-party-documentation.readthedocs.org/en/latest/sso/obtaincharacterid/
	// We can then get the current token expiry from the json that the get request spits back. Then we only need to check it against the current timestamp. It's magic.
	// Update: this is not needed anymore, using multi curl we only use one php script now which abides by the request rate limits of the CREST API, so no way the 20 minutes limit would be exceeded even if we do a thousand requests and populate the user's contact list fully.
	
	// Delete CSRF token cookie.
	$.cookie(csrfTokenName, null);
	
	// Load data
	document.getElementById("page-wrap").innerHTML = "Oookay, the authentication was successful, so here we go!<br /><br />";
	document.getElementById("page-wrap").innerHTML += "Now we need the details of your Red Frog Freight contract alt. It is very important to fill in the form below with the details of a character of yours that is in the Red Frog Freight alliance (membership in any Red Frog alliance corporation is okay). It's also super important to fill in your character name absolutely right.<br />";
	document.getElementById("page-wrap").innerHTML += "We also need an API key which allows access to your in-game contacts. If you don&#39;t have such an API key, you can create one now by <a href='https://community.eveonline.com/support/api-key/CreatePredefined?accessMask=16' target='_blank'>clicking this link</a> (opens on a new browser tab, you can come back after you've done it). We'll wait for you, we won't go anywhere.<br />";
	document.getElementById("page-wrap").innerHTML += "After you filled in the form, please click the Submit button.<br /><br />";
	document.getElementById("page-wrap").innerHTML += '<form id="basedata" name="basedata">';
	document.getElementById("page-wrap").innerHTML += 'Red Frog Contract Alt character name: <input type="text" name="characterName" id="characterName"><br />';
	document.getElementById("page-wrap").innerHTML += 'API key ID: <input type="text" name="keyID" id="keyID"><br />';
	document.getElementById("page-wrap").innerHTML += 'API key vCode: <input type="text" name="vCode" id="vCode"><br />';
	document.getElementById("page-wrap").innerHTML += '<button onclick="getbasedata();">Submit</button>';
	document.getElementById("page-wrap").innerHTML += '</form>';
}
else {

	// Store CSRF token as cookie
	var csrfToken = uuidGen();
	$.cookie(csrfTokenName, csrfToken);

	// No OAuth token, request one from the OAuth authentication endpoint
	var loginlink = authorizationEndpoint + "?response_type=token" + "&client_id=" + clientId + "&scope=" + scopes + "&redirect_uri=" + redirectUri + "&state=" + csrfToken;
	
	document.getElementById("page-wrap").innerHTML = "<center><h1>Welcome Frog!</h1></center><br />";

	document.getElementById("page-wrap").innerHTML += "You can use this website to quickly update your contact list with those alliances, corporations and characters, which we like to keep an eye on. If you don't know what I'm talking about then I really recommend you to go to the Red Frog Manual website and read up on the Contract Procedures - Other Procedures - Scouting section.<br />";

	document.getElementById("page-wrap").innerHTML += "To start updating your contact list, first you need to log-in to your EVE character, whose contact list you'd like to update. To do that, click the button below.<br /><br />";

	document.getElementById("page-wrap").innerHTML += '<center><a href="#" onclick="' + "window.location.assign('" + loginlink + "')" + ';"><img src="EVE_SSO_Login_Buttons_Large_Black.png" /></a></center>';
	
	document.getElementById("page-wrap").innerHTML += "<center><h2>FAQ</h2></center>";

	document.getElementById("page-wrap").innerHTML += "<b>Question:</b>Heeey! You won't fool me! You just want me to log-in to your crappy website with my EVE login details to steal my character!<br />";
	document.getElementById("page-wrap").innerHTML += "<b>Answer:</b>Ummm, no, that's not how I roll. :-) This application is registered with CCP according to their rules which you can read on the developer website: <a href='https://developers.eveonline.com/' target='_blank'>https://developers.eveonline.com/</a> The button above will take you to a legit CCP EVE login website. I promise I can't see your password or anything like that, and I can't steal anything from your account. With this app, I can only access those information that you specifically authorize the app to access. In this case that would be to read and write your contact list. That means in the worst case scenario I could theoretically see and &#34;steal&#34; all your contacts and the standings you set for them. Or I could make you best buddies (Excellent standing) with any alliance in EVE. I promise you I won't do that with this app, and really I'm not that interested in who is included on your contact list, but for full disclosure this is what I could do. If you are uncomfortable with that, you of course have the option of not using this app. So in short the login button above only serves the purpose of logging in, selecting the correct character and giving the app permission to the above mentioned. It is done in a very user friendly way, you just need to click it through, it'll be self explanatory. The authorization you give by the way is only good for 20 minutes, so it's not like you log-in here once and then I could access your contact list forever. Okay, I hope that clears every concern you might have, but in case you'd still like to use the app, have some knowledge of php, javascript and html and you have access to a web server, just can't trust me :-) you can find the <a href='https://github.com/rpotor/rffbaddies' target='_blank'>source code here</a> to do with it whatever you like. :-)<br /><br />";

	document.getElementById("page-wrap").innerHTML += "<b>Question:</b> What if I already have some of the contacts added on my contact list?<br />";
	document.getElementById("page-wrap").innerHTML += "<b>Answer:</b> No problem. The app will just &#34;overwrite&#34; the existing contact with the same contact, so you'll see no change on your contact list regarding that contact. The only thing which could go wrong is that let's say you are best buddies with - okay this is just an example, I got nothing against them or anything - &#34;The Order of Saint James the Divine&#34; alliance and you set them to blue on your contact list. If you run this application it will set them to Bad standing on your contact list, because currently the Red Frog alliance has a Bad standing set towards them. I can see that could be a problem for some, but I think for the majority of RFF pilots it shouldn't be a problem.<br /><br />";

	document.getElementById("page-wrap").innerHTML += "<b>Question:</b> I see hundreds of contacts on the Red Frog alliance contact list in game. That could take a long time to add...<br />";
	document.getElementById("page-wrap").innerHTML += "<b>Answer:</b> You're correct...ish! It will take about 10 seconds for every 100 contacts currently on the list. This also depends on the time of day you run it, the load on the EVE servers at the moment, etc. You shouldn't close your browser window until the application gives you the &#34;All done&#34; message. This could go faster, but the EVE server requires code like this application to slow down a bit so as not to overrun the server with lots of requests.<br /><br />";
	
	document.getElementById("page-wrap").innerHTML += "<b>Question:</b> This is a super-awesome application! Which character should I give lots of ISK to?<br />";
	document.getElementById("page-wrap").innerHTML += "<b>Answer:</b> I'm glad you like it. :-) No need to donate me money as I'd have done this for myself anyway, now I'm just making it available to all the frogs in the pond. If you really, absolutely would like to &#34;thank&#34; me, then you can maybe go to <a href='https://www.youtube.com/watch?v=RrutzRWXkKs' target='_blank'>this Youtube video</a> and like it if you like it. :-) Lindsey Stirling is a world-class dancing hip-hop violinist, okay actually the greatest violinist in human history with a music that sounds like a bunch of rats being strangled. :-) She is doing something right though, because she has a combined view count on her Youtube channel of more than 1 billion and well she is just awesome 'nuff said. :-)";
}