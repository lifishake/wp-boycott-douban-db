<?php

/**
 * 简陋,由工具做成,少量修改
 * 工具URL: http://wpsettingsapi.jeroensormani.com/
*/

add_action( 'admin_menu', 'bddb_add_admin_menu' );
add_action( 'admin_init', 'bddb_settings_init' );
require_once( BDDB_PLUGIN_DIR . '/class/class-bddb-settings.php');
define('BDDB_OPTION_FILE_NANE', 'wp-boycott-douban-db/bddb-options.php');

function bddb_add_admin_menu(  ) {
  add_menu_page( 'bddb settings', 'bddb settings', 'manage_options', __FILE__, 'bddb_options_page', 'dashicons-screenoptions' );
}


/*
支持的功能列表
01. 改进的功能摘要

*/
function bddb_settings_init(  ) {
	$s = new BDDB_Settings();
	$arg = array(
		'sanitize_callback' => array($s, 'sanitize_options'),
		'default' => $s->default_options(),
	);
  register_setting( 'bddb_settings_group', 'bddb_settings', $arg);

  add_settings_section(
  'bddb_pluginPage_section',
  '总体设定',
  'bddb_settings_section_callback',
  'bddb_option_tab'
  );

  add_settings_section(
  'bddb_movie_section',
  '电影选项',
  'bddb_settings_section_callback',
  'bddb_movie_tab'
  );

  add_settings_section(
  'bddb_book_section',
  '书籍选项',
  'bddb_settings_section_callback',
  'bddb_book_tab'
  );
  
  add_settings_section(
  'bddb_game_section',
  '游戏选项',
  'bddb_settings_section_callback',
  'bddb_game_tab'
  );
  
  add_settings_section(
  'bddb_album_section',
  '专辑选项',
  'bddb_settings_section_callback',
  'bddb_album_tab'
  );

  //01
  add_settings_field(
  'basic_folder_setting',
  '目录设定',
  'bddb_basic_setting_render',
  'bddb_option_tab',
  'bddb_pluginPage_section'
  );
  
  add_settings_field(
  'basic_poster_setting',
  '图片设定',
  'bddb_poster_render',
  'bddb_option_tab',
  'bddb_pluginPage_section'
  );
  add_settings_field(
  'basic_order_setting',
  '共通順序设定',
  'bddb_general_order_render',
  'bddb_option_tab',
  'bddb_pluginPage_section'
  );

  //08
    //-1
	
	add_settings_field(
		'bddb_m_omdb_key',
		'OMDB Auth KEY',
		'bddb_m_omdb_key_render',
		'bddb_movie_tab',
		'bddb_movie_section'
	);
  
  add_settings_field(
  'bddb_b_max_serial_count',
  '系列书籍有效本数',
  'bddb_b_max_serial_count_render',
  'bddb_book_tab',
  'bddb_book_section'
  );
  
  add_settings_field(
  'bddb_g_giantbomb_key',
  'GiantBomb Auth KEY',
  'bddb_g_giantbomb_key_render',
  'bddb_game_tab',
  'bddb_game_section'
  );
  
  add_settings_field(
  'bddb_a_poster_setting',
  '专辑预留',
  'bddb_test_field_render',
  'bddb_album_tab',
  'bddb_album_section'
  );

}

function bddb_basic_setting_render(  ) {
  //03
	global $global_option_class;
	$options = $global_option_class->get_options();
  ?>
  <span>当前TAX版本号：</span>
  <input type='text' name='bddb_settings[tax_version]' readonly='readonly' size='24' value='<?php echo $options['tax_version']; ?>'/><br />
  <span>当前TYPE版本号：</span>
  <input type='text' name='bddb_settings[type_version]' readonly='readonly' size='24' value='<?php echo $options['type_version']; ?>'/><br />
  <span>收藏页使用Lazyload：</span>
  <input type='checkbox' name='bddb_settings[local_lazyload]' <?php checked( $options['local_lazyload'], 1 ); ?> value='1'/>
  <?php
}


function bddb_basic_setting_render1(  ) {
  //03
	global $global_option_class;
	$options = $global_option_class->get_options();
  ?>
  <span>    图片缓存路径：</span>
  <input type='text' name='bddb_settings[default_folder]' size='24' value='<?php echo $options['default_folder']; ?>'/><br />
  <span>    默认排序：</span>
  <select name="bddb_settings[primary_common_order] size=20" id="id_primary_common_order">
  <?php
	$strs = array("1111","2222","3333","4444");
	foreach ($strs as $str) {
		echo sprintf("\n\t<option value='%s'>%s</option>", $str, $str);
	}
  ?>
  </select>
  <?php
}

function bddb_poster_render() {
	global $global_option_class;
	$options = $global_option_class->get_options();
?>
	<span>图像宽度：</span><input type='text' name='bddb_settings[poster_width]' size='24' value='<?php echo $options['poster_width']; ?>'/><br />
	<span>缩略图宽度：</span><input type='text' name='bddb_settings[thumbnail_width]' size='24' value='<?php echo $options['thumbnail_width']; ?>'/></br>
	<span>海报宽高比: 1:1.48</span>
<?php
}

