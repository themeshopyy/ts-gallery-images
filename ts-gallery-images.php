<?php
/*
 Plugin Name: TS Gallery Images
 Plugin URI: https://www.themeshopy.com/
 Description: Use to create and display gallery images.
 Author: Themeshopy
 Version: 0.1
 Author URI:https://www.themeshopy.com/
*/

define( 'TS_GALLERY_IMAGES_VERSION', '0.1' );

add_action( 'init', 'ts_gallery_images_init' );

function ts_gallery_images_init() {
	register_post_type( 'ts_gallery', array(
		'labels' => array(
			'name'               => __( 'Gallery','ts-gallery-images' ),
			'singular_name'      => __( 'Gallery','ts-gallery-images' ),
			'add_new'            => __( 'Add New Gallery','ts-gallery-images' ),
			'add_new_item'       => __( 'Add New Gallery','ts-gallery-images' ),
			'edit_item'          => __( 'Edit Gallery', 'ts-gallery-images' ),
			'new_item'           => __( 'New Gallery', 'ts-gallery-images' ),
			'view_item'          => __( 'View Gallery', 'ts-gallery-images' ),
			'search_items'       => __( 'Search Gallery', 'ts-gallery-images' ),
			'not_found'          => __( 'No Gallery found.', 'ts-gallery-images' ),
			'not_found_in_trash' => __( 'No Gallery found in trash.', 'ts-gallery-images' ),
			'parent_item_colon'  => '',
			'menu_name'          => __( 'TS Gallery', 'ts-gallery-images' ),
			),
		'public'              => true,
		'exclude_from_search' => false,
		'show_ui'             => true,
		'show_in_menu'        => true,
		'show_in_admin_bar'   => true,
		'rewrite'             => false,
		'query_var'           => false,
		'menu_position'       => '',
		'menu_icon'           => 'dashicons-format-gallery',
		'supports'            => array( 'title' ),
		) );

}

function pw_add_image_sizes() {
    add_image_size( 'ts-gallery-image-medium', 300, 300, true );
}
add_action( 'init', 'pw_add_image_sizes' );
 
function pw_show_image_sizes($sizes) {
    $sizes['ts-gallery-image-medium'] = __( 'Custom Thumb', 'pippin' );
 
    return $sizes;
}
add_filter('image_size_names_choose', 'pw_show_image_sizes');


// Including the CSS and JS for the front end
add_action('wp_enqueue_scripts', 'ts_gallery_images_callback_for_setting_up_scripts');
function ts_gallery_images_callback_for_setting_up_scripts() {
	wp_enqueue_script( 'pretty-custom-js', plugins_url( '/js/jquery.prettycustom.js', __FILE__ ), array('jquery') );
	wp_enqueue_script( 'pretty-photo-js', plugins_url( '/js/jquery.prettyPhoto.js', __FILE__ ), array('jquery') );
	
    wp_enqueue_style( 'prettyPhoto-css', plugins_url( 'css/prettyPhoto.css', __FILE__ ), '', '1.0' );

}


function ts_gallery_images_metabox_enqueue($hook) {
	if ( 'post.php' === $hook || 'post-new.php' === $hook ) {
		wp_enqueue_script('ts-gallery-images-metabox', plugin_dir_url( __FILE__ ) . '/js/vw-gm.js', array('jquery', 'jquery-ui-sortable'));
		wp_enqueue_style('ts-gallery-images-metabox', plugin_dir_url( __FILE__ ) . '/css/vw-gm.css');

		global $post;
		if ( $post ) {
			wp_enqueue_media( array(
					'post' => $post->ID,
				)
			);
		}

	}
}

add_action('admin_enqueue_scripts', 'ts_gallery_images_metabox_enqueue');

function ts_gallery_images_add_gallery_metabox($post_type) {
	$types = array('ts_gallery');

	if (in_array($post_type, $types)) {
		add_meta_box(
			'ts-gallery-image-metabox',
			__( 'Gallery Images', 'ts-gallery-images' ),
			'ts_gallery_images_meta_callback',
			$post_type,
			'normal',
			'high'
			);
	}
}

add_action('add_meta_boxes', 'ts_gallery_images_add_gallery_metabox');

