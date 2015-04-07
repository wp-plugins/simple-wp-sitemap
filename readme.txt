=== Simple Wp Sitemap ===
Contributors: Webbjocke
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl.html
Tags: sitemap, google sitemap, xml, simple sitemap, html, xml sitemap, html sitemap, seo, seo sitemap
Requires at least: 4.0
Tested up to: 4.1.1
Stable tag: 1.0.6

An easy, fast and secure plugin that adds both an xml and an html sitemap to your site, which updates and maintains themselves so you dont have to!

== Description ==

Simple Wp Sitemap is a plugin that creates and adds both an xml and an html sitemap to your page as static files. These two are updated automatically everytime a post or page is created, edited or deleted, and makes sure they're easily indexed. What this means you only have to install and activate the plugin once, and it will just work for you without you ever having to worry.

Reason the sitemaps are created as static files and aren't generated everytime someone's visiting them, is because of the awesome performance that comes with not having to do database queries and php rendering all day. Sure you have to wait a couple milliseconds extra when you create a new or edit a post, but in my opinion that's totally worth it!

Also supports the option to add pages to the sitemaps that aren't part of your original wordpress site. For instance if you create a little html file and upload to your server and want it to be included in them, it's easily done. You can also block pages that you don't want to be included.

So what the plugin actually does is creating two files, one sitemap.xml and one sitemap.html. These two becomes available directly on your site at like yourpage.com/sitemap.xml and yourpage.com/sitemap.html.

And yes, of course the sitemaps are mobile friendly :)

== Installation ==

1. 1. Go to the plugins page in your wordpress admin area and hit "add new".
   2. Either search for "simple-wp-sitemap" and click install, or hit "upload plugin" and upload the zip file.
   3. Another way is by just uploading the "simple-wp-sitemap" folder via ftp to the /wp-content/plugins/ directory.

2. Activate the plugin and thats it, done. The two sitemaps have now been created and can be found at like yourpage.com/sitemap.xml and yourpage.com/sitemap.html.

3. Customize the plugin and add/block pages by hitting the "Simple Wp Sitemap" option in the settings menu.

== Frequently Asked Questions ==

= I have installed the plugin, where can I find the sitemaps? =

They are located in your sites so called "home directory", you can find them at like yourpage.com/sitemap.xml and yourpage.com/sitemap.html. There's also links to them from the plugins customization page in your admin area.

= Where can I find the customization or admin page for the plugin? =

Click the link called "Simple Wp Sitemap" in your admin areas settings menu and it will take you there. Theres also a link from the plugins page, where you activate and deactivate them etc.

= Does it create both an xml and an html sitemap? =

Yes sir, it does.

= Is it possible to add the sitemaps anywhere else on my site? Like on a page with a shortcode or something? =

Sorry no, not at the moment it isn't.

= Are the sitemaps created "on the fly" dynamically or as static files? =

As static files. They get updated everytime you create, edit or delete a post or page. And also when changes are made in the admin area.

= How do I remove the sitemaps if I stop using the plugin? =

When you deactivate the plugin they get removed automatically.

== Screenshots ==

1. Settings page
2. Html sitemap
3. Xml sitemap

== Changelog ==

= 1.0.6 (April 7, 2015) =
* Made the plugin more user friendly
* Added links to the sitemaps from the admin area
* Added FAQ's (was about time huh)
* Formatted the code a bit better

= 1.0.5 (March 26, 2015) =
* Fixed timezone bug
* Made the sitemaps a bit lighter, they were so dark n dull
* Corrected some css problems in older browsers
* Updated screenshots

= 1.0.4 (March 18, 2015) =
* Added options to include category, tag and author pages
* Changed layout a bit and made it more responsive
* Changed font to a more readable one

= 1.0.3 (March 16, 2015) =
* New layout for both html and xml sitemaps
* Created a logo for the plugin
* Added a banner
* Added screenshots
* Fixed bug with custom post types

= 1.0.2 (March 14, 2015) =
* Messed up the upload, try again

= 1.0.1 (March 14, 2015) =
* Added link to settings from the plugins page
* Now also escapes output for user added urls
* Added max-length for user added urls

= 1.0.0 (March 14, 2015) =
* Initial public release