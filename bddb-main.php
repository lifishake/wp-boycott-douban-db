<?php

/**
 * Plugin Name: Boycott Douban Database
 * Plugin URI:  http://pewae.com
 * GitHub Plugin URI: https://github.com/lifishake/wp-boycott-douban-db
 * Description: 抵制源于喜爱。既然无法改变它，那就自己创造一个。
 * Author:      lifishake
 * Author URI:  http://pewae.com
 * Version:     1.1.2
 * Date:        2025-11-16
 * License:     GNU General Public License 3.0+ http://www.gnu.org/licenses/gpl.html
 */

/*宏定义*/
define('BDDB_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define('BDDB_PLUGIN_DIR', plugin_dir_path( __FILE__ ) ) ;
define ('BDDB_PLUGIN_BASE_NAME', plugin_basename(__FILE__));
//define('BDDB_GALLERY_URL',home_url('/',is_ssl()?'https':'http').'wp-content/poster_gallery/');
//define('BDDB_GALLERY_DIR', ABSPATH.'wp-content/poster_gallery/');
define('BDDB_TAX_VER', '20220101');
define('BDDB_META_VER', '20230210');

register_activation_hook( __FILE__, 'bddb_plugin_activation' );
register_deactivation_hook( __FILE__,'bddb_plugin_deactivation' );
register_uninstall_hook(__FILE__, 'bddb_plugin_uninstall');

require_once( BDDB_PLUGIN_DIR . '/bddb-funcs.php');
require_once( BDDB_PLUGIN_DIR . '/class/class-bddb-editor.php');
require_once( BDDB_PLUGIN_DIR . '/class/class-bddb-douban-fecther.php');
require_once( BDDB_PLUGIN_DIR . '/class/class-bddb-fecther.php');
require_once( BDDB_PLUGIN_DIR . '/class/class-bddb-image.php');
require_once( BDDB_PLUGIN_DIR . '/class/class-bddb-templates.php');
require_once( BDDB_PLUGIN_DIR . '/class/class-bddb-settings.php');
require_once( BDDB_PLUGIN_DIR . '/class/class-bddb-statics.php');
require_once( BDDB_PLUGIN_DIR . '/class/class-bddb-types-list-table.php');

function bddb_is_debug_mode()
{
	if (isset( $_SERVER['PHPRC'] ) && strpos($_SERVER['PHPRC'], "xampp" ) > 0)
	{
		return 1;
	}
	return 0;
}

/* 打log用 */
function bddb_log()
{
	//echo '<pre>'.$any.'</pre>';
	print_r(debug_backtrace());
}

/*创建目录*/
function bddb_create_dir($dir) {
	if (file_exists ($dir)) {
		if (! is_writeable ( $dir )) {
			@chmod ( $dir, '511' );
		}
	} else {
		@mkdir ( $dir, '511', true );
	}
}

/*创建必须文件*/
function bddb_create_nopic($width, $height) {
	if ($width == $height){
		$src = sprintf("%s/img/nocover_square.png", BDDB_PLUGIN_DIR);
	}else{
		$src = sprintf("%s/img/nocover_oblone.png", BDDB_PLUGIN_DIR);
	}
	$dest = sprintf("%s/img/nocover_%s_%s.png", BDDB_PLUGIN_DIR, $width, $height);
	if (file_exists ($dest)) {
		return;
	}
	$image = new Bddb_SimpleImage();
	$image->load($src);
	$image->resize($width,$height);
	$image->save($dest);
}

/*插件激活*/
function bddb_plugin_activation() {
	
}

/*检查路径，检查默认文件*/
function bddb_check_paths(){
	$dir_o = BDDB_Settings::getInstance()->get_default_folder();
	$gallery_dir= ABSPATH.$dir_o;
	$thumb_dir = $gallery_dir."thumbnails/";
	bddb_create_dir($gallery_dir);
	bddb_create_dir($thumb_dir);
	foreach (BDDB_Statics::get_valid_types() as $type) {
		bddb_create_nopic(BDDB_Settings::getInstance()->get_poster_width($type),BDDB_Settings::getInstance()->get_poster_height($type));
		bddb_create_nopic(BDDB_Settings::getInstance()->get_thumbnail_width($type),BDDB_Settings::getInstance()->get_thumbnail_height($type));		
	}
}

/*插件反激活*/
function bddb_plugin_deactivation()
{
}

/*插件卸载*/
function bddb_plugin_uninstall()
{
}

/*配置画面*/
if (is_admin())
{
	require_once( BDDB_PLUGIN_DIR . '/bddb-options.php');
}

/*变量初期化， 更早*/
add_action('plugins_loaded', 'bddb_init', 11);
function bddb_init()
{
	bddb_check_paths();
	add_action('admin_init','bddb_admin_init');
	add_action('init', 'bddb_init_actions', 11);
}

/* Plugin页面追加配置选项 */
function bddb_init_actions()
{   
	BDDB_Statics::check_db();
	BDDB_Statics::check_taxonomies();
	BDDB_Statics::check_types();
	//js和css加载
	add_action( 'wp_enqueue_scripts', 'bddb_scripts' );
	//Quick Tag追加
	add_shortcode('bddbr', 'qt_show_record');
	//ajax 显示 page 回调
	add_action('wp_ajax_bddb_next_gallery_page', 'ajax_get_gallery_page');
	add_action('wp_ajax_nopriv_bddb_next_gallery_page', 'ajax_get_gallery_page');
	//修改主题对应的模板名
	add_filter('page_template_hierarchy', 'bddb_add_theme_template_supported');
}

/**
 * @brief	解析豆瓣页面内容。
 * @param	string	$pic_mass	批量图片地址
 * @param	string	$default	默认图片地址
 * @return 	array
 * @since 	0.6.1
*/
function bddb_add_theme_template_supported($templates) {
	$bfound = false;
	foreach($templates as $template) {
		foreach(BDDB_Statics::get_valid_types() as $bddb_type) {
			$imaged_name = sprintf("page-%ssgallery.php", $bddb_type);
			if ($imaged_name === $template) {
				$bfound = true;
				break;
			}
		}
		if ($bfound) {
			break;
		}
	}
	if ($bfound) {
		array_unshift($templates, "page-bddbgallery.php");
	}
	return $templates;
}

function qt_show_record($atts, $content = null) {
	extract( $atts );
	$post_type = get_post_type($id);
	$ret = '';
	if ('book' == $post_type) {
		$ret = BDDB_Book::getInstance()->show_record($atts, $content);
	} elseif('movie' == $post_type) {
		$ret = BDDB_Movie::getInstance()->show_record($atts, $content);
	} elseif('game' == $post_type) {
		$ret = BDDB_Game::getInstance()->show_record($atts, $content);
	} elseif('album' == $post_type) {
		$ret = BDDB_Album::getInstance()->show_record($atts, $content);
	}
	return $ret;
}

/*取下一页的ajax回调函数*/
function ajax_get_gallery_page() {
	if (!isset($_POST['nonce']) || !isset($_POST['pid']) || !isset($_POST['type']) || !isset($_POST['nobj']) ) {
		wp_die();
	}
	if ('book' == $_POST['type']) {
		BDDB_Book::getInstance()->ajax_get_gallery_page();
	} elseif('movie' == $_POST['type']) {
		BDDB_Movie::getInstance()->ajax_get_gallery_page();
	} elseif('game' == $_POST['type']) {
		BDDB_Game::getInstance()->ajax_get_gallery_page();
	} elseif('album' == $_POST['type']) {
		BDDB_Album::getInstance()->ajax_get_gallery_page();
	}
}

/*取豆瓣信息的ajax回调函数*/
//TODO 放进类中
function ajax_douban_fetch() {
	$resp = array('title' => 'here is the title', 'content' => 'finished') ;
	if (!isset($_GET['nonce']) || !isset($_GET['id']) || !isset($_GET['ptype']) || !isset($_GET['doulink']) ) {
		wp_die();
	}
	if ( !wp_verify_nonce($_GET['nonce'],"douban-spider-".$_GET['id'])) {
		wp_die();
	}
	if (!BDDB_Statics::is_valid_type($_GET['ptype'])){
		wp_die();
	}
	$post_id = $_GET['id'];
	$link = $_GET['doulink'];
	$got = BDDB_Fetcher::fetch($link, $_GET['ptype']);
	$resp['result'] = $got['content'];
	wp_send_json($resp) ;
	wp_die();

}

//后台初始化
function bddb_admin_init() {
	add_action( 'admin_enqueue_scripts', 'bddb_admin_scripts' );
	add_action( 'wp_ajax_bddb_douban_fetch', 'ajax_douban_fetch' );
	//清理多余图片的ajax回调函数
	add_action( 'wp_ajax_bddb_thumb_clear', 'ajax_clear_duplicate_thumbs');
	//重新刷新目录并显示的ajax回调函数
	add_action( 'wp_ajax_bddb_rescan_thumb_folder', 'ajax_scan_thumb_folder');
	BDDB_Statics::admin_init();
	BDDB_Typed_List::admin_init();
	BDDB_Editor_Factory::admin_init();
}

//js和css初始化
function bddb_scripts() {
	if (is_page(array('moviesgallery','booksgallery','gamesgallery','albumsgallery'))) {
		remove_action( 'wp_head','print_emoji_detection_script',7);
		remove_action( 'wp_print_styles', 'print_emoji_styles');
		wp_enqueue_script( 'bddb-fancy', BDDB_PLUGIN_URL . 'js/fancybox.umd.js', array(), '20211123', true );
		wp_enqueue_script( 'bddb-color-thief', BDDB_PLUGIN_URL . 'js/color-thief.js', array(), '20221128', true );
		wp_enqueue_script( 'bddb-fancy-func', BDDB_PLUGIN_URL . 'js/fancygallery.js', array(), '20251108', true );
		wp_localize_script( 'bddb-fancy-func', 'ajaxurl', array('url'=>admin_url('admin-ajax.php')));
		wp_enqueue_style( 'bddb-boxstyle', BDDB_PLUGIN_URL . 'css/fancybox.css', array(), '20220829' );
		$css = '';
		$rate = floatval(BDDB_Settings::getInstance()->get_poster_height(false)/BDDB_Settings::getInstance()->get_poster_width(false));

		if (is_page('booksgallery')) {
			$rate = floatval(BDDB_Settings::getInstance()->get_poster_height('book')/BDDB_Settings::getInstance()->get_poster_width('book'));
		}
		elseif (is_page('moviesgallery')) {
			$rate = floatval(BDDB_Settings::getInstance()->get_poster_height('movie')/BDDB_Settings::getInstance()->get_poster_width('movie'));
		}
		elseif (is_page('gamesgallery')) {
			$rate = floatval(BDDB_Settings::getInstance()->get_poster_height('game')/BDDB_Settings::getInstance()->get_poster_width('game'));
		}
		elseif (is_page('albumsgallery')) {
			$rate = floatval(BDDB_Settings::getInstance()->get_poster_height('album')/BDDB_Settings::getInstance()->get_poster_width('album'));
		}
		$thumbs_height = intval(80 * $rate);
		$css = ".fancybox__container {	--fancybox-thumbs-width: 80px;	--fancybox-thumbs-height: {$thumbs_height}px;  }";
		wp_add_inline_style('bddb-boxstyle', $css);

		wp_enqueue_style( 'bddb-gallery-boxstyle', BDDB_PLUGIN_URL . 'css/bddb-fancy-gallery.css', array(), '20241010' );
		
	}
	wp_enqueue_style( 'bddb-style-front', BDDB_PLUGIN_URL . 'css/bddb.css', array(), '20230608' );
}

/**
 * @brief	统一处理后台相关的脚本
 * @since 	0.0.1
 * @version 1.0.4
*/
function bddb_admin_scripts() {
	wp_enqueue_script('bddb-js-admin', BDDB_PLUGIN_URL . 'js/bddb-admin.js', array('jquery', 'quicktags'), '20251108', true);
	//wp_localize_script('bddb-js-admin', 'nomouse_names', array());
	wp_enqueue_style('bddb-adminstyle', BDDB_PLUGIN_URL . 'css/bddb-admin.css', array(), '20220526');
	wp_deregister_style('open-sans');
	wp_register_style('open-sans', false);
}

