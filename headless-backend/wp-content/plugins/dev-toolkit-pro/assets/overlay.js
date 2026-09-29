/* Dev Toolkit Pro — media picker + design overlay compare. */
(function ($) {
	'use strict';

	// --- Media library picker for reference fields -----------------------
	$(document).on('click', '.dtp-media__pick', function (e) {
		e.preventDefault();
		var wrap = $(this).closest('.dtp-media');
		var frame = wp.media({ title: 'Select image', multiple: false, library: { type: 'image' } });
		frame.on('select', function () {
			var att = frame.state().get('selection').first().toJSON();
			wrap.find('input[type=hidden]').val(att.id);
			var src = (att.sizes && att.sizes.thumbnail) ? att.sizes.thumbnail.url : att.url;
			wrap.find('.dtp-media__preview').html('<img src="' + src + '" style="max-width:120px;height:auto">');
		});
		frame.open();
	});

	$(document).on('click', '.dtp-media__clear', function (e) {
		e.preventDefault();
		var wrap = $(this).closest('.dtp-media');
		wrap.find('input[type=hidden]').val('');
		wrap.find('.dtp-media__preview').html('');
	});

	// --- Overlay comparison ---------------------------------------------
	$(function () {
		var design = document.querySelector('.dtp-overlay__design');
		var op = document.getElementById('dtp-op');
		var offy = document.getElementById('dtp-offy');
		if (!design || !op) { return; }

		function apply() {
			design.style.opacity = (op.value / 100).toFixed(2);
			design.style.top = (parseInt(offy.value, 10) || 0) + 'px';
		}
		op.addEventListener('input', apply);
		offy.addEventListener('input', apply);
		apply();
	});
})(jQuery);
