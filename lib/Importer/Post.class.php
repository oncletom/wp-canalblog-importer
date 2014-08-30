<?php

class CanalblogImporterImporterPost extends CanalblogImporterImporterBase
{
  protected $uri, $id, $data;
  protected static $media_pattern = array(
    'new' => array(
      'detection_pattern' => '#(http://(p\d+\.)?storage.canalblog.com/[^_]+(?:\.|_p\.)[a-z0-9]+)[^a-z0-9]#iUs',
      'detection_pattern_inline' => '#^http://(p\d+\.)?storage.canalblog.com/#',
      'thumbnail_replacement_callback' => array(__CLASS__, 'thumbnailFilenameFixNew'),
    ),
    'old' => array(
      'detection_pattern' => '#(%canalblog_domain%/images/[^t][^\/]+(?:\.|t-\.)[a-z0-9]+)[^a-z0-9]#iUs',
      'detection_pattern_inline' => '#http://%canalblog_domain%/images/#',
      'thumbnail_replacement_callback' => array(__CLASS__, 'thumbnailFilenameFixOld'),
    ),
    'storagev1' => array(
      'detection_pattern' => '#(%canalblog_domain%/images/[^t][^\/]+(?:\.|t-\.)[a-z0-9]+)[^a-z0-9]#iUs',
      'detection_pattern_inline' => '#storagev1/%canalblog_domain%/images/#',
      'thumbnail_replacement_callback' => array(__CLASS__, 'thumbnailFilenameFixOld'),
    ),
  );

  public function __construct(CanalblogImporterConfiguration $configuration)
  {
    parent::__construct($configuration);

    $this->overwrite_contents = get_option('canalblog_overwrite_contents', 0);
    $this->comments_status =    get_option('canalblog_comments_status', 'open');
    $this->trackbacks_status =  get_option('canalblog_trackbacks_status', 'open');
  }

  public static function getMediaPattern($id) {
    return self::$media_pattern[$id];
  }

  /**
   * @see lib/Importer/CanalblogImporterImporterBase#dispatch()
   */
  public function dispatch()
  {
    if (!$this->uri)
    {
      return false;
    }

    return true;
  }

  public function getContentFromUri($uri) {
    $html = null;

    $query = $this->getRemoteXpath($uri, "//div[@id='content']", $html);
    $dom = new DomDocument();
    $dom->appendChild($dom->importNode($query->item(0), true));

    return array('dom' => $dom, 'html' => $html);
  }

  /**
   * @see lib/Importer/CanalblogImporterImporterBase#process()
   */
  public function process()
  {
  	$data = array();
    $remote = $this->getContentFromUri($this->uri);

    $data['post'] = $this->savePost($remote['dom'], $remote['html']);
    $data['comments'] = $this->saveComments($remote['dom'], $remote['html']);
    $data['medias'] = $this->saveMedias($remote['dom'], $remote['html']);

    return $data;
  }

  public function getData() {
    return $this->data;
  }

  public function extractTitle($xpath) {
    $title = '';
    $attempt = $xpath->query("//div[@class='blogbody']//a[@rel='bookmark']");

    if ($attempt->length) {
      $title = $attempt->item(0)->getAttribute('title');
    }
    else {
      $title = $xpath->query("//div[@class='blogbody']//h3")->item(0)->textContent;
    }

    return trim($title);
  }

  public function extractPostDate($xpath) {
    $result = $xpath->query("//meta[@itemprop='dateCreated']");

    if (!$result->length) {
      return null;
    }

    preg_match('#^(?P<year>\d{4})-(?P<month>\d{2})-(?P<day>\d{2})T(?P<hour>\d{2}):(?P<minutes>\d{2})$#U', $result->item(0)->getAttribute('content'), $matches);
    extract($matches);

    return sprintf('%s-%s-%s %s:%s', $year, $month, $day, $hour, $minutes);
  }

  public function extractPostAuthorName($xpath) {
    $result = $xpath->query("//*[@class='articlecreator' and @itemprop='creator']");

    if (!$result->length) {
      return 'admin';
    }

    return $result->item(0)->textContent;
  }

