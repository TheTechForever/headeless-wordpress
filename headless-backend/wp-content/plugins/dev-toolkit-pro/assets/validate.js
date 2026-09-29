/* Dev Toolkit Pro — client-side form validation.
 * Add data-dt-validate to a <form>. Mark required fields with the "required"
 * attribute. For a confirm-password field add data-dt-match="password_field_name".
 * A message field can opt out of "required" naturally by omitting the attribute. */
(function () {
	'use strict';

	function strong(pw) {
		return pw.length >= 8 && /[A-Z]/.test(pw) && /[a-z]/.test(pw) && /[0-9]/.test(pw);
	}

	function showError(field, msg) {
		clearError(field);
		var e = document.createElement('span');
		e.className = 'dt-field-error';
		e.style.color = '#c92c2c';
		e.style.display = 'block';
		e.style.fontSize = '13px';
		e.textContent = msg;
		field.insertAdjacentElement('afterend', e);
		field.setAttribute('aria-invalid', 'true');
	}

	function clearError(field) {
		field.removeAttribute('aria-invalid');
		var next = field.nextElementSibling;
		if (next && next.classList.contains('dt-field-error')) {
			next.remove();
		}
	}

	function validateForm(form) {
		var ok = true;
		var fields = form.querySelectorAll('input, textarea, select');

		fields.forEach(function (field) {
			clearError(field);
			var val = (field.value || '').trim();

			if (field.hasAttribute('required') && !val) {
				showError(field, 'This field is required.');
				ok = false;
				return;
			}
			if (field.type === 'email' && val && !/^[^@\s]+@[^@\s]+\.[^@\s]+$/.test(val)) {
				showError(field, 'Enter a valid email address.');
				ok = false;
				return;
			}
			if (field.dataset.dtPassword !== undefined && val && !strong(val)) {
				showError(field, 'Min 8 chars with upper, lower and a number.');
				ok = false;
				return;
			}
			if (field.dataset.dtMatch) {
				var other = form.querySelector('[name="' + field.dataset.dtMatch + '"]');
				if (other && field.value !== other.value) {
					showError(field, 'Passwords do not match.');
					ok = false;
				}
			}
		});
		return ok;
	}

	document.addEventListener('DOMContentLoaded', function () {
		document.querySelectorAll('form[data-dt-validate]').forEach(function (form) {
			form.addEventListener('submit', function (e) {
				if (!validateForm(form)) {
					e.preventDefault();
				}
			});
		});
	});
})();
