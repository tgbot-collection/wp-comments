<?php
/*
Plugin Name: WordPress Comments Telegram Bot
Plugin URI: https://github.com/BennyThink/MemorialDay
Description: A telegram bot
Version: 0.0.1
Author: Benny
Author URI: http://www.bennythink.com
*/

/*  Copyright 2020  Benny (benny.think@gmail.com)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation; either version 2 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program; if not, write to the Free Software
    Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/
//This plugin is designed for WordPress


date_default_timezone_set( 'Asia/Shanghai' );


add_action( 'comment_post', 'show_message_function', 10, 3 );
function show_message_function( $comment_ID, $comment_approved, $comment_obj ) {

	// fill out fields
	$comment_obj['comment_ID']         = $comment_ID;
	$comment_obj['comment_website']    = get_site_url();
	$comment_obj['comment_post_title'] = get_the_title( $comment_obj['comment_post_ID'] );
	$comment_obj['post_link']          = get_permalink( $comment_obj['comment_post_ID'] );
	$comment_obj['comment_permlink']   = get_comment_link( $comment_ID );
	//approved and not admin
	if ( 1 === $comment_approved && ! is_super_admin() ) {
		sendMessage( $comment_obj, get_option( 'wptgbot' ) );
	}
}


function sendMessage( $comment_data, $options ) {

	function a_link( $url, $text = null ) {
		$text = $text ? $text : $url;

		return sprintf( '<a href="%s">%s</a>', $url, $text );
	}

	$token = $options["tg_token"];
	$uid   = $options["tg_uid"];
	$proxy = $options["tg_proxy"];
	$api   = "https://api.telegram.org/bot$token/sendMessage";

	$text     = sprintf( "A new comment on %s
    
Title: %s
User: %s (IP: %s)
Email: %s
Website: %s
Comment:
%s

See all comments here:
%s

Permanent  url: %s

",
		a_link( $comment_data["comment_website"], get_bloginfo() ),
		$comment_data["comment_post_title"],
		$comment_data["comment_author"], $comment_data["comment_author_IP"],
		$comment_data["comment_author_email"],
		a_link( $comment_data["comment_author_url"] ),
		$comment_data["comment_content"],
		a_link( $comment_data['post_link'] . '#comments' ),
		a_link( $comment_data['comment_permlink'] )
	);
	$postdata = http_build_query( array( 'chat_id' => $uid, 'parse_mode' => "html", "text" => $text ) );
	$opts     = array(
		'http' => array(
			'proxy'   => 'tcp://127.0.0.1:1087',
			'method'  => 'POST',
			'header'  => 'Content-type: application/x-www-form-urlencoded',
			'content' => $postdata
		)
	);
	if ( $proxy ) {
		$opts["http"]["proxy"] = "tcp://$proxy";
	} else {
		unset( $opts["http"]["proxy"] );
	}

	$context = stream_context_create( $opts );

	file_get_contents( $api, false, $context );
	file_put_contents( '1.txt', print_r( $text, true ) );


}

add_filter( 'plugin_action_links', 'bt_tgbot_settings', 10, 2 );
function bt_tgbot_settings( $links, $file ) {
	static $this_plugin;
	if ( ! $this_plugin ) {
		$this_plugin = plugin_basename( __FILE__ );
	}

	if ( $file == $this_plugin ) {
		$settings_link = '<a href="' . wp_nonce_url( "admin.php?page=wptgbot" ) . '">Settings</a>';
		array_unshift( $links, $settings_link );
	}

	return $links;
}


add_action( 'admin_menu', 'bt_tgbot_admin_add_page' );
function bt_tgbot_admin_add_page() {
	add_options_page(
		'wp tgbot 设置页面',
		'wptgbot 设置',
		'manage_options',
		'wptgbot',
		'bt_tgbot_options_page' );
}

function bt_tgbot_options_page() {
	?>
    <div>
        <h2>WordPress Comments Telegram Bot</h2>
        Setup bot token and uid
        <form action="options.php" method="post">
			<?php settings_fields( 'wptgbot' ); ?>
			<?php do_settings_sections( 'plugin' ); ?>

            <input name="Submit" type="submit" value="<?php esc_attr_e( 'Save Changes' ); ?>"/>
        </form>
    </div>

	<?php
}


add_action( 'admin_init', 'bt_tgbot_admin_init' );
function bt_tgbot_admin_init() {
	register_setting(
		'wptgbot',
		'wptgbot' );

	add_settings_section(
		'plugin_main',
		'Bot Token',
		'bt_tgbot_token',
		'plugin'
	);
	add_settings_section(
		'plugin_main2',
		'Your user id',
		'bt_tgbot_uid',
		'plugin'
	);
	add_settings_section(
		'plugin_main3',
		'Proxy, http only',
		'bt_tgbot_proxy',
		'plugin'
	);


}

function bt_tgbot_token() {
	$options = get_option( 'wptgbot' );
	echo "<input id='tg_uid' name='wptgbot[tg_token]' type='text' value='{$options['tg_token']}' />";

}

function bt_tgbot_uid() {
	$options = get_option( 'wptgbot' );
	echo "<input id='tg_uid' name='wptgbot[tg_uid]' type='text' value='{$options['tg_uid']}' />";

}

function bt_tgbot_proxy() {
	$options = get_option( 'wptgbot' );
	echo "Leave blank if your don't need it.<br>";

	echo "<input id='tg_proxy' name='wptgbot[tg_proxy]' type='text' value='{$options['tg_proxy']}' 
placeholder='127.0.0.1:1087'
/>";
	echo "<br><br>";

}
