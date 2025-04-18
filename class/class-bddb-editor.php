<?php
/**
 * 后台编辑用类
*/

if (!class_exists('BDDB_Settings')) {
	require_once( BDDB_PLUGIN_DIR . '/class/class-bddb-settings.php');
}

/**
 * @class	BDDB_Editor_Factory
 * @brief	编辑类工厂，用于生成编辑类以及外部静态接口
 * @date	2024-09-03
 * @author	大致
 * @version	0.9.1
 * @since	0.5.4
 * 
 */
class BDDB_Editor_Factory {
	/**
	 * 后台初始化。
	 * @since 	0.5.4
	 * @version 0.7.5
	 * @date 2023-02-13
	 * @see		bddb_admin_init()
	 */
	public static function admin_init() {
		//统一到一个函数中方便查找。
		add_action ( 'save_post', 'BDDB_Editor_Factory::update_all_items', 10, 2);
		add_filter ( 'wp_insert_post_data', 'BDDB_Editor_Factory::generate_content', 10, 2);
		add_action( 'wp_ajax_bddb_get_pic', 'BDDB_Editor_Factory::download_pic');
		add_action( 'wp_ajax_bddb_get_imdbpic', 'BDDB_Editor_Factory::download_imdbpic');
		add_action( 'wp_ajax_bddb_get_scovers', 'BDDB_Editor_Factory::download_serial_pics');
	}
	public static function add_meta_boxes($pt) {
		$post_type = $pt->post_type;
		if (!BDDB_Statics::is_valid_type($post_type)) {
			return;
		}
		$e = new BDDB_Editor($post_type);
		$e->add_meta_box();
	}
	/******    钩子回调函数 开始    ******/
	/**
	 * 保存时更新追加的内容。
	 * @public
	 * @param int $post_ID	正在编辑的post_ID
	 * @param object $post	正在编辑的post
	 * @see		action::save_post
	 * @since 	0.0.1
	 * @version 0.5.4
	 */
	public static function update_all_items($post_ID, $post) {
		//验证信息在meta_box里
		if (!isset( $_POST['bddb_nonce'] ) || !wp_verify_nonce( $_POST['bddb_nonce'], basename( __FILE__ ) ) )
			return;
		$post_type = $post->post_type;
		if (!BDDB_Statics::is_valid_type($post_type)) {
			return;
		}
		$e = new BDDB_Editor($post_type);
		$e->update_all_items($post_ID);
	}

	/**
	 * 根据附加项内容生成文章正文。
	 * @param array $data		要保存的post_data
	 * @param array $postarr	还没有落地的post_data
	 * @return array	$data
	 * @see	filter::wp_insert_post_data
	 * @since 	0.0.1
	 * @version 0.5.4
	 */
	public static function generate_content($data, $postarr ) {
		if (!isset($postarr['post_type']) || 
			!BDDB_Statics::is_valid_type($postarr['post_type'])) {
			return $data;
		}
		//使用_POST中的数据,它们来自box控件
		//主要更新post_content的内容
		$e = new BDDB_Editor($postarr['post_type']);
		return $e->generate_content($data, $postarr);
	}

	/******    钩子回调函数 结束    ******/

	/******    AJAX回调函数 开始    ******/
	/**
	 * 获取封面的Callback。
	 * @see		AJAX::bddb_get_pic
	 * @since 	0.0.1
	 * @version	1.0.5
	 */
	public static function download_pic(){
		if (!isset($_POST['nonce']) || !isset($_POST['id']) || !isset($_POST['ptype']) || !isset($_POST['piclink']) ) {
		   wp_die();
	   }
	   if ( !wp_verify_nonce($_POST['nonce'],"bddb-get-pic-".$_POST['id'])) { 
		   wp_die();
	   }
	   if (!isset($_POST['rrotate'])) {
		$need_rotate = 0;
	   } else {
		$need_rotate = intval($_POST['rrotate']);
	   }
	   if (!isset($_POST['makecover'])) {
		$need_cover = 0;
	   } else {
		$need_cover = intval($_POST['makecover']);
	   }
	   if (!isset($_POST['adapt'])) {
		$need_adapt = 0;
	   } else {
		$need_adapt = intval($_POST['adapt']);
	   }

	   $options = BDDB_Settings::getInstance()->get_options();
	   $names = bddb_get_poster_names($_POST['ptype'], $_POST['id']);
	   $poster_full_name = $names->poster_name;
	   $thumbnail_full_name = $names->thumb_name;
	   if (file_exists($poster_full_name)) {
		   unlink($poster_full_name);
	   }
	   if (file_exists($thumbnail_full_name)) {
		   unlink($thumbnail_full_name);
	   }
	   $piclink = htmlspecialchars_decode($_POST['piclink']);
	   if (strpos($piclink, "doubanio.com")> 0 && strpos($piclink,".webp")>0){
		   $piclink = str_replace(".webp", ".jpg", $piclink);
	   }
	   $response = @wp_remote_get( 
			   $piclink, 
			   array( 
				   'timeout'  => 3000, 
				   'stream'   => true, 
				   'sslverify'	=> false,
				   'filename' => $poster_full_name 
			   ) 
		   );
	   if ( is_wp_error( $response ) )
	   {
			wp_die();
		   return false;
	   }
	   $full_width = BDDB_Settings::getInstance()->get_poster_width($_POST['ptype']);
	   $full_height = BDDB_Settings::getInstance()->get_poster_height($_POST['ptype']);
	   $thumb_width = BDDB_Settings::getInstance()->get_thumbnail_width($_POST['ptype']);
	   $thumb_height = BDDB_Settings::getInstance()->get_thumbnail_height($_POST['ptype']);

	   $image = new Bddb_SimpleImage();
	   $image->load($poster_full_name);

	   if ($need_rotate) {
		$image->rotate(-90);
	   }
	   if ($need_cover) {
		$image->addcover($full_width, $full_height, 5);
	   }
	   else if ($need_adapt) {
		$image->adapt($full_width, $full_height);
	   }

	   $image->resize($full_width, $full_height);
	   $image->save($poster_full_name);
	   $image->resize($thumb_width, $thumb_height);
	   $image->save($thumbnail_full_name);
	   wp_die();
	}

