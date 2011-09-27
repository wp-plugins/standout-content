<?php
/*
Plugin Name: Standout Content
Plugin URI: http://www.gameplorer.de/tools/standout-content/
Description: Standout Content adds an option to mark a post as standout for Google News. You can check or uncheck the option for each post.
Author: Dennis Pietsch
Author URI: http://www.dennispietsch.com
Tags: standout, google, news, tag, link, rel, content
Version: 1.1
*/

if (!class_exists('StandoutContent')):

	class StandoutContent {

		private $metaKey = '_standout_content';

		public function __construct() {
			add_action('admin_menu', array(&$this, 'onAdminMenu'));
			add_action('save_post', array(&$this, 'onSave'));
			add_action('wp_head', array(&$this, 'onWPHead'));
		}
		
		public function onAdminMenu() {
			add_meta_box('standout-content-metabox', 'Standout Content', array(&$this, 'metaBox'), 'post');
		}
			
		public function onWPHead() {
		
			if (!is_single())
				return;
				
			global $post;
			$postId = (is_object($post)) ? $post->ID : $post;
			
			$isStandoutContent = get_post_meta($postId, $this->metaKey, true);
			if ($isStandoutContent == '1')
				echo '<link rel="standout" href="' . get_permalink($postId) . '" />' . "\n";
		}
			
		public function onSave($postId) {
		
			// verify nonce
			if (!wp_verify_nonce($_POST['standout_content_noncename'], plugin_basename(__FILE__)))
				return $postId;
			
			// never save while auto saving
			if (defined('DOING_AUTOSAVE') and DOING_AUTOSAVE) 
				return $postId;
				
			// check permission
			if (!current_user_can('edit_post', $postId))
				return $postId;
				
			// save
			if (isset($_POST['standout_content_checkbox']) and $_POST['standout_content_checkbox'] == "1")
				update_post_meta($postId, $this->metaKey, '1');
			else
				delete_post_meta($postId, $this->metaKey);
		}
		
		public function metaBox() {
		
			global $post;
			$postId = (is_object($post)) ? $post->ID : $post;
		
			echo '<input type="hidden" name="standout_content_noncename" value="' . wp_create_nonce(plugin_basename(__FILE__)) . '" />';
			$checked = get_post_meta($postId, $this->metaKey, true) == '1' ? ' checked="checked"' : null;
			
			echo '<p><input type="checkbox" name="standout_content_checkbox" id="standout_content_checkbox"' . $checked . ' value="1" /> Add a <a href="http://googlenewsblog.blogspot.com/2011/09/recognizing-publishers-standout-content.html" target="_blank">standout tag</a> to this post.</p>';
			
			$posts = $this->getRecentStandoutPosts();
			echo '<p>You tagged <strong>' . count($posts) . '</strong> post(s) as Standout Content within the last 7 days.';
			if (count($posts) > 0):
				$postsStream = '<br/>';
				foreach ($posts as $p):
					$postsStream .= '<a href="' . get_bloginfo('url') . '/wp-admin/post.php?post=' . $p->ID . '&action=edit">' . $p->post_title . '</a> | ';
				endforeach;
				$postsStream = substr($postsStream, 0, strlen($postsStream) - 2);
				echo $postsStream;
			endif;
			echo '</p>';
		}
		
		private function getRecentStandoutPosts() {
			global $wpdb;
			$qry = 'SELECT p.ID, p.post_title
					FROM ' . $wpdb->posts . ' AS p
						INNER JOIN ' . $wpdb->postmeta . ' AS m ON p.ID = m.post_id AND m.meta_key = "' . $this->metaKey . '"
					WHERE p.post_status = "publish"
					  AND m.meta_value = "1"
					  AND DATEDIFF(NOW(), p.post_date) < 8
					ORDER BY p.post_date DESC';
			return $wpdb->get_results($qry);
		}
	}

	// initialize
	new StandoutContent();
	
endif;
?>