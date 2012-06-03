<?php
/*****************

Unfuddle Submit Ticket Page

Copyright (c) 2008 Ben Strackany

Lets users submit tickets to an Unfuddle project.

Portions from Unfuddle's API code examples page (http://unfuddle.com/docs/api/code_examples)
 
Released under MIT license (i.e. do what you want just keep the copyright)

Copyright (C) 2008 Ben Strackany. 

Permission is hereby granted, free of charge, to any person
obtaining a copy of this software and associated documentation
files (the "Software"), to deal in the Software without
restriction, including without limitation the rights to use,
copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the
Software is furnished to do so, subject to the following
conditions:

The above copyright notice and this permission notice shall be
included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
OTHER DEALINGS IN THE SOFTWARE.



*/

ini_set('display_errors', 1);
ini_set('log_errors', 1);
ini_set('error_log', '/var/log/php-error.log');
error_reporting(E_ALL);
ini_set('error_reporting', E_ERROR ^ E_PARSE);

/*** CHANGE THE BELOW ***/

// your unfuddle domain
define ("UNFUDDLE_DOMAIN", "yourdomain.unfuddle.com");
// your project short name 
define ("UNFUDDLE_PROJECTSHORTNAME", "yourproject");
// your unfuddle log in info username:password					
define ("UNFUDDLE_AUTH", "yourusername:yourpassword");

/*** define some other variables ***/
$project = get_project_details($projectshortname);
$projectid = $project->id;

$project_name = $project->title;

$error = false;
$message = false;
$subject = false;
$priority = 3;
$description = false;
$yourname = $_COOKIE['ticketmakerusername'];

process_page();

?>
<html>
<head>
<title><?=$project_name?> : Submit Ticket</title>
<style>

* { font-family: Arial,Helvetica; }
label,input {
	display: block;
	float: left;
	margin-bottom: 10px;
}

