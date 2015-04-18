<?php if (!defined( 'ABSPATH' )){ die(); }

/*
 * Plugin Name: Simple Wp Sitemap
 * Plugin URI: http://www.webbjocke.com/simple-wp-sitemap/
 * Description: An easy, fast and secure plugin that adds both an xml and an html sitemap to your site, which updates and maintains themselves so you dont have to!
 * Version: 1.0.7
 * Author: Webbjocke
 * Author URI: http://www.webbjocke.com/
 * License: GPLv3
 */

// Main class

class SimpleWpSitemap {
	
	// Updates the sitemaps
	public static function updateSitemaps() {
		require_once('simpleWpMapBuilder.php');	
		new SimpleWpMapBuilder('generate');
	}
	
	// Delete the files sitemap.xml and sitemap.html on deactivate
	public static function removeSitemaps() {
		require_once('simpleWpMapBuilder.php');
		new SimpleWpMapBuilder('delete');
	}
	
	// Adds a link to settings from the plugins page
	public static function pluginSettingsLink($links) {
		$theLink = array(sprintf('<a href="%s">%s</a>', esc_url(admin_url('options-general.php?page=simpleWpSitemapSettings')), __('Settings')));
		return array_merge($links, $theLink);
	}
	
	// Sets the menu option for admins and enqueues scripts n styles
	public static function sitemapAdminSetup() {
		add_options_page('Simple Wp Sitemap', 'Simple Wp Sitemap', 'administrator', 'simpleWpSitemapSettings', array('SimpleWpSitemap', 'sitemapAdminArea'));
		add_action('admin_enqueue_scripts', array('simpleWpSitemap', 'sitemapScriptsAndStyles'));
		add_action('admin_init', array('SimpleWpSitemap', 'sitemapAdminInit'));
	}
	
	// Register settings on admin_init
	public static function sitemapAdminInit() {
		register_setting('simple_wp-sitemap-group', 'simple_wp_other_urls');
		register_setting('simple_wp-sitemap-group', 'simple_wp_block_urls');
		register_setting('simple_wp-sitemap-group', 'simple_wp_attr_link');
		register_setting('simple_wp-sitemap-group', 'simple_wp_disp_categories');
		register_setting('simple_wp-sitemap-group', 'simple_wp_disp_tags');
		register_setting('simple_wp-sitemap-group', 'simple_wp_disp_authors');
		register_setting('simple_wp-sitemap-group', 'simple_wp_disp_sitemap_order');
	}
	
	// Add custom scripts and styles to the plugins customization page in admin area
	public static function sitemapScriptsAndStyles($page) {
		if ($page !== 'settings_page_simpleWpSitemapSettings') {
			return;
		}
		wp_enqueue_style('simple-wp-sitemap-admin-css', plugin_dir_url( __FILE__ ) . '/css/simple-wp-sitemap-admin.css');
		wp_enqueue_script('simple-wp-sitemap-admin-js', plugin_dir_url( __FILE__ ) . '/js/simple-wp-sitemap-admin.js', array('jquery'), false, true);
	}
	
