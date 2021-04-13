<?php

class jolmoAdminPanel {
    function __construct() {
        add_action( 'admin_menu', array( &$this, 'add_menu' ) );
        add_action( 'admin_init', array( &$this, 'setting_init' ) );
    }
    function add_menu() {
        add_menu_page( 'jolley memo', 'JolleyMemo', 'manage_options', 'jolmo-option-all', array(
            &$this,
            'show_menu'
        ), JOLMO_URLPATH . 'admin/images/JM.png' );
    }
    function show_menu() {
        switch ( $_GET['page'] ) {
            default:
                $this->show_chief_option();
            break;
        }
    }
    function show_chief_option() {
        if( isset( $_GET[ 'tab' ] ) ) {
            $active_tab = $_GET[ 'tab' ];
        } else {
            $active_tab = 'tab_option';
        }
        ?>
        <div id="apip_page_content" class="wrap apip-option" >
            <h1><span>Jol</span>ly Me<span>mo</span> </h1>
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
    function setting_init() {
        register_setting( 'apip_option_group', 'jmemo_options' );
    }
}