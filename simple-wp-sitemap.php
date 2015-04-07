<?php if (!defined( 'ABSPATH' )){ die(); }

/*
 * Plugin Name: Simple Wp Sitemap
 * Plugin URI: http://www.webbjocke.com/simple-wp-sitemap/
 * Description: An easy, fast and secure plugin that adds both an xml and an html sitemap to your site, which updates and maintains themselves so you dont have to!
 * Version: 1.0.6
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
	
	// Sets the menu option for admins 
	public static function sitemapAdminSetup() {
		add_options_page('Simple Wp Sitemap', 'Simple Wp Sitemap', 'administrator', 'simpleWpSitemapSettings', array('SimpleWpSitemap', 'sitemapAdminArea'));
		add_action('admin_init', array('SimpleWpSitemap', 'sitemapAdminInit'));
	}
	
	// Registers settings on admin_init
	public static function sitemapAdminInit() {
		register_setting('simple_wp-sitemap-group', 'simple_wp_other_urls');
		register_setting('simple_wp-sitemap-group', 'simple_wp_block_urls');
		register_setting('simple_wp-sitemap-group', 'simple_wp_attr_link');
		register_setting('simple_wp-sitemap-group', 'simple_wp_disp_categories');
		register_setting('simple_wp-sitemap-group', 'simple_wp_disp_tags');
		register_setting('simple_wp-sitemap-group', 'simple_wp_disp_authors');
	}
	
	// Interface for settings page, also handles initial post request when settings are changed
	public static function sitemapAdminArea() {
		require_once('simpleWpMapOptions.php');
		$options = new SimpleWpMapOptions();
		
		if (isset($_POST['simple_wp_other_urls'], $_POST['simple_wp_block_urls'])) {
			$options->setOptions($_POST['simple_wp_other_urls'], $_POST['simple_wp_block_urls'], (isset($_POST['simple_wp_attr_link']) ? 1 : 0), (isset($_POST['simple_wp_disp_categories']) ? 1 : 0), (isset($_POST['simple_wp_disp_tags']) ? 1 : 0), (isset($_POST['simple_wp_disp_authors']) ? 1 : 0));
			self::updateSitemaps();
		} ?>
		
		<div class="wrap">
		
			<h1>Simple Wp Sitemap settings</h1>
			
			<p>Your two sitemaps have been created and are active! Here you can change and customize them</p>
			
			<p><strong>Links to your xml and html sitemap:</strong>
			
			<ul>
				<li>Xml sitemap: <a href="<?php echo $options->sitemapUrl('xml'); ?>"><?php echo $options->sitemapUrl('xml'); ?></a></li>
				<li>Html sitemap: <a href="<?php echo $options->sitemapUrl('html'); ?>"><?php echo $options->sitemapUrl('html'); ?></a></li>
			</ul>
			
			<form method="post" action="options-general.php?page=simpleWpSitemapSettings">
			
				<?php settings_fields('simple_wp-sitemap-group'); ?>
				
				<table class="widefat form-table">
				
					<tr><td><strong>Add pages</strong></td></tr>
					<tr><td>Add pages to the sitemaps in addition to your original wordpress ones. Just paste "absolute" links in the textarea like: <strong>http://www.example.com/</strong>, each link on a new row. (This will affect both your xml and html sitemap)</td></tr>
					<tr><td><textarea rows="7" name="simple_wp_other_urls" class="large-text code"><?php echo $options->getOptions('simple_wp_other_urls'); ?></textarea></td></tr>
					
					<tr><td><strong>Block pages</strong></td></tr>
					<tr><td>Add pages you don't want to show up in the sitemaps. Same as above, just paste every link on a new row. (Hint: copy paste links from one of the sitemaps to get correct urls).</td></tr>
					<tr><td><textarea rows="7" name="simple_wp_block_urls" class="large-text code"><?php echo $options->getOptions('simple_wp_block_urls'); ?></textarea></td></tr>
					
					<tr><td><strong>Extra sitemap includes</strong></td></tr>
					<tr><td>Check if you want to include categories, tags and/or author pages in the sitemaps.</td></tr>
					<tr><td><input type="checkbox" name="simple_wp_disp_categories" id="simple_wp_cat" <?php echo $options->getOptions('simple_wp_disp_categories'); ?>></input><label for="simple_wp_cat"> Include categories</label></td></tr>
					<tr><td><input type="checkbox" name="simple_wp_disp_tags" id="simple_wp_tags" <?php echo $options->getOptions('simple_wp_disp_tags'); ?>></input><label for="simple_wp_tags"> Include tags</label></td></tr>
					<tr><td><input type="checkbox" name="simple_wp_disp_authors" id="simple_wp_authors" <?php echo $options->getOptions('simple_wp_disp_authors'); ?>></input><label for="simple_wp_authors"> Include authors</label></td></tr>
										
					<tr><td><strong>Like the plugin?</strong></td></tr>
					<tr><td>Show your support by rating the plugin at wordpress.org, or atleast by adding an attribution link to the sitemap.html file :)</td></tr>
					<tr><td><input type="checkbox" name="simple_wp_attr_link" id="simple_wp_check" <?php echo $options->getOptions('simple_wp_attr_link'); ?>></input><label for="simple_wp_check"> Add "Generated by Simple Wp Sitemap" link at bottom of sitemap.html.</label></td></tr>
					
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