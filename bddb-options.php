<?php

/**
 * BDDB后台配置页面
 * @since   0.0.1
 * @version 0.8.6
 * 工具URL: http://wpsettingsapi.jeroensormani.com/
*/

//增加后台配置菜单
add_action( 'admin_menu', 'bddb_add_admin_menu' );
//设置描绘回调函数
add_action( 'admin_init', 'bddb_settings_init' );
//读取默认配置用
require_once( BDDB_PLUGIN_DIR . '/class/class-bddb-settings.php');
//定义文件宏
define('BDDB_OPTION_FILE_NANE', 'wp-boycott-douban-db/bddb-options.php');

	/**
	 * @brief   追加后台菜单
	 * @since	  0.0.1
	*/
function bddb_add_admin_menu() {
  add_menu_page( 'bddb settings', 'bddb settings', 'manage_options', __FILE__, 'bddb_options_page', 'dashicons-screenoptions' );
}


	/**
	 * @brief   添加渲染用的组件
	 * @since	  0.0.1
   * @version 0.8.6
	*/
function bddb_settings_init(  ) {
	$arg = array(
		'sanitize_callback' => 'BDDB_Settings::sanitize_options',
		'default' => BDDB_Settings::default_options(),
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

  //目录设定·待更改
  add_settings_field(
  'basic_folder_setting',
  '目录设定',
  'bddb_basic_setting_render',
  'bddb_option_tab',
  'bddb_pluginPage_section'
  );
  
  //总体图片设置
  add_settings_field(
  'basic_poster_setting',
  '图片设定',
  'bddb_poster_render',
  'bddb_option_tab',
  'bddb_pluginPage_section'
  );

  //顺序设置·待更改
  add_settings_field(
  'basic_order_setting',
  '共通顺序设定',
  'bddb_general_order_render',
  'bddb_option_tab',
  'bddb_pluginPage_section'
  );

  add_settings_field(
    'special_function',
    '特殊功能',
    'bddb_special_function_render',
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
    'bddb_m_misc_map',
    '特殊图标列表',
    'bddb_all_misc_map_render',
    'bddb_movie_tab',
    'bddb_movie_section',
    array('type'=>'movie')
    );

  add_settings_field(
  'bddb_b_max_serial_count',
  '系列书籍有效本数',
  'bddb_b_max_serial_count_render',
  'bddb_book_tab',
  'bddb_book_section'
  );

  add_settings_field(
  'bddb_b_countries_map',
  '国名缩写对照表',
  'bddb_b_countries_map_render',
  'bddb_book_tab',
  'bddb_book_section'
  );

  add_settings_field(
  'bddb_b_poster_setting',
  '封面',
  'bddb_b_poster_render',
  'bddb_book_tab',
  'bddb_book_section'
  );

  add_settings_field(
    'bddb_b_misc_map',
    '特殊图标列表',
    'bddb_all_misc_map_render',
    'bddb_book_tab',
    'bddb_book_section',
    array('type'=>'book')
    );
  
  add_settings_field(
  'bddb_g_giantbomb_key',
  'GiantBomb Auth KEY',
  'bddb_g_giantbomb_key_render',
  'bddb_game_tab',
  'bddb_game_section'
  );
  
  add_settings_field(
  'bddb_g_poster_setting',
  '游戏海报',
  'bddb_g_poster_render',
  'bddb_game_tab',
  'bddb_game_section'
  );
  
  add_settings_field(
    'bddb_g_misc_map',
    '特殊图标列表',
    'bddb_all_misc_map_render',
    'bddb_game_tab',
    'bddb_game_section',
    array('type'=>'game')
    );

  add_settings_field(
  'bddb_a_poster_setting',
  '专辑规格',
  'bddb_a_poster_render',
  'bddb_album_tab',
  'bddb_album_section'
  );

  add_settings_field(
  'bddb_a_language_define',
  '专辑语种',
  'bddb_a_language_define_render',
  'bddb_album_tab',
  'bddb_album_section'
  );

  add_settings_field(
  'bddb_a_test_setting',
  '专辑预留',
  'bddb_test_field_render',
  'bddb_album_tab',
  'bddb_album_section'
  );

}

function bddb_basic_setting_render(	 ) {
  //03
	global $global_option_class;
	$options = $global_option_class->get_options();
  ?>
	<span>当前TAX版本号：</span>
	<input type='text' name='bddb_settings[tax_version]' readonly='readonly' size='24' value='<?php echo $options['tax_version']; ?>'/><br />
	<span>当前TYPE版本号：</span>
	<input type='text' name='bddb_settings[type_version]' readonly='readonly' size='24' value='<?php echo $options['type_version']; ?>'/><br />
	<?php
}


function bddb_basic_setting_render1(  ) {
  //03
	global $global_option_class;
	$options = $global_option_class->get_options();
  ?>
  <span>	图片缓存路径：</span>
  <input type='text' name='bddb_settings[default_folder]' size='24' value='<?php echo $options['default_folder']; ?>'/><br />
  <span>	默认排序：</span>
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

/**
 * @brief	  渲染misc的有图标对应项。
 * @param   array   $args
 *                  type  =>  book,movie,game,album
 * @since	  0.6.5
*/
function bddb_all_misc_map_render($args) {
  if (!is_array($args)|| !array_key_exists('type', $args)) {
    return;
  }
  $type = $args['type'];
  if (!BDDB_Statics::is_valid_type($type)) {
    return;
  }
  $misc_key = substr($type,0,1).'_misc_map';
  $option_key = 'bddb_settings['.$misc_key.']';
  global $global_option_class;
	$options = $global_option_class->get_options();
?>
  <span>slug用半角分号分割：</span><br />
	<textarea rows='4' cols='40' name='<?php echo $option_key; ?>' ><?php echo $options[$misc_key]; ?></textarea>
<?php
}

function bddb_poster_render() {
	global $global_option_class;
	$options = $global_option_class->get_options();
?>
	<span>每页缓存海报数：</span><input type='text' name='bddb_settings[thumbnails_per_page]' size='24' value='<?php echo $options['thumbnails_per_page']; ?>'/></br>
	<span>图像宽度：</span><input type='text' name='bddb_settings[poster_width]' size='24' value='<?php echo $options['poster_width']; ?>'/><br />
  <span>图像高度：</span><input type='text' name='bddb_settings[poster_height]' size='24' value='<?php echo $options['poster_height']; ?>'/><br />
	<span>缩略图宽度：</span><input type='text' name='bddb_settings[thumbnail_width]' size='24' value='<?php echo $options['thumbnail_width']; ?>'/></br>
  <span>缩略图高度：</span><input type='text' name='bddb_settings[thumbnail_height]' size='24' value='<?php echo $options['thumbnail_height']; ?>'/></br>
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

function bddb_special_function_render() {
  $type = 'game';
  //$all_bddbs = get_post()
  ?>
  <textarea rows='4' cols='40' name='bddb_special_functon_disp_area' ><?php echo "1234567"; ?></textarea>
  <?php
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

/**
 * @brief	渲染书籍封面和缩略图规格输入项。
 * @since	  0.6.2
*/
function bddb_b_poster_render()
{
	global $global_option_class;
	$options = $global_option_class->get_options();
?>
  <span>设定书籍封面宽度和高度，false为与总体设定一致，建议比例1：1.40：</span><br />
  <span>封面宽度：</span><input type='text' name='bddb_settings[poster_width_book]' size='24' value='<?php echo $options['poster_width_book']; ?>'/></br>
  <span>封面高度：</span><input type='text' name='bddb_settings[poster_height_book]' size='24' value='<?php echo $options['poster_height_book']; ?>'/></br>
	<span>缩略图宽度：</span><input type='text' name='bddb_settings[thumbnail_width_book]' size='24' value='<?php echo $options['thumbnail_width_book']; ?>'/></br>
  <span>缩略图高度：</span><input type='text' name='bddb_settings[thumbnail_height_book]' size='24' value='<?php echo $options['thumbnail_height_book']; ?>'/></br>
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

/**
 * @brief	渲染图书国名缩写输入项。
 * @since	  0.5.3
*/
function bddb_b_countries_map_render() {
  global $global_option_class;
	$options = $global_option_class->get_options();
?>
  <span>缩写与全名间用半角逗号分割，国名间用半角分号分割：</span><br />
	<textarea rows='4' cols='40' name='bddb_settings[b_countries_map]' ><?php echo $options['b_countries_map']; ?></textarea>
<?php
}

/**
 * @brief	渲染游戏海报和缩略图规格输入项。
 * @since	  0.6.2
*/
function bddb_g_poster_render()
{
	global $global_option_class;
	$options = $global_option_class->get_options();
?>
  <span>设定游戏海报宽度和高度，false为与总体设定一致，建议比例1：1.42：</span><br />
  <span>海报宽度：</span><input type='text' name='bddb_settings[poster_width_game]' size='24' value='<?php echo $options['poster_width_game']; ?>'/></br>
  <span>海报高度：</span><input type='text' name='bddb_settings[poster_height_game]' size='24' value='<?php echo $options['poster_height_game']; ?>'/></br>
	<span>缩略图宽度：</span><input type='text' name='bddb_settings[thumbnail_width_game]' size='24' value='<?php echo $options['thumbnail_width_game']; ?>'/></br>
  <span>缩略图高度：</span><input type='text' name='bddb_settings[thumbnail_height_game]' size='24' value='<?php echo $options['thumbnail_height_game']; ?>'/></br>
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

/**
 * @brief	渲染专辑封面和缩略图规格输入项。
 * @since	  0.6.2
*/
function bddb_a_poster_render()
{
	global $global_option_class;
	$options = $global_option_class->get_options();
?>
  <span>设定专辑图片宽度和高度，false为与总体设定一致，建议比例1：1：</span><br />
  <span>海报宽度：</span><input type='text' name='bddb_settings[poster_width_album]' size='24' value='<?php echo $options['poster_width_album']; ?>'/></br>
  <span>海报高度：</span><input type='text' name='bddb_settings[poster_height_album]' size='24' value='<?php echo $options['poster_height_album']; ?>'/></br>
	<span>缩略图宽度：</span><input type='text' name='bddb_settings[thumbnail_width_album]' size='24' value='<?php echo $options['thumbnail_width_album']; ?>'/></br>
  <span>缩略图高度：</span><input type='text' name='bddb_settings[thumbnail_height_album]' size='24' value='<?php echo $options['thumbnail_height_album']; ?>'/></br>
<?php
}

/**
 * @brief	定义语种情报
 * @since	  0.8.6
*/
function bddb_a_language_define_render()
{
	global $global_option_class;
	$options = $global_option_class->get_options();
?>
  <span>设定专辑语种。格式为000-语言，多种语言合并用半角逗号【,】分割；多个语言用半角分号【;】分割</span><br />
  <textarea rows='6' cols='40' name='bddb_settings[a_languages_def]' ><?php echo $options['a_languages_def']; ?></textarea>

<?php
}

	/**
	 * @brief	section渲染时的回调函数，根据section id显示不同的文字。
	 * @param	array	$section			section
	 * @since	  0.0.1
	 * @version	0.6.2
	*/
function bddb_settings_section_callback( $section ) {

  switch ($section['id']) {
    case 'bddb_book_section':
      echo '<span>书籍相关设定</span>';
      break;
    case 'bddb_movie_section':
      echo '<span>电影相关设定</span>';
      break;
    case 'bddb_game_section':
      echo '<span>游戏相关设定</span>';
      break;
    case 'bddb_album_section':
      echo '<span>专辑相关设定</span>';
      break;
    case 'bddb_pluginPage_section':
      default:
        echo '<span>一些基本设定项目，某些可以被子项目覆盖</span>';
        break;
  }
}

function bddb_options_page(	 ) {
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
