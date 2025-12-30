
//Fancybox.defaults.showClass = fancybox-fadeIn;
//Fancybox.defaults.hideClass = fancybox-fadeOut;
//Fancybox.defaults.click = next;

//ajax
jQuery( document ).ready( function( $ ) {
	var finished = 'unknown';
	var load_flag = false;

	//search last element of current page on scrolling
	$(window).on('scroll', TreateLast);
	function TreateLast() {
		if ('done' === finished) {
			$(window).off('scroll', TreateLast);
			return;
		}
		var t = $(this),
		elem = $('.bddb-poster-thumb').last();

		if (typeof elem == 'undefined') {
			finished = 'unknown';
			return;
		}
		if (true === load_flag) {
			return;
		}
		if ( t.scrollTop() + t.height() <
				elem.offset().top + elem.height() ) {
			return;
		}
		
		var type = elem.attr('type');
		var page_id = elem.attr('pid');
		var nonce = elem.attr('nonce');
		var nobj = elem.attr('nobj');
		if (typeof type == 'undefined' ||
			typeof page_id == 'undefined' ||
			typeof nonce == 'undefined' ||
			typeof nobj == 'undefined'
		) {
			finished = 'unknown';
			return;
		}
		
		elem.removeAttr('type');
		elem.removeAttr('pid');
		elem.removeAttr('nonce');
		elem.removeAttr('nobj');

		if ('0' === page_id){
			finished = 'done';
			return;
		}
		//call ajax load
		load_next_page(type, parseInt(page_id) + 1, nonce, nobj);
	};

	//ajax load gallery
	function load_next_page(t,p,n,s) {
		var data = {
			action: 'bddb_next_gallery_page',
			nonce: n,
			pid: p,
			type: t,
			nobj: s,
		};
		$.ajax({
			url: ajaxurl.url,
			type: 'POST',
			data: data,
			cache: false,
			beforeSend: show_loader,
			success: function (results) {
				hide_loader();
				var obj = $(results);
				var elems = obj.find('.bddb-poster-thumb');
				elems.each(function (i, v) {
					$('.bddb-poster-thumb').last().after($(this));
				});
			},
			error: function () {
				hide_loader();
			},
		});
	};
	
	function show_loader() {
		load_flag = true;
		var myTop = $('#colophon')[0].offsetTop - 48;
		$('.ring-loading').css('top', myTop.toString(10)+'px');
		$('.ring-loading').show();
	};

	function hide_loader() {
		$('.ring-loading').hide();
		load_flag = false;
	};

});

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

function rgb_to_rgba_string(rgb_array, ocp) {
	return 'RGBA('+rgb_array.toString()+','+ocp.toString()+')';
}

//======== Modified from fancybox official ========
//@original url: //fancyapps.com/playground/16W
function set_funcy_panel(fancybox, $trigger) {
	var img = $trigger.firstChild;
	//Use colorthief to fetch main color of poster.
	var colorThief = new ColorThief();
	var picmaincolor=colorThief.getColor(img, 7);
	var lcolor = rgb_to_rgba_string(picmaincolor,0.92);
	var rcolor = rgb_to_rgba_string(picmaincolor,0.54);

	//fancybox.$leftCol.setAttribute("style","background-color:"+lcolor);
	//fancybox.$rightCol.setAttribute("style","background-color:"+rcolor);
	const data = $trigger.dataset.info || "";
	fancybox.$info.innerHTML = `${data}`;
	fancybox.$container.style.setProperty(
		"--fancybox-left-control-bg",
		lcolor
	);
	fancybox.$container.style.setProperty(
		"--fancybox-right-control-bg",
		rcolor
	);/*
	fancybox.$container.style.setProperty(
		"--fancybox-hover-color",
		rgb_to_hex_string(picmaincolor)
	);*/
	fancybox.$container.style.setProperty(
		"--fancybox-thumb-color",
		rgb_to_hex_string(picmaincolor)
	);
}

Fancybox.bind('[data-fancybox="gallery"]', {
	showClass: "fancybox-fadeIn",
	Toolbar: {
		display: [
		  {
			id: "counter",
			position: "center",
		  },
		  "zoom",
		  "thumbs",
		  "close",
		],
	},
	click: false,
	//click:next,
	/*Thumbs: false,*/
	on: {
		initLayout: (fancybox) => {
			const $leftCol = document.createElement("div");
			$leftCol.classList.add("fancybox__leftCol");

			while (fancybox.$container.firstChild) {
				$leftCol.appendChild(fancybox.$container.firstChild);
			}

			// Create right column
			const $rightCol = document.createElement("div");
			$rightCol.classList.add("fancybox__rightCol");

			$rightCol.innerHTML = '<p class="screen-reader-text">榤掤��/p>';

			// Create info-box
			const $info = document.createElement("div");
			$rightCol.appendChild($info);
			$info.classList.add("bddb_disp_panel");
			fancybox.$info = $info;

			// Add elements to DOM
			fancybox.$container.appendChild(fancybox.$backdrop);

			fancybox.$container.appendChild($leftCol);
			fancybox.$container.appendChild($rightCol);

			fancybox.$leftCol = $leftCol;
			fancybox.$rightCol = $rightCol;
		},
		
		"Carousel.ready": (fancybox, carousel, slideIndex) => {
			slideIndex = slideIndex || carousel.options.initialPage;
			// Get link related to current item
			const $trigger = fancybox.items[slideIndex].$trigger;
			set_funcy_panel(fancybox, $trigger);
			
		},
		"Carousel.change": (fancybox, carousel, to, from) => {
			const slide = carousel.slides[to];
			const $trigger = slide.$trigger;
			set_funcy_panel(fancybox, $trigger);
			/*
			*/
			},
		},
});
