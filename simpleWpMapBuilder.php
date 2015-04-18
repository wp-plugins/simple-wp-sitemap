<?php if (!defined( 'ABSPATH' )){ die(); }

/*
 * The sitemap creating class
 */
class SimpleWpMapBuilder {
	private $xml;
	private $html;
	private $file;
	private $url;
	private $homeUrl;
	private $blockedUrls;
	private $home;
	private $posts;
	private $pages;
	private $categories;
	private $tags;
	private $authors;
	private $order;
	
	// Constructor, the only public function this class has
	public function __construct($command) {
		$this->url = esc_url(plugins_url() . '/simple-wp-sitemap');
		$this->homeUrl = esc_url(get_home_url() . (substr(get_home_url(), -1) === '/' ? '' : '/'));
		
		switch ($command) {
			case 'generate':
				$this->generateSitemaps();
				break;
			case 'delete':
				$this->deleteSitemaps();
		}
	}
	
	// Generates the maps
	private function generateSitemaps() {
		$this->categories = (get_option('simple_wp_disp_categories') ? array(0 => 0) : false);
		$this->tags = (get_option('simple_wp_disp_tags') ? array(0 => 0) : false);
		$this->authors = (get_option('simple_wp_disp_authors') ? array(0 => 0) : false);
		$this->order = get_option('simple_wp_disp_sitemap_order');
		$this->posts = array('xml' => '', 'html' => '');
		$this->pages = array('xml' => '', 'html' => '');
		$this->home = null;
		
		$this->setUpBlockedUrls();
		$this->getContent();
		
		$this->writeToFile($this->xml, 'xml');
		$this->writeToFile($this->html, 'html');
	}
	
	// Deletes the maps
	private function deleteSitemaps() {
		$this->deleteFile('xml');
		$this->deleteFile('html');
	}
	