	// Interface for settings page, also handles initial post request when settings are changed
	public static function sitemapAdminArea() {
		require_once('simpleWpMapOptions.php');
		$options = new SimpleWpMapOptions();
		
		if (isset($_POST['simple_wp_other_urls'], $_POST['simple_wp_block_urls'], $_POST['simple_wp_home_n'], $_POST['simple_wp_posts_n'], $_POST['simple_wp_pages_n'], $_POST['simple_wp_other_n'], $_POST['simple_wp_categories_n'], $_POST['simple_wp_tags_n'], $_POST['simple_wp_authors_n'])) {
			
			$options->setOptions($_POST['simple_wp_other_urls'], $_POST['simple_wp_block_urls'], (isset($_POST['simple_wp_attr_link']) ? 1 : 0), (isset($_POST['simple_wp_disp_categories']) ? 1 : 0), (isset($_POST['simple_wp_disp_tags']) ? 1 : 0), (isset($_POST['simple_wp_disp_authors']) ? 1 : 0), array('Home' => $_POST['simple_wp_home_n'], 'Posts' => $_POST['simple_wp_posts_n'], 'Pages' => $_POST['simple_wp_pages_n'], 'Other' => $_POST['simple_wp_other_n'], 'Categories' => $_POST['simple_wp_categories_n'], 'Tags' => $_POST['simple_wp_tags_n'], 'Authors' => $_POST['simple_wp_authors_n']));
			self::updateSitemaps();
		} ?>
		
		<div class="wrap">
		
			<h2>Simple Wp Sitemap settings</h2>
			
			<p>Your two sitemaps have been created and are active! Here you can change and customize them</p>
			
			<p><strong>Links to your xml and html sitemap:</strong>
			
			<ul>
				<li>Xml sitemap: <a href="<?php echo $options->sitemapUrl('xml'); ?>"><?php echo $options->sitemapUrl('xml'); ?></a></li>
				<li>Html sitemap: <a href="<?php echo $options->sitemapUrl('html'); ?>"><?php echo $options->sitemapUrl('html'); ?></a></li>
			</ul>
			
			<noscript>(Enable javascript for order options)</noscript>
			
			<form method="post" action="options-general.php?page=simpleWpSitemapSettings" id="simple-wp-sitemap-form">
			
				<?php settings_fields('simple_wp-sitemap-group'); ?>
				
				<ul id="sitemap-settings">
					<li id="sitemap-normal" class="sitemap-active">General</li>
					<li id="sitemap-advanced">Order</li>
				</ul>
				
				<table id="sitemap-table-show" class="widefat form-table">
				
					<tr><td><strong>Add pages</strong></td></tr>
					<tr><td>Add pages to the sitemaps in addition to your normal wordpress ones. Just paste "absolute" links in the textarea like: <strong>http://www.example.com/a-page/</strong>. Each link on a new row (this will affect both your xml and html sitemap).</td></tr>
					<tr><td><textarea rows="7" name="simple_wp_other_urls" class="large-text code"><?php echo $options->getOptions('simple_wp_other_urls'); ?></textarea></td></tr>
					
					<tr><td><strong>Block pages</strong></td></tr>
					<tr><td>Add pages you want to block from showing up in the sitemaps. Same as above, just paste every link on a new row. (Hint: copy paste links from one of the sitemaps to get correct urls).</td></tr>
					<tr><td><textarea rows="7" name="simple_wp_block_urls" class="large-text code"><?php echo $options->getOptions('simple_wp_block_urls'); ?></textarea></td></tr>
					
					<tr><td><strong>Extra sitemap includes</strong></td></tr>
					<tr><td>Check if you want to include categories, tags and/or author pages in the sitemaps.</td></tr>
					<tr><td><input type="checkbox" name="simple_wp_disp_categories" id="simple_wp_cat" <?php echo $options->getOptions('simple_wp_disp_categories'); ?>><label for="simple_wp_cat"> Include categories</label></td></tr>
					<tr><td><input type="checkbox" name="simple_wp_disp_tags" id="simple_wp_tags" <?php echo $options->getOptions('simple_wp_disp_tags'); ?>><label for="simple_wp_tags"> Include tags</label></td></tr>
					<tr><td><input type="checkbox" name="simple_wp_disp_authors" id="simple_wp_authors" <?php echo $options->getOptions('simple_wp_disp_authors'); ?>><label for="simple_wp_authors"> Include authors</label></td></tr>
					
					<tr><td><strong>Like the plugin?</strong></td></tr>
					<tr><td>Show your support by rating the plugin at wordpress.org, or/and by adding an attribution link to the sitemap.html file :)</td></tr>
					<tr><td><input type="checkbox" name="simple_wp_attr_link" id="simple_wp_check" <?php echo $options->getOptions('simple_wp_attr_link'); ?>><label for="simple_wp_check"> Add "Generated by Simple Wp Sitemap" link at bottom of sitemap.html.</label></td></tr>
					
				</table>
				
				<table id="sitemap-table-hide" class="widefat form-table">
				
					<tr><td><strong>Change display order</td></tr>
					<tr><td>If you want to change the display order in your sitemaps, click the arrows to move sections up or down. They will be displayed as ordered below, highest up is displayed first and lowest down last.</td></tr>
					<tr><td>
					
						<ul id="sitemap-display-order">
					
							<?php
							
								if (!($orderArray = $options->getOptions('simple_wp_disp_sitemap_order'))) {
									$orderArray = array('Home' => null, 'Posts' => null, 'Pages' => null, 'Other' => null, 'Categories' => null, 'Tags' => null, 'Authors' => null);
								}
								$count = 0;
								
								foreach ($orderArray as $title => $val) {
									printf('<li>%s<span class="sitemap-down" title="move down"></span><span class="sitemap-up" title="move up"></span><input type="hidden" name="simple_wp_%s_n" value="%d"></li>', $title, lcfirst($title), (++$count));
								}
							
							?>
						
						</ul>
						
					</td></tr>
					
					<tr><td id="sitemap-defaults" title="Restore the default display order">Restore default order</td></tr>
					
				</table>
			
				<p class="submit"><input type="submit" class="button-primary" value="<?php _e('Save Changes'); ?>"></p>
				
			</form>
			
		</div>
<?php }
}
add_action('admin_menu', array('SimpleWpSitemap', 'sitemapAdminSetup'));
add_action('deleted_post', array('SimpleWpSitemap', 'updateSitemaps'));
add_action('save_post', array('SimpleWpSitemap', 'updateSitemaps'));
register_activation_hook(__FILE__, array('SimpleWpSitemap', 'updateSitemaps'));
register_deactivation_hook(__FILE__, array('SimpleWpSitemap', 'removeSitemaps'));
add_filter("plugin_action_links_" . plugin_basename(__FILE__), array('SimpleWpSitemap', 'pluginSettingsLink'));