  public function extractPostContent($xpath) {
    $tmpDom = new DomDocument();
    $finder = new DomXpath($tmpDom);

    $result = $xpath->query("//div[@itemtype='http://schema.org/Article']")->item(0);
    $result = $tmpDom->importNode($result, true);
    $tmpDom->appendChild($result);

    // remove footer and everything after
    $footer = $finder->query("//div[@class='itemfooter']");

    if ($footer->length) {
      $footer = $footer->item(0);

      $childCursor = 0;
      $parentNode = $footer->parentNode;
      $delete = false;

      while ($childCursor < $parentNode->childNodes->length) {
        $item = $parentNode->childNodes->item($childCursor);

        if ($item->isSameNode($footer)) {
          $delete = true;
        }

        if ($delete) {
          $parentNode->removeChild($item);
        }

        $childCursor++;
      }
    }

    // remove itemprops
    foreach($finder->query("//*[boolean(@itemprop)]") as $item) {
      if ($item->getAttribute('itemprop') === 'articleBody') {
        continue;
      }

      $item->parentNode->removeChild($item);
    }

    // remove titles
    $anchor = $finder->query("//a[@name='". $result->getAttribute('id') ."']")->item(0);
    $childCursor = 0;
    $parentNode = $anchor->parentNode;


    while ($childCursor < $parentNode->childNodes->length) {
      $childNode = $parentNode->childNodes->item($childCursor);

      $parentNode->removeChild($childNode);

      // and we remove the next one as it's the real title
      if ($childNode->isSameNode($anchor)) {
        $parentNode->removeChild($parentNode->childNodes->item($childCursor + 1));
        break;
      }

      $childCursor++;
    }

    //remove attributes
    $tmpDom->firstChild->removeAttribute('id');
    $tmpDom->firstChild->removeAttribute('itemscope');
    $tmpDom->firstChild->removeAttribute('itemtype');
    $tmpDom->firstChild->removeAttribute('itemref');
    $tmpDom->firstChild->removeAttribute('class');
    $tmpDom->firstChild->removeAttribute('data-edittype');

    return $tmpDom;
  }

  public function extractComments($xpath) {
    $comments = array();

    foreach ($xpath->query("//*[@itemprop='comment']") as $commentNode) {
      if (!$commentNode->hasAttribute('data-pid')) {
        continue;
      }

      $data = array(
        '__comment_id' => $commentNode->getAttribute('data-pid'),
        'comment_approved' => 1,
        'comment_karma' => 1,
        'comment_post_ID' =>  $this->id,
        'comment_author_email' => 'nobody@canalblog',
        'comment_agent' => 'Canalblog Importer',
        'comment_author_IP' => '127.0.0.1',
        'comment_type' => 'comment',
        'comment_author_url' => '',
      );

      /*
       * Content
       */
      $tmpdom = new DomDocument();
      $tmpNode = $tmpdom->importNode($commentNode, true);
      $tmpdom->appendChild($tmpNode);
      $finder = new DomXpath($tmpdom);

      foreach($finder->query("//h3") as $item) {
        $item->parentNode->removeChild($item);
      }

      foreach($finder->query("//*[@class='itemfooter']") as $item) {
        $item->parentNode->removeChild($item);
      }

      $data['comment_content'] = trim($tmpdom->textContent);
      unset($tmpdom, $tmpnode);

      /*
       * Author
       */
      $commentAuthor = $xpath->query("div[@class='itemfooter']/a", $commentNode);

      if ($commentAuthor->length) {
        $data['comment_author_url'] = $commentAuthor->item(0)->getAttribute('href');
        $data['comment_author'] = $commentAuthor->item(0)->textContent;
      }
      else {
        preg_match('#^Posté par (?P<author>[^,]+),#U', $xpath->query("div[@class='itemfooter']", $commentNode)->item(0)->textContent,  $matches);

        if (!empty($matches)) {
          $data['comment_author'] = $matches['author'];
        }
      }

      /*
       * Date
       */
      // Modern Mode
      $commentDate = $xpath->query("//div[@class='itemfooter']/*[@class='timeago']", $commentNode);

      if ($commentDate->length) {
        $data['comment_date'] = str_replace('T', ' ', $commentDate->item(0)->getAttribute('title'));
      }

      // Legacy Mode
      else {
        $tmp = trim(str_replace(array("\r\n", "\r", "\n"), ' ', $xpath->query("div[@class='itemfooter']", $commentNode)->item(0)->textContent));
        $tmp = str_replace('  ', ' ', $tmp);
        preg_match('#, (le )?(?P<day>[^ ]+) (?P<month>[^ ]+) (?P<year>[^ ]+) (à|&agrave;?) (?P<hour>[^:]+):(?P<minute>.+)$#iUs', $tmp, $matches);
        $matches['strptime'] = strptime(sprintf('%s %s %s %s:%s', $matches['day'], $matches['month'], $matches['year'], $matches['hour'], $matches['minute']), '%d %B %Y %H:%M');
        $matches['month'] = sprintf('%02s', $matches['strptime']['tm_mon'] + 1);

        $data['comment_date'] = sprintf('%s-%s-%s %s:%s:00', $matches['year'], $matches['month'], $matches['day'], $matches['hour'], $matches['minute']);
      }

      $data['comment_date_gmt'] = $data['comment_date'];

      array_push($comments, $data);
    }

    return $comments;
  }

