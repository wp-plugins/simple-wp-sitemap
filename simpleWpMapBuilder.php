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
	private $categories;
	private $tags;
	private $authors;
	
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
		$this->categories = (get_option('simple_wp_disp_categories') ? array(0 => 0) : null);
		$this->tags = (get_option('simple_wp_disp_tags') ? array(0 => 0) : null);
		$this->authors = (get_option('simple_wp_disp_authors') ? array(0 => 0) : null);
		
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
	
	// Returns other urls (not standard wordpress) user has submitted
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
	
	// Creates the actual sitemaps content, and querys the database
	private function getContent() {
		$q = new WP_Query('post_type=any&posts_per_page=-1');
		$name = get_bloginfo('name');
		$xml = sprintf("<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<?xml-stylesheet type=\"text/css\" href=\"%s/css/xml.css\"?>\n<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xsi:schemaLocation=\"http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd\">\n", $this->url);
		$html = sprintf("<!doctype html>\n<html>\n<head>\n\t<meta charset=\"utf-8\">\n\t<meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\n\t<title>%s Html Sitemap</title>\n\t<link rel=\"stylesheet\" href=\"%s/css/html.css\">\n</head>\n<body>\n<div id=\"wrapper\">\n\n\t<h1>%s Html Sitemap</h1>\n\n%s\t<ul>\n", $name, $this->url, $name, $this->htmlTableH('Home'));
		$posts = array('xml' => '', 'html' => '');
		$pages = array('xml' => '', 'html' => '');
		$homePage = false;
		
		if ($q->have_posts()) {
			while ($q->have_posts()) { 
				$q->the_post();
				
				$link = esc_url(get_permalink());
				$date = esc_html(get_the_modified_date('Y-m-d\TH:i:sP'));
				
				$this->getCategoriesTagsAndAuthor($date);
				
				if (!$this->isBlockedUrl($link)) {			
					if ($link === $this->homeUrl) {
						$xml .= $this->getXml($link, $date);
						$html .= $this->getHtml($link, $date);
						$homePage = true;
					}
					elseif ('page' === get_post_type()) {
						$pages['xml'] .= $this->getXml($link, $date);
						$pages['html'] .= $this->getHtml($link, $date);
					}
					else { // posts (also all custom post types are added here)
						$posts['xml'] .= $this->getXml($link, $date);
						$posts['html'] .= $this->getHtml($link, $date);
					}
				}
			}
		}
		
		$localArr = $this->mergeArraysAndGetOtherPages($posts, $pages, $homePage);
		
		$this->xml = sprintf("%s%s</urlset>", $xml, $localArr['xml']);
		$this->html = sprintf("%s%s%s</div>\n</body>\n</html>", $html, $localArr['html'], $this->attributionLink());
		wp_reset_postdata();
	}
	
	// Displays attribution link if admin has checked the checkbox
	private function attributionLink() {
		if (get_option('simple_wp_attr_link')) {
			return "\t<p id=\"attr\">Generated by: <a href=\"http://www.webbjocke.com/simple-wp-sitemap/\">Simple Wp Sitemap</a></p>\n";
		}
		return '';
	}
	
	// Gets a posts categories, tags and author, and compares for last modified date
	private function getCategoriesTagsAndAuthor($date) {
		if ($this->categories) {
			if ($postCats = get_the_category()) {
				foreach ($postCats as $category) {
					$id = $category->term_id;
					if (!isset($this->categories[$id]) || $this->categories[$id] < $date) {
						$this->categories[$id] = $date;
					}
				}
			}
		}
		if ($this->tags) {
			if ($postTags = get_the_tags()) {
				foreach ($postTags as $tag) {
					$id = $tag->term_id;
					if (!isset($this->tags[$id]) || $this->tags[$id] < $date) {
						$this->tags[$id] = $date;
					}
				}
			}
		}
		if ($this->authors) {
			if ($id = get_the_author_meta('ID')) {
				if (is_int($id) && (!isset($this->authors[$id]) || $this->authors[$id] < $date)) {
					$this->authors[$id] = $date;
				}
			}
		}
	}
	
	// Merges the arrays with post data into strings and gets user submitted pages, categories, tags and author pages
	private function mergeArraysAndGetOtherPages($posts, $pages, $homePage) {
		$xml = '';
		$html = '';
		
		if (!$homePage) { // if homepage isn't found in the query add it here (for instance if it's not a real "page" it wont be found)
			date_default_timezone_set(get_option('timezone_string'));
			$date = date('Y-m-d\TH:i:sP');
			$xml .= $this->getXml($this->homeUrl, $date);
			$html .= sprintf("%s\t</ul>\n", $this->getHtml($this->homeUrl, $date));
		}
		else{
			$html .= "\t</ul>\n";
		}
		
		if ($posts['xml']) { 
			$xml .= $posts['xml'];
			$html .= sprintf("%s\t<ul>\n%s\t</ul>\n", $this->htmlTableH('Posts'), $posts['html']);
		}
		if ($pages['xml']) {
			$xml .= $pages['xml'];
			$html .= sprintf("%s\t<ul>\n%s\t</ul>\n", $this->htmlTableH('Pages'), $pages['html']);
		}
		
		$otherPages = $this->getOtherPages();
		if ($otherPages['xml']) {
			$xml .= $otherPages['xml'];
			$html .= sprintf("%s\t<ul>\n%s\t</ul>\n", $this->htmlTableH('Other'), $otherPages['html']);
		}
		
		if ($this->categories) {
			$locArr = $this->stringifyCatsTagsAuths('Categories');
			$xml .= $locArr['xml'];
			$html .= $locArr['html'];
		}		
		if ($this->tags) {
			$locArr = $this->stringifyCatsTagsAuths('Tags');
			$xml .= $locArr['xml'];
			$html .= $locArr['html'];
		}
		if ($this->authors) {
			$locArr = $this->stringifyCatsTagsAuths(count($this->authors) > 2 ? 'Authors' : 'Author');
			$xml .= $locArr['xml'];
			$html .= $locArr['html'];
		}
		
		return array('xml' => $xml, 'html' => $html);
	}
	
	// Returns category, tag and author links as ready xml and html strings
	private function stringifyCatsTagsAuths($type) {
		$html = sprintf("%s\t<ul>\n", $this->htmlTableH($type));
		$xml = '';
		
		switch ($type) {
			case 'Tags':
				$arr = $this->tags;
				break;
			case 'Categories':
				$arr = $this->categories;
				break;
			default: // 'Author'
				$arr = $this->authors;
		}
		
		foreach ($arr as $id => $date) {
			if ($date) {
				$link = esc_url($this->getLink($id, $type));
				if (!$this->isBlockedUrl($link)) {
					$xml .= $this->getXml($link, $date);
					$html .= $this->getHtml($link, $date);
				}
			}
		}
		return array('xml' => $xml, 'html' => $html . "\t</ul>\n");
	}
	
	// Returns either a category, tag or an author link
	private function getLink($id, $type) {
		switch ($type) {
			case 'Tags':
				return get_tag_link($id);
			case 'Categories':
				return get_category_link($id);
			default: // 'Author'
				return get_author_posts_url($id);
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