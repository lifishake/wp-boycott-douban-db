<?php
/**
后台编辑用类
*/

define ('OBLONG_THUMB_WIDTH', 100);
define ('OBLONG_THUMB_HEIGHT', 148);
define ('SQUARE_THUMB_WIDTH', 100);
define ('SQUARE_THUMB_HEIGHT', 100);

function alert($message,$url='',$isAlert=true,$title='提示'){
echo '<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /><title>',$title,'</title></head><body>';
echo '<script type="text/javascript">';
echo $isAlert?'alert("'.$message.'");':'';
echo $url==''?'history.back();':'location.href="'.$url.'";';
echo '</script>';
echo '</body></html>';
//exit();
}

/**
 * 编辑用模板类，不可直接创建对象
 */
class BDDB_Template {
	//成员列表
	protected $common_items;		/*四种档案都包括的共通项目*/
	protected $settings;			/*保留*/
	protected $total_items;			/*每个档案的所有项目,初始为空,留待子类填充后再一起使用*/
	protected $box_title;			/*编辑盒子的标题,初始为空,待子类覆盖*/
	protected $self_post_type;			/*档案自身的种类*/
	protected $default_item;
	/**
	 * 构造函数。
	 * @access protected
	 * @param	array	$settings	reserved
	 * @since 0.0.1
	 */
	protected function __construct($settings){
		$this->settings = $settings;
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
			'limit' => 0,
		);
		$this->common_items = array(
			'bddb_display_name' => array(	'name' => 'bddb_display_name',
											'label' => '表示名',
											'comment' => '<strong>*必填</strong>',
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
			'bddb_external_link' => array(	'name' => 'bddb_external_link',
											'label' => '外部链接',
											'comment' => array($this, 'link_special'),
											'placeholder' => 'http://',
											'sanitize_callback' => array($this,'sanitize_link')
											),
			'bddb_poster_link'	=> array(	'name' => 'bddb_poster_link',
											'label' => '图片链接',
											'comment'=> array($this, 'poster_special'),
											'placeholder' => 'http://',
											),
			'bddb_publish_time' => array(	'name' => 'bddb_publish_time',
											'label' => '出版时间',
											'size' => 16,
											'comment' => '',
											'sanitize_callback' => array($this, 'sanitize_publish_time'),
											'placeholder' => '年年年年-月月',
											),
			'bddb_view_time' => array(		'name' => 'bddb_view_time',
											'label' => '邂逅年月',
											'size' => 16,
											'comment'=>'<strong>*必填</strong>，不填默认为保存年月。',
											'sanitize_callback' => array($this, 'sanitize_view_time'),
											'placeholder' => '年年年年-月月',
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
			'country' => array(				'name' => 'country',
											'label' => '地区',
											'size' => 16,
											'type' => 'tax',
											'comment' => '',
											'placeholder' => '大陆, 香港, 美国, 日本, etc',
											),
		);
	}
	
	/**
	 * 创建编辑盒子。
	 * @access public
	 * @since 0.0.1
	 */
	public function add_meta_box() {
		add_meta_box('bddbstsdiv', '状态显示', array($this, 'show_pic_meta_box'), $this->self_post_type, 'normal', 'core');
		add_meta_box('bddbcommondiv', $this->box_title, array($this, 'show_meta_box'), $this->self_post_type, 'normal', 'core');
	}
	
	/**
	 * 显示图片工具
	 * @access public
	 * @param object $post	正在编辑的wp的post
	 * @since 0.0.1
	 */
	public function show_pic_meta_box($post) {
		$dir = $this->settings['base_dir'];
		$thumb_name = sprintf("%sthumbnails/%s_%013d.jpg", $dir, $post->post_type, $post->ID);
		$is_got_thumb= is_file($thumb_name);
		$thumb_image_src = $this->settings['base_url'].sprintf("thumbnails/%s_%013d.jpg",$post->post_type, $post->ID);
		if ($is_got_thumb) {
			$thumb_src = $thumb_image_src;
		} else {
			$thumb_src = $this->settings['plugin_url'].sprintf("img/nocover_%d_%d.png", $this->settings['thumb_width'], $this->settings['thumb_height']);
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
		$catch_status=(''==$val_str) ? '网页未抓取' : '网页已抓取';
		$t_class=(''==$val_str) ? "pic" : "no-pic";
		
		$box_str="<table>";
		$box_str.="<tr><th>缩略图:</th><td><img id='img_poster_thumbnail' src='{$thumb_src}'/></td></tr>";
		$box_str.="<tr><th>抓取状态:</th><td><span class='{$t_class}'>{$catch_status}<span></td></tr>";
		$box_str.="<tr><th>实时状态:</th><td><input type='text' size='16' name='ajax-status' value='' readonly='readonly' /></td></tr>";
		$box_str.='</table>';
		echo $box_str;
	}
	/**
	 * 显示编辑盒子。
	 * @access public
	 * @param object $post	正在编辑的wp的post
	 * @since 0.0.1
	 */
	public function show_meta_box($post) {
		echo '<div  class="misc-pub-section"><table><tr><th>项目</th><th>输入</th><th>说明</th></tr>';
		wp_nonce_field(basename( __FILE__ ), 'bddb_nonce');
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
				$val_str = " value='".$val_str."' ";
				$type_str = " type='text' ";
			}elseif($arg['type'] === 'meta') {
				//post_meta
				$val_str = get_post_meta($post->ID, $arg['name'], true);
				$val_str = " value='".$val_str."' ";
				$type_str = " type='text' ";
				if ('number'===$arg['inputstyle']) {
					$type_str = " type='number' min='{$arg['min']}' max='{$arg['max']}' step='{$arg['step']}'";
				}
			}elseif($arg['type'] === 'boolean_meta') {
				$val_str = get_post_meta($post->ID, $arg['name'], true);
				if ('1' == $val_str) {
					$val_str = " checked = 'checked' ";
				} else {
					$val_str = '';
				}
				$val_str .= " value='1' ";
				$type_str = " type='checkbox' ";
			}
			echo("<tr><th><label> {$arg['label']}：</label></th><td><input {$type_str} size='{$arg['size']}' name='{$arg['name']}' {$val_str} {$placeholder_str}></td><td>{$comment_str}</td></tr>");
		}
		echo '</table></div>';
	}
	
