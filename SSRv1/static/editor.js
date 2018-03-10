(function() {
	"use strict";

	document.execCommand('defaultParagraphSeparator', false, 'div');

	let divs = document.querySelectorAll('.features.editing [contenteditable]');
	let selects = document.querySelectorAll('.features.editing select');
	let deletes = document.querySelectorAll('.features.editing .delete');
	let itemEditing = document.querySelector('.item.head.editing');
	let isNew = itemEditing.classList.contains('new');

	/** @type {Set<string>} */
	let deletedFeatures;
	let deleteClickBound;
	if(isNew) {
		deleteClickBound = deleteFeature.bind(null, null);
		// noinspection JSUnresolvedFunction It's perfectly resolved, it's there, it exists
		itemEditing.querySelector('.itembuttons .save').addEventListener('click', saveNew);
		// noinspection JSUnresolvedFunction
		itemEditing.querySelector('.itembuttons .addnew').addEventListener('click', addNewClick);
		// Root "new item" cannot be deleted, just cancel the entire operation
		// itemEditing.querySelector('.itembuttons .removenew').addEventListener('click', removeNewClick);
	} else {
		deletedFeatures = new Set();
		deleteClickBound = deleteFeature.bind(null, deletedFeatures);
		// noinspection JSUnresolvedFunction
		itemEditing.querySelector('.itembuttons .save').addEventListener('click', saveModified);
	}

	// noinspection JSUnresolvedFunction
	itemEditing.querySelector('.itembuttons .cancel').addEventListener('click', goBack);

	// There may be more than one new item
	for(let item of document.querySelectorAll('.item.editing')) {
		let featuresElement;

		// Find own features
		for(let el of item.children) {
			if(el.classList.contains('own') && el.classList.contains('features')) {
				featuresElement = el;
				break;
			}
		}

		// Find "add" button and add listener
		for(let el of item.children) {
			if(el.classList.contains('addfeatures')) {
				console.log(el);
				el.querySelector('button').addEventListener('click', addFeatureClick.bind(null, el.querySelector('select'), item, featuresElement, typeof deletedFeatures !== 'undefined'));
				break;
			}
		}
	}

	// Enable the "X" button next to features
	for(let deleteButton of deletes) {
		deleteButton.addEventListener('click', deleteClickBound);
	}

	// Event listeners for string and numeric features
	for(let div of divs) {
		if(div.dataset.internalType === 's') {
			div.addEventListener('input', textChanged);
		} else {
			div.addEventListener('blur', numberChanged);
		}
	}

	// For enum features
	for(let select of selects) {
		select.addEventListener('change', selectChanged);
	}

	/**
	 * Remove an error message (or any element, really)
	 *
	 * @param {HTMLElement|null} element to be removed, or null to remove last error message
	 */
	function removeError(element = null) {
		if(element === null) {
			let last = document.getElementById('feature-edit-last-error');
			if(last !== null) {
				last.remove();
			}
		} else {
			element.remove();
		}
	}

	/**
	 * Handle changing content of an editable text div
	 *
	 * @param ev Event
	 * @TODO: adding and removing newlines should count as "changed", but it's absurdly difficult to detect, apparently...
	 */
	function textChanged(ev) {
		fixDiv(ev.target);
		// Newly added element
		if(!ev.target.dataset.initialValue) {
			return;
		}

		if(ev.target.textContent.length === ev.target.dataset.initialValue.length) {
			if(ev.target.textContent === ev.target.dataset.initialValue) {
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
		// New elements don't have an initial value
		if(!ev.target.dataset.initialValue) {
			return;
		}
		if(ev.target.value === ev.target.dataset.initialValue) {
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
		fixDiv(ev.target);
		let value = ev.target.textContent;
		let unit;
		if(ev.target.dataset.unit) {
			unit = ev.target.dataset.unit;
		} else {
			// Extreme caching techniques
			unit = nameToType(ev.target.dataset.internalName);
			ev.target.dataset.unit = unit;
		}
		try {
			let newValue = printableToValue(unit, value);
			if(ev.target.dataset.internalType === 'i' && (newValue % 1 !== 0)) {
				// noinspection ExceptionCaughtLocallyJS
				throw new Error("fractional-not-allowed");
			}
			// Store new value
			ev.target.dataset.internalValue = newValue.toString();
			// Print it
			let lines = ev.target.getElementsByTagName('DIV');
			lines[0].textContent = valueToPrintable(unit, newValue);
			while(lines.length > 1) {
				let last = lines[lines.length - 1];
				last.remove();
			}
			// Save if for later
			ev.target.dataset.previousValue = newValue.toString();
		} catch(e) {
			// rollback
			ev.target.dataset.internalValue = ev.target.dataset.previousValue;
			ev.target.getElementsByTagName('DIV')[0].textContent = valueToPrintable(unit, parseInt(ev.target.dataset.previousValue));
			// Display error message
			let displayed = displayError(e.message);
			if(!displayed) {
				throw e;
			}
		}
		// New elements don't have an initial value
		if(!ev.target.dataset.initialValue) {
			return;
		}
		if(ev.target.dataset.internalValue === ev.target.dataset.initialValue) {
			ev.target.classList.remove('changed');
		} else {
			ev.target.classList.add('changed');
		}
	}

	/**
	 * Show error messages.
	 *
	 * @param {string|null} templateName
	 * @param {string|null} message
	 */
	function displayError(templateName = null, message = null) {
		let templateThingThatShouldExist;
		if(templateName === null) {
			templateThingThatShouldExist = document.getElementById('feature-edit-template-generic-error');
		} else {
			templateThingThatShouldExist = document.getElementById('feature-edit-template-' + templateName);
			if(templateThingThatShouldExist === null) {
				// Unhandled exception!
				return false;
			}
		}
		let template = document.importNode(templateThingThatShouldExist.content, true);

		let item = document.querySelector('.item.head.editing');
		item.insertBefore(template, item.getElementsByTagName('HEADER')[0].nextElementSibling);
		// "template" is a document fragment, there's no way to get the element itself
		// TODO: does template[0] or something like that work?
		let inserted = document.querySelector('.item.head.editing .error.message');
		document.getElementById('feature-edit-last-error').id = undefined;
		inserted.id = 'feature-edit-last-error';
		inserted.getElementsByTagName('BUTTON')[0].addEventListener('click', removeError.bind(null, inserted));
		if(message !== null) {
			inserted.firstChild.textContent = message;
		}
	}

	/**
	 * Get the correct representation of a unit, from the internal (untranslated) feature name
	 *
	 * @param {string} name "frequency-hertz" et al
	 * @return {string} "Hz" et al
	 */
	function nameToType(name) {
		let pieces = name.split('-');
		switch(pieces[pieces.length - 1]) {
			case 'byte':
				return 'byte';
			case 'hertz':
				return 'Hz';
			case 'decibyte':
				return 'B';
			case 'ampere':
				return 'A';
			case 'volt':
				return 'V';
			case 'watt':
				return 'W';
			case 'inch':
				return 'in.';
			case 'gram':
				return 'g';
			default: // mm, rpm, n, byte (they're all handled separately)
				return pieces[pieces.length - 1];
		}
	}

	/**
	 * Parse the unit prefix and return exponent (or 0 if it isn't a prefix)
	 *
	 * @param {string} char - lowercase character
	 * @returns {number} exponent
	 */
	function prefixToExponent(char) {
		switch(char) {
			case 'k':
				return 1;
			case 'm':
				return 2;
			case 'g':
				return 3;
			case 't':
				return 4;
			case 'p':
				return 5;
			case 'e':
				return 6;
			//case 'µ':
			//case 'u':
			//	return -2;
			//case 'n':
			//	return -3;
			default:
				return 0;
		}
	}

	/**
	 * Convert that number into something printable
	 *
	 * @param {string} unit - byte, Hz, V, W, etc...
	 * @param {int} value
	 * @returns {string}
	 */
	function valueToPrintable(unit, value) {
		let prefix = 0;
		switch(unit) {
			case 'n':
				return value.toString();
			case 'rpm':
			case 'mm':
			case 'in.':
				return value.toString() + ' ' + unit;
			case 'byte':
				while(value >= 1024 && prefix <= 6) {
					value /= 1024; // this SHOULD already be optimized internally to use bit shift
					prefix++;
				}
				let i = '';
				if(prefix > 0) {
					i = 'i';
				}
				return '' + value + ' ' + prefixToPrintable(prefix, true) + i + 'B';
			default:
				return appendUnit(value, unit);
		}
	}

	/**
	 * Reduce a number to 3 digits (+ decimals) and add a unit to it
	 *
	 * @param {int} value - numeric value of the base unit (e.g. if base unit is -1, unit is "W", value is 1500, then result is "1.5 W")
	 * @param {string} unit - unit symbol, will be added to the prefix
	 * @param {int} [baseUnit] - base unit multiplier (e.g. 0 for volts, -1 for millivolts, 1 of kilovolts)
	 * @return {string} "3.2 MHz" and the like
	 */
	function appendUnit(value, unit, baseUnit = 0) {
		let prefix = baseUnit;
		while(value >= 1000 && prefix <= 6) {
			value /= 1000;
			prefix++;
		}
		return '' + value + ' ' + prefixToPrintable(prefix) + unit;
	}

	/**
	 * Get unit prefix in string format. 0 is none.
	 *
	 * @param {int} int - 1 for k, 2 for M, etc...
	 * @param {boolean} bigK - Use K instead of the standard k. Used for bytes, for some reason.
	 * @return {string}
	 */
	function prefixToPrintable(int, bigK = false) {
		switch(int) {
			case 0:
				return '';
			case 1:
				if(bigK) {
					return 'K';
				} else {
					return 'k';
				}
			case 2:
				return 'M';
			case 3:
				return 'G';
			case 4:
				return 'T';
			case 5:
				return 'P';
			case 6:
				return 'E';
			case -1:
				return 'm';
			//case -2:
			//	return 'µ';
			//case -3:
			//	return 'n';
		}
		throw new Error('invalid-prefix');
	}

	/**
	 * Parse input (from HTML) and convert to internal value.
	 *
	 * @param {string} unit
	 * @param {string} input - a non-empty string
	 * @throws Error if input is in wrong format
	 * @return {number}
	 * @private
	 */
	function printableToValue(unit, input) {
		/** @type {string} */
		let string = input.trim();
		if(string === "") {
			throw new Error("empty-input")
		} else if(unit === 'n') {
			let number = parseInt(input);
			if(isNaN(number) || number < 0) {
				throw new Error("negative-input")
			} else {
				return number;
			}
		}
		let i;
		for(i = 0; i < string.length; i++) {
			if(!((string[i] >= '0' && string[i] <= '9') || string[i] === '.' || string[i] === ',')) {
				break;
			}
		}
		if(i === 0) {
			throw new Error('string-start-nan');
		}
		let number = parseFloat(string.substr(0, 0 + i));
		if(isNaN(number)) {
			throw new Error('string-parse-nan')
		}
		let exp = 0;
		if(unit === 'mm') {
			// everything breaks down because:
			// - base unit ("m") contains an M
			// - "m" and "M" are acceptable prefixes (M could be ignored, but still "m" and "m" and "mm" are ambiguous)
			// so...
			exp = 0;
			// TODO: match exactly "m", "Mm" and "mm", coerce "mM" and "MM" into something sensibile, if we need this. Also, shouldn't this be a double?
		} else {
			for(; i < string.length; i++) {
				let lower = string[i].toLowerCase();
				if(lower >= 'a' && lower <= 'z') {
					exp = prefixToExponent(lower);
					break;
				}
			}
		}
		let base;
		if(unit === 'byte') {
			base = 1024;
		} else {
			base = 1000;
		}
		return number * (base ** exp);
	}

	/**
	 * Handle clicking the "X" button
	 *
	 * @param {Set<string>|null} set - Deleted features, null if not tracking
	 * @param ev Event
	 */
	function deleteFeature(set, ev) {
		if(set !== null) {
			set.add(ev.target.dataset.name);
		}
		ev.target.parentElement.parentElement.remove();
	}

	/**
	 * Handle clicking the "add" button for new features
	 *
	 * @param {HTMLSelectElement} select - HTML "select" element
	 * @param {HTMLElement} item - The item itself
	 * @param {HTMLElement} featuresElement - The "own features" element
	 * @param {boolean} trackDeleted - Note deleted/undeleted features in the global set
	 */
	function addFeatureClick(select, item, featuresElement, trackDeleted) {
		let name = select.value;
		let translatedName = select.options[select.selectedIndex].textContent;
		let id = 'feature-edit-' + name;

		let element = document.getElementById(id);
		if(element !== null) {
			element.focus();
			return null;
		}

		let newElement = newFeature(name, translatedName, id);

		if(trackDeleted) {
			// Undelete
			deletedFeatures.delete(name);
		}

		// Insert
		featuresElement.querySelector('.new ul').appendChild(newElement);
	}

	/**
	 * Maybe a template would have been better...
	 *
	 * @param {string} name - Feature name
	 * @param {string} translatedName - Human-readable feature name
	 * @param {string} id - Element id (already checked to be unique)
	 */
	function newFeature(name, translatedName, id) {
		let type = featureTypes.get(name);

		let newElement = document.createElement("li");
		let elementName = document.createElement("div");
		elementName.classList.add("name");
		newElement.appendChild(elementName);
		let elementLabel = document.createElement("label");
		elementLabel.htmlFor = id;
		elementLabel.textContent = translatedName;
		elementName.appendChild(elementLabel);

		let valueElement, div;
		switch(type) {
			case 'e':
				valueElement = document.createElement('select');
				let options = featureValues.get(name);
				let optionsTranslated = featureValuesTranslated.get(name);
				let optionsArray = [];
				for(let i = 0; i < options.length; i++) {
					let option = document.createElement('option');
					option.value = options[i];
					option.textContent = optionsTranslated[i];
					optionsArray.push(option);
				}
				optionsArray.sort((a, b) => a.textContent.localeCompare(b.textContent, 'en'));
				for(let option of optionsArray) {
					valueElement.appendChild(option);
				}
				break;
			case 'i':
			case 'd':
				valueElement = document.createElement('div');
				valueElement.dataset.internalValue = '0';
				valueElement.dataset.previousValue = '0';
				valueElement.contentEditable = 'true';
				valueElement.addEventListener('blur', numberChanged);

				div = document.createElement('div');
				div.textContent = '0';
				valueElement.appendChild(div);
				break;
			default:
				valueElement = document.createElement('div');
				valueElement.dataset.internalValue = ''; // Actually unused
				valueElement.dataset.previousValue = '';
				valueElement.contentEditable = 'true';
				valueElement.addEventListener('input', textChanged);

				div = document.createElement('div');
				div.textContent = '?'; // empty <div>s break everything
				valueElement.appendChild(div);
				break;
		}

		valueElement.dataset.internalType = type;
		valueElement.dataset.internalName = name;
		valueElement.classList.add("value");
		valueElement.id = id;
		newElement.appendChild(valueElement);

		let controlsElement = document.createElement('div');
		controlsElement.classList.add('controls');
		newElement.appendChild(controlsElement);

		let deleteButton = document.createElement('button');
		deleteButton.classList.add('delete');
		deleteButton.dataset.name = name;
		deleteButton.textContent = '❌';
		deleteButton.addEventListener('click', deleteFeature.bind(null, deletedFeatures));
		controlsElement.appendChild(deleteButton);

		return newElement;
	}

	function newItem() {
		let clone = document.importNode(document.getElementById('new-item-template').content, true);
		// noinspection JSUnresolvedFunction PHPStorm decided that addEventListener doesn't exist, that's it. The end. There's nothing to do about that, other than littering every file with noinspection.
		clone.querySelector('.removenew').addEventListener('click', removeNewClick);
		// noinspection JSUnresolvedFunction
		clone.querySelector('.addnew').addEventListener('click', addNewClick);
		let item = clone.children[0];
		let featuresElement = item.querySelector('.own.features.editing');
		clone.querySelector('.addfeatures button').addEventListener('click', addFeatureClick.bind(null, clone.querySelector('.addfeatures select'), item, featuresElement, false));
		return clone;
	}

	/**
	 * Handle clicking the "add" button inside new items
	 *
	 * @param ev Event
	 */
	function addNewClick(ev) {
		let item = newItem();
		ev.target.parentElement.parentElement.querySelector('.subitems').appendChild(item);
	}

	/**
	 * Handle clicking the "delete" button inside new items
	 *
	 * @param ev Event
	 */
	function removeNewClick(ev) {
		ev.target.parentElement.parentElement.remove();
	}

	/**
	 * Get changed or added features (changeset)
	 *
	 * @param {HTMLElement|Element} featuresElement
	 * @param {object} delta - Where to add the changeset
	 *
	 * @return {int} How many features have been changed or added
	 */
	function getChangedFeatures(featuresElement, delta) {
		let changed = featuresElement.querySelectorAll('.value.changed, .new .value');
		let counter = 0;

		for(let element of changed) {
			switch(element.dataset.internalType) {
				case 'e':
					delta[element.dataset.internalName] = element.value;
					break;
				case 'i':
				case 'd':
					delta[element.dataset.internalName] = element.dataset.internalValue;
					break;
				case 's':
				default:
					let paragraphs = element.getElementsByTagName('DIV');
					let lines = [];
					for(let paragraph of paragraphs) {
						lines.push(paragraph.textContent);
					}
					delta[element.dataset.internalName] = lines.join('\n');
			}
			counter++;
		}

		return counter;
	}

	/**
	 * Get new features (changeset) recursively, for a (sub)tree of new items
	 *
	 * @param {HTMLElement|Element} root - Item, the one with the "item" class
	 * @param {object} delta - Where to add the changeset
	 * @param {object[]} contents - Where to place inner items
	 *
	 * @return {int} How many features have been changed or added
	 */
	function getNewFeaturesRecursively(root, delta, contents) {
		let counter;

		let features, subitems;
		for(let el of root.children) {
			if(el.classList.contains('features')) {
				features = el;
			} else if(el.classList.contains('subitems')) {
				subitems = el;
			}
		}

		counter = getChangedFeatures(features, delta);

		for(let subitem of subitems.children) {
			let inner = {};
			let code = subitem.querySelector('.newcode').value;

			if(code) {
				inner.code = code;
			}
			inner.features = {};
			inner.contents = [];
			counter += getNewFeaturesRecursively(subitem, inner.features, inner.contents);
			contents.push(inner);
		}

		return counter;
	}

	/**
	 * @return {Promise<void>} Nothing, really.
	 */
	async function saveNew() {
		let counter;
		let root = document.querySelector('.head.item.editing');
		let delta = {};
		let contents = [];

		counter = getNewFeaturesRecursively(root, delta, contents);

		if(counter <= 0) {
			return;
		}

		let request, response;

		request = {};

		let code = document.querySelector('.newcode').value;

		// TODO: request.parent
		request.features = delta;
		request.contents = contents;

		/////////////////////
		// Extremely advanced debugging tools
		/////////////////////
		console.log(request);
		return;
		/////////////////////

		toggleButtons(true);

		let method, uri;
		if(code) {
			method = 'PUT';
			uri = '/v1/items/' + encodeURIComponent(code);
		} else {
			method = 'POST';
			uri = '/v1/items';
		}

		response = await fetch(uri, {
			headers: {
				'Accept': 'application/json',
				'Content-Type': 'application/json'
			},
			method: method,
			credentials: 'include',
			body: JSON.stringify(request)
		});

		// TODO: don't use goBack when saving entirely new items
		try {
			await jsendMe(response, goBack, displayError.bind(null, null));
		} finally {
			toggleButtons(false);
		}
	}

	/**
	 * @return {Promise<void>} Nothing, really.
	 */
	async function saveModified() {
		let counter;
		let delta = {};

		counter = getChangedFeatures(document.querySelector('.item.head.editing .features.own.editing'), delta);

		for(let deleted of deletedFeatures) {
			delta[deleted] = null;
			counter++;
		}

		if(counter <= 0) {
			return;
		}

		toggleButtons(true);
		let code = document.querySelector('.item.head.editing').dataset.code;
		let response;

		response = await fetch('/v1/items/' + encodeURIComponent(code) + '/features', {
			headers: {
				'Accept': 'application/json',
				'Content-Type': 'application/json'
			},
			method: 'PATCH',
			credentials: 'include',
			body: JSON.stringify(delta)
		});

		try {
			await jsendMe(response, goBack, displayError.bind(null, null));
		} finally {
			toggleButtons(false);
		}
	}

	/**
	 * Disable itembuttons. Or enable them.
	 *
	 * @param {boolean} disabled
	 */
	function toggleButtons(disabled) {
		for(let button of document.querySelectorAll('.itembuttons button')) {
			button.disabled = disabled;
		}
	}

	async function jsendMe(response, onsuccess, onerror) {
		if(response.headers.get("content-type").indexOf("application/json") > -1) {
			try {
				let jsend = await response.json();
				if(response.ok && jsend.status === 'success') {
					onsuccess();
				} else {
					if(jsend.status === 'fail') {
						if(jsend.data) {
							for(let field of Object.keys(jsend.data)) {
								let message = jsend.data[field];
								onerror(message);
								let input = document.getElementById('feature-edit-' + field);
								if(input !== null) {
									input.classList.add('invalid');
								}
							}
						} else {
							// "fail" with no data
							onerror(response.status.toString() + ': unspecified validation error');
						}
					} else {
						// JSend error, or not a JSend response
						onerror(response.status.toString() + ': ' + jsend.message ? jsend.message : '');
					}
				}
			} catch(e) {
				// invalid JSON
				onerror(e.message);
				console.error(response.body);
			}
		} else {
			// not JSON
			let text = await response.text();
			onerror(response.status.toString() + ': ' + text);
		}
	}

	function goBack() {
		let here = window.location.pathname;
		let query = window.location.search;
		let hash = window.location.hash;

		let pieces = here.split('/');
		let penultimate = pieces[pieces.length - 2];
		if(penultimate === 'edit' || penultimate === 'add') {
			pieces.splice(pieces.length - 2);
			window.location.href = pieces.join('/') + query + hash;
		} else {
			// This feels sooooo 2001
			window.history.back();
		}
	}

	/**
	 * Add divs that disappear randomly from contentEditable elements
	 *
	 * @param {HTMLElement} element
	 */
	function fixDiv(element) {
		for(let node of element.childNodes) {
			if(node.nodeType === 3) {
				let div = document.createElement('div');
				div.textContent = node.textContent;
				element.insertBefore(div, node);
				element.removeChild(node);

				// Dima Viditch is the only person in the universe that has figured this out: https://stackoverflow.com/a/16863913
				// Nothing else worked. NOTHING.
				let wrongSelection = window.getSelection();
				let pointlessRange = document.createRange();
				div.innerHTML = '\u00a0';
				pointlessRange.selectNodeContents(div);
				wrongSelection.removeAllRanges();
				wrongSelection.addRange(pointlessRange);
				document.execCommand('delete', false, null);
			}
		}
	}
}());
