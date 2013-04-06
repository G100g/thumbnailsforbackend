<?php
/**
 * @package thumbnailsforbackend
 * @author G100g
 * @version 0.0.5
 */
/*
Plugin Name: Thumbnails for Backend
Plugin URI: http://g100g.net/wordpress-stuff/thumbnails-for-backend-plugin/
Description: Simple plugin to add thumbnails to your Posts list within the WordPress backend.
Author: G100g
Version: 0.0.5
Author URI: http://g100g.net/

	Copyright (C) 2011 by Giorgio Aquino
	
	Permission is hereby granted, free of charge, to any person obtaining a copy
	of this software and associated documentation files (the "Software"), to deal
	in the Software without restriction, including without limitation the rights
	to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
	copies of the Software, and to permit persons to whom the Software is
	furnished to do so, subject to the following conditions:
	
	The above copyright notice and this permission notice shall be included in
	all copies or substantial portions of the Software.
	
	THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
	IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
	FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
	AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
	LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
	OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
	THE SOFTWARE.

*/

class Thumbnailsforbackend {

	var $thumbfb_options = array(
								'thumbfb_post_types' => array(),
								'thumbfb_embedded_images' => 0
								);

	public function __construct() {
		$this->update_options();
	}
	
	private function get_options() {
	
		$this->thumbfb_options['thumbfb_post_types'] = get_option('thumbfb_post_types');
		$this->thumbfb_options['thumbfb_embedded_images'] = get_option('thumbfb_embedded_images');
		
		return $this->thumbfb_options;
	}
	
	private function update_options() {
		
		//Aggirono le opzioni alla nuova versione
		
		$thumbfb_options = get_option('thumbfb_options');
		
		if ($thumbfb_options != "") {

			$thumbfb_options = unserialize( $thumbfb_options );
			
			update_option( 'thumbfb_post_types', $thumbfb_options["thumbfb_post_types"] );
			update_option( 'thumbfb_options', "" );
		
		}
		
	}
	
	public function add_thumbnails() {
		
		$this->get_options();
			
		$showing_thumbnail = FALSE;
		
		if (is_array($this->thumbfb_options["thumbfb_post_types"])) { 
		
			foreach ($this->thumbfb_options["thumbfb_post_types"] as $post_type) {
			
				add_filter('manage_edit-'.$post_type.'_columns', array(&$this, 'posts_columns'));
				$showing_thumbnail = TRUE;
				
			}
			
			if ($showing_thumbnail) {
				
				add_action('manage_pages_custom_column', array(&$this, 'posts_column'));
				add_action('manage_posts_custom_column', array(&$this, 'posts_column'));
				
				add_action('admin_head', array(&$this, 'admin_header_style'));
				
			}
		
		}
		
				
	}

	public function posts_columns($post_columns) {
		
			global $post;
			
			$_post_columns = array();
			
			foreach ($post_columns as $k => $post_column) {
				
				if ( $k == "title" ) {
					
					$_post_columns['preview'] = _('Preview');
					
				}
				
				$_post_columns[$k] = $post_columns[$k];	
			}

			return $_post_columns;
	}
	
	public function posts_column($name) {
	    
	    global $post;
	    switch ($name) {
	    	
	        case 'preview':
	        
	        	$image_html = "";
	        
	        	$edit_post_link = get_edit_post_link($post->ID);
	        
	        	if ($post->post_parent) {

					$parents = get_post_ancestors($post);
					
//					var_dump($parents);
					
					$style = 'margin-left: ' .(count($parents) * 10) . 'px;';
					$child = 'child';
					$size = array(60,60);
				
				} else {
				
					$style = '';				
					$child = '';
					$size = array(80,80);
				}
	        
	        	//Becco il thumb della prima immagine
	        	if (function_exists('get_post_thumbnail_id')) {
	        		$id_thumb = get_post_thumbnail_id($post->ID);
	        		if ($id_thumb != null) {
	        			$image_html = '<a href="' . $edit_post_link . '">'. the_post_thumbnail( $size, array('style' => $style, 'class' => $child) ) . '</a>';
	        		}
	        	}
	        	
	        	//Get first Attached Image
	        	if ($image_html == "") {
					
					$images = get_posts('post_parent='.$post->ID.'&post_type=attachment&post_mime_type=image&order=ASC&orderby=menu_order&posts_per_page=1');
					
					if ( !empty($images) ) {

						reset($images);
						$image = current($images);						
						$image_id = $image->ID;
						
						$image_html = '<a href="' . $edit_post_link .'">'. wp_get_attachment_image( $image_id , $size, null, array('style' => $style, 'class' => $child)) . '</a>';

					}        	
	        	}
	        	
	        	//Get first embedded image
	        	if ($image_html == "" && $this->thumbfb_options["thumbfb_embedded_images"] == 1) {
	        		
	        		$content = apply_filters('the_content', $post->post_content);

	        		preg_match_all( '/<img[^>]+src=[\'"]([^\'"]+)[\'"].*>/i', $content, $images);

					if (is_array($images) && array_key_exists(1, $images) && array_key_exists(0, $images[1])) {
						$image_html = '<a class="external_thumbnail" href="' . $edit_post_link .'"><img src="' . $images[1][0] . '" alt="" class"'. $child .'" /></a>';
						
					}
	        	
	        	}
	        	
	        	//No image
	        	if ($image_html == "") $image_html = "&nbsp;";
	     		
	     		echo $image_html;
	     		
	     	break;
	            
	    }
	}
	
