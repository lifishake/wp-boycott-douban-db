<?php
add_action( 'admin_menu', 'doukuzu_add_admin_menu' );
add_action( 'admin_init', 'doukuzu_settings_init' );
if ( !defined( 'DOUKUZU_OPTION_KEY' ) ) {
	define( 'DOUKUZU_OPTION_KEY', 'doukuzu_options' );
}

function doukuzu_add_admin_menu(  ) {

	add_menu_page( '豆屑', '豆屑', 'manage_options', __FILE__, 'doukuzu_options_page','' );
	//add_submenu_page

}

/*配置主画面*/
function doukuzu_options_page(  ) {
        if( isset( $_GET[ 'tab' ] ) ) {
            $active_tab = $_GET[ 'tab' ];
        } else {
            $active_tab = 'tab_option';
        }
        ?>
    <div id="apip_page_content" class="wrap apip-option" >
  <h1><span>豆</span>dou<span>屑</span>kuzu </h1>
  <div class="description">通过插件建立小型数据库，记录自己心仪的电影、书籍、游戏、音乐。</div>
            <?php settings_errors(); ?>

            <h2 class="nav-tab-wrapper">
                <a href="?page=<?php echo __FILE__;?>&tab=tab_config_all" class="nav-tab <?php echo $active_tab == 'tab_config_all' ? 'nav-tab-active' : ''; ?>">基本设置</a>
				<a href="?page=<?php echo __FILE__;?>&tab=tab_config_movie" class="nav-tab <?php echo $active_tab == 'tab_config_movie' ? 'nav-tab-active' : ''; ?>">电影</a>
				<a href="?page=<?php echo __FILE__;?>&tab=tab_config_book" class="nav-tab <?php echo $active_tab == 'tab_config_book' ? 'nav-tab-active' : ''; ?>">书籍</a>
				<a href="?page=<?php echo __FILE__;?>&tab=tab_config_game" class="nav-tab <?php echo $active_tab == 'tab_config_game' ? 'nav-tab-active' : ''; ?>">游戏</a>
				<a href="?page=<?php echo __FILE__;?>&tab=tab_config_music" class="nav-tab <?php echo $active_tab == 'tab_config_music' ? 'nav-tab-active' : ''; ?>">音乐</a>
                <a href="?page=<?php echo __FILE__;?>&tab=tab_extra" class="nav-tab <?php echo $active_tab == 'tab_extra' ? 'nav-tab-active' : ''; ?>">实验台</a>
            </h2>
     <form action='options.php' method='post'>
  <?php
  if( $active_tab == 'tab_config_all' ) {
      //settings_fields( 'apip_option_group' );
      //do_settings_sections( 'apip_option_group' );
  }
  else {
      //settings_fields( 'apip_extra_group' );
      //do_settings_sections( 'apip_extra_group' );
  }
  submit_button();
  ?>

  </form>
</div>
  <?php
}

/*注册设定项*/
function doukuzu_settings_init() {

	register_setting( 'doukuzu_option_group', DOUKUZU_OPTION_KEY );

	add_settings_section(
		'wp_pcd_pluginPage_section',
		__( 'Your section description', 'wordpress' ),
		'wp_pcd_settings_section_callback',
		'doukuzu_option_group'
	);

    add_settings_section(
		'wp_pcd_pluginPage_section666',
		__( '新追加的section', 'wordpress' ),
		'wp_pcd_settings_section666_callback',
		'pluginPage666'
	);

	add_settings_field(
		'wp_pcd_text_field_0',
		__( 'Settings field description', 'wordpress' ),
		'wp_pcd_text_field_0_render',
		'doukuzu_option_group',
		'wp_pcd_pluginPage_section'
	);

	add_settings_field(
		'wp_pcd_textarea_field_1',
		__( '第二页', 'wordpress' ),
		'wp_pcd_textarea_field_1_render',
		'pluginPage666',
		'wp_pcd_pluginPage_section666'
	);


}

function wp_pcd_settings_init(  ) {

	register_setting( 'doukuzu_option_group', 'wp_pcd_settings' );

	add_settings_section(
		'wp_pcd_pluginPage_section',
		__( 'Your section description', 'wordpress' ),
		'wp_pcd_settings_section_callback',
		'doukuzu_option_group'
	);

    add_settings_section(
		'wp_pcd_pluginPage_section666',
		__( '新追加的section', 'wordpress' ),
		'wp_pcd_settings_section666_callback',
		'pluginPage666'
	);

	add_settings_field(
		'wp_pcd_text_field_0',
		__( 'Settings field description', 'wordpress' ),
		'wp_pcd_text_field_0_render',
		'doukuzu_option_group',
		'wp_pcd_pluginPage_section'
	);

	add_settings_field(
		'wp_pcd_textarea_field_1',
		__( '第二页', 'wordpress' ),
		'wp_pcd_textarea_field_1_render',
		'pluginPage666',
		'wp_pcd_pluginPage_section666'
	);


}


function wp_pcd_text_field_0_render(  ) {

	$options = get_option( 'wp_pcd_settings' );
	?>
	<input type='text' name='wp_pcd_settings[wp_pcd_text_field_0]' value='<?php echo $options['wp_pcd_text_field_0']; ?>'>
	<?php

}


function wp_pcd_textarea_field_1_render(  ) {

	$options = get_option( 'wp_pcd_settings' );
	?>
	<textarea cols='40' rows='5' name='wp_pcd_settings[wp_pcd_textarea_field_1]'>
		<?php echo $options['wp_pcd_textarea_field_1']; ?>
 	</textarea>
	<?php

}


function wp_pcd_settings_section_callback(  ) {

	echo __( 'This section description', 'wordpress' );

}

function wp_pcd_settings_section666_callback(  ) {

	echo '选中了第二个tab页';

}



function wp_pcd_options_page(  ) {
    if( isset( $_GET[ 'tab' ] ) ) {
        $active_tab = $_GET[ 'tab' ];
    } else {
        $active_tab = 'tab_1';
    }
	?>
    <h2 class="nav-tab-wrapper">
        <a href="?page=<?php echo __FILE__;?>&tab=tab_1" class="nav-tab <?php echo $active_tab == 'tab_1' ? 'nav-tab-active' : ''; ?>">Tab One</a>
        <a href="?page=<?php echo __FILE__;?>&tab=tab_2" class="nav-tab <?php echo $active_tab == 'tab_2' ? 'nav-tab-active' : ''; ?>">Tab Tow</a>
    </h2>
	<form action='options.php' method='post'>

		<h2>plugin_config_demo</h2>

		<?php
        if( $active_tab == 'tab_1' ) {
      settings_fields( 'doukuzu_option_group' );
      do_settings_sections( 'doukuzu_option_group' );
  }
  else {
      settings_fields( 'pluginPage666' );
      do_settings_sections( 'pluginPage666' );
  }
		submit_button();
		?>

	</form>
	<?php

}

?>
