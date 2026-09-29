/* Dev Toolkit — front-end behaviour */
(function () {
	'use strict';
	var cfg = window.DT_FRONT || {};

	document.addEventListener('DOMContentLoaded', function () {
		if (cfg.sliderAria) {
			addSliderAria();
		}
	});

	// Add accessible names to common slider arrow controls so PageSpeed's
	// "Links do not have a discernible name" audit passes.
	function addSliderAria() {
		var map = [
			['.slick-prev', 'Previous Slide'], ['.slick-next', 'Next Slide'],
			['.owl-prev', 'Previous Slide'], ['.owl-next', 'Next Slide'],
			['.swiper-button-prev', 'Previous Slide'], ['.swiper-button-next', 'Next Slide'],
			['.slider-prev', 'Previous Slide'], ['.slider-next', 'Next Slide']
		];
		map.forEach(function (pair) {
			document.querySelectorAll(pair[0]).forEach(function (el) {
				if (!el.getAttribute('aria-label')) {
					el.setAttribute('aria-label', pair[1]);
				}
			});
		});
	}
})();