	/**
	 * 获取imdb封面的Callback。
	 * @see		AJAX::bddb_get_imdbpic
	 * @since 	0.3.6
	 * @version	1.0.5
	 */
	public static function download_imdbpic(){
		if (!isset($_POST['nonce']) || !isset($_POST['id']) || !isset($_POST['imdbno']) ) {
		   wp_die();
	   }
	   if ( !wp_verify_nonce($_POST['nonce'],"bddb-get-imdbpic-".$_POST['id'])) { 
		   wp_die();
	   }
	   $options = BDDB_Settings::getInstance()->get_options();
	   $names = bddb_get_poster_names('movie', $_POST['id']);
	   $poster_full_name = $names->poster_name;
	   $thumbnail_full_name = $names->thumb_name;
	   if (file_exists($poster_full_name)) {
		   unlink($poster_full_name);
	   }
	   if (file_exists($thumbnail_full_name)) {
		   unlink($thumbnail_full_name);
	   }
	   
	   $omdb_ret = BDDB_Fetcher::fetch($_POST['imdbno']);
	   $piclink = $omdb_ret['content']['pic'];
	   $piclink = htmlspecialchars_decode($piclink);
	   $response = @wp_remote_get( 
			   $piclink, 
			   array( 
				   'timeout'  => 3000, 
				   'stream'   => true, 
				   'filename' => $poster_full_name 
			   ) 
		   );
	   if ( is_wp_error( $response ) )
	   {
		   wp_die();
	   }
	   $full_width = BDDB_Settings::getInstance()->get_poster_width('movie');
	   $full_height = BDDB_Settings::getInstance()->get_poster_height('movie');
	   $thumb_width = BDDB_Settings::getInstance()->get_thumbnail_width('movie');
	   $thumb_height = BDDB_Settings::getInstance()->get_thumbnail_height('movie');
	   $image = new Bddb_SimpleImage();
	   $image->load($poster_full_name);
	   $image->resize($full_width, $full_height);
	   $image->save($poster_full_name);
	   $image->resize($thumb_width, $thumb_height);
	   $image->save($thumbnail_full_name);
	   wp_die();
	}

	/**
	 * 获取系列封面的AJAX的Callback。
	 * @see		AJAX::bddb_get_scovers
	 * @since 	0.0.8
	 * @version	1.0.5
	 */
	public function download_serial_pics(){
		if (!isset($_POST['nonce']) || !isset($_POST['id']) || !isset($_POST['ptype']) || !isset($_POST['slinks']) ) {
			wp_die();
		}
		if ( !wp_verify_nonce($_POST['nonce'],"bddb-get-scovers-".$_POST['id'])) { 
			wp_die();
		}
		$options = BDDB_Settings::getInstance()->get_options();
		$default_serial_count = $options['b_max_serial_count'];
		$thumb_width = BDDB_Settings::getInstance()->get_thumbnail_width('book');
		$thumb_height = BDDB_Settings::getInstance()->get_thumbnail_height('book');
		$obj_names = bddb_get_poster_names($_POST['ptype'],$_POST['id']);
		$slinks = $_POST['slinks'];
		$parts = explode(";", $slinks);
		$serial_count = min(count($parts), $default_serial_count, $_POST['stotal']);
		for($i=0; $i<$default_serial_count; ++$i) {
			$dest = sprintf("%s%02d.jpg",$obj_names->thumb_series_front,$i);
			if (file_exists($dest))
				unlink($dest);
		}
		for($i=0;$i<$serial_count;++$i) {
			$dest = sprintf("%s%02d.jpg",$obj_names->thumb_series_front,$i);
			$src = $parts[$i];
			$response = @wp_remote_get( 
				htmlspecialchars_decode($src), 
				array( 
					'timeout'  => 3000, 
					'stream'   => true, 
					'filename' => $dest 
				) 
			);
			if ( is_wp_error( $response ) )
			{
				continue;
			}
			$image = new Bddb_SimpleImage();
			$image->load($dest);
			$image->resize($thumb_width, $thumb_height);
			$image->save($dest);
		}
		wp_die();
	 }
/******    AJAX回调函数 结束    ******/
}//BDDB_Editor_Factory

/**
 * @class	BDDB_Editor
 * @brief	编辑类，后台post用
 * @date	2021-12-21
 * @author	大致
 * @version	0.5.1
 * @since	0.0.1
 * 
 */
class BDDB_Editor {

	//成员列表
	private $common_items;			/*四种档案都包括的共通项目*/
	private $total_items;			/*每个档案的所有项目,初始为空,留待子类填充后再一起使用*/
	private $self_post_type;		/*档案自身的种类*/
	private $default_item;			/*单条档案对应的默认值*/
	private $options;				/*配置选项，在set_working_mode时被设置*/