	public function admin_header_style() {
	?>
	<style type="text/css">
	th#preview { width: 90px; }
		td.preview img {
				display: block;
				text-align: left; width: 80px;
				height: auto;}		
		td.preview img.child {
			border-left: 4px solid #DDD;
			padding-left: 4px;			
		}
		td.column-preview { float: none; width: 90px; text-align: center; }
		
		a.external_thumbnail {
			display: block;
			width: 80px;
			height: 80px;
			overflow: hidden;
		}
		
	</style>
	<?php
	}
	
	/**
	
		Admin Functions
		
	**/	

	public function admin_menu() {
		add_options_page('Thumbnails for Backend', 'Thumbnails for Backend', 'edit_posts', basename(__FILE__), array(&$this, 'admin_page') );
	}
	
	public function admin_page() {
	
		if ( !current_user_can( 'manage_options' ) )  {
			wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
		}

?>
	
	<div class="wrap">
	<?php screen_icon(); ?>
	<h2>Thumbnails for Backend</h2>
	
		<form method="post" action="options.php"> 
	
	<?php settings_fields( 'thumbnailsforbackend-options-group' );
	
	
		//do_settings_fields( 'playoptions-group' );
		do_settings_sections('thumbnailsforbackend-options');
	
	?>
	
	<?php submit_button(); ?>
		</form>
	</div>

<?php

	}
	
	public function initialize_options () {
		
		//Options
		register_setting('thumbnailsforbackend-options-group', 'thumbfb_post_types');
		register_setting('thumbnailsforbackend-options-group', 'thumbfb_embedded_images');
		
		add_settings_section(
			'section-one',
			'Options',
			null,
			'thumbnailsforbackend-options'
		);
		
		add_settings_field('field-posts_types', 'Show thumbnails in', array(&$this, 'field_posts_types'), 'thumbnailsforbackend-options', 'section-one');
		add_settings_field('field-embedded_images', 'Use Embedded images', array(&$this, 'field_embedded_images'), 'thumbnailsforbackend-options', 'section-one');

	}
	
	public function field_embedded_images() {
	
		$thumbfb_embedded_images = get_option('thumbfb_embedded_images');		
		$selected = $thumbfb_embedded_images == 1 ? ' checked="checked" ' : "";
		
?>
		<input type="checkbox" value="1" id="thumbfb_embedded_images" name="thumbfb_embedded_images" <?php echo $selected; ?>/>
<?php

	}
	
	public function field_posts_types() {

		$custom_post_types_builtin = get_post_types(array(
				'_builtin' => true,
				'show_ui' => true
		
		), 'objects');
		
		$custom_post_types = get_post_types(array(
				'public'   => true,
				'_builtin' => false,
				'show_ui' => true
		
		), 'objects');
		
		$custom_post_types = array_merge($custom_post_types, $custom_post_types_builtin);
		
		$thumbfb_post_types = get_option('thumbfb_post_types');
		
		foreach ($custom_post_types as $post_type => $cpt) {
		
			if ($post_type == "attachment") continue;
			$selected = "";
			if (is_array( $thumbfb_post_types )) {	
				$selected = ( in_array( $post_type, $thumbfb_post_types ) ? ' checked="checked" ' : '' ); 
			}
		
?>	
			<label for="thumbfb_post_types-<?php echo $post_type; ?>"><input type="checkbox" value="<?php echo $post_type; ?>" id="thumbfb_post_types-<?php echo $post_type; ?>" name="thumbfb_post_types[]" <?php echo $selected; ?>/> <?php echo $cpt->labels->name; ?></label>
<?php
		
		}

	}

}
if (is_admin()) {
	$thumbfb = new Thumbnailsforbackend();
	
	add_action('admin_menu', array(&$thumbfb, 'admin_menu'), 10);
	add_action('admin_init', array(&$thumbfb, 'initialize_options'), 10);
	add_action('admin_init', array(&$thumbfb, 'add_thumbnails'), 10); //backwards compatible
}