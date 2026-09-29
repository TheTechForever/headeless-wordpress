/* Dev Toolkit Pro — responsive checker.
 * Builds an iframe per device width from the process doc and flags
 * horizontal overflow. Overflow detection only works for same-origin
 * pages (this site); cross-origin URLs are preview-only. */
(function () {
	'use strict';

	document.addEventListener('DOMContentLoaded', function () {
		var mount = document.getElementById('dtp-responsive');
		if (!mount || !window.DTP_DEVICES) { return; }

		var url = mount.getAttribute('data-url');
		var sameOrigin = isSameOrigin(url);

		var groups = {};
		DTP_DEVICES.forEach(function (d) {
			(groups[d.group] = groups[d.group] || []).push(d);
		});

		Object.keys(groups).forEach(function (g) {
			var h = document.createElement('h3');
			h.textContent = g;
			mount.appendChild(h);

			var row = document.createElement('div');
			row.style.cssText = 'display:flex;flex-wrap:wrap;gap:16px;margin-bottom:20px;';
			mount.appendChild(row);

			groups[g].forEach(function (d) {
				row.appendChild(buildFrame(url, d, sameOrigin));
			});
		});
	});

	function isSameOrigin(url) {
		try { return new URL(url, location.href).origin === location.origin; }
		catch (e) { return false; }
	}

	function buildFrame(url, d, sameOrigin) {
		var scale = Math.min(1, 260 / d.w); // fit preview into a ~260px column
		var wrap = document.createElement('div');
		wrap.style.cssText = 'width:' + (d.w * scale) + 'px;';

		var cap = document.createElement('div');
		cap.style.cssText = 'font-size:12px;font-weight:600;margin-bottom:4px;';
		cap.textContent = d.label + ' — ' + d.w + '×' + d.h;
		wrap.appendChild(cap);

		var box = document.createElement('div');
		box.style.cssText = 'width:' + d.w + 'px;height:' + Math.min(d.h, 640) +
			'px;transform:scale(' + scale + ');transform-origin:top left;border:1px solid #dcdcde;overflow:hidden;';

		var frame = document.createElement('iframe');
		frame.src = url;
		frame.style.cssText = 'width:' + d.w + 'px;height:' + Math.min(d.h, 640) + 'px;border:0;';
		box.appendChild(frame);

		// Keep the scaled box from taking full layout height.
		var spacer = document.createElement('div');
		spacer.style.height = (Math.min(d.h, 640) * scale) + 'px';
		spacer.appendChild(box);
		wrap.appendChild(spacer);

		var status = document.createElement('div');
		status.style.cssText = 'font-size:11px;margin-top:4px;';
		status.textContent = sameOrigin ? 'Checking…' : 'Preview only (cross-origin)';
		wrap.appendChild(status);

		if (sameOrigin) {
			frame.addEventListener('load', function () {
				try {
					var doc = frame.contentDocument;
					var overflow = doc.documentElement.scrollWidth > d.w + 1;
					if (overflow) {
						status.textContent = '⚠ Horizontal overflow (' + doc.documentElement.scrollWidth + 'px)';
						status.style.color = '#c92c2c';
						box.style.borderColor = '#c92c2c';
					} else {
						status.textContent = '✓ No horizontal overflow';
						status.style.color = '#1a9d5a';
					}
				} catch (e) {
					status.textContent = 'Preview only (blocked)';
				}
			});
		}

		return wrap;
	}
})();