  public function extractMediaUris($html, $canalblogDomain) {
    $remote_uris = array();
    $dom = $this->getDomDocumentFromHtml($html);
    $xpath = new DomXpath($dom);

    // Get storage hyperlinks
    foreach ($xpath->query("//a[contains(@href, 'canalblog.com/storagev1') or contains(@href, 'storage.canalblog.com') or contains(@href, 'canalblog.com/docs')]") as $link) {
      array_push($remote_uris, $link->getAttribute('href'));
    }

    // Get image sources
    foreach ($xpath->query("//img[contains(@src, 'canalblog.com/storagev1') or contains(@src, 'storage.canalblog.com') or contains(@src, 'canalblog.com/images')]") as $link) {
      array_push($remote_uris, $link->getAttribute('src'));
    }

    return array_unique($remote_uris);
  }

  public function isImageSrcPattern($src, $media_pattern, $host) {
    $hostname = parse_url($host, PHP_URL_HOST);
    $media_pattern['detection_pattern_inline'] = str_replace('%canalblog_domain%', $hostname, $media_pattern['detection_pattern_inline']);

    return preg_match($media_pattern['detection_pattern_inline'], $src) === 1;
  }

  /**
   * Save post content
   *
   * @author oncletom
   * @since 1.0
   * @version 1.0
   * @param DomDocument $dom
   * @return Integer Post ID
   */
  public function savePost(DomDocument $dom)
  {
    $xpath = new DomXpath($dom);
    $data = array(
      'post_status' => 'publish',
    );
    $stats = array('title' => $this->uri, 'status' => __('error', 'canalblog-importer'));

    /*
     * Initial stuff
     *
     * Original ID, date etc.
     */
    preg_match('#/(\d+)\.html$#U', $this->uri, $matches);
    $canalblog_id = $matches[1];

    /*
     * Determining title and date
     */
    $data['post_title'] = $this->extractTitle($xpath);
    $data['post_date'] = $this->extractPostDate($xpath);

    if (!$data['post_date']) {
      return $stats;
    }

    /*
     * Striping images attributes such as their size
     * Also centering them with WordPress CSS class
     */
    foreach ($dom->getElementsByTagName('img') as $imgNode)
    {
      foreach (self::$media_pattern as &$config)
      {
        if ($this->isImageSrcPattern($imgNode->getAttribute('src'), $config, get_option('canalblog_importer_blog_uri')))
        {
          $imgNode->removeAttribute('height');
          $imgNode->removeAttribute('width');
          $imgNode->removeAttribute('border');
          $imgNode->setAttribute('alt', '');
          $imgNode->setAttribute('class', 'aligncenter size-medium');
          $imgNode->parentNode->removeAttribute('target');
        }
      }
    }

    /*
     * Determining content
     */
    $data['post_content'] = trim($this->extractPostContent($xpath)->saveHTML());

    /*
     * Determining author
     */
    $author_name = $this->extractPostAuthorName($xpath);
    $data['post_author'] = $this->getOrCreateAuthorByUsername($author_name);

    /*
     * Opened to comments + trackbacks
     */
    $data['comment_status'] = $this->comments_status;
    $data['ping_status'] = $this->trackbacks_status;

    /*
     * Saving
     *
     * As for now, we don't save again an existing post
     */
    if ($post_id = post_exists($data['post_title'], '', $data['post_date']))
    {
      $data['ID'] = $post_id;
      $stats['status'] = __('skipped', 'canalblog-importer');

      if ($this->overwrite_contents)
      {
        wp_untrash_post($post_id);
        wp_update_post($data);
        $stats['status'] = __('overwritten', 'canalblog-importer');
      }

      $post_existed = true;
    }
    else
    {
      $post_id = wp_insert_post($data);
      $data['ID'] = $post_id;
      $stats['status'] = __('imported', 'canalblog-importer');
    }

    $stats['id'] = $data['ID'];
    $stats['title'] = $data['post_title'];

    /*
     * Post save extras
     */
    /*
     * Determining categories
     */
    $categories = array();
    foreach ($xpath->query("//div[@class='blogbody']//div[@class='itemfooter']//a[@title='Autres messages dans cette catégorie']") as $category)
    {
      $categories[] = category_exists($category->textContent);
    }

    if (!empty($categories))
    {
      wp_set_post_categories($post_id, $categories);
    }

    /*
     * Determining tags
     */
    $tags = array();
    foreach ($xpath->query("//div[@class='blogbody']//div[@class='itemfooter']//a[@rel='tag']") as $tag)
    {
      $tags[] = $tag->textContent;
    }

    if (!empty($tags))
    {
      wp_set_post_tags($post_id, implode(',', $tags));
    }

    /*
     * Saving some extra meta
     * - original ID
     * - original URI
     */
    add_post_meta($post_id, 'canalblog_id', $canalblog_id, true);
    add_post_meta($post_id, 'canalblog_uri', $this->uri, true);

    $this->data = $data;
    $this->id = $post_id;

    return $stats;
  }

