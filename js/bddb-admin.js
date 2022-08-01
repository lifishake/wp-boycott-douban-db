/**
 * @file	class-admin.js
 * @brief	处理后台编辑画面
 * @date	2021-12-21
 * @author	大致
 * @version	0.5.2
 * @since	0.0.1
 * 
 */

/**
 * 
 * @param {number} dec 
 * @param {number} length 
 * @returns {Array}
 */
function dec_to_hex_string(dec, length) {
    var hex = dec.toString(16).toUpperCase();
    if (hex.length < length) {
        hex = new Array( length - hex.length + 1 ).join( '0' ) + hex;
    }
    return hex;
}

/**
 * 
 * @param {Array} rgb_array 
 * @returns {string}
 */
function rgb_to_hex_string(rgb_array) {
    var hex_string = '';
    for( var i = 0; i < rgb_array.length; i++) {
        hex_string += dec_to_hex_string(rgb_array[i], 2);
    }
    return '#' + hex_string;
}

/**
 * 
 * @param {Array} rgb_array 
 * @returns {string}
 */
function rgb_to_rgb_string(rgb_array) {
    var rgb_string = 'RGB(';
    for( var i = 0; i < rgb_array.length; i++) {
        rgb_string += rgb_array[i];
        if ( i< 2) {
            rgb_string += ',';
        }
    }
    return rgb_string + ')';
}

/**
 * 
 * @param {Event} event 
 */
function StopMouseWheel(event) {
    event = event || window.event;
    event.preventDefault();
}

