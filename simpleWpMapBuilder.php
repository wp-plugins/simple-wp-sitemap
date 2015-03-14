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
	
	// Constructor, the only public function this class has
	public function __construct($command){
		$this->url = esc_url(plugins_url() . '/simple-wp-sitemap');
		$this->homeUrl = esc_url(get_home_url() . (substr(get_home_url(), -1) === '/' ? '' : '/'));
		
		switch($command){
			case 'generate':
				$this->generateSitemaps();
				break;
			case 'delete':
				$this->deleteSitemaps();
				break;
		}
	}
	
	// Generates the maps
	private function generateSitemaps(){
		$this->setUpBlockedUrls();
		$this->getContent();
		
		$this->writeToFile($this->xml, 'xml');
		$this->writeToFile($this->html, 'html');
	}
	
	// Deletes the maps
	private function deleteSitemaps(){
		$this->deleteFile('xml');
		$this->deleteFile('html');
	}
	
	// Returns other urls (not standard wordpress) user has submitted
	private function getOtherPages(){
		$options = get_option('simple_wp_other_urls');
		$html = '';
		$xml = '';
		
		if ($options){
			foreach($options as $option){
				if ($option && is_array($option)){
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
	private function setUpBlockedUrls(){
		$blocked = get_option('simple_wp_block_urls');
		if ($blocked && is_array($blocked)){
			$this->blockedUrls = array();
			
			foreach($blocked as $block){
				$this->blockedUrls[] = $block['url'];
			}
		}
		else{
			$this->blockedUrls = null;
		}
	}
	
	// Matches url against all blocked ones that shouldn't be displayed
	private function isBlockedUrl($url){
		return $this->blockedUrls && in_array($url, $this->blockedUrls);
	}
	
	// Returns an html string
	private function getHtml($link, $date){
		return "\t<div>\n\t\t<p><a title=\"$link\" href=\"$link\">$link</a></p>\n\t\t<p>$date</p>\n\t</div>\n";
	}
	
	// Returns an xml string
	private function getXml($link, $date){
		return "<url>\n\t<loc>$link</loc>\n\t<lastmod>$date</lastmod>\n</url>\n";
	}
	
	// Returns table headers with specific names (has been changed to div)
	private function htmlTableH($name){
		return "\t<div class=\"header\">\n\t\t<p>$name:</p>\n\t\t<p>Last modified:</p>\n\t</div>\n";
	}
	
	// Creates the actual sitemaps content, and querys the database
	private function getContent(){
		$q = new WP_Query('post_type=any&posts_per_page=-1');
		$name = get_bloginfo('name');
		$xml = sprintf("<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n<?xml-stylesheet type=\"text/css\" href=\"%s/css/xml.css\"?>\n<urlset xmlns=\"http://www.sitemaps.org/schemas/sitemap/0.9\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xsi:schemaLocation=\"http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd\">\n", $this->url);
		$html = sprintf("<!doctype html>\n<html>\n<head>\n\t<meta charset=\"utf-8\">\n\t<meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\n\t<title>%s Html Sitemap</title>\n\t<link rel=\"stylesheet\" href=\"%s/css/html.css\">\n</head>\n<body>\n\t<h1>%s Html Sitemap</h1>\n%s", $name, $this->url, $name, $this->htmlTableH('Home'));
		$posts = array('xml' => '', 'html' => '');
		$pages = array('xml' => '', 'html' => '');
		$others = array('xml' => '', 'html' => '');
		$homePage = false;
		
		if ($q->have_posts()) {
			while ($q->have_posts()) {
				$q->the_post();
				
				$link = esc_url(get_permalink());
				$date = esc_html(get_the_modified_date('Y-m-d\TH:i:sP'));
				
				if (!$this->isBlockedUrl($link)){						
					if ($link === $this->homeUrl){
						$xml .= $this->getXml($link, $date);
						$html .= $this->getHtml($link, $date);
						$homePage = true;
					}
					elseif ('post' === get_post_type()){
						$posts['xml'] .= $this->getXml($link, $date);
						$posts['html'] .= $this->getHtml($link, $date);
					}
					elseif ('page' === get_post_type()){
						$pages['xml'] .= $this->getXml($link, $date);
						$pages['html'] .= $this->getHtml($link, $date);
					}
					else{
						$others['xml'] .= $this->getXml($link, $date);
						$others['html'] .= $this->getHtml($link, $date);
					}
				}
			}
		}
		
		$localArr = $this->mergeArraysAndGetOtherPages($posts, $pages, $others, $homePage);
		
		$this->xml = sprintf("%s%s</urlset>", $xml, $localArr['xml']);
		$this->html = sprintf("%s%s\n\t%s</body>\n</html>", $html, $localArr['html'], $this->attributionLink());
		wp_reset_postdata();
	}
	
	// Displays attribution link. Not default, user has to check a checkbox for this to be displayed (returns an empty string as default)
	private function attributionLink(){
		if (get_option('simple_wp_attr_link')){
			return "<p id=\"attr\">Generated by: <a href=\"http://www.webbjocke.com/\">Simple Wp Sitemap</a></p>"; // will be changed to like webbjocke.com/simple-wp-sitemap or plugin page on wordpress.org if the plugin gets accepted
		}
		return '';
	}
	
	// Merges the arrays with post data into strings and gets user submitted pages
	private function mergeArraysAndGetOtherPages($posts, $pages, $others, $homePage){
		$xml = '';
		$html = '';
		
		if (!$homePage){ // if homepage isn't found in the query add it here (for instance if it's not a real "page" it wont be found)
			$date = date('Y-m-d\TH:i:sP');
			$xml .= $this->getXml($this->homeUrl, $date);
			$html .= $this->getHtml($this->homeUrl, $date);
		}
		
		if ($posts['xml']) { 
			$xml .= $posts['xml'];
			$html .= $this->htmlTableH('Posts') . $posts['html'];
		}
		if ($pages['xml']) {
			$xml .= $pages['xml'];
			$html .= $this->htmlTableH('Pages') . $pages['html'];
		}
		if ($others['xml']) {
			$xml .= $others['xml'];
			$html .= $this->htmlTableH('Other') . $others['html'];
		}
		
		$otherPages = $this->getOtherPages();
		if ($otherPages['xml']){
			$xml .= $otherPages['xml'];
			$html .= (!$others['html'] ? $this->htmlTableH('Other') : '') . $otherPages['html'];
		}
		
		return array('xml' => $xml, 'html' => $html);
	}
	
	// Sets up file paths to home directory
	private function setFile($fileType){
		$this->file = sprintf("%s%ssitemap.%s", get_home_path(), (substr(get_home_path(), -1) === '/' ? '' : '/'), $fileType);
	}
	
	// Creates sitemap files and overrides old ones if there are any
	private function writeToFile($data, $fileType){
		$this->setFile($fileType);
		try{
			$fp = fopen($this->file, 'w');
			if (file_exists($this->file)){
				fwrite($fp, $data);
				fclose($fp);
			}
		}
		catch(Exception $ex){
			die();
		}
	}
	
	// Deletes the sitemap files
	private function deleteFile($fileType){
		$this->setFile($fileType);
		try{
			unlink($this->file);
		}
		catch(Exception $ex){
			die();
		}
	}
}