label {
	text-align: right;
	width: 100px;
	padding-right: 20px;
	font-weight:bold; 
    color:#777; 
}
input { border:1px solid #777; width:500px; padding:3px; }
textarea { border:1px solid #777; float:left; width:500px; height:300px; padding:3px; }

br {
	clear: left;
}

input#submit { 
    margin:30px 0px 0px 120px; 
    display:block; 
    border:1px solid #777; 
    padding:8px 16px;
    width:auto; 
    background-color:#eef; 
    font-weight:bold; 
    text-align:center; 
}

h3.error { color:red; text-align:center; font-weight:bold; }
h3.message { color:blue; text-align:center; font-weight:bold; }
form { border:2px solid #ccf; padding:30px; margin:30px auto; width:660px; display:block; }

</style>
</head>
<body>

<h1>Submit Ticket Form</h1>

<p class="info">Please submit a ticket for the <?php echo $project_name ?> project. All fields are required.</p>

<h3 class="error"><?php echo $error; ?></h3>
<h3 class="message"><?php echo $message; ?></h3>
    
<form action="submitticket.php" method="POST">

<label for="subject">Subject</label>
<input name="subject" id="subject" value="<?=$subject?>">
<br />

<label for="yourname">Your Name</label>
<input name="yourname" id="yourname" value="<?=$yourname?>">
<br />

<label for="subject">Priority</label>
<select name="priority" id="priority">
<option value="4" <?php if ($priority=="4") echo " selected ";?>>High</option>
<option value="3" <?php if ($priority=="3") echo " selected ";?>>Normal</option>
<option value="2" <?php if ($priority=="2") echo " selected ";?>>Low</option>
</select>
<br />

<label for="description">Description</label>
<textarea id="description" name="description"><?=$description?></textarea>
<br />

<input type="submit" id="submit" name="submit" value="Submit">

<br style="clear:both;" />

</form>

</body>
</html>

<?php 

/***** functions here ****/

function process_page()
{
    global $error, $message, $subject, $description, $yourname;

    if (!empty($_POST['submit']))
    {
        $subject = $_POST['subject'];
        $yourname = $_POST['yourname'];
        $description = $_POST['description'];
        $priority = $_POST['priority'];
    
        if (empty($subject))
            $error = "Please enter a subject.";
        else if (empty($description))
            $error = "Please enter a description.";
        else if (empty($yourname))
            $error = "Please enter your name.";
        else
        {
        
            $ticket_number = create_ticket($subject, $yourname, $priority, $description);
            
            if (!$ticket_number)
                $error = "Could not create a ticket.";
            else
            {
        		$url = "https://".UNFUDDLE_DOMAIN."/p/" . UNFUDDLE_PROJECTSHORTNAME . "/tickets/show/" . $ticket_number;
        		
                $message = "Ticket #" . $ticket_number . " created! You can <a href=\"" . $url . "\" target=\"_blank\">go here</a> to view the ticket.";
                                
                // remember for next time
                setcookie("ticketmakerusername", $yourname, time()+60*60*24*30);
                
                // clear
                $subject = '';
                $description = '';
                $priority = 3;
                
            }
        
        }
    
    }

}


function create_ticket($subject, $user, $priority, $description)
{
    global $projectid;
    
    $config_userpass = UNFUDDLE_AUTH;    
    $config_address = 'https://' . UNFUDDLE_DOMAIN . '/api/v1/projects/' . $projectid . '/tickets';
    
    $postbody = "<ticket><priority>" . $priority . "</priority><summary>" . XMLStrFormat($subject) . "</summary>" . 
        "<description>From " . XMLStrFormat($user) . "\n\n" . XMLStrFormat($description) . "</description></ticket>"; 

    $config_headers[] = "MIME-Version: 1.0";
    $config_headers[] = 'Accept: application/xml';
    $config_headers[] = 'Content-type: application/xml';
    $config_headers[] = "Content-length: ".strlen($postbody);
    $config_headers[] = "Cache-Control: no-cache";

    $chandle = curl_init();
    
    curl_setopt($chandle, CURLOPT_URL, $config_address);    
    curl_setopt( $chandle, CURLOPT_RETURNTRANSFER, true);
    curl_setopt ($chandle, CURLOPT_HEADER, true);
    curl_setopt($chandle, CURLOPT_USERPWD, $config_userpass);
    curl_setopt($chandle, CURLOPT_CUSTOMREQUEST,'POST');
    curl_setopt ($chandle, CURLOPT_POSTFIELDS, $postbody);
    
    curl_setopt($chandle, CURLOPT_HTTPHEADER, $config_headers);    

    // Do the POST and then close the session
    $response = curl_exec($chandle);
    curl_close($chandle);
    
    //echo $config_address . "<hr>" . $response . "</hr>";
    
    // Get HTTP Status code from the response
    $status_code = array();
    preg_match('/\d\d\d/', $response, $status_code);
    
    // Check for errors
    switch( $status_code[0] ) {
    	case 201:
    		// created successfully
    		break;
    	case 503:
    		die('Your call to Unfuddle failed and returned an HTTP status of 503. That means: Service unavailable. An internal problem prevented us from returning data to you.');
    		break;
    	case 403:
    		die('Your call to Unfuddle failed and returned an HTTP status of 403. That means: Forbidden. You do not have permission to access this resource, or are over your rate limit.');
    		break;
    	case 400:
    		// You may want to fall through here and read the specific XML error
    		die('Your call to Unfuddle failed and returned an HTTP status of 400. That means:  Bad request. The parameters passed to the service did not match as expected. The exact error is returned in the XML response.');
    		break;
    	default:
    		die('Your call to Unfuddle returned an unexpected HTTP status of:' . $status_code[0]);
    }
    
    $return_headers = http_parse_headers($response);
    
    $ticket_api_url = $return_headers['Location'];
    
    $ticket_id = substr($ticket_api_url, strrpos($ticket_api_url, '/')+1);
    
    $ticket = get_ticket($ticket_id);
    
    return $ticket->number;
}

function get_ticket($id)
{
    global $projectid;

    // Edit your values here to match your account settings.
    $config_method = 'GET';
    $config_userpass = UNFUDDLE_AUTH;
    $config_headers[] = 'Accept: application/xml';
    $config_address = 'https://' . UNFUDDLE_DOMAIN . '/api/v1/projects/' . $projectid . '/tickets/';
    $config_datasource = $id . '.xml';
    
    // Here we set up CURL to grab the data from Unfuddle
    $chandle = curl_init();
    curl_setopt($chandle, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($chandle, CURLOPT_URL, $config_address . $config_datasource);
    curl_setopt($chandle, CURLOPT_HTTPHEADER, $config_headers);
    curl_setopt($chandle, CURLOPT_USERPWD, $config_userpass);
    curl_setopt($chandle, CURLOPT_CUSTOMREQUEST, $config_method);
    $output = curl_exec($chandle);
    curl_close($chandle);
    
    $xml = new SimpleXMLElement($output);
    
    return $xml;
}

$asc2uni = Array();
for($i=128;$i<256;$i++){
  $asc2uni[chr($i)] = "&#x".dechex($i).";";    
}

// from http://us2.php.net/manual/en/function.htmlentities.php#78371
function XMLStrFormat($str){
    global $asc2uni;
    $str = str_replace("&", "&amp;", $str); 
    $str = str_replace("<", "&lt;", $str);  
    $str = str_replace(">", "&gt;", $str);  
    $str = str_replace("'", "&apos;", $str);   
    $str = str_replace("\"", "&quot;", $str);  
    $str = str_replace("\r", "", $str);
    // $str = strtr($str,$asc2uni);
    $str = fixoutput($str);
    return $str;
} 

// from: http://php.oregonstate.edu/manual/en/function.strtr.php#64108
function fixoutput($str){
    $good[] = 9;  #tab
    $good[] = 10; #nl
    $good[] = 13; #cr
    for($a=32;$a<127;$a++){
        $good[] = $a;
    }   
    $len = strlen($str);
    for($b=0;$b < $len+1; $b++){
        if(in_array(ord($str[$b]), $good)){
            $newstr .= $str[$b];
        }//fi
    }//rof
    return $newstr;
}

function get_project_details($project_short_name)
{
    // Edit your values here to match your account settings.
    $config_method = 'GET';
    $config_userpass = UNFUDDLE_AUTH;
    $config_headers[] = 'Accept: application/xml';
    $config_address = 'https://' . UNFUDDLE_DOMAIN . '/api/v1/projects/by_short_name/';
    $config_datasource = UNFUDDLE_PROJECTSHORTNAME . '.xml';
    
    // Here we set up CURL to grab the data from Unfuddle
    $chandle = curl_init();
    curl_setopt($chandle, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($chandle, CURLOPT_URL, $config_address . $config_datasource);
    curl_setopt($chandle, CURLOPT_HTTPHEADER, $config_headers);
    curl_setopt($chandle, CURLOPT_USERPWD, $config_userpass);
    curl_setopt($chandle, CURLOPT_CUSTOMREQUEST, $config_method);
    $output = curl_exec($chandle);
    curl_close($chandle);
    
    $xml = new SimpleXMLElement($output);

    if (!$xml)
        return false;
    else
        return $xml;    

}


// from http://php.oregonstate.edu/manual/en/function.http-parse-headers.php#77241
    function http_parse_headers( $header )
    {
        $retVal = array();
        $fields = explode("\r\n", preg_replace('/\x0D\x0A[\x09\x20]+/', ' ', $header));
        foreach( $fields as $field ) {
            if( preg_match('/([^:]+): (.+)/m', $field, $match) ) {
                $match[1] = preg_replace('/(?<=^|[\x09\x20\x2D])./e', 'strtoupper("\0")', strtolower(trim($match[1])));
                if( isset($retVal[$match[1]]) ) {
                    $retVal[$match[1]] = array($retVal[$match[1]], $match[2]);
                } else {
                    $retVal[$match[1]] = trim($match[2]);
                }
            }
        }
        return $retVal;
    }

?>