	/**
	 * 构造函数。
	 * @protected
	 * @param	array	$post_type 不设置时大部分功能不能使用。
	 * @since 0.0.1
	 */
	public function __construct($post_type = false){
		$this->options = false;
		$this->default_item = array(
			'name' => '',
			'label' => '',
			'size' => 64,
			'type' => 'meta',
			'comment' => '',
			'placeholder' => '',
			'sanitize_callback' => false,
			'inputstyle' => '',
			'min' => -1,
			'max' => 9999,
			'step' => 1,
			'limit' => 10,//TODO
		);
		$this->self_post_type = false;
		$this->common_items = array(
			'bddb_display_name' => array(	'name' => 'bddb_display_name',
											'label' => '表示名',
											'comment' => '<strong>*必填，不填无法显示！</strong>',
											'placeholder' => '尽量使用中文',
											),
			'bddb_personal_review' => array(	'name' => 'bddb_personal_review',
											'label' => '简评',
											'comment' => '<strong>*必填</strong>',
											'placeholder' => '一句话简评',
											'sanitize_callback' => array($this,'sanitize_personal_review')
											),
			'bddb_original_name' => array(	'name' => 'bddb_original_name',
											'label' => '原名',
											'comment' => '译作时选填，如果不填则默认与项目名相同。',
											'sanitize_callback' => array($this,'sanitize_original_name')
											),
			'bddb_aka' 			 => array(	'name' => 'bddb_aka',
											'label' => '别名',
											'placeholder' => '多个别名用“,”分割',
											),
			'bddb_external_link' => array(	'name' => 'bddb_external_link',
											'label' => '外部链接',
											'comment' => array($this, 'echo_fetch_button'),
											'placeholder' => 'http://',
											'sanitize_callback' => array($this,'sanitize_link')
											),
			'bddb_poster_link'	=> array(	'name' => 'bddb_poster_link',
											'label' => '图片链接',
											'comment'=> array($this, 'echo_poster_button'),
											'placeholder' => 'http://',
											'sanitize_callback' => array($this,'sanitize_post_link')
											),
			'bddb_publish_time' => array(	'name' => 'bddb_publish_time',
											'label' => '出版时间',
											'size' => 16,
											//'sanitize_callback' => array($this, 'sanitize_publish_time'),
											'sanitize_callback' => 'BDDB_Tools::sanitize_year_month',
											'placeholder' => 'YYYY-MM',
											),
			'bddb_view_time' => array(		'name' => 'bddb_view_time',
											'label' => '邂逅年月',
											'size' => 16,
											'comment'=>'<strong>*必填</strong>，不填默认为保存年月。',
											'sanitize_callback' => array($this, 'sanitize_view_time'),
											'placeholder' => 'YYYY-MM',
											),
			'bddb_personal_rating' => array( 'name' => 'bddb_personal_rating',
											'label' => '评分',
											'size' => 16,
											'comment'=>'百分制，首页按去一法显示成10分制。',
											'sanitize_callback' => array($this,'sanitize_personal_rating'),
											'inputstyle' => 'number',
											'min' => '-1',
											'max' => '100',
											'placeholder' => '59',
											),
			'bddb_id_douban'		=>	array(	'name' => 'bddb_id_douban',
											'label' => '豆瓣ID',
											'size' => 16,
											'type' => 'meta',
											),
			'bddb_score_douban'	=>	array(	'name' => 'bddb_score_douban',
											'label' => '豆瓣评分',
											'size' => 16,
											'type' => 'meta',
											'comment'=>'',
											'inputstyle' => 'number',
											'min' => '2.0',
											'max' => '10.0',
											'step' => '0.1',
											),
		);
		if (!empty($post_type) && BDDB_Statics::is_valid_type($post_type)) {
			$this->set_working_mode($post_type);
		}
	}
	
	/********    外部函数 开始    ********/
	/******  钩子调用的外部函数 开始  ******/
	
	/**
	 * 创建编辑盒子。
	 * @see		action::register_meta_box_cb
	 * @since 	0.0.1
	 * @version 0.5.4
	 */
	public function add_meta_box() {
		switch($this->self_post_type){
			case 'movie':
			$addi = '影片'; 
			break;
			case 'book':
			$addi = '书籍'; 
			break;
			case 'game':
			$addi = '游戏'; 
			break;
			case 'album':
			$addi = '专辑'; 
			break;
			default:
			return;
		}
		$title = "追加{$addi}信息";
		//工作状态box，使用最后一个参数，默认加到侧边。
		add_meta_box('bddbstsdiv', '状态显示', array($this, 'show_status_meta_box'), NULL, 'side');
		add_meta_box('bddbcommondiv', $title, array($this, 'show_meta_box'));
		//使用APIP函数，增加手动做成slug按钮。
		if (function_exists('apip_title_hex_meta_box')){
			remove_meta_box('slugdiv', $this->self_post_type, 'normal');
			add_meta_box('apipslugdiv', 'Slug to unicode', 'apip_title_hex_meta_box', $this->self_post_type, 'normal', 'core');
		}
	}

	/**
	 * 保存时更新追加的内容。
	 * @param int $post_ID	正在编辑的post_ID
	 * @see		action::save_post
	 * @since 	0.0.1
	 * @version 0.5.4
	 */
	public function update_all_items($post_ID) {
		if (!is_array($this->total_items)) {
			return;
		}
		foreach ($this->total_items as $item) {
			//$item = array_merge( $this->default_item, $item );
			if ('tax' == $item['type']) {
				$term_str = $this->update_terms($post_ID, $item);
			}elseif('meta' == $item['type']) {
				$meta_str = $this->update_meta($post_ID, $item);
			}
		}
	}

	/**
	 * 根据附加项内容生成文章正文。
	 * @param array $data		要保存的post_data
	 * @param array $postarr	还没有落地的post_data
	 * @return array	$data
	 * @see		filter::wp_insert_post_data
	 * @since 	0.0.1
	 * @version 0.5.4
	 */
	public function generate_content($data, $postarr ) {

		//使用_POST中的数据,它们来自box控件
		//主要更新post_content的内容
		//post_content中保存ID，方便其它post通过ID调用。
		$data['post_content'] = 'ID:'.$postarr['ID']."\n";
		foreach ($this->total_items as $item) {
			if (isset($_POST[$item['name']]) && !empty($_POST[$item['name']]) ) {
				$data['post_content'] .= sprintf("%s:%s\n",$item['label'], $_POST[$item['name']]);
			}
		}
		return $data;
	}