	//优化函数
	/**
	 * 优化个人评分。
	 * @access public
	 * @param string $str	编辑框中的评分
	 * @return string	-1~100的十进制字符串
	 * @since 0.0.1
	 */
	public function sanitize_personal_rating($str) {
		$int = intval($str);
		if ($int < 0 || $int >100) {
			return '-1';
		} else {
			return strval($int);
		}
	}
	
	/**
	 * 优化原名。如果输入参数为空,则把显示名复制到原名上
	 * @access public
	 * @param string $str	编辑框中的原名
	 * @return string	原名
	 * @since 0.0.1
	 */
	public function sanitize_original_name($str) {
		if ($str == "" && isset($_POST['bddb_display_name'])) {
			$str = htmlentities($_POST['bddb_display_name']);
		}
		return $str;
	}
	
	/**
	 * 优化评价。如果输入参数为空,则显示“无聊到无话可说”
	 * @access public
	 * @param string $str	编辑框中的原名
	 * @return string	原名
	 * @since 0.0.1
	 */
	public function sanitize_personal_review($str) {
		if ($str == "" && isset($_POST['bddb_display_name'])) {
			$str = "《".htmlentities($_POST['bddb_display_name'])."》实在是无聊到无语。";
		}
		return $str;
	}
	/**
	 * 优化出品时间。改为年-月格式。如果只输入年则默认定位到该年1月
	 * @access public
	 * @param string $str	编辑框中的出品年份
	 * @return string	修改后的出品年份
	 * @since 0.0.1
	 */
	public function sanitize_publish_time($str) {
		if (strtotime(date("Y-m-d",strtotime($str))) == strtotime($str)) {
			$str = date("Y-m", strtotime($str));
		}
		if (!strpos($str,"-")&&intval($str)>1904) {
			$str .= '-01';
		}
		return $str;
	}
	/**
	 * 优化系列作品的封面列表。
	 * @access 	public
	 * @param 	string $str	编辑框中的所有封面地址
	 * @return 	string	优化后的封面地址
	 * @since 0.0.1
	 */
	public function sanitize_series_covers($str) {
		//之前油猴采集到的链接用逗号分隔，替换成分号。
		$str = str_replace(",", ";", $str);
		return $this->sanitize_link($str);
	}
	/**
	 * 优化链接输入。
	 * @access 	public
	 * @param 	string $str	编辑框中的所有封面地址
	 * @return 	string	优化后的封面地址
	 * @since 0.0.1
	 */
	public function sanitize_link($str) {
		return htmlspecialchars_decode($str);
	}
	
	public function sanitize_name($str) {
		return str_replace(".","·", $str);
	}