	// Returns other urls user has submitted
	private function getOtherPages() {
		$html = '';
		$xml = '';
		
		if ($options = get_option('simple_wp_other_urls')) {
			foreach ($options as $option) {
				if ($option && is_array($option)) {
					$url = esc_url($option['url']);
					$date = esc_html($option['date']);
					$html .= $this->getHtml($url, $date);
					$xml .= $this->getXml($url, $date);
				}
			}
		}
		return array('xml' => $xml, 'html' => $html);
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
	
	// Returns an html string
	private function getHtml($link, $date) {
		return "\t\t<li>\n\t\t\t<a title=\"$link\" href=\"$link\">$link</a>\n\t\t\t<span class=\"date\">$date</span>\n\t\t</li>\n";
	}
	
	// Returns an xml string
	private function getXml($link, $date) {
		return "<url>\n\t<loc>$link</loc>\n\t<lastmod>$date</lastmod>\n</url>\n";
	}
	
	// Returns table headers with specific names (has been changed to div)
	private function htmlTableH($name) {
		return "\t<div class=\"header\">\n\t\t<p class=\"header-txt\">$name:</p>\n\t\t<p class=\"header-date\">Last modified:</p>\n\t</div>\n";
	}
	
	// Creates the actual sitemaps content, and querys the database. Might be long strings in one line.. I have a big screen
	private function getContent() {
		$q = new WP_Query(array('post_type' => 'any', 'post_status' => 'publish', 'posts_per_page' => -1, 'has_password' => false));
		$name = get_bloginfo('name');
		$this->xml = sprintf("<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<?xml-stylesheet type=\"text/css\" href=\"%s/css/xml.css\"?>\n<urlset xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xsi:schemaLocation=\"http://www.sitemaps.org/schemas/sitemap/0.9\n\thttp://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd\" xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\">\n", $this->url);
		$this->html = sprintf("<!doctype html>\n<html>\n<head>\n\t<meta charset=\"utf-8\">\n\t<meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\n\t<title>%s Html Sitemap</title>\n\t<link rel=\"stylesheet\" href=\"%s/css/html.css\">\n</head>\n<body>\n<div id=\"wrapper\">\n\n\t<h1>%s Html Sitemap</h1>\n\n", $name, $this->url, $name);
		
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
						$this->home = array('xml' => $this->getXml($link, $date), 'html' => $this->getHtml($link, $date));
					}
					elseif ('page' === get_post_type()) {
						$this->pages['xml'] .= $this->getXml($link, $date);
						$this->pages['html'] .= $this->getHtml($link, $date);
					}
					else { // posts (also all custom post types are added here)
						$this->posts['xml'] .= $this->getXml($link, $date);
						$this->posts['html'] .= $this->getHtml($link, $date);
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
		$html = '';		
		$sections = $this->getSortedArray();
		
		foreach ($sections as $title => $content) {
			if ($content) {
				if ($title === 'Categories' || $title === 'Tags' || $title === 'Authors') {
					$content = $this->stringifyCatsTagsAuths($title, $content);					
					if ($title === 'Authors' && count($this->authors) <= 2) { // only one author (<= 2 cause one extra item is added to the array earlier)
						$title = 'Author';
					}
				}
				
				if ($content['xml']) {
					$xml .= $content['xml'];
					$html .= sprintf("%s\t<ul>\n%s\t</ul>\n", $this->htmlTableH($title), $content['html']);				
				}
			}
		}
		
		$this->xml = sprintf("%s%s</urlset>", $this->xml, $xml);
		$this->html = sprintf("%s%s%s</div>\n</body>\n</html>", $this->html, $html, $this->attributionLink());
	}
	
	// Displays attribution link if admin has checked the checkbox
	private function attributionLink() {
		if (get_option('simple_wp_attr_link')) {
			return "\t<p id=\"attr\">Generated by: <a href=\"http://www.webbjocke.com/simple-wp-sitemap/\">Simple Wp Sitemap</a></p>\n";
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
			$date = date('Y-m-d\TH:i:sP');
			$this->home = array('xml' => $this->getXml($this->homeUrl, $date), 'html' => $this->getHtml($this->homeUrl, $date));
		}
		
		$arr['Home'] = $this->home;
		$arr['Posts'] = $this->posts;
		$arr['Pages'] = $this->pages;
		$arr['Other'] = $this->getOtherPages();
		$arr['Categories'] = $this->categories;
		$arr['Tags'] = $this->tags;
		$arr['Authors'] = $this->authors;
		
		return $arr;
	}
	
	// Returns category, tag and author links as ready xml and html strings
	private function stringifyCatsTagsAuths($type, $content) {
		$html = '';
		$xml = '';
		
		foreach ($content as $id => $date) {
			if ($date) {
				$link = esc_url($this->getLink($id, $type));
				if (!$this->isBlockedUrl($link)) {
					$xml .= $this->getXml($link, $date);
					$html .= $this->getHtml($link, $date);
				}
			}
		}
		return array('xml' => $xml, 'html' => $html);
	}
	
	// Returns either a category, tag or an author link
	private function getLink($id, $type) {
		switch ($type) {
			case 'Tags': return get_tag_link($id);
			case 'Categories': return get_category_link($id);
			default: return get_author_posts_url($id); // Authors
		}
	}
	
	// Sets up file paths to home directory
	private function setFile($fileType) {
		$this->file = sprintf("%s%ssitemap.%s", get_home_path(), (substr(get_home_path(), -1) === '/' ? '' : '/'), $fileType);
	}
	
	// Creates sitemap files and overrides old ones if there's any
	private function writeToFile($data, $fileType) {
		$this->setFile($fileType);
		try {
			$fp = fopen($this->file, 'w');
			if (file_exists($this->file)) {
				fwrite($fp, $data);
				fclose($fp);
			}
		}
		catch (Exception $ex) {
			die();
		}
	}
	
	// Deletes the sitemap files
	private function deleteFile($fileType) {
		$this->setFile($fileType);
		try {
			unlink($this->file);
		}
		catch (Exception $ex) {
			die();
		}
	}
}