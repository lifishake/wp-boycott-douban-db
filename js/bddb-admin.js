/**
 * @file	bddb-admin.js
 * @brief	处理后台编辑画面
 * @date	2026-09-18
 * @author	大致
 * @version	1.3.7   适配webp
 * @since	0.0.1
 *
 */

var nomouse_names = nomouse_names || {};

//追加bddb 记录插入用button
QTags.addButton("eg_bddbr", "BDDbRd", "[bddbr id='", "' /]", "p");

/**
 *
 * @param number dec
 * @param number length
 * @returns Array
 */
function dec_to_hex_string(dec, length) {
  var hex = dec.toString(16).toUpperCase();
  if (hex.length < length) {
    hex = new Array(length - hex.length + 1).join("0") + hex;
  }
  return hex;
}

/**
 *
 * @param Array rgb_array
 * @returns string
 */
function rgb_to_hex_string(rgb_array) {
  var hex_string = "";
  for (var i = 0; i < rgb_array.length; i++) {
    hex_string += dec_to_hex_string(rgb_array[i], 2);
  }
  return "#" + hex_string;
}

/**
 *
 * @param Array rgb_array
 * @returns string
 */
function rgb_to_rgb_string(rgb_array) {
  var rgb_string = "RGB(";
  for (var i = 0; i < rgb_array.length; i++) {
    rgb_string += rgb_array[i];
    if (i < 2) {
      rgb_string += ",";
    }
  }
  return rgb_string + ")";
}

/**
 *
 * @param Event event
 */
function StopMouseWheel(event) {
  event = event || window.event;
  event.preventDefault();
}

