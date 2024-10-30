<?php
/*
  Plugin Name: Kuratur Connect
  Description: The painless way to always have fresh relevant content on your blog. Curate a blog page of automated content using your favorite social media sources.
  Version: 1.1
  Author: Minh Nguyen and Kirsten Lambertsen
 *
  Author URI: http://kuratur.com
 */
 /*	Copyright 2014 Kuratur, Inc.

	This file is part of Kuratur Connect.

    Kuratur Connect is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    Kuratur Connect is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with Kuratur Connect.  If not, see http://www.gnu.org/licenses/.
*/
/*
  add_filter('the_content', 'before_show');

  function before_show($content){

  } */

global $kuratur_db_version;
$kuratur_db_version = '1.0';
global $kuraturUrl;
$kuraturUrl		=	'http://www.kuratur.com';
add_action('admin_menu', 'kuratur_create_menu');

//add_action('admin_menu','add_new_post');
function kuratur_create_menu() {
	//create custom top-level menu
	add_menu_page('Kuratur Importer', 'Kuratur', 'manage_options', __FILE__, 'add_content_kuratur', plugins_url('/images/favicon.ico', __FILE__), 500);
	add_submenu_page(__FILE__, 'Create content from Kuratur', 'Create Content', 'manage_options', __FILE__, 'add_content_kuratur');
//	add_submenu_page(__FILE__, 'Manage Kuratur Digests', 'Manage Digests', 'manage_options', __FILE__ . '_manage_digests', 'manage_digests');
}

if (!function_exists('manage_digests')) {

	function manage_digests() {
		echo 'sdfsdfsd';
		die;
	}

}


