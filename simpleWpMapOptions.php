<?php if (!defined( 'ABSPATH' )){ die(); }

/*
 * Class that handles all admin settings
 */
class SimpleWpMapOptions {
	
	// Updates the settings/options
	public function setOptions($otherUrls, $blockUrls, $attrLink){
		update_option('simple_wp_other_urls', $this->addUrls($otherUrls, get_option('simple_wp_other_urls')));		
		update_option('simple_wp_block_urls', $this->addUrls($blockUrls));
		update_option('simple_wp_attr_link', $attrLink);
	}
	
	// Returns the options as strings to be displayed in textareas, and checkbox values
	public function getOptions($val){
		switch($val){
			case 'simple_wp_other_urls':
				$val = get_option('simple_wp_other_urls');
				break;
			case 'simple_wp_block_urls':
				$val = get_option('simple_wp_block_urls');
				break;
			case 'simple_wp_attr_link':
				$val = get_option('simple_wp_attr_link');
				return $val ? 'checked' : ''; // return checkbox checked value right here and dont bother with the loop below
			default:
				$val = null;
		}
		
		if (!$this->isNullOrWhiteSpace($val)){
			$str = '';
			foreach($val as $sArr){
				$str .= $this->sanitizeUrl($sArr['url']) . "\n"; 
			}
			return trim($str);
		}
		return '';
	}
	
	// Checks if string/array is empty
	private function isNullOrWhiteSpace($word){
		if (is_array($word)){
			return false;
		}
		return ($word === null || $word === false || trim($word) === '');
	}
	
	// Sanitizes urls with esc_url and trim
	private function sanitizeUrl($url){
		return esc_url(trim($url));
	}
	
	// Adds new urls to the sitemaps
	private function addUrls($urls, $oldUrls=null){
		$arr = array();
		
		if (!$this->isNullOrWhiteSpace($urls)){
			$urls = explode("\n", $urls);
			
			foreach ($urls as $u){
				if (!$this->isNullOrWhiteSpace($u)){
					$u = $this->sanitizeUrl($u);
					$b = false;
					if ($oldUrls && is_array($oldUrls)){
						foreach ($oldUrls as $o){
							if ($o['url'] === $u && !$b){
								$arr[] = $o;
								$b = true;
							}
						}
					}
					if (!$b && strlen($u) < 500){
						$arr[] = array('url' => $u, 'date' => date('Y-m-d\TH:i:sP'));
					}
				}
			}
		}
		return !empty($arr) ? $arr : '';
	}
}