function bddb_general_order_render() {
	global $global_option_class;
	$options = $global_option_class->get_options();
	$t = new BDDB_Common_Template();
	$option_value = '';
	for($i=0;$i<10;$i++){
		$sel_list .= sprintf("\n\t<option value='%02d'>%02d</option>",$i,$i);
	}
  /*
  TBD
	foreach ($options['general_order'] as $key=>$ci) {
		$value = $ci['priority'];
		if (empty($value)) {
			$value = false;
		}
		printf('<span>%1$s:</span><select name="bddb_settings[general_order][%1$s] size=20" id="id_general_common_order_%1$s" value="%2$s">%3$s</select></br>',
		$key, $value, $sel_list);
	}*/
}


function bddb_m_omdb_key_render()
{
  //08
	global $global_option_class;
	$options = $global_option_class->get_options();
?>
	<input type='text' name='bddb_settings[m_omdb_key]' size='64' value='<?php echo $options['m_omdb_key']; ?>'/>
<?php
}

function bddb_b_max_serial_count_render()
{
  //08
	global $global_option_class;
	$options = $global_option_class->get_options();
?>
	<input type='text' name='bddb_settings[b_max_serial_count]' size='64' value='<?php echo $options['b_max_serial_count']; ?>'/>
<?php
}


function bddb_g_giantbomb_key_render()
{
  //08
	global $global_option_class;
	$options = $global_option_class->get_options();
?>
	<input type='text' name='bddb_settings[g_giantbomb_key]' size='64' value='<?php echo $options['g_giantbomb_key']; ?>'/>
<?php
}

function bddb_settings_section_callback(  ) {

  echo '<span>一些基本设定项目，某些可以被子项目覆盖</span>';

}

function bddb_extra_section_callback() {
    echo '<span>一些基本设定项目，抄自多个插件</span>';
}

function bddb_options_page(  ) {
	global $global_option_class;
	$global_option_class = new BDDB_Settings();
        if( isset( $_GET[ 'tab' ] ) ) {
            $active_tab = $_GET[ 'tab' ];
        } else {
            $active_tab = 'tab_option';
        }
        ?>
    <div id="bddb_page_content" class="wrap bddb-option" >
  <h1><span>B</span>oycott <span>D</span>ouban <span>D</span>ata<span>b</span>ase</h1>
  <div class="description">This is description of the page.</div>
            <?php settings_errors(); ?>

            <h2 class="nav-tab-wrapper">
                <a href="?page=<?php echo BDDB_OPTION_FILE_NANE;?>&tab=tab_option" class="nav-tab <?php echo $active_tab == 'tab_option' ? 'nav-tab-active' : ''; ?>">基本功能</a>
                <a href="?page=<?php echo BDDB_OPTION_FILE_NANE;?>&tab=tab_movie" class="nav-tab <?php echo $active_tab == 'tab_movie' ? 'nav-tab-active' : ''; ?>">影片设定</a>
                <a href="?page=<?php echo BDDB_OPTION_FILE_NANE;?>&tab=tab_book" class="nav-tab <?php echo $active_tab == 'tab_book' ? 'nav-tab-active' : ''; ?>">书籍设定</a>
				<a href="?page=<?php echo BDDB_OPTION_FILE_NANE;?>&tab=tab_game" class="nav-tab <?php echo $active_tab == 'tab_game' ? 'nav-tab-active' : ''; ?>">游戏设定</a>
				<a href="?page=<?php echo BDDB_OPTION_FILE_NANE;?>&tab=tab_album" class="nav-tab <?php echo $active_tab == 'tab_album' ? 'nav-tab-active' : ''; ?>">专辑设定</a>
            </h2>
     <form action='options.php' method='post'>
  <?php
  settings_fields( 'bddb_settings_group' );
  switch($active_tab) {
      case 'tab_option':
      default:
        do_settings_sections( 'bddb_option_tab' );
        break;
      case 'tab_movie':
        do_settings_sections( 'bddb_movie_tab' );
        break;
      case 'tab_book':
        do_settings_sections( 'bddb_book_tab' );
        break;
      case 'tab_game':
        do_settings_sections( 'bddb_game_tab' );
        break;
      case 'tab_album':
        do_settings_sections( 'bddb_album_tab' );
        break;
  }
  submit_button();
  ?>

  </form>
</div>
  <?php

}

function bddb_test_field_render() {
    ?>
    <span>WP->is_ssl = <?php echo is_ssl()? 'YES':'NO'; ?> "wp_http_supports( array( 'ssl' ) )" = <?php echo wp_http_supports( array( 'ssl' ) )?'YES':'NO'; ?> </span>
    <?php
}

?>