if (!function_exists('add_content_kuratur')) {
	function add_content_kuratur() {
		$imgPath = plugins_url('/images/logo.png', __FILE__);
		$imgnextPath = plugins_url('/images/next_button.png', __FILE__);
		$createPath = admin_url('/admin.php?page='.basename(dirname(__FILE__)).'/index.php', __FILE__);
		wp_enqueue_script('action_Script', plugin_dir_url(__FILE__) . 'js/action.js', false, false, true);
		$contentOb	=	$content = '';
		$keyAPI		=	'';

		if (!empty($_POST['key_api'])) {
			$keyAPI	=	trim($_POST['key_api']);
			$contents	=	getWebcontent('/contents/content/'.$keyAPI);
			if(!empty($contents['content'])){
				$contentOb	=	json_decode($contents['content']);
				if(!empty($contentOb->update_frequency)){
					$content	=	$contentOb->content;
					$_POST['kuratur_update_frequency']	=		intval($contentOb->update_frequency);
				}
			}
			$contents	=	null;
		}

		if (empty($content) || empty($_POST['key_api'])) {
			echo <<<HEADER
			<div class="kuratur_introduction" style="padding:30px 20px;text-align:center;">
				<img src="$imgPath" alt=""/><br /><br /><br /><br /><br /><br />
				<p style="text-align:center;width:100%;font-size: 16px;">
					Enter the API key from your Kuratur magazine to import it automatically as page in your WordPress site.<br />
					If you don't have a key, please go to Kuratur.com to create an account and a magazine to import.<br />
				</p><br /><br /><br /><br />
				<form action="$createPath" method="post" id="import_content_from_kuratur" style="padding:20px 200px;text-align:left;">
					<p>
						<label for="key_api">Key of digest(from Kuratur) : </label> <input type="text" id="key_api" name="key_api" style="width:500px" value="$keyAPI" />
					</p>
					<p>
						<!--label for="create_post">Import as Post&nbsp;&nbsp;</label><input type="radio" id="create_post" name="create_post" /-->
						<label for="create_page">Import as Page</label>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type="radio" id="create_page" name="create_post" value="3"/>
					</p><br /><br /><br /><br />
					<p>	<input type="submit" value="Import Content"/></p>
				</form>
			</div>
HEADER;
		} else {
			$_GET['post_type']	=	!empty($_POST['create_post'])  && $_POST['create_post']=='post' ? 'post' : 'page';
//			wp_title();
//			add_filter('wp_head', 'add_tinymce_editor');
			require_once(ABSPATH . 'wp-admin/admin.php');
			if ( !isset($_GET['post_type']) )
				$post_type = 'page';
			elseif ( in_array( $_GET['post_type'], get_post_types( array('show_ui' => true ) ) ) )
				$post_type = $_GET['post_type'];
			else
				wp_die( __('Invalid post type') );

			if(!empty($keyAPI))		$post	=	exist_magazine($keyAPI);

			$newPost		=	false;
			if(empty($keyAPI) || empty($post->ID)){
				$post	=	get_default_post_to_edit( $post_type, true );
				$newPost	=	true;
			}else{
				$post_type	=	$post->post_type;
			}

			$post->post_content		=	$content;
			if(!empty($contentOb->update_frequency))
				$post->kuratur_update_frequency					=	$contentOb->update_frequency;
			$post->page_template	=	'page-templates/full-width.php';
			$GLOBALS['post']		=	$post;
			$post_ID				=	$post->ID;

			$post_type_object = get_post_type_object( $post_type );
			$title = $post_type_object->labels->add_new_item;
			if(!$newPost){
				$title = $post_type_object->labels->edit_item;
			}


			if ( 'post' == $post_type ) {
				$parent_file = 'edit.php';
				$submenu_file = 'post-new.php';
			} elseif ( 'attachment' == $post_type ) {
				wp_redirect( admin_url( 'media-new.php' ) );
				exit;
			} else {
				$submenu_file = "post-new.php?post_type=$post_type";
				if ( isset( $post_type_object ) && $post_type_object->show_in_menu && $post_type_object->show_in_menu !== true ) {
					$parent_file = $post_type_object->show_in_menu;
					if ( ! isset( $_registered_pages[ get_plugin_page_hookname( "post-new.php?post_type=$post_type", $post_type_object->show_in_menu ) ] ) )
						$submenu_file = $parent_file;
				} else {
					$parent_file = "edit.php?post_type=$post_type";
				}
			}



			$editing = true;

			if ( ! current_user_can( $post_type_object->cap->edit_posts ) || ! current_user_can( $post_type_object->cap->create_posts ) )
				wp_die( __( 'Cheatin&#8217; uh?' ) );

			// Schedule auto-draft cleanup
			if ( ! wp_next_scheduled( 'wp_scheduled_auto_draft_delete' ) )
				wp_schedule_event( time(), 'daily', 'wp_scheduled_auto_draft_delete' );
//			$content	= json_decode($content);
			wp_enqueue_script( 'autosave' );
			// Show post form.

			include(dirname(__FILE__).'/first_part_edit.php');
		}
		return true;
	}

}

function getWebcontent($path) {
	global $kuraturUrl;
	$path	=	$kuraturUrl.$path;
	$options = array(
		CURLOPT_RETURNTRANSFER => true, // return web page
		CURLOPT_HEADER => false, // don't return headers
		CURLOPT_FOLLOWLOCATION => false, // follow redirects
		CURLOPT_ENCODING => '', // handle compressed
		CURLOPT_USERAGENT => 'include_request', // who am i
		CURLOPT_AUTOREFERER => true, // set referer on redirect
		CURLOPT_CONNECTTIMEOUT => 1200, // timeout on connect
		CURLOPT_TIMEOUT => 1200, // timeout on response
		CURLOPT_MAXREDIRS => 10, // stop after 10 redirects
//		CURLOPT_COOKIE => $strCookie,
//			CURLOPT_POST			=>	true,
//			CURLOPT_POSTFIELDS		=>	$query,
		CURLOPT_URL => $path, 
	);

	session_write_close();

	$ch = curl_init();
	curl_setopt_array($ch, $options);
	$content = curl_exec($ch);
	$err = curl_errno($ch);
	$errmsg = curl_error($ch);
	$header = curl_getinfo($ch);
	curl_close($ch);
	$header['errno'] = $err;
	$header['errmsg'] = $errmsg;
	$header['content'] = $content;
	return $header;
}