  /**
   * Save comments from a post
   *
   * @author oncletom
   * @since 1.0
   * @version 2.0
   * @param DomDocument $dom
   */
  public function saveComments(DomDocument $dom, $html)
  {
    $xpath = new DomXpath($dom);
  	$stats = array('count' => 0, 'new' => 0, 'skipped' => 0, 'overwritten' => 0);

    if ($this->data['comment_status'] == 'closed')
    {
      return $stats;
    }

    /*
     * Canalblog is only in french, hopefully for us (and me...)
     */
    setlocale(LC_TIME, 'fr_FR.UTF-8', 'fr_FR@euro', 'fr_FR', 'fr', 'french');

    $found_comments = $this->extractComments($xpath);
 		$stats['count'] = count($found_comments);
    unset($matches);

    if (empty($found_comments)) {
      return $stats;
    }

    $comments = get_comments(array('post_id' => $this->id));

    foreach ($found_comments as $data) {
      $canalblog_comment_id = $data['__comment_id'];
      unset($data['__comment_id']);

      /*
       * Saving (only if not exists)
       */
      $data = wp_filter_comment($data);
      if ($comment_id = comment_exists($data['comment_author'], $data['comment_date']))
      {
        $data['comment_ID'] = $comment_id;

        if ($this->overwrite_contents)
        {
          if ('trash' === wp_get_comment_status($comment_id))
          {
            wp_untrash_comment($comment_id);
          }

          wp_update_comment($data);
          $stats['overwritten']++;
        }
        else
        {
        	$stats['skipped']++;
        }
      }
      else
      {
        $comment_id = wp_insert_comment($data);
        add_comment_meta($comment_id, 'canalblog_id', $canalblog_comment_id, true);
        $stats['new']++;
      }

      unset($tmp, $data);
    }

    /*
     * Recounting comments for this post
     */
    wp_update_comment_count_now($this->id);
    return $stats;
  }

