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

function bddb_n_pos($del, $find, $n){
	$start = 0;
	for($i=0; $i < $n; ++$i) {
		$pos = strpos($find, $del, $start);
		if (false===$pos) {
			return false;
		}
		$start = $pos;
	}
	return $start - 1;
}

/**
 * 编辑用模板类，不可直接创建对象
 */
class BDDB_Editor {
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
	public function __construct($settings){
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
			'limit' => 10,//TODO
			'show_admin_column' => false,
		);
		$this->self_post_type = false;
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
											'comment' => array($this, 'echo_fetch_button'),
											'placeholder' => 'http://',
											'sanitize_callback' => array($this,'sanitize_link')
											),
			'bddb_poster_link'	=> array(	'name' => 'bddb_poster_link',
											'label' => '图片链接',
											'comment'=> array($this, 'echo_poster_button'),
											'placeholder' => 'http://',
											),
			'bddb_publish_time' => array(	'name' => 'bddb_publish_time',
											'label' => '出版时间',
											'size' => 16,
											'comment' => '',
											'sanitize_callback' => array($this, 'sanitize_publish_time'),
											'placeholder' => 'YYYY-MM',
											),
			'bddb_view_time' => array(		'name' => 'bddb_view_time',
											'label' => '邂逅年月',
											'size' => 16,
											'comment'=>'<strong>*必填</strong>，不填默认为保存年月。',
											'sanitize_callback' => array($this, 'sanitize_view_time'),
											'placeholder' => 'YYYY-MM',
											'show_admin_column' => true,
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
											'show_admin_column' => true,
											),
			'country' => array(				'name' => 'country',
											'label' => '地区',
											'size' => 16,
											'type' => 'tax',
											'comment' => '',
											),
		);
	}
	
	//外部接口
	/**
	 * 创建编辑盒子。
	 * @access public
	 * @since 0.0.1
	 */
	public function add_meta_box($post_type) {
		if (empty($this->self_post_type)){
			$this->set_working_mode($post_type);
		}
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
		$title = sprintf("完善%s信息", $addi);
		add_meta_box('bddbstsdiv', '状态显示', array($this, 'show_status_meta_box'));
		add_meta_box('bddbcommondiv', $title, array($this, 'show_meta_box'));
	}
	
	/**
	 * 显示图片工具
	 * @access public
	 * @param object $post	正在编辑的wp的post
	 * @since 0.0.1
	 */
	public function show_status_meta_box($post) {
		$dir = BDDB_GALLERY_DIR;
		$thumb_name = sprintf("%sthumbnails/%s_%013d.jpg", $dir, $post->post_type, $post->ID);
		$is_got_thumb= is_file($thumb_name);
		$thumb_image_src = BDDB_GALLERY_URL.sprintf("thumbnails/%s_%013d.jpg",$post->post_type, $post->ID);
		if ($is_got_thumb) {
			$thumb_src = $thumb_image_src;
		} else {
			$thumb_src = BDDB_PLUGIN_URL.sprintf("img/nocover_%d_%d.png", $this->settings['thumb_width'], $this->settings['thumb_height']);
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
				$comment_str = $this->get_tax_hint_str($post->ID, $arg);
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
	
	/**
	 * 后台类别列表增加标题。
	 * @access public
	 * @param string 	$columns	修改前的标题
	 * @param string 	$post_type	list的种类
	 * @since 0.1.0
	 */
	public function get_admin_edit_headers($columns, $post_type) {
		if (!$this->self_post_type){
			if (!$this->set_working_mode($post_type)){
				return $columns;
			}
		}
		unset($columns['date']);
		foreach ($this->total_items as $arg) {
			if ($arg['show_admin_column']) {
				$columns[$arg['name']] = $arg['label'];
			}
		}
		$columns['date'] = 'Date';
		return $columns;
	}
	
	/**
	 * 后台类别列表增加显示内容。
	 * @access public
	 * @param string 	$column_name	列标题
	 * @param string 	$id				post_ID
	 * @since 0.1.0
	 */
	public function manage_movie_admin_columns($column_name, $id){
		$this->get_column('movie',$column_name, $id);
	}
	
	/**
	 * 后台类别列表增加显示内容。
	 * @access public
	 * @param string 	$column_name	列标题
	 * @param string 	$id				post_ID
	 * @since 0.1.0
	 */
	public function manage_book_admin_columns($column_name, $id){
		$this->get_column('book',$column_name, $id);
	}
	
	/**
	 * 后台类别列表增加显示内容。
	 * @access public
	 * @param string 	$column_name	列标题
	 * @param string 	$id				post_ID
	 * @since 0.1.0
	 */
	public function manage_game_admin_columns($column_name, $id){
		$this->get_column('game',$column_name, $id);
	}
	
	/**
	 * 后台类别列表增加显示内容。
	 * @access public
	 * @param string 	$column_name	列标题
	 * @param string 	$id				post_ID
	 * @since 0.1.0
	 */
	public function manage_album_admin_columns($column_name, $id){
		$this->get_column('album',$column_name, $id);
	}
	
	/**
	 * 后台类别列表增加显示内容。
	 * @access public
	 * @param string 	$post_type
	 * @param string 	$column_name	列标题
	 * @param string 	$id				post_ID
	 * @since 0.1.0
	 */
	private function get_column($post_type, $column_name, $id){
		$this->set_working_mode($post_type);
		if (!array_key_exists($column_name, $this->total_items)){
			echo "not found";
		}else{
			$arg = $this->total_items[$column_name];
			if ('meta'==$arg['type']) {
				echo get_post_meta($id, $column_name, true);
			} else {
				echo $arg['type'];
			}
		}
	}
	
	/**
	 * 后台类别列表增加排序属性。
	 * @access public
	 * @param array 	$columns	已经存在的排序列
	 * @since 0.1.0
	 */
	public function add_movie_sortable_columns($columns){
		return $this->set_sortalbe_columns('movie', $columns);
	}
	/**
	 * 后台类别列表增加排序属性。
	 * @access public
	 * @param array 	$columns	已经存在的排序列
	 * @since 0.1.0
	 */
	public function add_book_sortable_columns($columns){
		return $this->set_sortalbe_columns('book', $columns);
	}
	/**
	 * 后台类别列表增加排序属性。
	 * @access public
	 * @param array 	$columns	已经存在的排序列
	 * @since 0.1.0
	 */
	public function add_game_sortable_columns($columns){
		return $this->set_sortalbe_columns('game', $columns);
	}
	/**
	 * 后台类别列表增加排序属性。
	 * @access public
	 * @param array 	$columns	已经存在的排序列
	 * @since 0.1.0
	 */
	public function add_album_sortable_columns($columns){
		return $this->set_sortalbe_columns('album', $columns);
	}
	
	/**
	 * 后台类别列表增加排序属性。
	 * @access private
	 * @param string 	$post_type
	 * @param array 	$columns	已经存在的排序列
	 * @since 0.1.0
	 */
	private function set_sortalbe_columns($post_type, $columns){
		$this->set_working_mode($post_type);
		foreach($this->total_items as $arg) {
			if ($arg['show_admin_column']){
				$columns[$arg['name']] = $arg['name'];
			}
		}
		return $columns;
	}
	
	//优化函数
	/**
	 * 优化个人评分。
	 * @access protected
	 * @param string $str	编辑框中的评分
	 * @return string	-1~100的十进制字符串
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
	 * @access protected
	 * @param string $str	编辑框中的原名
	 * @return string	原名
	 * @since 0.0.1
	 */
	protected function sanitize_original_name($str) {
		if ($str == "" && isset($_POST['bddb_display_name'])) {
			$str = htmlentities($_POST['bddb_display_name']);
		}
		return $str;
	}
	
	/**
	 * 优化评价。如果输入参数为空,则显示“无聊到无话可说”
	 * @access protected
	 * @param string $str	编辑框中的原名
	 * @return string	原名
	 * @since 0.0.1
	 */
	protected function sanitize_personal_review($str) {
		if ($str == "" && isset($_POST['bddb_display_name'])) {
			$str = "《".htmlentities($_POST['bddb_display_name'])."》实在是无聊到无语。";
		}
		return $str;
	}
	/**
	 * 优化出品时间。改为年-月格式。如果只输入年则默认定位到该年1月
	 * @access protected
	 * @param string $str	编辑框中的出品年份
	 * @return string	修改后的出品年份
	 * @since 0.0.1
	 */
	protected function sanitize_publish_time($str) {
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
	 * @access 	protected
	 * @param 	string $str	编辑框中的所有封面地址
	 * @return 	string	优化后的封面地址
	 * @since 0.0.1
	 */
	protected function sanitize_series_covers($str) {
		//之前油猴采集到的链接用逗号分隔，替换成分号。
		$str = str_replace(",", ";", $str);
		return $this->sanitize_link($str);
	}
	/**
	 * 优化链接输入。
	 * @access 	protected
	 * @param 	string $str	编辑框中的所有封面地址
	 * @return 	string	优化后的封面地址
	 * @since 0.0.1
	 */
	protected function sanitize_link($str) {
		return htmlspecialchars_decode($str);
	}
	/**
	 * 优化人名输入。
	 * @access 	protected
	 * @param 	string $str	编辑框中的人名
	 * @return 	string	优化后的人名
	 * @since 0.0.1
	 */
	protected function sanitize_name($str) {
		if (strpos($str, ',')) {
			$arr_person = explode(",", $str);
			if (count($arr_person) > 10) {
				$arr_person = array_slice($arr_person, 0, 10);
				$str = implode(', ',$arr_person);
			}
		}		
		return str_replace(".","·", $str);
	}

	/**
	 * 优化接触时间，不填时默认为当天。
	 * @access protected
	 * @param string $str	编辑框中的接触时间
	 * @return string	观影/阅读/游戏/欣赏时间
	 * @since 0.0.1
	 */
	protected function sanitize_view_time($str) {
		if ('' == $str) {
			$str = date('Y-m');
		} elseif (strtotime(date("Y-m-d",strtotime($str))) == strtotime($str)) {
			$str = date("Y-m", strtotime($str));
		}
		return $str;
	}
	
	//优化函数
	/**
	 * 优化花费时间，不填时默认0。
	 * @access public
	 * @param string $str	编辑框中的花费时间
	 * @return string	花费时间
	 * @since 0.0.1
	 */
	protected function sanitize_cost_time($str) {
		$int = intval($str);
		if ($int <= 0) {
			$str = '0';
		}
		return $str;
	}
	
	/**
	 * 优化丛书本数，不填时默认为非丛书，设成1本。
	 * @access protected
	 * @param string $str	编辑框中的丛书本数
	 * @return string	丛书本数
	 * @since 0.0.1
	 */
	protected function sanitize_series_total($str) {
		$int = intval($str);
		if ($int <= 0) {
			$str = '1';
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
		if (empty($this->self_post_type)){
			$this->set_working_mode($post->post_type);
		}
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
	
	/**
	 * 根据附加项内容生成文章正文。
	 * @access public
	 * @param array $data	要保存的post_data
	 * @param array $post	还没有落地的post_data
	 * @since 0.0.1
	 */
	public function generate_content($data, $postarr ) {
		if (!isset($postarr['post_type']) || 
			!in_array($postarr['post_type'], array('movie', 'book', 'game', 'album'))) {
			return $data;
		}
		//使用_POST中的数据,它们来自box控件
		$this->set_working_mode($postarr['post_type']);
		$data['post_content'] = 'ID:'.$postarr['ID']."\n";
		foreach ($this->total_items as $item) {
			if (isset($_POST[$item['name']]) && !empty($_POST[$item['name']]) ) {
				$data['post_content'] .= sprintf("%s:%s\n",$item['label'], $_POST[$item['name']]);
			}
		}
		return $data;
	}
	
	//说明栏中的特殊项
	/**
	 * 获取封面的按钮。
	 * @access protected
	 * @param object $post	
	 * @since 0.0.1
	 */
	protected function echo_poster_button( $post ) {
		$nonce_str = wp_create_nonce('bddb-get-pic-'.$post->ID);
		$thumb_image_src = BDDB_GALLERY_URL.sprintf("thumbnails/%s_%013d.jpg",$post->post_type, $post->ID);
		$btn_get = '<button class="button" name="bddb_get_pic_btn" type="button" id="'.$post->ID.'" ptype="'.$post->post_type.'" wpnonce="'.$nonce_str.'" dest_src="'.$thumb_image_src.'" >取得</button>';
		return $btn_get;
	}
	
	/**
	 * 抓取按钮。
	 * @access protected
	 * @param object $post	
	 * @since 0.0.1
	 */
	protected function echo_fetch_button($post) {
		$link = get_post_meta($post->ID, 'bddb_external_link',true);
		$nonce = wp_create_nonce('douban-spider-'.$post->ID);
		$str = '<button class="button" name="douban_spider_btn" type="button" doulink="'.$link.'" id="'.$post->ID.'"  ptype="'.$post->post_type.'" wpnonce="'.$nonce.'" >抓取</button>';
		return $str;
	}

	/**
	 * 取多张封面按钮。。
	 * @access protected
	 * @param object $post	
	 * @since 0.0.1
	 */
	protected function echo_series_covers_button($post) {
		$links = get_post_meta($post->ID, 'b_series_covers',true);
		$scount = get_post_meta($post->ID, 'b_series_total',true);
		$nonce = wp_create_nonce('bddb-get-scovers-'.$post->ID);
		$str = '<button class="button" name="bddb_get_scovers_btn" type="button" scount="'.$scount.'" slinks="'.$links.'" id="'.$post->ID.'" ptype="'.$post->post_type.'" wpnonce="'.$nonce.'" >生成</button>';
		return $str;
	}

	/**
	 * 为taxinomy类型的输入项增加辅助标签。
	 * @access private
	 * @param int $id	正在编辑的post_ID
	 * @param array $item	要更新的条目
	 * @since 0.1.0
	 */
	private function get_tax_hint_str($id, $item) {
		if (!is_array($item) || !isset($item['type']) || $item['type']!=='tax') {
			return '';
		}
		$ret = '';
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

	//私有工具函数
	/**
	 * 更新分类项目。
	 * @access private
	 * @param int $post_ID	正在编辑的post_ID
	 * @param array $item	要更新的条目
	 * @since 0.0.1
	 */
	private function update_terms($post_ID, $item) {
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
	 * @access private
	 * @param int $post_ID	正在编辑的post_ID
	 * @param array $item	要更新的条目
	 * @since 0.0.1
	 */
	private function update_meta($post_ID, $item) {
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
	
	//设定工作模式相关
	/**
	 * 为项目添加默认值。
	 * @access protected
	 * @since 0.0.1
	 */
	protected function merge_default_column($inItem) {
		if (!is_array($inItem)){
			return $this->default_item;
		}
		return array_merge($this->default_item, $inItem);
	}
	
	/**
	 * 根据post_type设置工作模式,主要是设定好每个种类的条目。
	 * @param string $post_type
	 * @access private
	 * @since 0.1.0
	 */
	private function set_working_mode($post_type){
		if (!in_array($post_type, array('movie', 'book', 'game', 'album'))) {
			return false;
		}
		$this->self_post_type = $post_type;
		if ('album' == $post_type) {
			$this->settings['thumb_width'] = SQUARE_THUMB_WIDTH;
			$this->settings['thumb_height'] = SQUARE_THUMB_WIDTH;
		}else {
			$this->settings['thumb_width'] = OBLONG_THUMB_WIDTH;
			$this->settings['thumb_height'] = OBLONG_THUMB_HEIGHT;
		}
		if (is_callable(array($this,"set_additional_items_{$post_type}"))){
			call_user_func(array($this,"set_additional_items_{$post_type}"));
		} else {
			return false;
		}
		$this->total_items = array_map(array($this, 'merge_default_column'), $this->total_items);
		return true;
	}
	/**
	 * 设置电影的表示条目。
	 * @access private
	 * @since 0.1.0
	 */
	private function set_additional_items_movie() {
		$this->common_items['bddb_display_name']['label'] = '电影名';
		$this->common_items['bddb_publish_time']['label'] = '首映年月';
		$this->common_items['bddb_view_time']['label'] = '观看年月';
		$additional_items = array(
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
		$this->total_items = array_merge($this->common_items, $additional_items);
	}
	/**
	 * 设置电影的表示条目。
	 * @access private
	 * @since 0.1.0
	 */
	private function set_additional_items_book() {
		$this->common_items['bddb_display_name']['label'] = '书名';
		$this->common_items['bddb_publish_time']['label'] = '出版年月';
		$this->common_items['bddb_view_time']['label'] = '品读年月';
		$additional_items = array(
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
											),
			'bddb_score_douban'	=>	array(	'name' => 'bddb_score_douban',
											'label' => '豆瓣评分',
											'size' => 16,
											'inputstyle' => 'number',
											'min' => '2.0',
											'max' => '10.0',
											'step' => '0.1',
											),
			'b_misc_brand'		=>	array(	'name' => 'b_misc_brand',
											'label' => '特殊头衔',
											'size' => 16,
											'type' => 'tax',
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
	 * 设置电影的表示条目。
	 * @access private
	 * @since 0.1.0
	 */
	private function set_additional_items_game() {
		$this->common_items['bddb_display_name']['label'] = '游戏名';
		$this->common_items['bddb_publish_time']['label'] = '首发年月';
		$this->common_items['bddb_view_time']['label'] = '接触年月';
		$additional_items = array(
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
			'g_p_producer'	=>		array(	'name' => 'g_p_producer',
											'label' => '制作人',
											'size' => 16,
											'type' => 'tax',
											'sanitize_callback' => array($this, 'sanitize_name'),
											),
			'g_p_musician'	=>		array(	'name' => 'g_p_musician',
											'label' => '作曲家',
											'size' => 16,
											'type' => 'tax',
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
											'show_admin_column' => true,
											),
			'g_score_ign'	=>		array(	'name' => 'g_score_ign',
											'label' => 'IGN评分',
											'size' => 16,
											'inputstyle' => 'number',
											'min' => '0.1',
											'max' => '10.0',
											'step' => '0.1',
											),
		);
		$this->total_items = array_merge($this->common_items, $additional_items);
	}
	/**
	 * 设置电影的表示条目。
	 * @access private
	 * @since 0.1.0
	 */
	private function set_additional_items_album() {
		$this->common_items['bddb_display_name']['label'] = '专辑名';
		$this->common_items['bddb_publish_time']['label'] = '发行年月';
		$this->common_items['bddb_view_time']['label'] = '欣赏年月';
		$additional_items = array(
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
											),
		);
		$this->total_items = array_merge($this->common_items, $additional_items);
	}
};

