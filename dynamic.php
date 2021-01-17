<?php

$dmhy_url = 'https://share.dmhy.org/topics/list?keyword=%s&sort_id=%d&team_id=%d&order=date-desc';

function get_var_value($var, $default) {
  if(!isset($var) || empty($var))
    return $default;
  else
    return $var;
}

$keyword  = (!isset($_GET['keyword'])  || empty($_GET['keyword']))  ? "" : $_GET['keyword'];
$subgroup = (!isset($_GET['subgroup']) || empty($_GET['subgroup'])) ? 0  : $_GET['subgroup'];
$type     = (!isset($_GET['type'])     || empty($_GET['type']))     ? 0  : $_GET['type'];

// Get web page using cURL
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, sprintf($dmhy_url, $keyword, $type, $subgroup));
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
@$dom->loadHTML($raw_data);
$list_table = $dom->getElementById('topic_list');
$list_content = $list_table->getElementsByTagName('tbody');
$list_items = $list_content[0]->getElementsByTagName('tr');

?>

{
    "HasMore": true,
    "Resources": [
<?php
foreach($list_items as $item) {
  $item_attr = $item->getElementsByTagName('td');
  // [0]: date/time
  $item_date_container = $item_attr[0]->getElementsByTagName('span')->item(0);
  $item_date           = trim($item_date_container->textContent);
  // [1]: type
  $item_type_container      = $item_attr[1]->getElementsByTagName('a')->item(0);
  $item_type_text_container = $item_type_container->getElementsByTagName('font')->item(0);
  $item_type_url            = $item_type_container->getAttribute('href');
  $item_type_id             = trim(substr($item_type_url, 21));
  $item_type_name           = trim($item_type_text_container->textContent);
  // [2]: subgroup + title
  $item_path = new DOMXpath($item_attr[2]);
  $item_subgroup = $xpath->query("//span[@class='tag']");
  if(!$item_subgroup) {
    $item_subgroup_id   = 0;
    $item_subgroup_name = Unknown;
  } else {
    $item_subgroup_container = $item_subgroup[0]->getElementsByTagName('a')->item(0);
    $item_subgroup_url       = $item_subgroup_container->getAttribute('href');
    $item_subgroup_id        = trim(substr($item_subgroup_url, 21));;
    $item_subgroup_name      = trim($item_subgroup_container->textContent);
  }
  $item_info     = $item_attr[2]->getElementsByTagName('a')->item(0);
  $item_title    = trim($item_info->textContent);
  $item_page_url = 'https://share.dmhy.org' . trim($item_info->getAttribute('href'));
  //[3]: Magnet
  $item_magnet_container = $item_attr[3]->getElementsByTagName('a')->item(0);
  $item_magnet           = trim($item_magnet_container->getAttribute('href'));
  //[4]: File size
  $item_file_size = trim($item_attr[4]->textContent);

?>

        {
            "Title": <?php echo "$item_title"; ?>,
            "TypeId": <?php echo $item_type_id; ?>,
            "TypeName":  <?php echo "$item_type_name"; ?>,
            "SubgroupId": <?php echo $item_subgroup_id; ?>,
            "SubgroupName": <?php echo "$item_subgroup_name"; ?>,
            "Magnet": <?php echo "$item_magnet"; ?>,
            "PageUrl": <?php echo "$item_page_url"; ?>,
            "FileSize": <?php echo "$item_file_size"; ?>,
            "PublishDate": <?php echo "$item_date"; ?>
        },

<?php
}
?>
    ]
}