function ts_gallery_images_meta_callback( $post ) {
	wp_nonce_field( basename( __FILE__ ), 'ts_gallery_images_meta_nonce' );
	$ids = get_post_meta( $post->ID, 'ts_gallery_images_gal_id', true );

	?>
	<table class="form-table">
		<tr>
			<td>
				<a class="gallery-add button" href="#" data-uploader-title="<?php esc_attr_e( 'Add image(s) to gallery', 'ts-gallery-images' ); ?>" data-uploader-button-text="<?php esc_attr_e( 'Add image(s)', 'ts-gallery-images' ); ?>"><?php esc_html_e( 'Add image(s)', 'ts-gallery-images' ); ?></a>

				<ul id="ts-gallery-images-item-list">
					<?php if ( $ids ) : foreach ( $ids as $key => $value ) : $image = wp_get_attachment_image_src( $value ); ?>

						<li>
							<input type="hidden" name="ts_gallery_images_gal_id[<?php echo $key; ?>]" value="<?php echo $value; ?>">
							<img class="image-preview" src="<?php echo esc_url( $image[0] ); ?>">
							<a class="change-image button button-small" href="#" data-uploader-title="<?php esc_attr_e( 'Change image', 'ts-gallery-images' ) ; ?>" data-uploader-button-text="<?php esc_attr_e( 'Change image', 'ts-gallery-images' ) ; ?>"><?php esc_html_e( 'Change image', 'ts-gallery-images' ) ; ?></a><br>
							<small><a class="remove-image" href="#"><?php esc_html_e( 'Remove image', 'ts-gallery-images' ) ; ?></a></small>
						</li>

					<?php endforeach;
					endif; ?>
				</ul>
			</td>
		</tr>
	</table>
	<?php
}

function ts_gallery_images_meta_save($post_id) {
	if (!isset($_POST['ts_gallery_images_meta_nonce']) || !wp_verify_nonce($_POST['ts_gallery_images_meta_nonce'], basename(__FILE__))) {
		return;
	}

	if (!current_user_can('edit_post', $post_id)) {
		return;
	}

	if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
		return;
	}

	if(isset($_POST['ts_gallery_images_gal_id'])) {
		$sanitized_values = array_map('intval', $_POST['ts_gallery_images_gal_id']);
		update_post_meta($post_id, 'ts_gallery_images_gal_id', $sanitized_values );
	} else {
		delete_post_meta($post_id, 'ts_gallery_images_gal_id');
	}
}
add_action('save_post', 'ts_gallery_images_meta_save');

function ts_gallery_images_get_custom_post_type_template( $single_template ) {
	global $post;
	if ($post->post_type == 'ts_gallery') {
		if ( file_exists( get_template_directory() . '/page-template/gallery.php' ) ) {
			$single_template = get_template_directory() . '/page-template/gallery.php';
		}
	}
	return $single_template;
}

add_filter( 'single_template', 'ts_gallery_images_get_custom_post_type_template' );

/*Shortcode for Gallery*/
function ts_gallery_images_gallery_show($gallery_id,$numberofitem, $bootstraponecolsize) {
	// add_thickbox();
	$get_post_id = isset( $gallery_id['ts_gallery'] ) ? absint( $gallery_id['ts_gallery'] ) : 0;
	$numberofitem = isset( $gallery_id['numberofitem'] ) ? absint( $gallery_id['numberofitem'] ) : 8;
	$bootstraponecolsize = isset( $gallery_id['bootstraponecolsize'] ) ? absint( $gallery_id['bootstraponecolsize'] ) : 2;

	if ( ! $get_post_id ) {
		return;
	}

	$images = get_post_meta($get_post_id, 'ts_gallery_images_gal_id', true);

	$res = '';
	if(empty($images)){
		$res = '<p>' . esc_html__( 'No Image Found', 'ts-gallery-images' ) . '</p>';
	}
	else{
		$gal_i=1;
		$res .= '<ul class="ts_gallery_front row clearfix">';
		foreach ($images as $image) {
			global $post;
			$image_uri_medium = wp_get_attachment_image( $image, 'ts-gallery-image-medium' );
			$image_uri_large = wp_get_attachment_image_url( $image, 'full' );
			$full = wp_get_attachment_link($image, 'large');
			$attachment_title = get_the_title($image);
			$res .= '<li class="col-md-'.$bootstraponecolsize.' col-sm-4 col-6 p-0">
			<a href="'.$image_uri_large.'" rel="prettyPhoto[gallery_name]" title="'.$attachment_title.'">'.$image_uri_medium.'<div class="icon_overlay"><i class="fas fa-plus"></i></div></a>
			</li>';
			if($gal_i == $numberofitem) {
				break;
			}
			$gal_i++;
		}
		$res .= '</ul>';
	}

	return $res;
}

add_shortcode( 'ts-galleryshow', 'ts_gallery_images_gallery_show' );
?>