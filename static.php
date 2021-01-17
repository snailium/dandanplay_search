<?php

// This file gets the following static information
// - Sub-group
// - Type

$subgroup_json = "subgroup.json";
$type_json     = "type.json";

$dmhy_url      = 'https://share.dmhy.org/topics/advanced-search?team_id=0&sort_id=0&orderby=';

if(file_exists($subgroup_json) == false ||
   file_exists($type_json)     == false ||
   filemtime($subgroup_json) <= strtotime("-1 day") ||
   filemtime($type_json)     <= strtotime("-1 day")) {

  // Get web page using cURL
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $dmhy_url);
  curl_setopt($ch, CURLOPT_TIMEOUT, 5);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_HEADER, false);
  $raw_data = curl_exec($ch);

  // Handle cURL exceptions
  if(curl_errno($ch)){
    http_response_code(500);
  }
  $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
  if($statusCode != 200){
    http_response_code(500);
  }

  // Done cURL
  curl_close($ch);

  // Parse web page
  $dom = new DOMDocument();
  @$dom->loadHTML('<?xml encoding="UTF-8">' . $raw_data); // Force UTF-u encoding
  $raw_subgroup = $dom->getElementById('AdvSearchTeam');
  $raw_type     = $dom->getElementById('AdvSearchSort');

  // The list comes from the 'option' tags, which represents the options in drop-down menu on the web page
  $subgroups    = $raw_subgroup->getElementsByTagName('option');
  $types        = $raw_type->getElementsByTagName('option');

  // Cache subgroups
  $fp = fopen($subgroup_json, "w") or die("Cannot open ${subgroup_json} to write!");
  fwrite($fp, "{\n\t\"Subgroups\": {\n");
  for($i = 1; $i < count($subgroups); $i++) {
    fwrite($fp, sprintf("\t\t{ \"Id\": %d, \"Name\": %s }, \n", $subgroups[$i]->getAttribute('value'), $subgroups[$i]->textContent));
  }
  fwrite($fp, "\t}\n}\n");
  fclose($fp);

  // Cache types
  $fp = fopen($type_json, "w") or die("Cannot open ${type_json} to write!");
  fwrite($fp, "{\n\t\"Types\": {\n");
  for($i = 1; $i < count($types); $i++) {
    fwrite($fp, sprintf("\t\t{ \"Id\": %d, \"Name\": %s }, \n", $types[$i]->getAttribute('value'), $types[$i]->textContent));
  }
  fwrite($fp, "\t}\n}\n");
  fclose($fp);
}

// Send back data
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
