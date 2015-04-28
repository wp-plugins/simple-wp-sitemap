<?php if (!defined( 'ABSPATH' )){ die(); }

/*
 * The sitemap creating class
 */
class SimpleWpMapBuilder {
	private $home = null;
	private $xml = false;
	private $html = false;
	private $posts = '';
	private $pages = '';
	private $content = '';
	private $url;
	private $tags;
	private $order;
	private $homeUrl;
	private $authors;
	private $categories;
	private $blockedUrls;
	
	// Constructor
	public function __construct($command) {
		$this->url = esc_url(plugins_url() . '/simple-wp-sitemap');
		$this->homeUrl = esc_url(get_home_url() . (substr(get_home_url(), -1) === '/' ? '' : '/'));
		
		switch ($command) {
			case 'xml':
				$this->xml = true;
				$this->generateSitemaps();
				break;
			case 'html':
				$this->html = true;
				$this->generateSitemaps();
				break;
			case 'delete':
				$this->deleteFiles();
		}
	}
	
	// Get method for content
	public function getContent() {
		return $this->content;
	}
	
	// Generates the maps
	private function generateSitemaps() {
		$this->categories = (get_option('simple_wp_disp_categories') ? array(0 => 0) : false);
		$this->tags = (get_option('simple_wp_disp_tags') ? array(0 => 0) : false);
		$this->authors = (get_option('simple_wp_disp_authors') ? array(0 => 0) : false);
		$this->order = get_option('simple_wp_disp_sitemap_order');
		
		$this->setUpBlockedUrls();
		$this->generateContent();
	}
	
	// Returns other urls user has submitted
	private function getOtherPages() {
		$xml = '';
		
		if ($options = get_option('simple_wp_other_urls')) {
			foreach ($options as $option) {
				if ($option && is_array($option)) {
					$xml .= $this->getXml(esc_url($option['url']), esc_html($option['date']));
				}
			}
		}
		return $xml;
	}
	
	// Sets up blocked urls into an array
	private function setUpBlockedUrls() {
		$blocked = get_option('simple_wp_block_urls');
		if ($blocked && is_array($blocked)) {
			$this->blockedUrls = array();
			
			foreach ($blocked as $block) {
				$this->blockedUrls[$block['url']] = 'blocked';
			}
		}
		else {
			$this->blockedUrls = null;
		}
	}
	
	// Matches url against blocked ones that shouldn't be displayed
	private function isBlockedUrl($url) {
		return $this->blockedUrls && isset($this->blockedUrls[$url]);
	}
	
	
	// Returns an xml or html string
	private function getXml($link, $date) {
		if ($this->xml) {
			return "<url>\n\t<loc>$link</loc>\n\t<lastmod>$date</lastmod>\n</url>\n";
		}
		else{
			return "<li><a title=\"$link\" href=\"$link\">$link</a><span class=\"date\">$date</span></li>";
		}
	}
	
	// Returns table headers with specific names (has been changed to div)
	private function htmlTableH($name) {
		return '<div class="header"><p class="header-txt">' . $name . ':</p><p class="header-date">Last modified:</p></div>';
	}
	
	// Gets the actual sitemaps content, and querys the database
	private function generateContent() {
		$q = new WP_Query(array('post_type' => 'any', 'post_status' => 'publish', 'posts_per_page' => -1, 'has_password' => false));
				
		global $post;
		$localPost = $post;
		
		if ($q->have_posts()) {
			while ($q->have_posts()) {
				$q->the_post();
				
				$link = esc_url(get_permalink());
				$date = esc_html(get_the_modified_date('Y-m-d\TH:i:sP'));
				
				$this->getCategoriesTagsAndAuthor($date);
				
				if (!$this->isBlockedUrl($link)) {
					if (!$this->home && $link === $this->homeUrl) {
						$this->home = $this->getXml($link, $date);
					}
					elseif ('page' === get_post_type()) {
						$this->pages .= $this->getXml($link, $date);
					}
					else { // posts (also all custom post types are added here)
						$this->posts .= $this->getXml($link, $date);
					}
				}
			}
		}
		
		$this->mergeArraysAndGetOtherPages();
		wp_reset_postdata();
		
		$post = $localPost; // reset global post to its value before the loop
	}
	
