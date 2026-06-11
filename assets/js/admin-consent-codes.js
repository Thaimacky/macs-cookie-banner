(function () {
	'use strict';

	var list = document.querySelector('[data-lscc-codes-list]');
	var template = document.querySelector('[data-lscc-code-template]');
	var addButton = document.querySelector('[data-lscc-code-add]');

	if (!list || !template || !addButton) {
		return;
	}

	var counter = 0;

	function nextIndex() {
		counter += 1;
		return 'new' + counter;
	}

	function templateHtml() {
		// <template> content is the most reliable source; fall back to innerHTML.
		if (template.content && template.content.firstElementChild) {
			var holder = document.createElement('div');
			holder.appendChild(template.content.cloneNode(true));
			return holder.innerHTML;
		}
		return template.innerHTML;
	}

	addButton.addEventListener('click', function () {
		var html = templateHtml().replace(/__INDEX__/g, nextIndex());
		var wrap = document.createElement('div');
		wrap.innerHTML = html;
		var row = wrap.querySelector('[data-lscc-code-row]');

		if (row) {
			list.appendChild(row);
		}
	});

	list.addEventListener('click', function (event) {
		var target = event.target;

		if (typeof target.closest !== 'function') {
			return;
		}

		var row = target.closest('[data-lscc-code-row]');

		if (!row) {
			return;
		}

		if (target.hasAttribute('data-lscc-code-remove')) {
			event.preventDefault();
			row.parentNode.removeChild(row);
		} else if (target.hasAttribute('data-lscc-code-up')) {
			event.preventDefault();
			if (row.previousElementSibling) {
				row.parentNode.insertBefore(row, row.previousElementSibling);
			}
		} else if (target.hasAttribute('data-lscc-code-down')) {
			event.preventDefault();
			if (row.nextElementSibling) {
				row.parentNode.insertBefore(row.nextElementSibling, row);
			}
		}
	});
}());