//include the Kuratur style sheet in the header
if( !function_exists( 'kuratur_styles') ) :
	function kuratur_styles() {
		$kurfeedstyles = plugins_url( '/css/kuratur-feed-styles.css', __FILE__ );
		?>
		<link rel="stylesheet" id="kurstyles" href="<?php echo $kurfeedstyles;?>" type="text/css" media="all">
		<?php
	}
endif;
add_action( 'wp_head', 'kuratur_styles' );

add_filter( 'the_content', 'update_content_from_kuratur' );
function update_content_from_kuratur($content){
	global $post;
	$kuratur_update_frequency		=	empty($post->kuratur_update_frequency) ? 12 : $post->kuratur_update_frequency;
	if(!empty($post->from_kuratur) && !empty($kuratur_update_frequency) && $post->from_kuratur_date	+ $kuratur_update_frequency*60*60 <= time()){
		$contents	=	getWebcontent('/contents/content/'.$post->from_kuratur);
		if(!empty($contents['content'])){
			$contentOb	=	json_decode($contents['content']);
			if(!empty($contentOb->update_frequency)){
				global $wpdb;
				$content	=	$contentOb->content;//$_POST['kuratur_update_frequency']	=		intval($contentOb->update_frequency);
				$wpdb->query($wpdb->prepare("UPDATE $wpdb->posts SET post_content=%s, from_kuratur_date=%d, kuratur_update_frequency=%d WHERE ID=%d",$contentOb->content,time(),intval($contentOb->update_frequency), $post->ID));
			}
		}
	}
	return $content;
}

register_activation_hook(__FILE__, 'kuratur_install');

function kuratur_install() {
	global $wpdb;
	global $kuratur_db_version;
	add_option('kuratur_db_version', $kuratur_db_version);
	@$wpdb->query("ALTER table $wpdb->posts	ADD COLUMN `from_kuratur` VARCHAR(60) NOT NULL DEFAULT ''");
	@$wpdb->query("ALTER table $wpdb->posts	ADD COLUMN `from_kuratur_date` INT(11) UNSIGNED  DEFAULT 0");
	@$wpdb->query("ALTER table $wpdb->posts	ADD COLUMN `kuratur_update_frequency` TINYINT(3)  DEFAULT 0");
//	register_uninstall_hook( __FILE__, 'kuratur_uninstall' );
}
register_deactivation_hook( __FILE__, 'kuratur_uninstall');

function kuratur_uninstall(){
	global $wpdb;
	delete_option( 'kuratur_db_version' );
	@$wpdb->query("ALTER table $wpdb->posts	DROP COLUMN `from_kuratur`");
	@$wpdb->query("ALTER table $wpdb->posts	DROP COLUMN `from_kuratur_date` ");
	@$wpdb->query("ALTER table $wpdb->posts	DROP COLUMN `kuratur_update_frequency` ");
}
add_filter( 'wp_insert_post_data' , 'filter_post_data_from_kuratur' , '99', 2 );
function filter_post_data_from_kuratur( $data , $postarr ) {
    // Change post title 
	if (!empty($_POST['key_api'])) {
		$data['from_kuratur'] = trim($_POST['key_api']);
		$data['from_kuratur_date'] = time();
	}
	if(!empty($_POST['kuratur_update_frequency'])){
		$data['kuratur_update_frequency'] = $_POST['kuratur_update_frequency'];
	}
    return $data;
}

function exist_magazine( $keyAPI ) {
	global $wpdb;

	return $wpdb->get_row( $wpdb->prepare("SELECT * FROM $wpdb->posts WHERE from_kuratur = %s AND post_status<>'trash' LIMIT 1", $keyAPI) );
}

/* for update only
function kuratur_update_db_check() {
    global $kuratur_db_version;
	echo '<pre>';
	var_dump(version_compare( get_site_option( 'kuratur_db_version' ), $kuratur_db_version, '<=' ));
	echo '</pre>';
    if (version_compare( get_site_option( 'kuratur_db_version' ), $kuratur_db_version, '<=' )) {
        kuratur_install();
    }
}
add_action( 'plugins_loaded', 'kuratur_update_db_check' );
*/