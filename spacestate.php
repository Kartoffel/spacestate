<?php
/**
 * Space state monitor
 * By Niek Blankers <niek@niekproductions.com>
 * Uses the Space API (http://spaceapi.net/) and Pushover (http://pushover.net/)
 *
 * Set this script up to run every "update_interval" minutes
 */

# Configuration
$CONFIG = array(
        "update_interval" => 2, // Interval in which this script is run (minutes)
        "space_name" => "Bitlair",
        "spaceAPI_directory" => "http://spaceapi.net/directory.json",
        "pushover_userkey" => "xxxxxxxxxxxxxxxxxxxxxxxxxxxxxx", //PushOver.net user/group key
        "pushover_APItoken" => "xxxxxxxxxxxxxxxxxxxxxxxxxxxxxx", //PushOver.net API token

);


$spaceDirectory = json_decode(curl_download($CONFIG['spaceAPI_directory']), true);

$state = json_decode(curl_download($spaceDirectory[$CONFIG['space_name']]), true);

if(!is_null($state)){
        if(time() - $state['state']['lastchange'] < $CONFIG['update_interval']*60-1){ // Did the space state just change?
                if($state['open']){
                        $opener = (string)  $state['sensors']['people_now_present'][0]['names'][0];
                        push_notification($state['space']." is open! User ".$opener." joined.", $state['state']['lastchange'], 1);
                }else{
                         push_notification($state['space']." was closed.", $state['state']['lastchange'], 1);
                }
        }

}

/**
 * Uses curl to download a page
 *
 * $param  String  $url  What to download
 * @return Mixed   result
 */
function curl_download($url){
        $ch = curl_init();
        $timeout = 30;
        curl_setopt ($ch, CURLOPT_URL, $url);
        curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt ($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        $return = curl_exec($ch);
        curl_close($ch);
        return $return;
}

/**
 * Sends a push notification through the Pushover(https://pushover.net/) service if something goes wrong.
 *
 * @param  String   $message  What to send
 * @param  integer  $priority send as -2 to generate no notification/alert, -1 to always send as a quiet notificat$
 * @return boolean  true if push message was sent successfully
 */
function push_notification($message, $timestamp, $priority){
        global $CONFIG;
        if(!is_null($CONFIG['pushover_APItoken']) && !is_null($CONFIG['pushover_userkey'])){
                try {
                        curl_setopt_array($ch = curl_init(), array(
                        CURLOPT_URL => "https://api.pushover.net/1/messages.json",
                        CURLOPT_POSTFIELDS => array(
                                "token" => $CONFIG['pushover_APItoken'],
                                "user" => $CONFIG['pushover_userkey'],
                                "message" => $message,
                                "timestamp" => $timestamp,
                                "priority" => $priority,
                        )));
                        $result = curl_exec($ch);
                        curl_close($ch);
                        if (FALSE === $result)
                                throw new Exception(curl_error($ch), curl_errno($ch));
                } catch(Exception $e) {
                        error(sprintf(
                                'Curl failed with error #%d: %s',
                                $e->getCode(), $e->getMessage()));
                        return false;
                }
                return true;
        }
}

?>