	/**
	 * 显示图片工具的callback
	 * @param object $post	正在编辑的wp的post
	 * @see		add_meta_box()
	 * @since 	0.0.1
	 */
	public function show_status_meta_box($post) {
		$names = bddb_get_poster_names($post->post_type, $post->ID);
		$thumb_name = $names->thumb_name;
		$is_got_thumb= is_file($thumb_name);
		$thumb_url = $names->thumb_url;
		if ($is_got_thumb) {
			$thumb_src = $thumb_url;
		} else {
			$thumb_src = $names->nopic_thumb_url;
		}
		$val_str='';
		if ('movie' == $post->post_type) {
			$val_str = get_post_meta($post->ID, 'm_score_imdb', true);
		}elseif('book' == $post->post_type) {
			$val_str = get_post_meta($post->ID, 'bddb_score_douban', true);
		}elseif('game' == $post->post_type) {
		}elseif('album' == $post->post_type) {
			$val_str = get_post_meta($post->ID, 'bddb_score_douban', true);
		}
		//TODO：可以做成动态改。
		$catch_status=(''==$val_str) ? '网页未抓取' : '网页已抓取';
		$t_class=(''==$val_str) ? "pic" : "no-pic";
		
		$box_str="<table>";
		$box_str.="<tr><th>缩略图:</th><td><img id='img_poster_thumbnail' src='{$thumb_src}'/></td></tr>";
		$box_str.="<tr><th>抓取状态:</th><td><span class='{$t_class}' id='fetch-status'>{$catch_status}<span></td></tr>";
		$box_str.="<tr><th>实时状态:</th><td><input type='text' size='16' id='pic-status' name='ajax-status' value='' readonly='readonly' /></td></tr>";
		$box_str.='</table>';
		echo $box_str;
	}
	/**
	 * 显示编辑盒子。
	 * @param object $post	正在编辑的wp的post
	 * @see		add_meta_box()
	 * @since 	0.0.1
	 * @version 0.8.6
	 */
	public function show_meta_box($post) {
		echo '<div  class="misc-pub-section"><table><tr><th>项目</th><th>输入</th><th>说明</th></tr>';
		wp_nonce_field(basename( __FILE__ ), 'bddb_nonce');
		$nomouse_names = array();
		foreach ($this->total_items as $arg)
		{
			//$arg = array_merge( $this->default_item, $arg );
			$comment_str = '';
			if (is_callable($arg['comment'])) {
				$comment_str = call_user_func($arg['comment'], $post);
			} else {
				$comment_str = $arg['comment'];
			}
			$placeholder_str = '';
			if (!empty($arg['placeholder'])){
				$placeholder_str = 'placeholder="'.$arg['placeholder'].'"';
			}
			$val_str = '';
			$type_str = '';
			if($arg['type'] === 'tax') {
				$str_array = wp_get_post_terms($post->ID, $arg['name'], array('fields'=>'names', 'number'=>$arg['limit']));
				if(!is_wp_error($str_array)){
					if (count($str_array)>1) {
						$val_str = implode(', ', $str_array);
					} elseif(count($str_array) == 1) {
						$val_str =trim($str_array[0]);
					}
				}
				$comment_str = $this->get_tax_hint_str($post->ID, $arg);
				$val_str = " value='".$val_str."' ";
				$type_str = " type='text' ";
				if('list' === $arg['inputstyle']) {
					$type_str .= " list='cList-{$arg['name']}' ";
					$comment_before = "<datalist id='cList-{$arg['name']}'>";
					foreach ($arg['clist'] as $ilist) {
						$comment_before .= "<option value = '{$ilist}'></option>";
					}
					$comment_before .= "</datalist>";
					$comment_str = $comment_before.$comment_str;
				}
			}elseif($arg['type'] === 'meta') {
				//post_meta
				$val_str = get_post_meta($post->ID, $arg['name'], true);
				$val_str = " value='".$val_str."' ";
				$type_str = " type='text' ";
				if ('number'===$arg['inputstyle']) {
					$type_str = " type='number' min='{$arg['min']}' max='{$arg['max']}' step='{$arg['step']}'";
					$nomouse_names[] = $arg['name'];
				} elseif ('boolean'===$arg['inputstyle']) {
					$val_str = get_post_meta($post->ID, $arg['name'], true);
					if ('1' == $val_str) {
						$val_str = " checked = 'checked' ";
					} else {
						$val_str = '';
					}
					$val_str .= " value='1' ";
					$type_str = " type='checkbox' ";
				} elseif('list' === $arg['inputstyle']) {
					$type_str .= " list='cList-{$arg['name']}' ";
					$comment_before = "<datalist id='cList-{$arg['name']}'>";
					foreach ($arg['clist'] as $ilist) {
						$comment_before .= "<option value = '{$ilist}'></option>";
					}
					$comment_before .= "</datalist>";
					$comment_str = $comment_before.$comment_str;
				}
			}else{
			}
			wp_localize_script( 'bddb-js-admin', 'nomouse_names', $nomouse_names);
			echo("<tr><th><label> {$arg['label']}：</label></th><td><input {$type_str} size='{$arg['size']}' name='{$arg['name']}' {$val_str} {$placeholder_str}></td><td>{$comment_str}</td></tr>");
		}
		echo '</table></div>';
	}
	
	/******  钩子调用的外部函数 结束  ******/

	/********    外部函数 结束    ********/


	/********    私有函数 开始    ********

	/****   保存选项的优化回调函数 开始   ****/
	/**
	 * 优化个人评分。
	 * @param string $str	编辑框中的评分
	 * @return string	-1~100的十进制字符串
	 * @see		update_meta()->sanitize_callback
	 * @since 0.0.1
	 */
	protected function sanitize_personal_rating($str) {
		$int = intval($str);
		if ($int < 0 || $int >100) {
			return '-1';
		} else {
			return strval($int);
		}
	}
	
	/**
	 * 优化原名。如果输入参数为空,则把显示名复制到原名上
	 * @param string $str	编辑框中的原名
	 * @return string	原名
	 * @see		update_meta()->sanitize_callback
	 * @since 	0.0.1
	 */
	protected function sanitize_original_name($str) {
		if ($str == "" && isset($_POST['bddb_display_name'])) {
			$str = htmlspecialchars(stripslashes($_POST['bddb_display_name']), ENT_QUOTES);
		}
		return $str;
	}
	