	// Gets a posts categories, tags and author, and compares for last modified date
	private function getCategoriesTagsAndAuthor($date) {
		if ($this->categories && ($postCats = get_the_category())) {
			foreach ($postCats as $category) {
				$id = $category->term_id;
				if (!isset($this->categories[$id]) || $this->categories[$id] < $date) {
					$this->categories[$id] = $date;
				}
			}
		}
		if ($this->tags && ($postTags = get_the_tags())) {
			foreach ($postTags as $tag) {
				$id = $tag->term_id;
				if (!isset($this->tags[$id]) || $this->tags[$id] < $date) {
					$this->tags[$id] = $date;
				}
			}
		}
		if ($this->authors && ($id = get_the_author_meta('ID'))) {
			if (is_int($id) && (!isset($this->authors[$id]) || $this->authors[$id] < $date)) {
				$this->authors[$id] = $date;
			}
		}
	}
	
	// Merges the arrays with post data into strings and gets user submitted pages, categories, tags and author pages
	private function mergeArraysAndGetOtherPages() {
		$xml = '';
		$name = get_bloginfo('name');
		$sections = $this->getSortedArray();
		
		foreach ($sections as $title => $content) {
			if ($content) {
				if ($title === 'Categories' || $title === 'Tags' || $title === 'Authors') {
					$content = $this->stringifyCatsTagsAuths($title, $content);
					if ($title === 'Authors' && count($this->authors) <= 2) { // only one author
						$title = 'Author';
					}
				}
				
				if ($content) {
					$xml .= $this->xml ? $content : $this->htmlTableH($title) . "<ul>$content</ul>";
				}
			}
		}
				
		if ($this->xml) {
			$this->content = sprintf("<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<?xml-stylesheet type=\"text/css\" href=\"%s/css/xml.css\"?>\n<urlset xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xsi:schemaLocation=\"http://www.sitemaps.org/schemas/sitemap/0.9\n\thttp://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd\" xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n%s</urlset>", $this->url, $xml);
		}
		else {
			$this->content = sprintf('<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>%s Html Sitemap</title><link rel="stylesheet" href="%s/css/html.css"></head><body><div id="wrapper"><h1>%s Html Sitemap</h1>%s%s</div></body></html>', $name, $this->url, $name, $xml, $this->attributionLink());
		}
	}
	
	// Displays attribution link if admin has checked the checkbox
	private function attributionLink() {
		if (get_option('simple_wp_attr_link')) {
			return '<p id="attr">Generated by: <a href="http://www.webbjocke.com/simple-wp-sitemap/">Simple Wp Sitemap</a></p>';
		}
		return '';
	}
	
	// Gets sorted array according to specified order
	private function getSortedArray() {
		if (!($arr = $this->order)) {
			$arr = array('Home' => null, 'Posts' => null, 'Pages' => null, 'Other' => null, 'Categories' => null, 'Tags' => null, 'Authors' => null);
		}
		
		if (!$this->home) { // if homepage isn't found in the query (for instance if it's not a real "page" it wont be found)
			@date_default_timezone_set(get_option('timezone_string'));
			$this->home = $this->getXml($this->homeUrl, date('Y-m-d\TH:i:sP'));
		}
		
		// copy to array and also clear some memory (some sites have a huge amount of posts)
		$arr['Home'] = $this->home; $this->home = null;
		$arr['Posts'] = $this->posts; $this->posts = null;
		$arr['Pages'] = $this->pages; $this->pages = null;
		$arr['Categories'] = $this->categories; $this->categories = null;
		$arr['Tags'] = $this->tags; $this->tags = null;
		$arr['Authors'] = $this->authors; $this->authors = null;
		$arr['Other'] = $this->getOtherPages();
		
		return $arr;
	}
	
	// Returns category, tag and author links as ready xml and html strings
	private function stringifyCatsTagsAuths($type, $content) {
		$xml = '';
		
		foreach ($content as $id => $date) {
			if ($date) {
				$link = esc_url($this->getLink($id, $type));
				if (!$this->isBlockedUrl($link)) {
					$xml .= $this->getXml($link, $date);
				}
			}
		}
		return $xml;
	}
	
	// Returns either a category, tag or an author link
	private function getLink($id, $type) {
		switch ($type) {
			case 'Tags': return get_tag_link($id);
			case 'Categories': return get_category_link($id);
			default: return get_author_posts_url($id); // Authors
		}
	}
	
	// Deletes the sitemap files from old versions of the plugin
	private function deleteFiles() {
		if (function_exists('get_home_path')) {
			$path = sprintf('%s%ssitemap', get_home_path(), (substr(get_home_path(), -1) === '/' ? '' : '/'));
			try {
				if (file_exists($path . '.xml')) {
					unlink($path . '.xml');
				}
				if (file_exists($path . '.html')) {
					unlink($path . '.html');
				}
			}
			catch (Exception $ex) {
				return;
			}
		}
	}
}