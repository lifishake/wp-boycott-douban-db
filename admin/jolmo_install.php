<?php
if(preg_match('#' . basename(__FILE__) . '#', $_SERVER['PHP_SELF'])) { die('不能直接访问.'); }

/**
 * 作用: 创建表,创建默认配置项
 * 调用: activation_hook
 * 权限: 私有
 * @return void
*/
function jolmo_install() {
    global $wpdb;
    if ( !current_user_can('activate_plugins') ) 
		return;
    
    //引用dbDelta函数
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    
    $jolmo = $wpdb->prefix . "jolmo";
    $jolmo_meta = $wpdb->prefix . "jolmo_meta";
    $jolmo_person = $wpdb->prefix . "jolmo_person";
    $jolmo_relationship = $wpdb->prefix . "jolmo_relationship";
    $jolmo_defines = $wpdb->prefix . "jolmo_defines";
    
    //主表
    if($wpdb->get_var("show tables like '$jolmo'") != $jolmo) {
        $sql = "CREATE TABLE `".$jolmo."` (
              `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
              `name` VARCHAR(256) NOT NULL,
              `type` VARCHAR(64) NOT NULL,
              `original_name` VARCHAR(256) DEFAULT NULL,
              `link` VARCHAR(256) DEFAULT NULL,
              `pic_link` VARCHAR(256) DEFAULT NULL,
              `review` TEXT DEFAULT NULL,
              `pub_date` DATE DEFAULT NULL,
              `touch_date` DATE DEFAULT NULL,
              `sub_star` TINYINT(3) UNSIGNED NOT NULL,
              `update_timestamp` TIMESTAMP NOT NULL ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`)
            ) COLLATE utf8mb4_unicode_ci";
        dbDelta($sql);
    }

    //附加信息
    if($wpdb->get_var("show tables like '$jolmo_meta'") != $jolmo_meta) {
        $sql = "CREATE TABLE `".$jolmo_meta."` (
              `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
              `memo_id` BIGINT(20) NOT NULL,
              `meta_key` VARCHAR(256) NOT NULL,
              `meta_value` LONGTEXT NOT NULL,
              `update_timestamp` TIMESTAMP NOT NULL ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`),
              KEY (`memo_id`)
            ) COLLATE utf8mb4_unicode_ci ";
        dbDelta($sql);
    }
    
    //person
    if($wpdb->get_var("show tables like '$jolmo_person'") != $jolmo_person) {
        $sql = "CREATE TABLE `".$jolmo_person."` (
              `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
              `name` VARCHAR(64) NOT NULL,/
              `original_name` VARCHAR(64) DEFAULT NULL,
              `birthday` date DEFAULT NULL,
              `deathday` date DEFAULT NULL,
              `intro` MEDIUMTEXT DEFAULT NULL,
              `link` VARCHAR(256) DEFAULT NULL,
              `sameid` BIGINT(20) UNSIGNED DEFAULT NULL,
              `update_timestamp` TIMESTAMP NOT NULL ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`)
            ) COLLATE utf8mb4_unicode_ci ";
        dbDelta($sql);
    }
    //defines,预定义类型,厂商,出版社等枚举选项
    if($wpdb->get_var("show tables like '$jolmo_defines'") != $jolmo_defines) {
        $sql = "CREATE TABLE `".$jolmo_defines."` (
              `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
              `name` VARCHAR(256) NOT NULL,
              `slug` VARCHAR(512) NOT NULL,
              `description` mediumtext DEFAULT NULL,
              `parent_id` BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
              `is_default` TINYINT(1) NOT NULL DEFAULT '0',
              `type` VARCHAR(64) NOT NULL ,
              `update_timestamp` TIMESTAMP NOT NULL ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`id`)
            ) COLLATE utf8mb4_unicode_ci ";
        dbDelta($sql);
    }
    //relationship,define/persion与memo的关联关系，为了反查方便。
    if($wpdb->get_var("show tables like '$jolmo_relationship'") != $jolmo_relationship) {
        $sql = "CREATE TABLE `".$jolmo_relationship."` (
              `define_id` BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
              `person_id` BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
              `jolmo_id` BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
              `cnt` TINYINT(3) UNSIGNED NOT NULL DEFAULT 1,
              `update_timestamp` TIMESTAMP NOT NULL ON UPDATE CURRENT_TIMESTAMP,
              PRIMARY KEY (`define_id`, `person_id`, `jolmo_id` ),
              KEY (`define_id`, `person_id` )
            ) COLLATE utf8mb4_unicode_ci ";
        dbDelta($sql);
    }
    $options = get_option('jolmo_options');
    if ( empty( $options ) ) {
        jolmo_default_options();
    }
}

function jolmo_default_options() {
    $jolmo_options = array();
    $wp_content = basename(WP_CONTENT_DIR);
    $jolmo_options['ver'] = "20210412";
    $jolmo_options['galleryPath']= $wp_content.'/jolmo_gallery/';
    $jolmo_options['galSort']= 'sub_star';
    $jolmo_options['galSortDir']= 'DESC';
    update_option('jolmo_options', $jolmo_options);
}

function jolmo_uninstall() {
    global $wpdb;
    delete_option( 'jolmo_options' );
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}jolmo");
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}jolmo_meta");
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}jolmo_person");
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}jolmo_defines");
    $wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}jolmo_relationship");
}