	/**
	 * 优化接触时间，不填时默认为当天。
	 * @access public
	 * @param string $str	编辑框中的接触时间
	 * @return string	观影/阅读/游戏/欣赏时间
	 * @since 0.0.1
	 */
	public function sanitize_view_time($str) {
		if ('' == $str) {
			$str = date('Y-m');
		} elseif (strtotime(date("Y-m-d",strtotime($str))) == strtotime($str)) {
			$str = date("Y-m", strtotime($str));
		}
		return $str;
	}
	
	/**
	 * 更新追加的内容。
	 * @access public
	 * @param int $post_ID	正在编辑的post_ID
	 * @param object $post	正在编辑的post
	 * @since 0.0.1
	 */
	public function update_all_items($post_ID, $post) {
		if (!isset( $_POST['bddb_nonce'] ) || !wp_verify_nonce( $_POST['bddb_nonce'], basename( __FILE__ ) ) )
			return;
		if (!is_array($this->total_items)) {
			return;
		}
		foreach ($this->total_items as $item) {
			//$item = array_merge( $this->default_item, $item );
			if ('tax' == $item['type']) {
				$term_str = $this->update_terms($post_ID, $item);
			}elseif('meta' == $item['type'] || 'boolean_meta' == $item['type']) {
				$meta_str = $this->update_meta($post_ID, $item);
			}
		}
	}
	
	public function generate_content($data, $postarr ) {
		if ($data['post_type'] == $this->self_post_type) {
			$data['post_content'] = 'ID:'.$postarr['ID'];
			foreach ($this->total_items as $item) {
				if ('tax' == $item['type']) {
					$str_array = wp_get_post_terms($postarr['ID'], $item['name'], array('fields'=>'names'));
					if (count($str_array)>1) {
						$val_str = implode(', ', $str_array);
					} elseif(count($str_array) == 1) {
						$val_str =trim($str_array[0]);
					} else {
						$val_str = '';
					}
					if ($val_str != '' ) {
						$data['post_content'] .= sprintf("%s:%s\n",$item['label'], $val_str);
					}
				}elseif('meta' == $item['type'] || 'boolean_meta' == $item['type']) {
					$meta_str = get_post_meta($postarr['ID'], $item['name'], true);
					if ($meta_str != '' ) {
						$data['post_content'] .= sprintf("%s:%s\n",$item['label'], $meta_str);
					}
				}
			}
		}
		return $data;
	}
	
	//说明栏中的特殊项
	public function poster_special( $post ) {
		//$dir = $this->settings['base_dir'];
		$nonce_str = wp_create_nonce('bddb-get-pic-'.$post->ID);
		$thumb_image_src = $this->settings['base_url'].sprintf("thumbnails/%s_%013d.jpg",$post->post_type, $post->ID);
		$btn_get = '<button class="button" name="bddb_get_pic_btn" type="button" id="'.$post->ID.'" ptype="'.$post->post_type.'" wpnonce="'.$nonce_str.'" dest_src="'.$thumb_image_src.'" >取得</button>';
		return $btn_get;
	}
	
	public function link_special($post) {
		$link = get_post_meta($post->ID, 'bddb_external_link',true);
		$nonce = wp_create_nonce('douban-spider-'.$post->ID);
		$str = '<button class="button" name="douban_spider_btn" type="button" doulink="'.$link.'" id="'.$post->ID.'"  ptype="'.$post->post_type.'" wpnonce="'.$nonce.'" >抓取</button>';
		return $str;
	}

	public function series_covers_special($post) {
		$links = get_post_meta($post->ID, 'b_series_covers',true);
		$scount = get_post_meta($post->ID, 'b_series_total',true);
		$nonce = wp_create_nonce('bddb-get-scovers-'.$post->ID);
		$str = '<button class="button" name="bddb_get_scovers_btn" type="button" scount="'.$scount.'" slinks="'.$links.'" id="'.$post->ID.'" ptype="'.$post->post_type.'" wpnonce="'.$nonce.'" >生成</button>';
		return $str;
	}

	/**1
	 * 更新分类项目。
	 * @access public
	 * @param int $post_ID	正在编辑的post_ID
	 * @param array $item	要更新的条目
	 * @since 0.0.1
	 */
	public function update_terms($post_ID, $item) {
		if(!is_array($item) || !isset($item['name'])) {
			return '';
		}
		$taxonomy_name = $item['name'];
		if (isset($_POST[$taxonomy_name])) {
			$new_terms_str = htmlentities($_POST[$taxonomy_name]);
			wp_set_post_terms($post_ID, $new_terms_str, $taxonomy_name);
			return $new_terms_str;
		}
		return '';
	}