	/**
	 * 优化评价。如果输入参数为空，则显示“没有评价”。如果结尾没输入结束标点，则用句号补足。
	 * @param string $str	编辑框中的原名
	 * @return string	原名
	 * @see		update_meta()->sanitize_callback
	 * @since 	0.0.1
	 * @version 0.8.5
	 */
	protected function sanitize_personal_review($str) {
		if (empty($str) && isset($_POST['bddb_display_name'])) {
			$str = "没有评价。";
		}
		$punctuation = mb_substr($str, -1);
		$good_punct = array("！","。","？","…",".","?","!");
		if (!in_array($punctuation, $good_punct)) {
			$str .= "。";
		}
		return $str;
	}
	
	/**
	 * 优化系列作品的封面列表。
	 * @param 	string $str	编辑框中的所有封面地址
	 * @return 	string	优化后的封面地址
	 * @see		update_meta()->sanitize_callback
	 * @since 	0.0.1
	 */
	protected function sanitize_series_covers($str) {
		//之前油猴采集到的链接用逗号分隔，替换成分号。
		$str = str_replace(",", ";", $str);
		return $this->sanitize_link($str);
	}
	/**
	 * 优化链接输入。
	 * @param 	string $str	编辑框中的所有地址
	 * @return 	string	优化后的地址
	 * @see		update_meta()->sanitize_callback
	 * @since 	0.0.1
	 */
	protected function sanitize_link($str) {
		return htmlspecialchars_decode($str);
	}
	/**
	 * 优化人名输入。
	 * @param 	string $str	编辑框中的人名
	 * @return 	string	优化后的人名
	 * @see		update_meta()->sanitize_callback
	 * @since 	0.0.1
	 */
	protected function sanitize_name($str) {
		if (strpos($str, ',')) {
			//TODO:10改为可以设置的limit，进而通过option定义
			$arr_person = explode(",", $str);
			if (count($arr_person) > 10) {
				$arr_person = array_slice($arr_person, 0, 10);
				$str = implode(', ',$arr_person);
			}
		}		
		return str_replace(array(".","•"),"·", $str);
	}

	/**
	 * 优化接触时间，不填时默认为当天。
	 * @param string $str	编辑框中的接触时间
	 * @return string	观影/阅读/游戏/欣赏时间
	 * @see		update_meta()->sanitize_callback
	 * @version 1.0.3
	 * @since 	0.0.1
	 */
	protected function sanitize_view_time($str) {
		if (empty($str)) {
			if ("album" === $this->self_post_type) {
				$str = date('Y');
			} else {
				$str = date('Y-m');
			}
		} elseif (strtotime(date("Y-m-d",strtotime($str))) == strtotime($str)) {
			if ("album" === $this->self_post_type) {
				$str = date("Y", strtotime($str));
			}else{
				$str = date("Y-m", strtotime($str));
			}			
		} elseif (strtotime(date("Y",strtotime($str))) == strtotime($str)) {
			if ("album" !== $this->self_post_type) {
				$str .= "-01";
			}		
		}
		return $str;
	}

	/**
	 * 优化花费时间，不填时默认0.5。
	 * @param string $str	编辑框中的花费时间
	 * @return string	花费时间
	 * @see		update_meta()->sanitize_callback
	 * @since 	0.0.1
	 */
	protected function sanitize_cost_time($str) {
		$int = intval($str);
		if ($int <= 0) {
			$str = '0.5';
		}
		return $str;
	}
	
	/**
	 * 优化丛书本数，不填时默认为非丛书，设成1本。
	 * @param string $str	编辑框中的丛书本数
	 * @return string	丛书本数
	 * @see		update_meta()->sanitize_callback
	 * @since 	0.0.1
	 */
	protected function sanitize_series_total($str) {
		$int = intval($str);
		if ($int <= 0) {
			$str = '1';
		}
		return $str;
	}

	/**
	 * 优化电影类型。
	 * @param string $str	编辑框中的电影类型
	 * @return string	电影类型
	 * @see		update_meta()->sanitize_callback
	 * @since 	0.2.9
	 */
	protected function sanitize_m_genre($str) {
		$str = str_replace("纪录片", "纪录", $str);
		return $str;
	}

	/**
	 * 优化图片链接。
	 * @param string $str	编辑框中的图片链接
	 * @return string	图片链接
	 * @see		update_meta()->sanitize_callback
	 * @since 	0.2.9
	 */
	protected function sanitize_post_link($str) {
		if (strpos($str, "doubanio.com")> 0 && strpos($str,".webp")>0){
			$str = str_replace(".webp", ".jpg", $str);
		}
		return $str;
	}

	/****   保存选项的优化回调函数 结束   ****/

	/**** comment 列的特殊回调函数 开始 ****/
	/**
	 * 获取封面的按钮。
	 * @param object $post
	 * @return	string	显示用字符串
	 * @see 	$this->show_meta_box()->iscallable('comment')
	 * @since 	0.0.1
	 * @version 0.7.2
	 */
	protected function echo_poster_button( $post ) {
		$nonce_str = wp_create_nonce('bddb-get-pic-'.$post->ID);
		$names = bddb_get_poster_names($post->post_type, $post->ID);
		$btn_get = '<button class="button" name="bddb_get_pic_btn" type="button" pid="'.$post->ID.'" ptype="'.$post->post_type.'" wpnonce="'.$nonce_str.'" dest_src="'.$names->thumb_url.'" >取得</button>';
		$btn_get .= '<label><input class="check-r90" type="checkbox" name="bddb_pic_rrotate" value="0"/>右转90°</label>';
		$btn_get .= '<label><input class="check-r90" type="checkbox" name="bddb_pic_cover" value="0"/>剪裁封面</label>';
		$btn_get .= '<label><input class="check-r90" type="checkbox" name="bddb_pic_adape" value="0"/>自适应</label>';
		return $btn_get;
	}

	
	/**
	 * 抓取按钮。
	 * @param object $post
	 * @return	string	显示用字符串
	 * @see 	$this->show_meta_box()->iscallable('comment')
	 * @since 	0.0.1
	 */
	protected function echo_fetch_button($post) {
		$link = get_post_meta($post->ID, 'bddb_external_link',true);
		$nonce = wp_create_nonce('douban-spider-'.$post->ID);
		$str = '<button class="button" name="douban_spider_btn" type="button" doulink="'.$link.'" pid="'.$post->ID.'"  ptype="'.$post->post_type.'" wpnonce="'.$nonce.'" >抓取</button>';
		return $str;
	}

