function dec_to_hex_string(dec, length) {
    var hex = dec.toString(16).toUpperCase();
    if (hex.length < length) {
        hex = new Array( length - hex.length + 1 ).join( '0' ) + hex;
    }
    return hex;
}

function rgb_to_hex_string(rgb_array) {
    var hex_string = '';
    for( var i = 0; i < rgb_array.length; i++) {
        hex_string += dec_to_hex_string(rgb_array[i], 2);
    }
    return '#' + hex_string;
}

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

jQuery(document).ready(function($) {
    $('button[name="douban_spider_btn"]').click(function(){
        $stsbox = document.getElementsByName('ajax-status');
        var link_bar = document.getElementsByName('bddb_external_link');
        var ddllkk=this.getAttribute('doulink');
        if (link_bar.length == 1) {
            ddllkk=link_bar[0].value;
        }
        var mybar = $stsbox[0];
        var data = {
            action: 'bddb_douban_fetch',
            nonce: this.getAttribute('wpnonce'),
            id:this.getAttribute('id'),
            ptype:this.getAttribute('ptype'),
			doulink:ddllkk,
		};
        $.ajax({
            url: ajaxurl,
            type: 'GET',
            data: data,
            cache: false,
            beforeSend: function () {
				mybar.value="网页抓取中...";
			},
            success:function(response){
                mybar.value=response.content;
                var the_input = document.getElementsByName("bddb_display_name");
                if (the_input.length == 1){
                    the_input[0].value = response.result.title;
                }
                the_input = document.getElementsByName("bddb_original_name");
                if (the_input.length == 1){
                    the_input[0].value = response.result.original_name;
                }
                the_input = document.getElementsByName("bddb_poster_link");
                if (the_input.length == 1){
                    the_input[0].value = response.result.pic;
                }
                the_input = document.getElementsByName("bddb_score_douban");
                if (the_input.length == 1){
                    the_input[0].value = response.result.average_score;
                }
                the_input = document.getElementsByName("bddb_id_douban");
                if (the_input.length == 1){
                    the_input[0].value = response.result.dou_id;
                }
                the_input = document.getElementsByName("country");
                if (the_input.length == 1){
                    the_input[0].value = response.result.country;
                }
                the_input = document.getElementsByName("b_publisher");
                if (the_input.length == 1){
                    the_input[0].value = response.result.publisher;
                }
                the_input = document.getElementsByName("bddb_publish_time");
                if (the_input.length == 1){
                    the_input[0].value = response.result.pubdate;
                }
                the_input = document.getElementsByName("m_genre");
                if (the_input.length == 1){
                    the_input[0].value = response.result.genre;
                }
                the_input = document.getElementsByName("m_p_director");
                if (the_input.length == 1){
                    the_input[0].value = response.result.director;
                }
                the_input = document.getElementsByName("m_p_actor");
                if (the_input.length == 1){
                    the_input[0].value = response.result.actor;
                }
                the_input = document.getElementsByName("m_p_screenwriter");
                if (the_input.length == 1){
                    the_input[0].value = response.result.screenwriter;
                }
                the_input = document.getElementsByName("m_id_imdb");
                if (the_input.length == 1){
                    the_input[0].value = response.result.imdbid;
                }
                the_input = document.getElementsByName("m_score_imdb");
                if (the_input.length == 1){
                    the_input[0].value = response.result.imdb_score;
                }
                the_input = document.getElementsByName("b_p_writer");
                if (the_input.length == 1){
                    the_input[0].value = response.result.author;
                }
                the_input = document.getElementsByName("b_p_translator");
                if (the_input.length == 1){
                    the_input[0].value = response.result.translator;
                }
                mybar.value="网页抓取完毕.";
            },
            error: function(request) {
                mybar.value="网页抓取异常";
			},
        });
    })
	$('button[name="bddb_get_pic_btn"]').click(function(){
		var pic_bar = document.getElementsByName("bddb_poster_link");
		var dest_pic = this.getAttribute('dest_src');
		if (pic_bar.length != 1) {
			return;
		}
        $stsbox = document.getElementsByName('ajax-status');
        var mybar = $stsbox[0];
		var pic_link = pic_bar[0].value;
		var data = {
            action: 'bddb_get_pic',
            nonce: this.getAttribute('wpnonce'),
            id:this.getAttribute('id'),
            ptype:this.getAttribute('ptype'),
			piclink:pic_link,
		};
		$.ajax({
            url: ajaxurl,
            type: 'POST',
            data: data,
            cache: false,
            success:function(response){
				$("#img_poster_thumbnail").attr('src',dest_pic);
                mybar.value="图片取得成功.";
            },
            beforeSend: function () {
                $("#img_poster_thumbnail").attr('src','');
				mybar.value="图片获得中...";
			},
            error: function(request) {
				mybar.value="取图片异常.";
			},
        });
	})
    $('button[name="bddb_get_scovers_btn"]').click(function(){
		var pic_bar = document.getElementsByName("b_series_covers");
		var count_bar = document.getElementsByName("b_series_total");

		if (pic_bar.length != 1) {
			return;
		}
        $stsbox = document.getElementsByName('ajax-status');
        var mybar = $stsbox[0];
		var pic_link = pic_bar[0].value;
		var stotal = count_bar[0].value;
        if(!pic_link.length){
            pic_link = this.getAttribute('slinks');
        }
		var data = {
            action: 'bddb_get_scovers',
            nonce: this.getAttribute('wpnonce'),
            id:this.getAttribute('id'),
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
                mybar.value="多图片抓取成功.";
            },
            beforeSend: function () {
				mybar.value="多图片获得中...";
			},
            error: function(request) {
				mybar.value="取多图片异常.";
			},
        });
	})
})

jQuery(function ($) { 
    $(document).ajaxComplete(function (event, xhr, settings)  {
        if (typeof settings.data==='string' && /action=get-post-thumbnail-html/.test(settings.data) && xhr.responseJSON && typeof xhr.responseJSON.data==='string') {
            if ( /thumbnail_id=-1/.test(settings.data) ) {
                return;
            }
            var pos = settings.data.toLowerCase().indexOf("thumbnail_id");
            if (pos <= 0) {
                return;
            }
            var res = settings.data.split("&");
            var pic_id = res[1].substr(res[1].indexOf("=")+1);
            if (!pic_id) {
                return;
            }
            var img=jQuery('#set-post-thumbnail')[0].childNodes[0];
            var colorThief = new ColorThief();
            var picmaincolor=colorThief.getColor(img);
            var colorhex=rgb_to_hex_string(picmaincolor);
            var colorrgb=rgb_to_rgb_string(picmaincolor);
            var data = {
                action: 'apip_new_thumbnail_color',
                picid: pic_id,
                maincolor: colorhex,
            };
            jQuery.ajax({
                url: ajaxurl,
                type: 'POST',
                data: data,
                success: function (response) {
				if (response) {
                    //成功后更新colorpicker的颜色
                    var picker = jQuery('#apipcolorthiefdiv').find('.wp-picker-container').find('.wp-color-result');
                    picker[0].setAttribute("style","background-color:"+colorrgb);
				}
			}
            });
           }
    });
});