  /**
   * Save medias from a post
   *
   * Also alter content to make it points locally
   *
   * @author oncletom
   * @since 1.0
   * @version 1.0
   * @param DomDocument $dom
   */
  public function saveMedias(DomDocument $dom)
  {
  	$stats = array('count' => 0, 'new' => 0, 'skipped' => 0, 'overwritten' => 0);

    /*
     * Initialize WordPress importer
     */
    try{
      self::requireWordPressImporter($this->getConfiguration());
    }
    catch (CanalblogImporterException $e)
    {
      return $stats;
    }

    $wpImport = new WP_Import();
    $wpImport->fetch_attachments = true;

    $attachments = array();
    $remote_uris = array();
    $remote_uris_mapping = array();
    $post = get_post($this->id, ARRAY_A);

    /*
     * Collecting attachment URIs
     */
    $remote_uris = $this->extractMediaUris($post['post_content'], get_option('canalblog_importer_blog_uri'));
    $stats['count'] = count($remote_uris);

    /*
     * No attachment? We skip the rest
     */
    if (empty($remote_uris))
    {
      return $stats;
    }

    $upload = wp_upload_dir($post['post_date']);

    foreach ($remote_uris as $remote_uri)
    {
      /*
       * Checking it does not exists yet
       */
      $candidates = get_posts(array(
        'meta_key' =>   'canalblog_attachment_uri',
        'meta_value' => $remote_uri,
        'post_type' =>  'attachment',
      ));

      /*
       * Skipping the save
       */
      if (!empty($candidates))
      {
      	$stats['skipped']++;
        continue;
      }

      /*
       * Saving attachment
       */
      $postdata = compact('post_author', 'post_date', 'post_date_gmt', 'post_content', 'post_excerpt', 'post_title', 'post_status', 'post_name', 'comment_status', 'ping_status', 'guid', 'post_parent', 'menu_order', 'post_type', 'post_password');
      $postdata['post_parent'] =   $this->id;
      $postdata['post_date'] =     $post['post_date'];
      $postdata['post_date_gmt'] = $post['post_date_gmt'];
      $postdata['post_author'] =   $this->data['post_author'];

      $attachment_id = $wpImport->process_attachment($postdata, $remote_uri);
      add_post_meta($attachment_id, 'canalblog_attachment_uri', $remote_uri, true);
      $attachments[$remote_uri] = $attachment_id;
      $stats['new']++;
    }

    /*
     * URL mapping
     * Basically, we change the thumbnail URI by the medium size
     */
    $new_map = array();
    foreach ($wpImport->url_remap as $old_uri => $new_uri)
    {
      if (!preg_match('/\.thumbnail\.[^\.]+$/', $old_uri))
      {
        if ($old_uri)
        {
          $new_map[$old_uri] = $new_uri;
        }

        continue;
      }

      /*
       * Restore Canalblog thumbnail URI
       */
      $original_uri = str_replace('.thumbnail', '', $old_uri);

      foreach (self::$media_pattern as $type => &$config)
      {
        if (!isset($remote_uris_mapping[$type]) || !in_array($original_uri, $remote_uris_mapping[$type]))
        {
          continue;
        }

        $old_uri = call_user_func($config['thumbnail_replacement_callback'], $old_uri);
      }


      /*
       * Replace it by our own thumbnail URI
       */
      $size_pattern = '-%sx%s';
      $new_uri = str_replace(
        sprintf($size_pattern, intval(get_option('thumbnail_size_w')), intval(get_option('thumbnail_size_h'))),
        sprintf($size_pattern, intval(get_option('medium_size_w')), intval(get_option('medium_size_h'))),
        $new_uri
      );

      $image_data = image_downsize($attachments[$original_uri], 'medium');
      $new_map[$old_uri] = $image_data[0];
    }

    $wpImport->url_remap = $new_map;


    /*
     * Saving mapping
     */
    if (!empty($wpImport->url_remap))
    {
      $wpImport->backfill_attachment_urls();
    }

    return $stats;
  }

  protected function thumbnailFilenameFixNew($uri)
  {
    return str_replace('.thumbnail', '_p', $uri);
  }

  protected function thumbnailFilenameFixOld($uri)
  {
    $base_uri = get_option('canalblog_importer_blog_uri').'/images/';
    $uri = str_replace('.thumbnail', '', $uri);
    $uri = str_replace($base_uri, $base_uri.'t-', $uri);

    return $uri;
  }

  /**
   * Retrieves or create a user upon its username
   *
   * @author oncletom
   * @protected
   * @since 1.0
   * @version 1.0
   * @param String $username
   * @return integer
   */
  protected function getOrCreateAuthorByUsername($username)
  {
    if ($user_infos = get_user_by('login', $username))
    {
      return $user_infos->ID;
    }

    $data = array(
      'display_name' =>  $username,
      'role' =>          'author',
      'user_login' =>    $username,
      'user_pass' =>     wp_generate_password(),
      'user_url'  =>     'http://',
    );

    return wp_insert_user($data);
  }

  /**
   * Set the post Canalblog URI
   *
   * @author oncletom
   * @since 1.0
   * @version 1.0
   * @param String $uri
   */
  public function setUri($uri)
  {
    $this->uri = $uri;
  }
}