	/**
	 * 取多张封面按钮。
	 * @param object $post
	 * @return	string	显示用字符串
	 * @see 	$this->show_meta_box()->iscallable('comment')
	 * @since 	0.0.8
	 */
	protected function echo_series_covers_button($post) {
		$links = get_post_meta($post->ID, 'b_series_covers',true);
		$scount = get_post_meta($post->ID, 'b_series_total',true);
		$nonce = wp_create_nonce('bddb-get-scovers-'.$post->ID);
		$str = '<button class="button" name="bddb_get_scovers_btn" type="button" scount="'.$scount.'" slinks="'.$links.'" pid="'.$post->ID.'" ptype="'.$post->post_type.'" wpnonce="'.$nonce.'" >生成</button>';
		return $str;
	}

	/**
	 * 取imdb封面按钮。
	 * @param object $post
	 * @return	string	显示用字符串
	 * @see 	$this->show_meta_box()->iscallable('comment')
	 * @since 	0.3.5
	 */
	protected function echo_imdbpic_button($post) {
		$nonce_str = wp_create_nonce('bddb-get-imdbpic-'.$post->ID);
		$names = bddb_get_poster_names('movie', $post->ID);
		$btn_get = '<button class="button" name="bddb_get_imdbpic_btn" type="button" pid="'.$post->ID.'" wpnonce="'.$nonce_str.'" dest_src="'.$names->thumb_url.'" >imdb海报</button>';
		return $btn_get;
	}

	/**
	 * 为taxinomy类型的输入项增加辅助标签。
	 * @param int $id	正在编辑的post_ID
	 * @param array $item	要更新的条目
	 * @return	string	显示用字符串
	 * @see 	$this->show_meta_box()->iscallable('comment')
	 * @since 	0.1.0
	 */
	private function get_tax_hint_str($id, $item) {
		if (!is_array($item) || !isset($item['type']) || $item['type']!=='tax') {
			return '';
		}
		$ret = '';
		//TODO：数量改为可配置
		$arg = array(	'taxonomy'=>$item['name'],
						'hide_empty'=>false,
						'orderby'=>'id',
						'order'=>'DESC',
						'fields'=>'id=>name',
						'number'=>'2',);
		$recent_terms = get_terms($arg);
		if (is_wp_error($recent_terms)) {
			$new_arg = $arg;
			$new_arg['number'] = '10';
			$new_arg['orderby'] = 'count';
		}else{
			$new_arg = $arg;
			$new_arg['number'] = 10 - count($recent_terms);
			$new_arg['orderby'] = 'count';
			$new_arg['exclude'] = array_keys($recent_terms);
		}
		$popular = get_terms($new_arg);
		if (!is_wp_error($popular)){
			$recent_terms = array_merge($recent_terms , $popular);
		}
		$ret = '';
		foreach ($recent_terms as $key=>$term){
			$ret .= sprintf('<span class="box-tag" data="%s">%s</span>', $item['name'], $term);
		}
		return $ret;
	}
	/**** comment 列的特殊回调函数 结束 ****/

	/******      工具函数 开始      ******/
	/**
	 * 更新term项目。
	 * @param int $post_ID	正在编辑的post_ID
	 * @param array $item	要更新的条目
	 * @return	string	更新后的内容
	 * @see $this->update_all_items()
	 * @since 	0.0.1
	 * @version 0.4.1
	 */
	private function update_terms($post_ID, $item) {
		if(!is_array($item) || !isset($item['name'])) {
			return '';
		}
		$taxonomy_name = $item['name'];
		if (isset($_POST[$taxonomy_name])) {
			$new_terms_str = htmlspecialchars(stripslashes ($_POST[$taxonomy_name]), ENT_QUOTES);
			$new_terms_str = trim($new_terms_str);
			if ('tax' === $item['type'] && !empty($new_terms_str)) {
				$new_terms_str = BDDB_Tools::tax_slugs_to_names($taxonomy_name, $new_terms_str, $item['limit']);
			}
			if ( isset($item['sanitize_callback']) && is_callable($item['sanitize_callback'])) {
				$new_terms_str = call_user_func( $item['sanitize_callback'], $new_terms_str);
			}
			wp_set_post_terms($post_ID, $new_terms_str, $taxonomy_name);
			return $new_terms_str;
		}
		return '';
	}

	/**
	 * 更新meta项目。
	 * @param int $post_ID	正在编辑的post_ID
	 * @param array $item	要更新的条目
	 * @return	string	更新后的内容
	 * @see $this->update_all_items()
	 * @since 	0.0.1
	 * @version 0.4.1
	 */
	private function update_meta($post_ID, $item) {
		if(!is_array($item) || !isset($item['name'])) {
			return '';
		}
		$meta_name = $item['name'];
		$strMetaVal = '';
		if (isset($_POST[$meta_name])) {
			$strMetaVal = htmlspecialchars(stripslashes($_POST[$meta_name]),ENT_QUOTES);
		}
		if ('boolean' == $item['inputstyle']) {
			if ($strMetaVal !== '1') {
				$strMetaVal = '';
			}
		}
	
		if ( isset($item['sanitize_callback']) && is_callable($item['sanitize_callback'])) {
			$strMetaVal = call_user_func( $item['sanitize_callback'], $strMetaVal);
		}
		if('' == $strMetaVal) {
			delete_post_meta($post_ID, $meta_name, $strMetaVal);
		}else {
			update_post_meta($post_ID, $meta_name, $strMetaVal);
		}
		return $strMetaVal;
	}
	
