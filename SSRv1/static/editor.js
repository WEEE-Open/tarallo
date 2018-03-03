"use strict";

(function() {
	document.execCommand("defaultParagraphSeparator", false, "p");

	let deletedFeatures = new Set();

	let divs = document.querySelectorAll('.item.editing :not(.subitems) .features.own [contenteditable]');
	let selects = document.querySelectorAll('.item.editing :not(.subitems) .features.own select');
	let deletes = document.querySelectorAll('.item.editing :not(.subitems) .features.own .delete');

	for(let div of divs) {
		if(div.dataset.internalType === 's') {
			div.addEventListener('input', textChanged);
		} else {
			div.addEventListener('blur', numberChanged);
		}
	}

	for(let select of selects) {
		select.addEventListener('change', selectChanged);
	}

	for(let deleteButton of deletes) {
		let bound = deleteItem.bind(null, deletedFeatures);
		deleteButton.addEventListener('click', bound);
	}


	/**
	 * Handle changing content of an editable text div
	 *
	 * @param ev Event
	 * @TODO: adding and removing newlines should count as "changed", but it's absurdly difficult to detect, apparently...
	 */
	function textChanged(ev) {
		let paragraphs = ev.target.getElementsByTagName('P');
		for(let p of paragraphs) {
			let br = p.getElementsByTagName('BR')[0];
			if(!br && p.textContent === '') {
				p.append(document.createElement('br'));
			} else if(br && p.textContent !== '') {
				p.removeChild(br);
			}
		}
		
		// newly added element
		if(!ev.target.dataset.previousValue) {
			ev.target.classList.add('changed');
			return;
		}

		if(ev.target.textContent.length === ev.target.dataset.previousValue.length) {
			if(ev.target.textContent === ev.target.dataset.previousValue) {
				ev.target.classList.remove('changed');
				return;
			}
		}
		ev.target.classList.add('changed');
	}

	/**
	 * Handle changing value of a <select>
	 *
	 * @param ev Event
	 */
	function selectChanged(ev) {
		console.log(ev.target.value);
		console.log(ev.target.dataset.previousValue);
		if(ev.target.value === ev.target.dataset.previousValue) {
			ev.target.classList.remove('changed');
		} else {
			ev.target.classList.add('changed');
		}
	}

	/**
	 * Handle changing content of an editable div containing numbers
	 *
	 * @param ev Event
	 */
	function numberChanged(ev) {
		// TODO: parse, store internal value and pretty print units. Also, set changed.
		console.log(ev.target);
	}

	/**
	 * Handle changing content of an editable div containing numbers
	 *
	 * @param {Set} set Deleted features
	 * @param ev Event
	 */
	function deleteItem(set, ev) {
		// TODO: store into the set
	}

	function save() {
		// TODO: get all deleted and "changed" features, make a JSON request, SEND IT.
	}
}());
