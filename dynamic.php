<?php declare(strict_types=1); // strict requirement

// To bypass Cloudflare's browser check, use RSS instead.
// But, RSS doesn't have all required information, so we
// have to get the missing information from resource page.
//$dmhy_url = 'https://share.dmhy.org/topics/list?keyword=%s&sort_id=%d&team_id=%d&order=date-desc';
$dmhy_url = 'https://share.dmhy.org/topics/rss/rss.xml?keyword=%s&sort_id=%d&team_id=%d';

class ResourceInfo {
  public $title         = "";
  public $type_id       = 0;
  public $type_name     = "Unknown";
  public $subgroup_id   = 0;
  public $subgroup_name = "Unknown";
  public $magnet        = "";
  public $url           = "";
  public $size          = "";
  public $date          = "";
}

function get_page(string $url) {
  // Get web page using cURL
  $ch = curl_init();
  curl_setopt($ch, CURLOPT_URL, $url);
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

  return $raw_data;
}

function get_resource_detail(ResourceInfo &$resource) {
  $page_data = get_page($resource->url);

  // Parse web page
  $dom = new DOMDocument();
  @$dom->loadHTML($page_data);

  // Get subgroup information
  $dom_path = new DOMXpath($dom);
  $sidebar = $dom_path->query("//div[@class='user-sidebar']")->item(0);

  foreach($sidebar->getElementsByTagName('a') as $link) {
    $link_url  = $link->getAttribute('href');
    $link_text = $link->textContent;
    $link_url_segments = explode('/', $link_url);
    if(count($link_url_segments) == 5 && $link_url_segments[1] == "topics" && $link_url_segments[2] == "list" && $link_url_segments[3] == "team_id") {
      $resource->subgroup_id   = intval($link_url_segments[4]);
      $resource->subgroup_name = $link_text;
    }
  }

  // Get size information
  $main_section   = $dom_path->query("//div[@class='topic-main']")->item(0);
  $info_section   = $main_section->getElementsByTagName('ul')->item(0);
  $info_content   = $info_section->getElementsByTagName('li');
  $size_index     = count($info_content) - 3;
  $size_content   = $info_content[$size_index];
  $resource->size = $size_content->getElementsByTagName('span')->item(0)->textContent;
}

$keyword  = (!isset($_GET['keyword'])  || empty($_GET['keyword']))  ? "" : $_GET['keyword'];
$subgroup = (!isset($_GET['subgroup']) || empty($_GET['subgroup'])) ? 0  : $_GET['subgroup'];
$type     = (!isset($_GET['type'])     || empty($_GET['type']))     ? 0  : $_GET['type'];

$page_data = get_page(sprintf($dmhy_url, $keyword, $type, $subgroup));

// Parse web page
$dom = new DOMDocument();
@$dom->loadXML($page_data);
$items = $dom->getElementsByTagName('item');

$has_more = "false";
$item_count = count($items);
if($item_count > 20) {
  $item_count = 20;
  $has_more = "true";
}

?>

{
    "HasMore": <?php echo $has_more; ?>,
    "Resources": [
<?php

for ($i=0; $i<$item_count; $i++) {
  $item = $items[$i];

  $resource = new ResourceInfo();
  $resource->title     = $item->getElementsByTagName('title')->item(0)->nodeValue;
  $resource->url       = $item->getElementsByTagName('link')->item(0)->nodeValue;
  $resource->date      = $item->getElementsByTagName('pubDate')->item(0)->nodeValue;
  $resource->magnet    = $item->getElementsByTagName('enclosure')->item(0)->getAttribute('url');
  $category            = $item->getElementsByTagName('category')->item(0);
  $category_url        = $category->getAttribute('domain');
  $category_id_start   = strrpos($category_url, '/') + 1;
  $category_id         = substr($category_url, $category_id_start);
  $resource->type_id   = intval($category_id);
  $resource->type_name = $category->nodeValue;

  get_resource_detail($resource);


?>

        {
            "Title": "<?php echo $resource->title; ?>",
            "TypeId": <?php echo $resource->type_id; ?>,
            "TypeName":  "<?php echo $resource->type_name; ?>",
            "SubgroupId": <?php echo $resource->subgroup_id; ?>,
            "SubgroupName": "<?php echo $resource->subgroup_name; ?>",
            "Magnet": "<?php echo $resource->magnet; ?>",
            "PageUrl": "<?php echo $resource->url; ?>",
            "FileSize": "<?php echo $resource->size; ?>",
            "PublishDate": "<?php echo $resource->date; ?>"
        },

<?php
}
?>
    ]
}