	/**
	 * 根据post_type设置工作模式,主要是设定好每个种类的条目。
	 * @param string $post_type
	 * @return	bool	成功
	 * @private
	 * @mention	每个外部和回调函数都需要种类支撑，所以外部函数都需要先调用set_working_mode
	 * @since 0.1.0
	 * @version 1.0.5
	 */
	private function set_working_mode($post_type){
		if (!BDDB_Statics::is_valid_type($post_type)) {
			return false;
		}
		$this->self_post_type = $post_type;
		$this->options = BDDB_Settings::getInstance()->get_options();
		if (is_callable(array($this,"set_additional_items_{$post_type}"))){
			call_user_func(array($this,"set_additional_items_{$post_type}"));
		} else {
			return false;
		}
		$this->total_items = array_map(array($this, 'merge_default_column'), $this->total_items);
		return true;
	}
	
	/**
	 * 为项目添加默认值。
	 * @return	array		
	 * @param	array		$inItem
	 * @see 	$this->set_working_mode()->array_map
	 * @since 	0.0.1
	 */
	protected function merge_default_column($inItem) {
		if (!is_array($inItem)){
			return $this->default_item;
		}
		return array_merge($this->default_item, $inItem);
	}
	
	/**
	 * 设置电影的表示条目。
	 * @see	$this->set_working_mode()->set_additional_items_{$post_type}
	 * @since 0.1.0
	 */
	private function set_additional_items_movie() {
		$this->common_items['bddb_display_name']['label'] = '电影名';
		$this->common_items['bddb_publish_time']['label'] = '首映年月';
		$this->common_items['bddb_view_time']['label'] = '观看年月';
		$additional_items = array(
			'm_region' 			=> array(	'name' => 'm_region',
											'label' => '地区',
											'size' => 16,
											'type' => 'tax',
											'comment' => '',
											),
			'm_p_director'		=>	array(	'name' => 'm_p_director',
											'label' => '导演',
											'size' => 16,
											'type' => 'tax',
											'sanitize_callback' => array($this, 'sanitize_name'),
											),
			'm_p_actor'			=>	array(	'name' => 'm_p_actor',
											'label' => '主要演员',
											'size' => 32,
											'type' => 'tax',
											'sanitize_callback' => array($this, 'sanitize_name'),
											),
			'm_genre'			=>	array(	'name' => 'm_genre',
											'label' => '类型',
											'size' => 16,
											'type' => 'tax',
											'sanitize_callback' => array($this, 'sanitize_m_genre'),
											'placeholder' => '剧情,动作,喜剧,恐怖,历史,战争,犯罪...',
											),
			'm_publisher'		=>	array(	'name' => 'm_publisher',
											'label' => '制作或发行方',
											'size' => 16,
											'type' => 'tax',
											'placeholder' => '建议使用简称',
											),
			'm_p_screenwriter'	=>	array(	'name' => 'm_p_screenwriter',
											'label' => '编剧',
											'size' => 16,
											'type' => 'tax',
											'sanitize_callback' => array($this, 'sanitize_name'),
											),
			'm_p_musician'		=>	array(	'name' => 'm_p_musician',
											'label' => '配乐',
											'size' => 16,
											'type' => 'tax',
											'sanitize_callback' => array($this, 'sanitize_name'),
											),
			'm_misc_brand'		=>	array(	'name' => 'm_misc_brand',
											'label' => '特殊头衔',
											'size' => 16,
											'type' => 'tax',
											'placeholder'=>'豆瓣250,IMDB250,露点,三级,R级',
											),
			'm_id_imdb'			=>	array(	'name' => 'm_id_imdb',
											'label' => 'IMDB编号',
											'comment' => array($this, 'echo_imdbpic_button'),
											'size' => 16,
											'type' => 'meta',
											),
			'm_score_imdb'		=>	array(	'name' => 'm_score_imdb',
											'label' => 'IMDB评分',
											'size' => 16,
											'type' => 'meta',
											'inputstyle' => 'number',
											'min' => '0.0',
											'max' => '10.0',
											'step' => '0.1',
											),
			'm_length'			=>	array(	'name' => 'm_length',
											'label' => '片长',
											'size' => 16,
											'type' => 'meta',
											'inputstyle' => 'number',
											'min' => '1',
											'max' => '999',
											'step' => '1',
											),
		);
		$this->total_items = array_merge($this->common_items, $additional_items);
	}
	/**
	 * 设置电影的表示条目。
	 * @see	$this->set_working_mode()->set_additional_items_{$post_type}
	 * @since 0.1.0
	 */
	private function set_additional_items_book() {
		$this->common_items['bddb_display_name']['label'] = '书名';
		$this->common_items['bddb_publish_time']['label'] = '出版年月';
		$this->common_items['bddb_view_time']['label'] = '品读年月';
		$additional_items = array(
			'b_region' 			=> 	array(	'name' => 'b_region',
											'label' => '地区',
											'size' => 16,
											'type' => 'tax',
											),
			'b_p_writer'		=>	array(	'name' => 'b_p_writer',
											'label' => '作者',
											'size' => 16,
											'type' => 'tax',
											'comment'=>'<strong>*作者和编者至少填一项</strong>',
											'sanitize_callback' => array($this, 'sanitize_name'),
											),
			'b_p_translator'	=>	array(	'name' => 'b_p_translator',
											'label' => '译者',
											'size' => 16,
											'type' => 'tax',
											'sanitize_callback' => array($this, 'sanitize_name'),
											),
			'b_p_editor'		=>	array(	'name' => 'b_p_editor',
											'label' => '编者',
											'size' => 16,
											'type' => 'tax',
											'sanitize_callback' => array($this, 'sanitize_name'),
											),
			'b_genre'			=>	array(	'name' => 'b_genre',
											'label' => '类别',
											'size' => 16,
											'type' => 'tax',
											),
			'b_publisher'		=>	array(	'name' => 'b_publisher',
											'label' => '出版社',
											'size' => 16,
											'type' => 'tax',
											),
			'b_series_total'	=>	array(	'name' => 'b_series_total',
											'label' => '全套册数',
											'size' => 16,
											'inputstyle' => 'number',
											'min' => '1',
											'max' => '999',
											'step' => '1',
											'comment'=>'默认为1',
											'sanitize_callback' => array($this, 'sanitize_series_total'),
											),
											//删？
			'b_misc_brand'		=>	array(	'name' => 'b_misc_brand',
											'label' => '特殊头衔',
											'size' => 16,
											'type' => 'tax',
											),
			'b_bl_series'	=>	array(	'name' => 'b_bl_series',
											'label' => '丛书',
											'size' => 8,
											'type' => 'meta',
											'inputstyle' => 'boolean',
											'comment'=>'默认不选',
											),
			'b_pub_time_end' => array(	'name' => 'b_pub_time_end',
											'label' => '最终出版时间',
											'size' => 16,
											'type' => 'meta',
											'placeholder' => '年年年年-月月',
											),
			'b_series_covers' => array(	'name' => 'b_series_covers',
											'label' => '系列封面',
											'comment' => array($this, 'echo_series_covers_button'),
											'type' => 'meta',
											'sanitize_callback' => array($this, 'sanitize_series_covers'),
											'placeholder' => '用分号分割',
											),
		);
		$this->total_items = array_merge($this->common_items, $additional_items);
	}
	/**
	 * 设置游戏的表示条目。
	 * @see	$this->set_working_mode()->set_additional_items_{$post_type}
	 * @since 	0.1.0
	 * @version 0.6.7
	 */
	private function set_additional_items_game() {
		$this->common_items['bddb_display_name']['label'] = '游戏名';
		$this->common_items['bddb_publish_time']['label'] = '首发年月';
		$this->common_items['bddb_view_time']['label'] = '接触年月';
		$additional_items = array(
			'g_language' 			=> 	array(	'name' => 'g_language',
											'label' => '语言版本',
											'size' => 16,
											'type' => 'tax',
											'placeholder' => '美版,欧版,日版,简中,繁中,盗中,汉化...',
											'inputstyle' => 'list',
											'clist' => array(
												"","日版","美版","欧版","中文","盗版中文","汉化",
												),
											),
			'g_genre'		=>		array(	'name' => 'g_genre',
											'label' => '类别',
											'size' => 16,
											'type' => 'tax',
											),
			'g_platform'	=>		array(	'name' => 'g_platform',
											'label' => '机种',
											'size' => 16,
											'type' => 'tax',
											'placeholder' => 'FC,MD,GB,GBC,GBA,SFC,ARC,PC,PS...',
											),
			'g_publisher'	=>		array(	'name' => 'g_publisher',
											'label' => '制作方',
											'size' => 16,
											'type' => 'tax',
											),

			'g_cost_time'	=>		array(	'name' => 'g_cost_time',
											'label' => '耗时',
											'size' => 16,
											'comment'=>'单位小时',
											'inputstyle' => 'number',
											'min' => '0.5',
											'max' => '9999.0',
											'step' => '0.1',
											'sanitize_callback' => array($this, 'sanitize_cost_time'),
											),
			'g_misc_brand'		=>	array(	'name' => 'g_misc_brand',
											'label' => '特殊头衔',
											'size' => 16,
											'type' => 'tax',
											),
		);
		$this->total_items = array_merge($this->common_items, $additional_items);
	}
	/**
	 * 设置电影的表示条目。
	 * @see	$this->set_working_mode()->set_additional_items_{$post_type}
	 * @since 0.1.0
	 * @version 1.0.5
	 */
	private function set_additional_items_album() {
		$this->common_items['bddb_display_name']['label'] = '专辑名';
		$this->common_items['bddb_publish_time']['label'] = '发行年月';
		$this->common_items['bddb_view_time']['label'] = '欣赏年月';
		$additional_items = array(
			'a_region' 			=> 	array(	'name' => 'a_region',
											'label' => '地区',
											'size' => 16,
											'type' => 'tax',
											),
			'a_language' 		=> 	array(	'name' => 'a_language',
											'label' => '语言',
											'size' => 16,
											'type' => 'meta',
											'inputstyle' => 'list',
											'clist' => array(
												"",
												),
											),								
			'a_genre'		=>		array(	'name' => 'a_genre',
											'label' => '风格',
											'size' => 16,
											'type' => 'tax',
											),
			'a_p_musician'	=>		array(	'name' => 'a_p_musician',
											'label' => '音乐家',
											'size' => 16,
											'type' => 'tax',
											'placeholder'=>'演唱者/乐队/演奏家',
											'sanitize_callback' => array($this, 'sanitize_name'),
											),
			'a_p_asstants'	=>		array(	'name' => 'a_p_asstants',
											'label' => '合作音乐家',
											'size' => 16,
											'type' => 'tax',
											'placeholder'=>'伴唱，对唱等',
											'sanitize_callback' => array($this, 'sanitize_name'),
											),
			'a_quantities'	=>		array(	'name' => 'a_quantity',
											'label' => '专辑规格',
											'size' => 16,
											'type' => 'tax',
											'placeholder'=>'单曲/EP/正常专辑/长专辑',
											),
			'a_publisher'	=>		array(	'name' => 'a_publisher',
											'label' => '厂牌',
											'size' => 16,
											'type' => 'tax',
											),
			'a_bl_multicreator'	=>	array(	'name' => 'a_bl_multicreator',
											'label' => '多人创作',
											'size' => 8,
											'type' => 'meta',
											'inputstyle' => 'boolean',
											),
		);
		$arr_defined = BDDB_Settings::getInstance()->get_language_list();
		$additional_items['a_language']['clist'] = array_merge($additional_items['a_language']['clist'], $arr_defined);
		$this->total_items = array_merge($this->common_items, $additional_items);
	}
	/******      工具函数 结束      ******/
	/********    私有函数 结束    ********/
};

