<?php if (!defined( 'ABSPATH' )) { die(); }

/*
 * Class that handles all admin settings
 */
class SimpleWpMapOptions {
	private $homeUrl;
	
	// Constructor: sets homeUrl with trailing slash
	public function __construct () {
		$this->homeUrl = esc_url(get_home_url() . (substr(get_home_url(), -1) === '/' ? '' : '/'));
	}
	
	// Returns a sitemap url
	public function sitemapUrl ($format) {
		return sprintf('%ssitemap.%s', $this->homeUrl, $format);
	}
	
	// Updates the settings/options
	public function setOptions ($otherUrls, $blockUrls, $attrLink, $categories, $tags, $authors, $orderArray) {
		@date_default_timezone_set(get_option('timezone_string'));
		update_option('simple_wp_other_urls', $this->addUrls($otherUrls, get_option('simple_wp_other_urls')));		
		update_option('simple_wp_block_urls', $this->addUrls($blockUrls));
		update_option('simple_wp_attr_link', $attrLink);
		update_option('simple_wp_disp_categories', $categories);
		update_option('simple_wp_disp_tags', $tags);
		update_option('simple_wp_disp_authors', $authors);
		
		if ($this->checkOrder($orderArray)) {
			asort($orderArray); // sort the array here
			update_option('simple_wp_disp_sitemap_order', $orderArray);
		}
	}
	
	// Returns the options as strings to be displayed in textareas, checkbox values and orderarray (to do: refactor this messy function)
	public function getOptions ($val) {
		if (preg_match("/^simple_wp_(other_urls|block_urls)$/", $val)) {
			$val = get_option($val);
		}
		elseif (preg_match("/^simple_wp_(attr_link|disp_categories|disp_tags|disp_authors)$/", $val)) {
			return get_option($val) ? 'checked' : ''; // return checkbox checked values right here and dont bother with the loop below
		}
		elseif ($val === 'simple_wp_disp_sitemap_order' && ($orderArray = get_option($val))) {
			return $this->checkOrder($orderArray);
		}
		else {
			$val = null;
		}
		
		$str = '';
		if (!$this->isNullOrWhiteSpace($val)) {
			foreach ($val as $sArr) {
				$str .= $this->sanitizeUrl($sArr['url']) . "\n"; 
			}	
		}
		return trim($str);
	}
	
	// Checks if string/array is empty
	private function isNullOrWhiteSpace ($word) {
		if (is_array($word)) {
			return false;
		}
		return ($word === null || $word === false || trim($word) === '');
	}
	
	// Sanitizes urls with esc_url and trim
	private function sanitizeUrl ($url) {
		return esc_url(trim($url));
	}
	
	// Checks if orderArray has valid numbers (from 1 to 7)
	private function checkOrder ($numbers) {
		if (is_array($numbers)) {
			foreach ($numbers as $key => $num) {
				if (!preg_match("/^[1-7]{1}$/", $num)) {
					return false;
				}
			}
			return $numbers;
		}
		return false;
	}
	
	// Adds new urls to the sitemaps
	private function addUrls ($urls, $oldUrls=null) {
		$arr = array();
		
		if (!$this->isNullOrWhiteSpace($urls)) {
			$urls = explode("\n", $urls);
			
			foreach ($urls as $u){
				if (!$this->isNullOrWhiteSpace($u)) {
					$u = $this->sanitizeUrl($u);
					$b = false;
					if ($oldUrls && is_array($oldUrls)) {
						foreach ($oldUrls as $o) {
							if ($o['url'] === $u && !$b) {
								$arr[] = $o;
								$b = true;
							}
						}
					}
					if (!$b && strlen($u) < 500) {
						$arr[] = array('url' => $u, 'date' => date('Y-m-d\TH:i:sP'));
					}
				}
			}
		}
		return !empty($arr) ? $arr : '';
	}
}