	/**
	 * 更新分类项目。
	 * @access public
	 * @param int $post_ID	正在编辑的post_ID
	 * @param array $item	要更新的条目
	 * @since 0.0.1
	 */
	function update_meta($post_ID, $item) {
		if(!is_array($item) || !isset($item['name'])) {
			return '';
		}
		$meta_name = $item['name'];
		$strMetaVal = '';
		if (isset($_POST[$meta_name])) {
			$strMetaVal = htmlentities($_POST[$meta_name]);
		}
		if ('boolean_meta' == $item['type']) {
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
	 * 为项目添加默认值。
	 * @access private
	 * @since 0.0.1
	 */
	public function merge_default_column($inItem) {
		if (!is_array($inItem)){
			return $this->default_item;
		}
		return array_merge($this->default_item, $inItem);
	}
};

/**
 * 编辑用电影类
 */
final class BDDB_T_Movie extends BDDB_Template{
	protected $additional_items;
	/**
	 * 构造函数。
	 * @access public
	 * @param	array	$settings	reserved
	 * @since 0.0.1
	 */
	public function __construct($settings = false){
		parent::__construct($settings);
		$this->settings['thumb_width'] = OBLONG_THUMB_WIDTH;
		$this->settings['thumb_height'] = OBLONG_THUMB_HEIGHT;
		$this->self_post_type = 'movie';
		$this->box_title = '完善影片信息';
		$this->common_items['bddb_display_name']['label'] = '电影名';
		$this->common_items['bddb_publish_time']['label'] = '首映年月';
		$this->common_items['bddb_view_time']['label'] = '观看年月';
		$this->additional_items = array(
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
			'm_id_imdb'			=>	array(	'name' => 'm_id_imdb',
											'label' => 'IMDB编号',
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
		);
		$this->total_items = array_merge($this->common_items, $this->additional_items);
		$this->total_items = array_map(array($this, 'merge_default_column'), $this->total_items);
	}
	
};

/**
 * 编辑用书籍类
 */
final class BDDB_T_Book extends BDDB_Template{
	protected $additional_items;
	/**
	 * 构造函数。
	 * @access public
	 * @param	array	$settings	reserved
	 * @since 0.0.1
	 */
	public function __construct($settings = false){
		parent::__construct($settings);
		$this->settings['thumb_width'] = OBLONG_THUMB_WIDTH;
		$this->settings['thumb_height'] = OBLONG_THUMB_HEIGHT;
		$this->self_post_type = 'book';
		$this->box_title = '完善书籍信息';
		$this->common_items['bddb_display_name']['label'] = '书名';
		$this->common_items['bddb_publish_time']['label'] = '出版年月';
		$this->common_items['bddb_view_time']['label'] = '品读年月';
		$this->additional_items = array(
			'b_p_writer'		=>	array(	'name' => 'b_p_writer',
											'label' => '作者',
											'size' => 16,
											'type' => 'tax',
											'comment'=>'<strong>*必填</strong>',
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
			'bddb_id_douban'		=>	array(	'name' => 'bddb_id_douban',
											'label' => '豆瓣ID',
											'size' => 16,
											'comment'=>'',
											),
			'bddb_score_douban'	=>	array(	'name' => 'bddb_score_douban',
											'label' => '豆瓣评分',
											'size' => 16,
											'comment'=>'',
											'inputstyle' => 'number',
											'min' => '2.0',
											'max' => '10.0',
											'step' => '0.1',
											),
			'b_bl_series'	=>	array(	'name' => 'b_bl_series',
											'label' => '丛书',
											'size' => 8,
											'type' => 'boolean_meta',
											'comment'=>'默认不选',
											),
			'b_pub_time_end' => array(	'name' => 'b_pub_time_end',
											'label' => '最终出版时间',
											'size' => 16,
											'type' => 'meta',
											'comment' => '',
											'placeholder' => '年年年年-月月',
											),
			'b_series_covers' => array(	'name' => 'b_series_covers',
											'label' => '系列封面',
											'comment' => array($this, 'series_covers_special'),
											'type' => 'meta',
											'sanitize_callback' => array($this, 'sanitize_series_covers'),
											'placeholder' => '用分号分割',
											),
		);
		$this->total_items = array_merge($this->common_items, $this->additional_items);
		$this->total_items = array_map(array($this, 'merge_default_column'), $this->total_items);
	}
	
	//优化函数
	/**
	 * 优化丛书本数，不填时默认为非丛书，设成1本。
	 * @access public
	 * @param string $str	编辑框中的丛书本数
	 * @return string	丛书本数
	 * @since 0.0.1
	 */
	public function sanitize_series_total($str) {
		$int = intval($str);
		if ($int <= 0) {
			$str = '1';
		}
		return $str;
	}
};

/**
 * 编辑用游戏类
 */
final class BDDB_T_Game extends BDDB_Template{
	protected $additional_items;
	/**
	 * 构造函数。
	 * @access public
	 * @param	array	$settings	reserved
	 * @since 0.0.1
	 */
	public function __construct($settings = false){
		parent::__construct($settings);
		$this->settings['thumb_width'] = OBLONG_THUMB_WIDTH;
		$this->settings['thumb_height'] = OBLONG_THUMB_HEIGHT;
		$this->self_post_type = 'game';
		$this->box_title = '完善游戏信息';
		$this->common_items['bddb_display_name']['label'] = '游戏名';
		$this->common_items['bddb_publish_time']['label'] = '首发年月';
		$this->common_items['bddb_view_time']['label'] = '接触年月';
		$this->additional_items = array(
			'g_genre'		=>		array(	'name' => 'g_genre',
											'label' => '类别',
											'size' => 16,
											'type' => 'tax',
											'comment'=>'',
											),
			'g_platform'	=>		array(	'name' => 'g_platform',
											'label' => '机种',
											'size' => 16,
											'type' => 'tax',
											'placeholder' => 'FC,MD,GB,GBC,GBA,SFC,ARC,PC,PS...',
											'comment'=>'',
											),
			'g_publisher'	=>		array(	'name' => 'g_publisher',
											'label' => '制作方',
											'size' => 16,
											'type' => 'tax',
											'comment'=>'',
											),
			'g_p_producer'	=>		array(	'name' => 'g_p_producer',
											'label' => '制作人',
											'size' => 16,
											'type' => 'tax',
											'comment'=>'',
											'sanitize_callback' => array($this, 'sanitize_name'),
											),
			'g_p_musician'	=>		array(	'name' => 'g_p_musician',
											'label' => '作曲家',
											'size' => 16,
											'type' => 'tax',
											'comment'=>'',
											'sanitize_callback' => array($this, 'sanitize_name'),
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
			'g_score_ign'	=>		array(	'name' => 'g_score_ign',
											'label' => 'IGN评分',
											'size' => 16,
											'inputstyle' => 'number',
											'min' => '0.1',
											'max' => '10.0',
											'step' => '0.1',
											'comment'=>'',
											),
		);
		$this->total_items = array_merge($this->common_items, $this->additional_items);
		$this->total_items = array_map(array($this, 'merge_default_column'), $this->total_items);
	}

	//优化函数
	/**
	 * 优化花费时间，不填时默认0。
	 * @access public
	 * @param string $str	编辑框中的花费时间
	 * @return string	花费时间
	 * @since 0.0.1
	 */
	public function sanitize_cost_time($str) {
		$int = intval($str);
		if ($int <= 0) {
			$str = '0';
		}
		return $str;
	}
};

/**
 * 编辑用专辑类
 */
final class BDDB_T_Album extends BDDB_Template{
	protected $additional_items;
	/**
	 * 构造函数。
	 * @access public
	 * @param	array	$settings	reserved
	 * @since 0.0.1
	 */
	public function __construct($settings = false){
		parent::__construct($settings);
		$this->self_post_type = 'album';
		$this->settings['thumb_width'] = SQUARE_THUMB_WIDTH;
		$this->settings['thumb_height'] = SQUARE_THUMB_HEIGHT;
		$this->box_title = '完善专辑信息';
		$this->common_items['bddb_display_name']['label'] = '专辑名';
		$this->common_items['bddb_publish_time']['label'] = '发行年月';
		$this->common_items['bddb_view_time']['label'] = '欣赏年月';
		$this->additional_items = array(
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
			'a_p_producer'	=>		array(	'name' => 'a_p_producer',
											'label' => '制作人',
											'size' => 16,
											'type' => 'tax',
											'sanitize_callback' => array($this, 'sanitize_name'),
											),
			'a_quantities'	=>		array(	'name' => 'a_quantities',
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
											'type' => 'boolean_meta',
											),
			'bddb_id_douban'		=>	array(	'name' => 'bddb_id_douban',
											'label' => '豆瓣ID',
											'size' => 16,
											),
			'bddb_score_douban'	=>	array(	'name' => 'bddb_score_douban',
											'label' => '豆瓣评分',
											'size' => 16,
											'inputstyle' => 'number',
											'min' => '2.0',
											'max' => '10.0',
											'step' => '0.1',
											'comment'=>'',
											),
		);
		$this->total_items = array_merge($this->common_items, $this->additional_items);
		$this->total_items = array_map(array($this, 'merge_default_column'), $this->total_items);
	}

};

//movie/brand