jQuery(document).ready(function ($) {
  //$stsbox = document.getElementsByName('ajax-status');
  //TODO:改成queryselector
  const mypicbar = document.getElementById("pic-status");
  const myfetchstsbar = document.getElementById("fetch-status");
  const mythumbnail = document.getElementById("img_poster_thumbnail");

  //nomouse_names被Editor本地化，如果有number类型的input，该数组非空。
  Object.entries(nomouse_names).forEach(([key, value]) => {
    const $input = $(`input[name="${value}"]`); // 推荐用模板字符串
    if ($input.length) {
      $input[0].addEventListener("DOMMouseScroll", StopMouseWheel, false);
    }
  });

  //抓取按钮
  //TODO：准备删除
  $('button[name="douban_spider_btn"]').click(function () {
    var link_bar = document.getElementsByName("bddb_external_link");
    var ddllkk = this.getAttribute("doulink");
    if (link_bar.length == 1) {
      ddllkk = link_bar[0].value;
    }
    var data = {
      action: "bddb_douban_fetch",
      nonce: this.getAttribute("wpnonce"),
      id: this.getAttribute("pid"),
      ptype: this.getAttribute("ptype"),
      doulink: ddllkk,
    };
    $.ajax({
      url: ajaxurl,
      type: "GET",
      data: data,
      cache: false,
      beforeSend: function () {
        mypicbar.value = "网页抓取中...";
      },
      success: function (response) {
        mypicbar.value = response.content;
        var the_input = document.getElementsByName("bddb_display_name");
        var update_title = false;
        if ($("#title-prompt-text").length == 1) {
          if ($("#title-prompt-text")[0].innerText === "Enter title here") {
            $("#title-prompt-text")[0].innerHTML = "";
            update_title = true;
          }
        }
        if (the_input.length == 1 && response.result.title.length > 0) {
          the_input[0].value = response.result.title;
          the_input = document.getElementById("title");
          if (the_input.value.length == 0) {
            the_input.value = response.result.title;
          }
        }
        //通用
        the_input = document.getElementsByName("bddb_original_name");
        if (
          the_input.length == 1 &&
          response.result.original_name !== undefined &&
          !the_input[0].value
        ) {
          the_input[0].value = response.result.original_name;
        }
        the_input = document.getElementsByName("bddb_poster_link");
        if (the_input.length == 1 && response.result.pic !== undefined) {
          the_input[0].value = response.result.pic;
        }
        the_input = document.getElementsByName("bddb_score_douban");
        if (
          the_input.length == 1 &&
          response.result.average_score !== undefined
        ) {
          the_input[0].value = response.result.average_score;
        }
        the_input = document.getElementsByName("bddb_id_douban");
        if (the_input.length == 1 && response.result.dou_id !== undefined) {
          the_input[0].value = response.result.dou_id;
        }

        the_input = document.getElementsByName("bddb_publish_time");
        if (the_input.length == 1 && response.result.pubdate !== undefined) {
          the_input[0].value = response.result.pubdate;
        }
        the_input = document.getElementsByName("bddb_aka");
        if (
          the_input.length == 1 &&
          response.result.akas !== undefined &&
          !the_input[0].value
        ) {
          the_input[0].value = response.result.akas;
        }
        if (response.result.url !== undefined && response.result.url) {
          link_bar[0].value = response.result.url;
        }

        //书
        the_input = document.getElementsByName("b_region");
        if (the_input.length == 1 && response.result.country !== undefined) {
          the_input[0].value = response.result.country;
        }
        the_input = document.getElementsByName("b_publisher");
        if (the_input.length == 1 && response.result.publisher !== undefined) {
          the_input[0].value = response.result.publisher;
        }
        the_input = document.getElementsByName("b_p_writer");
        if (the_input.length == 1 && response.result.author !== undefined) {
          the_input[0].value = response.result.author;
        }
        the_input = document.getElementsByName("b_p_translator");
        if (the_input.length == 1 && response.result.translator !== undefined) {
          the_input[0].value = response.result.translator;
        }
        the_input = document.getElementsByName("b_series_total");
        if (
          the_input.length == 1 &&
          response.result.series_total !== undefined
        ) {
          the_input[0].value = response.result.series_total;
          if (response.result.series_total > 1) {
            document.getElementsByName("b_bl_series")[0].checked = true;
          }
        }
        the_input = document.getElementsByName("b_genre");
        if (the_input.length == 1 && response.result.genre !== undefined) {
          the_input[0].value = response.result.genre;
        }

        //影
        the_input = document.getElementsByName("m_region");
        if (the_input.length == 1 && response.result.country !== undefined) {
          $str_regin = response.result.country;
          $iPos = $str_regin.indexOf(",");
          if ($iPos > 0) {
            the_input[0].value = $str_regin.substring(0, $iPos);
          } else {
            the_input[0].value = response.result.country;
          }
        }
        the_input = document.getElementsByName("m_genre");
        if (the_input.length == 1 && response.result.genre !== undefined) {
          the_input[0].value = response.result.genre;
        }
        the_input = document.getElementsByName("m_publisher");
        if (the_input.length == 1 && response.result.company !== undefined) {
          the_input[0].value = response.result.company;
        }
        the_input = document.getElementsByName("m_p_director");
        if (the_input.length == 1 && response.result.director !== undefined) {
          the_input[0].value = response.result.director;
        }
        the_input = document.getElementsByName("m_p_actor");
        if (the_input.length == 1 && response.result.actor !== undefined) {
          the_input[0].value = response.result.actor;
        }
        the_input = document.getElementsByName("m_p_screenwriter");
        if (
          the_input.length == 1 &&
          response.result.screenwriter !== undefined
        ) {
          the_input[0].value = response.result.screenwriter;
        }
        the_input = document.getElementsByName("m_p_musician");
        if (the_input.length == 1 && response.result.musician !== undefined) {
          the_input[0].value = response.result.musician;
        }
        the_input = document.getElementsByName("m_id_imdb");
        if (the_input.length == 1 && response.result.imdbid !== undefined) {
          the_input[0].value = response.result.imdbid;
        }
        the_input = document.getElementsByName("m_score_imdb");
        if (the_input.length == 1 && response.result.imdb_score !== undefined) {
          the_input[0].value = response.result.imdb_score;
        }
        the_input = document.getElementsByName("m_length");
        if (the_input.length == 1 && response.result.m_length !== undefined) {
          the_input[0].value = response.result.m_length;
        }
        //游
        the_input = document.getElementsByName("g_genre");
        if (the_input.length == 1 && response.result.genre !== undefined) {
          the_input[0].value = response.result.genre;
        }

        //碟
        the_input = document.getElementsByName("a_genre");
        if (the_input.length == 1 && response.result.genre !== undefined) {
          the_input[0].value = response.result.genre;
        }
        the_input = document.getElementsByName("a_region");
        if (the_input.length == 1 && response.result.country !== undefined) {
          $str_regin = response.result.country;
          $iPos = $str_regin.indexOf(",");
          if ($iPos > 0) {
            the_input[0].value = $str_regin.substring(0, $iPos);
          } else {
            the_input[0].value = response.result.country;
          }
        }
        the_input = document.getElementsByName("a_p_musician");
        if (the_input.length == 1 && response.result.artist !== undefined) {
          the_input[0].value = response.result.artist;
        }
        the_input = document.getElementsByName("a_quantity");
        if (the_input.length == 1 && response.result.quantity !== undefined) {
          the_input[0].value = response.result.quantity;
        }
        the_input = document.getElementsByName("a_publisher");
        if (the_input.length == 1 && response.result.publisher !== undefined) {
          the_input[0].value = response.result.publisher;
        }

        myfetchstsbar.value = "网页已抓取.";
        mypicbar.value = "网页抓取完毕.";
      },
      error: function (request) {
        mypicbar.value = "网页抓取异常";
      },
    });
  });

  //取图片按钮
  $('button[name="bddb_get_pic_btn"]').click(function () {
    const pic_link = $("[name='bddb_poster_link']")[0]?.value ?? "";
    const dest_pic = this.getAttribute("dest_src");
    const need_rrotate = $("[name='bddb_pic_rrotate']")[0]?.value ?? "0";
    const need_cover = $("[name='bddb_pic_cover']")[0]?.value ?? "0";
    const need_adapt = $("[name='bddb_pic_adape']")[0]?.value ?? "0";
    if (!pic_link) {
      return;
    }
    const data = {
      action: "bddb_get_pic",
      nonce: this.getAttribute("wpnonce"),
      id: this.getAttribute("pid"),
      ptype: this.getAttribute("ptype"),
      piclink: pic_link,
      rrotate: need_rrotate,
      makecover: need_cover,
      adapt: need_adapt,
    };
    $.ajax({
      url: ajaxurl,
      type: "POST",
      data: data,
      cache: false,
      success: function (response) {
        mythumbnail.setAttribute("src", dest_pic + "?tl=" + Math.random());
        mypicbar.value = "图片取得成功.";
      },
      beforeSend: function () {
        mythumbnail.setAttribute("src", "");
        mypicbar.value = "图片获得中...";
      },
      error: function (request) {
        mypicbar.value = "取图片异常.";
      },
    });
  });

  //取imdb图片按钮
  $('button[name="bddb_get_imdbpic_btn"]').click(function () {
    const imdb_id = $("[name='m_id_imdb']")[0]?.value ?? "";
    const dest_pic = this.getAttribute("dest_src");
    if (!imdb_id) {
      return;
    }
    const data = {
      action: "bddb_get_imdbpic",
      nonce: this.getAttribute("wpnonce"),
      id: this.getAttribute("pid"),
      imdbno: imdb_id,
    };
    $.ajax({
      url: ajaxurl,
      type: "POST",
      data: data,
      cache: false,
      success: function (response) {
        mythumbnail.setAttribute("src", dest_pic + "?tl=" + Math.random());
        mypicbar.value = "图片取得成功.";
      },
      beforeSend: function () {
        mythumbnail.setAttribute("src", "");
        mypicbar.value = "图片获得中...";
      },
      error: function (request) {
        mypicbar.value = "取图片异常.";
      },
    });
  });

  //取theomdb图片按钮
  $('button[name="bddb_get_tmdb_poster_btn"]').click(function () {
    var id_bar = document.getElementsByName("bddb_personal_review");
    var dest_pic = this.getAttribute("dest_src");
    if (id_bar.length != 1) {
      return;
    }
    var tmdbid = id_bar[0].value;
    var data = {
      action: "bddb_get_tmdb_poster",
      nonce: this.getAttribute("wpnonce"),
      id: this.getAttribute("pid"),
      tmdbno: tmdbid,
    };
    $.ajax({
      url: ajaxurl,
      type: "POST",
      data: data,
      cache: false,
      success: function (response) {
        mythumbnail.setAttribute("src", dest_pic + "?tl=" + Math.random());
        mypicbar.value = "图片取得成功.";
        var the_input = document.getElementsByName("bddb_poster_link");
        if (the_input.length == 1 && response.backdrop_path !== "undefined") {
          the_input[0].value = response.backdrop_path;
        }
      },
      beforeSend: function () {
        mythumbnail.setAttribute("src", "");
        mypicbar.value = "图片获得中...";
      },
      error: function (request) {
        mypicbar.value = "取图片异常.";
      },
    });
  });

  //批量取封面按钮
  $('button[name="bddb_get_scovers_btn"]').click(function () {
    const pic_bar = $('input[name="b_series_covers"]')?.[0] ?? null;

    if (!pic_bar) {
      return;
    }
    let pic_link = pic_bar.value;
    const stotal = $('input[name="b_series_total"]')?.[0]?.value ?? "";
    const extLink = $('input[name="bddb_extrenal_link"]')?.[0]?.value ?? "";
    if (!pic_link.length) {
      pic_link = this.getAttribute("slinks");
    }
    const data = {
      action: "bddb_get_scovers",
      nonce: this.getAttribute("wpnonce"),
      id: this.getAttribute("pid"),
      ptype: this.getAttribute("ptype"),
      slinks: pic_link,
      stotal: stotal,
      referer: extLink,
    };
    $.ajax({
      url: ajaxurl,
      type: "POST",
      data: data,
      cache: false,
      success: function (response) {
        mypicbar.value = "多图片抓取成功.";
      },
      beforeSend: function () {
        mypicbar.value = "多图片获得中...";
      },
      error: function (request) {
        mypicbar.value = "取多图片异常.";
      },
    });
  });

  //追加标签
  $(".box-tag").click(function () {
    const input_bar = document.querySelector(
      `input[name="${this.getAttribute("data")}"]`,
    );

    if (!input_bar) return;

    input_bar.value = input_bar.value
      ? `${input_bar.value}, ${this.innerText}`
      : this.innerText;
  });

  //取图片按钮
  $("#bddb_poster_scan").click(function () {
    const tableOuter = document.querySelector('[name="form-table"]')[0] ?? null;
    if (!tableOuter) {
      return;
    }
    const tabody = tableOuter.querySelector('[name="tbody"]')[0] ?? null;
    if (!tabody) {
      return;
    }

    tabody.innerHTML = "";
    const data = {
      action: "bddb_rescan_thumb_folder",
      nonce: this.getAttribute("wpnonce"),
    };
    $.ajax({
      url: ajaxurl,
      type: "GET",
      data: data,
      cache: false,
      success: function (response) {
        tabody.innerHTML = response;
      },
      beforeSend: function () {},
      error: function (request) {},
    });
  });

  //清理图片按钮
  $("#bddb_thumb_clear").click(function () {
    let arrPicFiles = [];
    let del_rows = [];
    let obj_tab = null;
    rows = document.querySelectorAll('input[name^="sel_thumb"]:checked');
    Array.from(rows).forEach((element, index) => {
      const rid = element.getAttribute("row_id");
      if (0 == index) {
        obj_tab = element.parentNode.parentNode.parentNode;
      }
      if (rid) {
        let namecell = document.getElementById("fname_" + rid);
        const pics = namecell.innerText.split("\n");
        arrPicFiles.push.apply(arrPicFiles, pics);
        let del_row = element.parentNode.parentNode;
        del_rows.push(del_row);
      }
    });
    const del_pics = arrPicFiles.join(",");
    const data = {
      action: "bddb_thumb_clear",
      nonce: this.getAttribute("wpnonce"),
      files: del_pics,
    };
    $.ajax({
      url: ajaxurl,
      type: "POST",
      data: data,
      cache: false,
      success: function () {
        for (let i = 0; i < del_rows.length; i++) {
          obj_tab.removeChild(del_rows[i]);
        }
      },
      beforeSend: function () {},
      error: function (request) {},
    });
  });
});
