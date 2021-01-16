<?php

// This file gets the following static information
// - Sub-group
// - Type

$subgroup_json = "subgroup.json";
$type_json     = "type.json";

$dmhy_url      = 'https://share.dmhy.org/topics/advanced-search?team_id=0&sort_id=0&orderby=';
$pattern       = '/\<option\s+value=\"(\d+)\".*?\>(.*?)\<\/option\>/';

if(file_exists($subgroup_json) == false ||
   file_exists($type_json)     == false ||
   filemtime($subgroup_json) <= strtotime("-1 day") ||
   filemtime($type_json)     <= strtotime("-1 day")) {

  //Create a cURL handle.
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $dmhy_url);
  curl_setopt($ch, CURLOPT_TIMEOUT, 5);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_HEADER, false);
//Execute the request.
  $raw_data = curl_exec($ch);

  //If there was an error, throw an Exception
  if(curl_errno($ch)){
    http_response_code(500);
  }

  //Get the HTTP status code.
  $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

  //Close the cURL handler.
  curl_close($ch);

  if($statusCode != 200){
    http_response_code(500);
  }


  $raw_lines = preg_split("/((\r?\n)|(\r\n?))/", $raw_data);

  // First line is 'subgroup'
  $raw_subgroup = $raw_lines[1];
  $raw_type     = $raw_lines[0];

  preg_match_all($pattern, $raw_subgroup, $subgroups);
  preg_match_all($pattern, $raw_type,     $types);

  $fp = fopen($subgroup_json, "w") or die("Cannot open ${subgroup_json} to write!");
  fwrite($fp, "{\n\t\"Subgroups\": {\n");
  for($i = 1; $i < count($subgroups[0]); $i++) {
    fwrite($fp, sprintf("\t\t{ \"Id\": %d, \"Name\": %s }, \n", $subgroups[1][$i], $subgroups[2][$i]));
  }
  fwrite($fp, "\t}\n}\n");
  fclose($fp);

  $fp = fopen($type_json, "w") or die("Cannot open ${type_json} to write!");
  fwrite($fp, "{\n\t\"Types\": {\n");
  for($i = 1; $i < count($types[0]); $i++) {
    fwrite($fp, sprintf("\t\t{ \"Id\": %d, \"Name\": %s }, \n", $types[1][$i], $types[2][$i]));
  }
  fwrite($fp, "\t}\n}\n");
  fclose($fp);
}


if(isset($_REQUEST["target"])) {

  switch($_REQUEST["target"]) {
    case "subgroup": echo file_get_contents($subgroup_json); break;
    case "type"    : echo file_get_contents($type_json);     break;
    default: http_response_code(401);
  }
} else {
  http_response_code(400);
}

?>
