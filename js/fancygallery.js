
//Fancybox.defaults.showClass = fancybox-fadeIn;
//Fancybox.defaults.hideClass = fancybox-fadeOut;
//Fancybox.defaults.click = next;

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

function set_funcy_panel(fancybox, $trigger) {
	var img = $trigger.firstChild;
	var colorThief = new ColorThief();
	var picmaincolor=colorThief.getColor(img);
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

			$rightCol.innerHTML = '<p class="screen-reader-text">概要</p>';

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
