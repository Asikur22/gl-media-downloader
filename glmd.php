<?php
/**
 * Plugin Name:       GL Media Downloader
 * Plugin URI:        http://greenlifeit.com/plugins
 * Description:       A WordPress plugin to download and insert image to WP Media.
 * Version:           1.0.0
 * Author:            Asiqur Rahman
 * Author URI:        https://asique.net/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       glmd
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

function glmd_form() {
	?>
	<div class="glmd-wrap" style="text-align: center;">
		<div class="glmd-input">
			<div style="margin-bottom: 15px;">or</div>
			<input name="url" type="url" id="glmd-url" placeholder="Image Link..." style="min-width: 300px;">
		</div>
		<div class="glmd-submit" style="margin-top: 15px;">
			<input type="hidden" name="glmd_nonce" value="<?php echo wp_create_nonce( 'glmd' ); ?>">
			<button type="button" id="glmd-submit-btn" class="button button-primary">Download & Add to Media</button>
			<div id="glmd-message" style="max-width: 300px; margin: 15px auto 0;"></div>
		</div>
	</div>
	<script>
		var formButton = document.getElementById( 'glmd-submit-btn' );
		
		formButton.addEventListener( 'click', function ( event ) {
			event.preventDefault();
			
			var urlInput = document.getElementById( 'glmd-url' );
			if ( urlInput.value.length == 0 ) {
				alert( 'Please add a valid image link' );
				return false;
			}
			
			var message = document.getElementById( 'glmd-message' );
			message.innerHTML = '<img src="<?php echo includes_url( 'images/spinner.gif' ); ?>" alt="Downloading...">';
			message.classList = 'downloading';
			
			var ajaxurl = '<?php echo admin_url( 'admin-ajax.php' ); ?>';
			var xhttp = new XMLHttpRequest();
			
			var formData = new FormData();
			formData.append( 'action', 'glmd_action' );
			formData.append( 'url', urlInput.value );
			formData.append( 'glmd_nonce', document.querySelector( 'input[name="glmd_nonce"]' ).value );
			
			xhttp.open( "POST", ajaxurl );
			xhttp.send( formData );
			
			xhttp.onreadystatechange = function () {
				if ( this.readyState === 4 && this.status === 200 ) {
					var data = JSON.parse( this.responseText );
					if ( data.success === true ) {
						message.classList.add( 'updated' );
						message.innerHTML = data.data;
						urlInput.value = '';
						
						// refresh media content
						if ( wp.media.frame.content.get() !== null ) {
							wp.media.frame.content.mode( 'browse' ).get().collection.props.set( {
								ignore: (
									+ new Date()
								)
							} );
							wp.media.frame.state().get( 'selection' ).add( wp.media.attachment( data.image ) );
						} else {
							wp.media.frame.library.props.set( {
								ignore: (
									+ new Date()
								)
							} );
						}
					} else {
						message.classList.add( 'error' );
						message.innerHTML = data.data;
					}
				}
			};
		} );
	</script>
	<?php
}

add_action( 'post-plupload-upload-ui', 'glmd_form' );

function glmd_action() {
	if ( isset( $_POST['glmd_nonce'] ) ) {
		if ( ! wp_verify_nonce( $_POST['glmd_nonce'], 'glmd' ) ) {
			wp_send_json_error( 'You don\'t have permission to upload image' );
			wp_die();
		}
	} else {
		wp_send_json_error( 'No nonce found!' );
		wp_die();
	}
	
	if ( isset( $_POST['url'] ) ) {
		$image = media_sideload_image( esc_url_raw( $_POST['url'] ), null, null, 'id' );
		if ( is_wp_error( $image ) ) {
			wp_send_json_error( $image->get_error_message() );
		} else {
			wp_send_json( array( 'success' => true, 'data' => 'Upload Successful.', 'image' => $image ) );
		}
	} else {
		wp_send_json_error( 'No link found!' );
	}
	
	wp_die();
}

add_action( 'wp_ajax_glmd_action', 'glmd_action' );
add_action( 'wp_ajax_nopriv_glmd_action', '__return_zero' );