jQuery(document).ready(function($) {
	//$stsbox = document.getElementsByName('ajax-status');
	var mypicbar = document.getElementById('pic-status');
	var myfetchstsbar = document.getElementById('fetch-status');
	var mythumbnail = document.getElementById('img_poster_thumbnail');

    //nomouse_names被Editor本地化，如果有number类型的input，该数组非空。
    if (nomouse_names === undefined) {
        //nothing
    } else {
        nomouse_names.forEach(element => {
            if ($('input[name="'+element+'"]').length>0)
                $('input[name="'+element+'"]')[0].addEventListener('DOMMouseScroll', StopMouseWheel, false);
        });
    }

    //抓取按钮
    $('button[name="douban_spider_btn"]').click(function(){
        
        var link_bar = document.getElementsByName('bddb_external_link');
        var ddllkk=this.getAttribute('doulink');
        if (link_bar.length == 1) {
            ddllkk=link_bar[0].value;
        }
        var data = {
            action: 'bddb_douban_fetch',
            nonce: this.getAttribute('wpnonce'),
            id:this.getAttribute('pid'),
            ptype:this.getAttribute('ptype'),
			doulink:ddllkk,
		};
        $.ajax({
            url: ajaxurl,
            type: 'GET',
            data: data,
            cache: false,
            beforeSend: function () {
				mypicbar.value="网页抓取中...";
			},
            success:function(response){
                mypicbar.value=response.content;
                var the_input = document.getElementsByName("bddb_display_name");
				var update_title = false;
				if ($('#title-prompt-text').length == 1) {
					if ($('#title-prompt-text')[0].innerText === "Enter title here") {
						$('#title-prompt-text')[0].innerHTML = "";
						update_title = true;
					}
				}
                if (the_input.length == 1 && response.result.title.length > 0){
                    the_input[0].value = response.result.title;
					the_input = document.getElementById("title");
					if (the_input.value.length == 0) {
						the_input.value = response.result.title;
					}
                }
                //通用
                the_input = document.getElementsByName("bddb_original_name");
                if (the_input.length == 1 && response.result.original_name !== undefined && !the_input[0].value){
                    the_input[0].value = response.result.original_name;
                }
                the_input = document.getElementsByName("bddb_poster_link");
                if (the_input.length == 1 && response.result.pic !== undefined){
                    the_input[0].value = response.result.pic;
                }
                the_input = document.getElementsByName("bddb_score_douban");
                if (the_input.length == 1 && response.result.average_score !== undefined){
                    the_input[0].value = response.result.average_score;
                }
                the_input = document.getElementsByName("bddb_id_douban");
                if (the_input.length == 1 && response.result.dou_id !== undefined){
                    the_input[0].value = response.result.dou_id;
                }
                              
                the_input = document.getElementsByName("bddb_publish_time");
                if (the_input.length == 1 && response.result.pubdate !== undefined){
                    the_input[0].value = response.result.pubdate;
                }
                the_input = document.getElementsByName("bddb_aka");
                if (the_input.length == 1 && response.result.akas !== undefined && !the_input[0].value){
                    the_input[0].value = response.result.akas;
                }
                if (response.result.url !== undefined && response.result.url) {
                    link_bar[0].value = response.result.url;
                }

                //书
                the_input = document.getElementsByName("b_region");
                if (the_input.length == 1 && response.result.country !== undefined){
                    the_input[0].value = response.result.country;
                }
                the_input = document.getElementsByName("b_publisher");
                if (the_input.length == 1 && response.result.publisher !== undefined){
                    the_input[0].value = response.result.publisher;
                }
                the_input = document.getElementsByName("b_p_writer");
                if (the_input.length == 1 && response.result.author !== undefined){
                    the_input[0].value = response.result.author;
                }
                the_input = document.getElementsByName("b_p_translator");
                if (the_input.length == 1 && response.result.translator !== undefined){
                    the_input[0].value = response.result.translator;
                }
                the_input = document.getElementsByName("b_series_total");
                if (the_input.length == 1 && response.result.series_total !== undefined){
                    the_input[0].value = response.result.series_total;
                    if (response.result.series_total > 1) {
                        document.getElementsByName("b_bl_series")[0].checked = true;
                    }
                }

                //影
                the_input = document.getElementsByName("m_region");
                if (the_input.length == 1 && response.result.country !== undefined){
                    $str_regin = response.result.country;
                    $iPos = $str_regin.indexOf(",");
                    if ($iPos>0) {
                        the_input[0].value = $str_regin.substring(0, $iPos );
                    }else {
                        the_input[0].value = response.result.country;
                    }
                }
                the_input = document.getElementsByName("m_genre");
                if (the_input.length == 1 && response.result.genre !== undefined){
                    the_input[0].value = response.result.genre;
                }

                the_input = document.getElementsByName("m_p_director");
                if (the_input.length == 1 && response.result.director !== undefined){
                    the_input[0].value = response.result.director;
                }
                the_input = document.getElementsByName("m_p_actor");
                if (the_input.length == 1 && response.result.actor !== undefined){
                    the_input[0].value = response.result.actor;
                }
                the_input = document.getElementsByName("m_p_screenwriter");
                if (the_input.length == 1 && response.result.screenwriter !== undefined){
                    the_input[0].value = response.result.screenwriter;
                }
                the_input = document.getElementsByName("m_id_imdb");
                if (the_input.length == 1 && response.result.imdbid !== undefined){
                    the_input[0].value = response.result.imdbid;
                }
                the_input = document.getElementsByName("m_score_imdb");
                if (the_input.length == 1 && response.result.imdb_score !== undefined){
                    the_input[0].value = response.result.imdb_score;
                }
                the_input = document.getElementsByName("m_length");
                if (the_input.length == 1 && response.result.m_length !== undefined){
                    the_input[0].value = response.result.m_length;
                }
                //游
                the_input = document.getElementsByName("g_genre");
                if (the_input.length == 1 && response.result.genre !== undefined){
                    the_input[0].value = response.result.genre;
                }

                //碟
                the_input = document.getElementsByName("a_genre");
                if (the_input.length == 1 && response.result.genre !== undefined){
                    the_input[0].value = response.result.genre;
                }
                the_input = document.getElementsByName("a_region");
                if (the_input.length == 1 && response.result.country !== undefined){
                    $str_regin = response.result.country;
                    $iPos = $str_regin.indexOf(",");
                    if ($iPos>0) {
                        the_input[0].value = $str_regin.substring(0, $iPos );
                    }else {
                        the_input[0].value = response.result.country;
                    }
                }
                the_input = document.getElementsByName("a_p_musician");
                if (the_input.length == 1 && response.result.artist !== undefined){
                    the_input[0].value = response.result.artist;
                }
                the_input = document.getElementsByName("a_quantity");
                if (the_input.length == 1 && response.result.quantity !== undefined){
                    the_input[0].value = response.result.quantity;
                }
                the_input = document.getElementsByName("a_publisher");
                if (the_input.length == 1 && response.result.publisher !== undefined){
                    the_input[0].value = response.result.publisher;
                }

				myfetchstsbar.value = "网页已抓取.";
                mypicbar.value="网页抓取完毕.";
            },
            error: function(request) {
                mypicbar.value="网页抓取异常";
			},
        });
    })

    //取图片按钮
	$('button[name="bddb_get_pic_btn"]').click(function(){
		var pic_bar = document.getElementsByName("bddb_poster_link");
		var dest_pic = this.getAttribute('dest_src');
		if (pic_bar.length != 1) {
			return;
		}
		var pic_link = pic_bar[0].value;
		var data = {
            action: 'bddb_get_pic',
            nonce: this.getAttribute('wpnonce'),
            id:this.getAttribute('pid'),
            ptype:this.getAttribute('ptype'),
			piclink:pic_link,
		};
		$.ajax({
            url: ajaxurl,
            type: 'POST',
            data: data,
            cache: false,
            success:function(response){
				mythumbnail.setAttribute('src',dest_pic+"?tl="+Math.random());
                mypicbar.value="图片取得成功.";
            },
            beforeSend: function () {
                mythumbnail.setAttribute('src','');
				mypicbar.value="图片获得中...";
			},
            error: function(request) {
				mypicbar.value="取图片异常.";
			},
        });
	})

    //取imdb图片按钮
	$('button[name="bddb_get_imdbpic_btn"]').click(function(){
		var pic_bar = document.getElementsByName("m_id_imdb");
		var dest_pic = this.getAttribute('dest_src');
		if (pic_bar.length != 1) {
			return;
		}
		var pic_link = pic_bar[0].value;
		var data = {
            action: 'bddb_get_imdbpic',
            nonce: this.getAttribute('wpnonce'),
            id:this.getAttribute('pid'),
			imdbno:pic_link,
		};
		$.ajax({
            url: ajaxurl,
            type: 'POST',
            data: data,
            cache: false,
            success:function(response){
				mythumbnail.setAttribute('src',dest_pic+"?tl="+Math.random());
                mypicbar.value="图片取得成功.";
            },
            beforeSend: function () {
                mythumbnail.setAttribute('src','');
				mypicbar.value="图片获得中...";
			},
            error: function(request) {
				mypicbar.value="取图片异常.";
			},
        });
	})

    //取GA信息按钮，鸡肋
	$('button[name="bddb_get_giantbomb_btn"]').click(function(){
		var gbid_edit = document.getElementsByName("g_giantbomb_id");
		var language_edit = document.getElementsByName("g_language");
		var platform_deit = document.getElementsByName("g_platform");
		//var dest_pic = this.getAttribute('dest_src');
		
		var data = {
            action: 'bddb_get_from_giantbomb',
            nonce: this.getAttribute('wpnonce'),
            id:this.getAttribute('pid'),
			giantbombno:gbid_edit[0].value,
			language:language_edit[0].value,
			platform:platform_deit[0].value,
		};
		$.ajax({
            url: ajaxurl,
            type: 'GET',
            data: data,
            success:function(response){
				var the_input = document.getElementsByName("bddb_original_name");
                var update_title = false;
				if ($('#title-prompt-text').length == 1) {
					if ($('#title-prompt-text')[0].innerText === "Enter title here") {
						$('#title-prompt-text')[0].innerHTML = "";
						update_title = true;
					}
				}
                if (the_input.length == 1 && response.content.original_name){
                    the_input[0].value = response.content.original_name;
                }
                the_input = document.getElementsByName("bddb_external_link");
                if (the_input.length == 1 && response.content.url !== undefined && !the_input[0].value){
                    the_input[0].value = response.content.url;
                }
				the_input = document.getElementsByName("bddb_aka");
                if (the_input.length == 1 && response.content.akas !== undefined){
                    the_input[0].value = response.content.akas;
                }
				the_input = document.getElementsByName("bddb_publish_time");
                if (the_input.length == 1 && response.content.pubdate !== undefined){
                    the_input[0].value = response.content.pubdate;
                }
				the_input = document.getElementsByName("bddb_poster_link");
                if (the_input.length == 1 && response.content.pic !== undefined){
                    the_input[0].value = response.content.pic;
                }
                the_input = document.getElementsByName("g_genre");
                if (the_input.length == 1 && response.content.genre !== undefined && !the_input[0].value){
                    the_input[0].value = response.content.genre;
                }
                the_input = document.getElementsByName("g_publisher");
                if (the_input.length == 1 && response.content.publisher !== undefined){
                    the_input[0].value = response.content.publisher;
                }
                the_input = document.getElementsByName("g_platform");
                if (the_input.length == 1 && response.content.platform !== undefined && !the_input[0].value){
                    the_input[0].value = response.content.platform;
                }
                myfetchstsbar.value = "网页已抓取.";
                mypicbar.value="giantbomb取得成功.";
            },
            beforeSend: function () {
                //$("#img_poster_thumbnail").attr('src','');
				mypicbar.value="giantbomb获得中...";
			},
            error: function(request) {
				mypicbar.value="giantbomb取得异常.";
			},
        });
	})

    //批量取封面按钮
    $('button[name="bddb_get_scovers_btn"]').click(function(){
		var pic_bar = document.getElementsByName("b_series_covers");
		var count_bar = document.getElementsByName("b_series_total");

		if (pic_bar.length != 1) {
			return;
		}
		var pic_link = pic_bar[0].value;
		var stotal = count_bar[0].value;
        if(!pic_link.length){
            pic_link = this.getAttribute('slinks');
        }
		var data = {
            action: 'bddb_get_scovers',
            nonce: this.getAttribute('wpnonce'),
            id:this.getAttribute('pid'),
            ptype:this.getAttribute('ptype'),
			slinks:pic_link,
			stotal:stotal,
		};
		$.ajax({
            url: ajaxurl,
            type: 'POST',
            data: data,
            cache: false,
            success:function(response){
                mypicbar.value="多图片抓取成功.";
            },
            beforeSend: function () {
				mypicbar.value="多图片获得中...";
			},
            error: function(request) {
				mypicbar.value="取多图片异常.";
			},
        });
	})

    //追加标签
	$('.box-tag').click(function(){
		var input_bar = document.getElementsByName(this.getAttribute('data'));
		var myself = this.innerText;
		var original_value = input_bar[0].value;
		var new_value = "";
		if (original_value.length>0) {
			new_value = original_value + ", " + myself;
		} else {
			new_value = myself;
		}
		input_bar[0].value = new_value;
	})
})
