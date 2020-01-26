(async function() {
	"use strict";

	// For search: these methods are called from there, even though the editor is not active
	window.newFeature = createFeatureElement;
	window.focusFeatureValueInput = focusFeatureValueInput;

	// To generate unique IDs for features
	let featureIdsCounter = 0;

	// Beamed from the server to here in a giant JSON
	let featureNames = new Map();
	let featureTypes = new Map();
	let featureValues = new Map();
	let featureValuesTranslated = new Map();
	let featureDefaultsByType = new Map();

	// Set and unset as needed by the client
	let fadingErrors = new Map();
	let linkedErrors = new Map();

	// Fill the feature selection menus
	for(let select of document.querySelectorAll('.allfeatures')) {
		select.appendChild(document.importNode(document.getElementById('features-select-template').content, true));
	}

	// Get the giant JSON
	let response = await fetch('/features.json', {
		headers: {
			'Accept': 'application/json'
		},
		method: 'GET',
		credentials: 'include',
	});

	if(response.ok) {
		let payload = await response.json();

		// Rebuild the Maps. These were previously precomputed.
		let features = payload["features"];
		for(let group of Object.keys(features)) { // Keys are group IDs
			let featuresInGroup = features[group]; // Features from a group
			for(let feature of featuresInGroup) { // For each feature, build Maps
				featureTypes.set(feature.name, feature.type);
				// noinspection JSUnresolvedVariable
				featureNames.set(feature.name, feature.printableName);
				if(feature.type === 'e') {
					featureValues.set(feature.name, Object.keys(feature.values));
					featureValuesTranslated.set(feature.name, Object.values(feature.values));
				}
			}
		}

		let defaults = payload["defaults"];
		for(let type of Object.keys(defaults)) { // Keys are types
			featureDefaultsByType.set(type, defaults[type]) // Value is a list of feature names
		}
	} else {
		console.error(response);
	}

	// Enable editor buttons, if some have been rendered server-side
	// noinspection JSUnresolvedVariable
	if(typeof activate === 'boolean' && activate) {
		document.execCommand('defaultParagraphSeparator', false, 'div');

		let itemEditing = document.querySelector('.item.head.editing');
		let isNew = itemEditing.classList.contains('new');

		/** @type {Set<string>} */
		let deletedFeatures;
		if(isNew) {
			deletedFeatures = null;
			if(itemEditing.classList.contains('product')) {
				itemEditing.querySelector('.itembuttons .save').addEventListener('click', saveNewProduct);
			} else {
				itemEditing.querySelector('.itembuttons .save').addEventListener('click', saveNewItem);
			}
			for(let el of itemEditing.querySelectorAll('.itembuttons .addnew')) {
				el.addEventListener('click', addNewClick);
			}
			for(let el of itemEditing.querySelectorAll('.itembuttons .removeemptyfeatures')) {
				el.addEventListener('click', removeEmptyFeaturesClick);
			}
			// Root "new item" cannot be deleted, just cancel the entire operation
			// itemEditing.querySelector('.itembuttons .removenew').addEventListener('click', removeNewClick);
		} else {
			deletedFeatures = new Set();
			itemEditing.querySelector('.itembuttons .save').addEventListener('click', saveModified.bind(null, deletedFeatures));
			let deleteButton = itemEditing.querySelector('.itembuttons .delete');
			if(deleteButton) {
				// noinspection JSUnresolvedFunction
				deleteButton.addEventListener('click', deleteClick);
			}
			let lostButton = itemEditing.querySelector('.itembuttons .lost');
			if(lostButton) {
				// noinspection JSUnresolvedFunction
				lostButton.addEventListener('click', lostClick);
			}
		}
		// Page may contain some non-head new items open for editing.
		// This happens mostly (possibly only) when cloning another item.
		// And we need to activate their buttons...
		for(let clone of itemEditing.querySelectorAll('.item.new.editing:not(head)')) {
			enableNewItemButtons(clone);
		}

		itemEditing.querySelector('.itembuttons .cancel').addEventListener('click', goBack.bind(null, null, true));

		for(let item of document.querySelectorAll('.item.editing')) {
			let featuresElement = null;
			let addFeaturesElement = null;

			// Find own features.
			// Cannot use querySelector et al or it may descend into other items, since the page structure is recursive
			for(let el of item.children) {
				if(el.classList.contains('own') && el.classList.contains('features')) {
					featuresElement = el;
					if(addFeaturesElement !== null) {
						// Terminate search as soon as both are found (in any order)
						break;
					}
				} else if(el.classList.contains('addfeatures')) {
					addFeaturesElement = el;
					if(featuresElement !== null) {
						break;
					}
				}
			}

			// Find "add" button and add listener
			if(addFeaturesElement) {
				let addFeatureButton = addFeaturesElement.querySelector('button');
				addFeatureButton.addEventListener('click', addFeatureClick.bind(null, addFeaturesElement.querySelector('select'), featuresElement, deletedFeatures));
			}

			enableFeatureHandlers(featuresElement, deletedFeatures);
		}
	}

	/**
	 * Enable all buttons, handlers, events, whatever in the .feature area.
	 *
	 * @param {Element} featuresElement
	 * @param {Set|null} deletedFeatures
	 */
	function enableFeatureHandlers(featuresElement, deletedFeatures = null) {
		// Find the default "type" feature and add a listener
		let type = featuresElement.querySelector('.feature-edit-type');
		if(type) {
			type.getElementsByTagName('SELECT')[0].addEventListener('change', setTypeClick.bind(null, featuresElement, type))
		}

		let handler, otherHandler;

		// Enable the "X" button next to features
		handler = deleteFeatureClick.bind(null, deletedFeatures);
		for(let deleteButton of featuresElement.querySelectorAll('.delete')) {
			deleteButton.addEventListener('click', handler);
		}

		// Enable key combinations to delete features and format text
		handler = deleteFeatureKey.bind(null, deletedFeatures);
		otherHandler = textFormatKey;
		for(let value of featuresElement.querySelectorAll('.value')) {
			value.addEventListener('keydown', handler);
			value.addEventListener('keydown', otherHandler);
		}

		// Event listeners for string and numeric features
		for(let div of featuresElement.querySelectorAll('[contenteditable]')) {
			if(div.dataset.internalType === 's') {
				div.addEventListener('paste', sanitizePaste);
				div.addEventListener('input', textChangedEvent);
			} else {
				div.addEventListener('blur', numberChanged);
			}
		}

		// For enum features
		for(let select of featuresElement.querySelectorAll('select')) {
			select.addEventListener('change', selectChanged);
		}
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
	 * @see textChangedEvent
	 * @param {HTMLElement} input The input field
	 * @TODO: adding and removing newlines should count as "changed", but it's absurdly difficult to detect, apparently...
	 */
	function textChanged(input) {
		let feature = input.parentElement;
		fixDiv(input);
		fadeOutInlineErrors(feature);
		// Newly added element
		if (!input.dataset.initialValue) {
			return;
		}

		if (input.textContent.length === input.dataset.initialValue.length) {
			if (input.textContent === input.dataset.initialValue) {
				input.classList.remove('changed');
				return;
			}
		}
		input.classList.add('changed');
	}

	/**
	 * Handle changing content of an editable text div
	 *
	 * @see textChanged
	 * @param ev Event
	 * @TODO: adding and removing newlines should count as "changed", but it's absurdly difficult to detect, apparently...
	 */
	function textChangedEvent(ev) {
		textChanged(ev.target);
	}

	/**
	 * Handle changing value of a <select>
	 *
	 * @param ev Event
	 */
	function selectChanged(ev) {
		let feature = ev.target.parentElement;
		fadeOutInlineErrors(feature);
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
		let feature = ev.target.parentElement;
		fixDiv(ev.target);
		fadeOutInlineErrors(feature);
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
			// Remove content
			while(ev.target.firstChild) {
				ev.target.removeChild(ev.target.firstChild);
			}
			// Add previous content
			let div = document.createElement("div");
			ev.target.appendChild(div);
			if(ev.target.dataset.previousValue === '') {
				div.textContent = '';
			} else {
				div.textContent = valueToPrintable(unit, parseInt(ev.target.dataset.previousValue));
			}
			// Display error message
			if(!(ev.target.dataset.previousValue === '' && e.message === 'empty-input')) {
				let displayed = displayInlineError(feature, e.message);
				if (!displayed) {
					throw e;
				}
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
	 * Remove formatting when pasting into contentEditable
	 *
	 * @param ev Event
	 */
	function sanitizePaste(ev) {
		ev.preventDefault();
		// noinspection JSUnresolvedVariable
		let text = ev.clipboardData.getData("text/plain");
		document.execCommand("insertHTML", false, text);
		if(ev.target.classList.contains("value")) {
			fixDiv(ev.target);
		} else {
			fixDiv(ev.target.parentElement);
		}
	}

	/**
	 * Show error messages near wrong features in editor mode.
	 *
	 * @param {HTMLElement} feature the feature li element. Error message will be appended here.
	 * @param {string|null} templateName Error identifier, used to get the correct template
	 * @param {Element} root Root item in edit page, to show a linked error at the top, too
	 */
	function displayInlineError(feature, templateName = null, root = null, ) {
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
		removeInlineErrors(feature);
		let template = document.importNode(templateThingThatShouldExist.content, true).firstElementChild;
		feature.appendChild(template);
		feature.classList.add("haserror");

		if(root !== null) {
			let linkedError = document.importNode(document.getElementById('feature-edit-template-linked-error').content, true).firstElementChild;
			root.querySelector('.itembuttons').insertAdjacentElement('afterend', linkedError);
			linkedErrors.set(template, linkedError);
			feature.id = 'first-error';
		}

		return true;
	}

	/**
	 * Remove all error messages from a feature.
	 *
	 * @param {HTMLElement} feature the feature li element. Error message will be appended here.
	 * @see displayInlineError
	 */
	function removeInlineErrors(feature) {
		let timeout = fadingErrors.get(feature);
		if(timeout !== null) {
			clearTimeout(timeout);
			fadingErrors.delete(feature);
		}
		feature.classList.remove("haserror");
		feature.classList.remove("fading");
		for(let error of feature.querySelectorAll('.error.description')) {
			feature.removeChild(error);
			let linked = linkedErrors.get(error);
			if(typeof linked !== "undefined") {
				// It's 2019, this function finally exists.
				linked.remove();
			}
		}
	}

	/**
	 * Remove all error messages from a feature after a timeout, with a fade effect.
	 *
	 * @param {HTMLElement} feature the feature li element. Error message will be appended here.
	 * @see displayInlineError
	 */
	function fadeOutInlineErrors(feature) {
		if(feature.classList.contains("haserror") && !feature.classList.contains("fading")) {
			feature.classList.add("fading");
			let timeout = setTimeout(removeInlineErrors.bind(null, feature), 1400);
			fadingErrors.set(feature, timeout);
		}
	}

	/**
	 * Show error messages.
	 *
	 * @param {string|null} message
	 * @deprecated
	 */
	function displayError(message = null) {
		let templateThingThatShouldExist;
		templateThingThatShouldExist = document.getElementById('feature-edit-template-generic-error');

		let template = document.importNode(templateThingThatShouldExist.content, true);
		let element = template.children[0];
		element.id = 'feature-edit-last-error';

		if(message !== null) {
			// firstChild is a text node
			element.firstChild.textContent = message;
		}

		// Quick and dirty fix to get the same element as before (template was changed)
		let button = document.createElement('button');
		button.textContent = 'OK';
		button.addEventListener('click', removeError.bind(null, element));
		element.appendChild(button);
		element.classList.add("message");

		let last = document.getElementById('feature-edit-last-error');
		if(last) {
			last.id = undefined;
		}

		let item = document.querySelector('.item.head.editing');
		item.insertBefore(template, item.getElementsByTagName('HEADER')[0].nextElementSibling);
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
			throw new Error("empty-input");
		}
		if(unit === 'n') {
			let number = parseInt(input);
			if(isNaN(number)) {
				throw new Error("string-parse-nan");
			} else if(number < 0) {
				throw new Error("negative-input");
			} else if(number === 0) {
				throw new Error("meaningless-zero");
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
		let number = parseFloat(string.substr(0, i));
		if(isNaN(number)) {
			throw new Error('string-parse-nan')
		} else if(number === 0) {
			throw new Error("meaningless-zero");
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
	 * @param {HTMLElement} element - Element to be deleted
	 * @param {string|null} name - feature name, used only if "set" is provided
	 * @param {Set<string>|null} set - Deleted features, null if not tracking
	 */
	function deleteFeature(element, name = '', set = null) {
		if(set !== null) {
			set.add(name);
		}
		element.remove();
	}

	/**
	 * Handle clicking the "X" button
	 *
	 * @param {Set<string>|null} set - Deleted features, null if not tracking
	 * @param ev Event
	 */
	function deleteFeatureClick(set, ev) {
		deleteFeature(ev.target.parentElement.parentElement, ev.target.dataset.name, set);
	}

	/**
	 * Handle a key combination alternative to the X button
	 *
	 * @param {Set<string>|null} set - Deleted features, null if not tracking
	 * @param ev KeyboardEvent
	 */
	function deleteFeatureKey(set, ev) {
		let pressed = (ev.ctrlKey && ev.key === 'Delete') || (ev.altKey && ev.ctrlKey && (ev.key === 'z' || ev.key === 'Z'));
		if(pressed) {
			ev.preventDefault();
			let row = ev.target.parentElement;
			if(row.nextElementSibling && row.nextElementSibling.tagName === 'LI') {
				row.nextElementSibling.querySelector('.value').focus();
			}
			deleteFeature(row, ev.target.dataset.internalName, set);
		}
	}

	/**
	 * Handle key combinations for text formatting
	 *
	 * @param ev KeyboardEvent
	 */
	function textFormatKey(ev) {
		if(ev.altKey && ev.ctrlKey && ev.target.dataset.internalType === "s") {
			switch(ev.key) {
				case 'U':
				case 'u':
					ev.target.childNodes[0].childNodes[0].textContent = ev.target.childNodes[0].childNodes[0].textContent.toLocaleUpperCase();
					textChanged(ev.target);
					break;
				case 'L':
				case 'l':
					ev.target.childNodes[0].childNodes[0].textContent = ev.target.childNodes[0].childNodes[0].textContent.toLocaleLowerCase();
					textChanged(ev.target);
					break;
				case 'y':
				case 'Y':
					// https://stackoverflow.com/a/22193094
					ev.target.childNodes[0].childNodes[0].textContent = ev.target.childNodes[0].childNodes[0].textContent
						.split(' ')
						.map(a => a[0].toUpperCase() + a.substr(1).toLowerCase())
						.join(' ');
					textChanged(ev.target);
					break;
			}
		}
	}

	/**
	 * Handle clicking the "add" button for new features
	 *
	 * @param {HTMLSelectElement} select - HTML "select" element
	 * @param {HTMLElement} featuresElement - The "own features" element
	 * @param {Set<string>|null} deletedFeatures - Deleted features set, can be null if not tracked
	 */
	function addFeatureClick(select, featuresElement, deletedFeatures = null) {
		let name = select.value;
		addFeatureEditableDedupe(featuresElement, name, deletedFeatures);
	}

	/**
	 * Find feature element for a feature, useful when checking for duplicates before insert
	 *
	 * @param {string} name - Feature name
	 * @param {HTMLElement} featuresElement - Element that contains feature elements
	 * @return {Element|null}
	 */
	function findFeatureElement(name, featuresElement) {
		let pseudoId = 'feature-edit-' + name;

		let elements = featuresElement.getElementsByClassName(pseudoId);
		if (elements.length > 0) {
			// There should be only one, hopefully
			return elements[0];
		}
		return null;
	}

	/**
	 * Add a feature, or focus it if it already exists.
	 *
	 * @param {HTMLElement} featuresElement - The "own features" element
	 * @param {string} name - Feature name
	 * @param {Set<string>|null} deletedFeatures - Deleted features set, can be null if not tracked
	 */
	function addFeatureEditableDedupe(featuresElement, name, deletedFeatures = null) {
		let theFeature = findFeatureElement(name, featuresElement);

		if(theFeature === null) {
			theFeature = addFeatureEditable(name, featuresElement, deletedFeatures);
		}

		focusFeatureValueInput(theFeature);
	}

	/**
	 * Focus the value (select or div) of a feature input.
	 *
	 * @param {Node|HTMLElement} element
	 */
	function focusFeatureValueInput(element) {
		let input = element.querySelector('select.value');
		if(input) {
			input.focus();
			return;
		}

		input = element.querySelector('.value div');
		if(input) {
			const selection = window.getSelection();
			const range = document.createRange();
			range.selectNodeContents(input);
			selection.removeAllRanges();
			selection.addRange(range);
		}
	}

	/**
	 * Add a new and editable feature element to the "own features" section.
	 * This attaches event listeners that are suitable for edit mode but not for search, so beware.
	 *
	 * @param {string} name - Feature name
	 * @param {HTMLElement} featuresElement - The "own features" element
	 * @param {Set<string>|null} deletedFeatures - Deleted features set, can be null if not tracked
	 */
	function addFeatureEditable(name, featuresElement, deletedFeatures = null) {
		let pseudoId = 'feature-edit-' + name;
		let newElement = createFeatureElement(name, pseudoId, deletedFeatures);

		// If it's a new item and we're adding a type, attach this listener...
		if(name === 'type' && deletedFeatures === null) {
			newElement.getElementsByTagName('SELECT')[0].addEventListener('change', setTypeClick.bind(null, featuresElement, newElement))
		}

		// Remove from set of deleted features ("undelete"), if there's a set
		if(deletedFeatures !== null) {
			deletedFeatures.delete(name);
		}

		// Insert
		featuresElement.querySelector('.newfeatures ul').appendChild(newElement);
		return newElement;
	}

	/**
	 * Delete empty features from an editable item
	 *
	 * @param {HTMLElement} featuresElement - Where features are located
	 * @param {string[]} except - These will be left even if empty
	 */
	function deleteEmptyFeatures(featuresElement, except = []) {
		let all = featuresElement.querySelectorAll('li.feature-edit');
		for(let el of all) {
			let valueElement = el.querySelector('.value');
			if(!except.includes(valueElement.dataset.internalName) && getValueFrom(valueElement) === '') {
				deleteFeature(el);
			}
		}
	}

	/**
	 * Add empty features according to object type, if nothing other than type has been added.
	 *
	 * @param {HTMLElement} featuresElement - The "own features" element
	 * @param {HTMLSelectElement} featureElement - The feature element itself, to get type
	 */
	function setTypeClick(featuresElement, featureElement) {
		deleteEmptyFeatures(featuresElement, ['type', 'working']);

		let features;
		let type = featureElement.getElementsByTagName('SELECT')[0].value;

		features = featureDefaultsByType.get(type);
		if(typeof features === 'undefined') {
			features = [];
		}

		for(let name of features) {
			addFeatureEditableDedupe(featuresElement, name, null);
		}
	}

	/**
	 * Maybe a template would have been better...
	 *
	 * @param {string} name - Feature name
	 * @param {string} pseudoId - Unique element identifier (already confirmed to be unique), used as class
	 * @param {Set<string>|null} deletedFeatures - Deleted features set. Null if not tracked (for new items)
	 * @param {function|null} getComparison - Get the comparison dropdown (for searches)
	 */
	function createFeatureElement(name, pseudoId, deletedFeatures, getComparison = null) {
		// Needed for labels
		let id = pseudoId + featureIdsCounter++;
		let type = featureTypes.get(name);

		let newElement = document.createElement("li");
		newElement.classList.add(pseudoId);
		newElement.classList.add("feature-edit");
		let nameElement = document.createElement("div");
		nameElement.classList.add("name");
		newElement.appendChild(nameElement);
		let labelElement = document.createElement("label");
		labelElement.htmlFor = id;
		labelElement.textContent = featureNames.get(name);
		nameElement.appendChild(labelElement);

		if(getComparison !== null) {
			newElement.appendChild(getComparison(type));
		}

		let valueElement, div;
		switch(type) {
			case 'e':
				valueElement = document.createElement('select');
				let defaultOption = document.createElement('option');
				defaultOption.value = "";
				defaultOption.disabled = true;
				defaultOption.selected = true;
                valueElement.appendChild(defaultOption);
                valueElement.addEventListener("change", selectChanged);

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
				valueElement.dataset.internalValue = '';
				valueElement.dataset.previousValue = '';
				valueElement.contentEditable = 'true';
				valueElement.addEventListener('blur', numberChanged);
				valueElement.addEventListener("paste", sanitizePaste);

				div = document.createElement('div');
				div.textContent = '';
				valueElement.appendChild(div);
				break;
			case 's':
			default:
				valueElement = document.createElement('div');
				valueElement.dataset.internalValue = ''; // Actually unused
				valueElement.dataset.previousValue = '';
				valueElement.contentEditable = 'true';
				valueElement.addEventListener('paste', sanitizePaste);
				valueElement.addEventListener('input', textChangedEvent);

				div = document.createElement('div');
				//div.textContent = '?'; // empty <div>s break everything // not anymore apparently? 2018-12-05
				valueElement.appendChild(div);
				break;
		}

		valueElement.dataset.internalType = type;
		valueElement.dataset.internalName = name;
		valueElement.classList.add("value");
		valueElement.classList.add("changed");
		valueElement.id = id;
		newElement.appendChild(valueElement);

		let controlsElement = document.createElement('div');
		controlsElement.classList.add('controls');
		newElement.appendChild(controlsElement);

		let deleteButton = document.createElement('button');
		deleteButton.classList.add('delete');
		deleteButton.dataset.name = name;
		deleteButton.textContent = '❌';
		deleteButton.tabIndex = -1;
		deleteButton.addEventListener('click', deleteFeatureClick.bind(null, deletedFeatures));
		valueElement.addEventListener('keydown', deleteFeatureKey.bind(null, deletedFeatures));
		valueElement.addEventListener('keydown', textFormatKey);
		controlsElement.appendChild(deleteButton);

		return newElement;
	}

	/**
	 * Enable the "More" and "Delete" buttons for new items
	 *
	 * @param item
	 */
	function enableNewItemButtons(item) {
		item.querySelector('.removenew').addEventListener('click', removeNewClick);
		for(let el of item.querySelectorAll('.addnew')) {
			el.addEventListener('click', addNewClick);
		}
		for(let el of item.querySelectorAll('.removeemptyfeatures')) {
			el.addEventListener('click', removeEmptyFeaturesClick);
		}
	}

	/**
	 * Create a new blank item
	 *
	 * @return {Node}
	 */
	function newItem() {
		let clone = document.importNode(document.getElementById('new-item-template').content, true);
		enableNewItemButtons(clone);
		let item = clone.children[0];
		let featuresElement = item.querySelector('.own.features.editing');
		let dropdown = clone.querySelector('.addfeatures .allfeatures');
		let type = clone.querySelector('.feature-edit-type');
		dropdown.appendChild(document.importNode(document.getElementById('features-select-template').content, true));
		clone.querySelector('.addfeatures button').addEventListener('click', addFeatureClick.bind(null, dropdown, featuresElement, null));
		type.querySelector('select').addEventListener('change', setTypeClick.bind(null, featuresElement, type));
		enableFeatureHandlers(featuresElement, null);
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
	 * Handle clicking the "remove empty features" button inside new items
	 *
	 * @param ev Event
	 */
	function removeEmptyFeaturesClick(ev) {
 		let item = ev.target.parentElement.parentElement;
 		let featuresElement;
 		// Avoids accidental depth-first search into other items
		for(let el of item.children) {
			if(el.classList.contains('features')) {
				featuresElement = el;
				break;
			}
		}
		let changed = featuresElement.querySelectorAll('.value');
		for(let element of changed) {
			let feature = element.parentElement;
			let value = getValueFrom(element);
			if(value === "") {
				feature.querySelector('.delete').click();
			}
		}
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
	 * Get value from an editable feature element
	 *
	 * @param valueElement - Value element, e.g. featuresElement.querySelectorAll('.value')
	 * @return {string} - Value. Empty string means default/no value
	 */
	function getValueFrom(valueElement) {
		let value;
		switch (valueElement.dataset.internalType) {
			case 'e':
				value = valueElement.value;
				break;
			case 'i':
			case 'd':
				value = valueElement.dataset.internalValue;
				break;
			case 's':
			default:
				let paragraphs = valueElement.getElementsByTagName('DIV');
				let lines = [];
				for (let paragraph of paragraphs) {
					lines.push(paragraph.textContent);
				}
				value = lines.join('\n');
		}
		return value;
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
			let feature = element.parentElement;
			let value = getValueFrom(element);
			if(value === "") {
				throw new EmptyFeatureValueError(feature);
			}
			delta[element.dataset.internalName] = value;
			counter++;
		}

		return counter;
	}

	class EmptyFeatureValueError extends Error {
		constructor(feature, message = "Empty value") {
			// noinspection JSCheckFunctionSignatures
			super(message);
			this.feature = feature;
			this.name = "EmptyFeatureValueError";
		}
	}


	/**
	 * Get new features (changeset) recursively, for a (sub)tree of new items
	 *
	 * @param {HTMLElement|Element} root - Item, the one with the "item" class
	 * @param {object} delta - Where to add the changeset
	 * @param {object[]|null} contents - Where to place inner items, or null to ignore them
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

		// End here if we don't want subitems
		if(contents === null) {
			return counter;
		}

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

	function getTimeoutController() {
		let controller = new AbortController();
		setTimeout(() => controller.abort(), 30000);
		return controller;
	}

	class TimeoutError extends Error {
		constructor() {
			super();
			//this.name = this.constructor.name;
		}
	}

	async function fetchWithTimeout(uri, init) {
		init.signal = getTimeoutController().signal;
		try {
			return response = await fetch(uri, init);
		} catch(err) {
			if (err.name === 'AbortError') {
				throw new TimeoutError();
			} else {
				throw err;
			}
		}
	}

	async function saveNewItem() {
		let counter;
		let root = document.querySelector('.head.item.editing');

		let delta = {};
		let contents = [];

		try {
			counter = getNewFeaturesRecursively(root, delta, contents);
		} catch(e) {
			if(e instanceof EmptyFeatureValueError) {
				displayInlineError(e.feature, "empty-input", root);
				return;
			} else {
				throw e;
			}
		}

		if(counter <= 0) {
			return;
		}

		let request = {};

		let code = document.querySelector('.newcode').value;
		let parentSelector = root.querySelector('.setlocation input');
		if(parentSelector) {
			if(parentSelector.value !== '') {
				request.parent = parentSelector.value;
			}
		} else {
			let parent = root.parentElement.parentElement.dataset.code;
			if(parent) {
				request.parent = parent;
			} else {
				displayError('Internal error: cannot find location');
				return;
			}
		}

		request.features = delta;
		request.contents = contents;

		let method, uri;
		if(code) {
			method = 'PUT';
			uri = '/v2/items/' + encodeURIComponent(code);
		} else {
			method = 'POST';
			uri = '/v2/items';
		}

		await saveNew(request, uri, method);
	}

	async function saveNewProduct() {
		let root = document.querySelector('.head.item.editing');

		let counter;
		let delta = {};

		try {
			counter = getNewFeaturesRecursively(root, delta, null);
		} catch(e) {
			if(e instanceof EmptyFeatureValueError) {
				displayInlineError(e.feature, "empty-input", root);
				return;
			} else {
				throw e;
			}
		}

		if(counter <= 0) {
			return;
		}

		let request;

		request = {};

		let brand = document.getElementById('new-product-brand').value;
		let model = document.getElementById('new-product-model').value;
		let variant = document.getElementById('new-product-variant').value;

		request.features = delta;

		let method = 'PUT';
		let uri = '/v2/products/' + encodeURIComponent(brand) + '/' + encodeURIComponent(model) + '/' + encodeURIComponent(variant);

		await saveNew(request, uri, method);
	}

	async function saveNew(request, uri, method) {
		let encoded = JSON.stringify(request);

		console.log("Send to " + uri);
		console.log(encoded);

		toggleButtons(true);

		try {
			let response = await fetchWithTimeout(uri, {
				headers: {
					'Accept': 'application/json',
					'Content-Type': 'application/json'
				},
				method: method,
				credentials: 'include',
				body: encoded
			});
			let json = await response.json();
			if (response.status === 200 || response.status === 201) {
				goBack(json);
			} else if (response.status === 401) {
				displayError('Session expired or logged out. Open another tab, log in and try again.');
			} else {
				let message = json.message || "Unknown error, status: " + response.status;
				displayError(message)
			}
		} catch (err) {
			if (err instanceof TimeoutError) {
				displayError('Request timed out');
			} else {
				throw err;
			}
		} finally {
			toggleButtons(false);
		}
	}

	async function deleteClick(ev) {
		let code = ev.target.parentElement.dataset.forItem;
		let go = confirm(`Delete item ${code}: are you sure? Really? REALLY?`);
		if(go) {
			toggleButtons(true);

			let method, uri;
			method = 'DELETE';
			uri = '/v2/items/' + encodeURIComponent(code);

			try {
				let response = await fetchWithTimeout(uri, {
					headers: {
						'Accept': 'application/json'
					},
					method: method,
					credentials: 'include'
				});

				if(response.status === 204) {
					goBack();
				} else if(response.status === 401) {
					displayError('Session expired or logged out. Open another tab, log in and try again.');
				} else {
					let json = await response.json();
					let message = json.message || "Unknown error, status: " + response.status;
					displayError(message);
				}
			} catch(err) {
				if(err instanceof TimeoutError) {
					displayError('Request timed out');
				} else {
					throw err;
				}
			} finally {
				toggleButtons(false);
			}
		}
	}

	async function lostClick(ev) {
		let code = ev.target.parentElement.dataset.forItem;
		toggleButtons(true);

		let method, uri;
		method = 'DELETE';
		uri = `/v2/items/${encodeURIComponent(code)}/parent`;

		try {
			let response = await fetchWithTimeout(uri, {
				headers: {
					'Accept': 'application/json'
				},
				method: method,
				credentials: 'include'
			});

			if(response.status === 204) {
				goBack();
			} else if(response.status === 401) {
				displayError('Session expired or logged out. Open another tab, log in and try again.');
			} else {
				let json = await response.json();
				let message = json.message || "Unknown error, status: " + response.status;
				displayError(message);
			}
		} catch(err) {
			if(err instanceof TimeoutError) {
				displayError('Request timed out');
			} else {
				throw err;
			}
		} finally {
			toggleButtons(false);
		}
	}

	/**
	 * @return {Promise<void>} Nothing, really.
	 */
	async function saveModified(deletedFeatures) {
		let counter;
		let root = document.querySelector('.head.item.editing');
		let delta = {};

		try {
			counter = getChangedFeatures(root.querySelector('.features.own.editing'), delta);
		} catch(e) {
			if(e instanceof EmptyFeatureValueError) {
				displayInlineError(e.feature, "empty-input", root);
				return;
			} else {
				throw e;
			}
		}

		for(let deleted of deletedFeatures) {
			delta[deleted] = null;
			counter++;
		}

		if(counter <= 0) {
			return;
		}

		toggleButtons(true);
		let dataset = document.querySelector('.item.head.editing').dataset;
		let code = dataset.code;
		let brand = dataset.brand;
		let model = dataset.model;
		let variant = dataset.variant;

		let uri, response;

		if(typeof code === "undefined") {
			uri = '/v2/products/' + encodeURIComponent(brand) + '/' + encodeURIComponent(model) + '/' + encodeURIComponent(variant) + '/features';
		} else {
			uri = '/v2/items/' + encodeURIComponent(code) + '/features';
		}

		let encoded = JSON.stringify(delta);

		console.log("Send to " + uri);
		console.log(encoded);

		try {
			response = await fetchWithTimeout(uri, {
				headers: {
					'Accept': 'application/json',
					'Content-Type': 'application/json'
				},
				method: 'PATCH',
				credentials: 'include',
				body: encoded
			});

			if(response.status === 204) {
				goBack();
			} else if(response.status === 401) {
				displayError('Session expired or logged out. Open another tab, log in and try again.');
			} else {
				let json = await response.json();
				let message = json.message || "Unknown error, status: " + response.status;
				displayError(message)
			}
		} catch(err) {
			if(err instanceof TimeoutError) {
				displayError('Request timed out');
			} else {
				throw err;
			}
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

	/**
	 * Go back to view mode. Or go forward to view mode, depending on tne point of view.
	 *
	 * @param {string|null} json - JSON response with newly added item code, to redirect there from add item page
	 * @param {boolean} confirm - Show the "leave/remain" message if there are unsaved changes
	 */
	function goBack(json = null, confirm = false) {
		let here = window.location.pathname;
		let query = window.location.search;
		let hash = window.location.hash;
		if(!confirm) {
			window.onbeforeunload = undefined;
		}

		let pieces = here.split('/');
		let penultimate = pieces[pieces.length - 2];
		if(penultimate === 'edit' || penultimate === 'add') {
			pieces.splice(pieces.length - 2);
			window.location.href = pieces.join('/') + query + hash;
		} else {
			if(json) {
				if(typeof json['brand'] === 'undefined') {
					window.location.href = '/item/' + encodeURIComponent(json);
				} else {
					window.location.href = '/product/' + encodeURIComponent(json['brand']) + '/' + encodeURIComponent(json['model']) + '/' + encodeURIComponent(json['variant']);
				}
			} else {
				// This feels sooooo 2001
				window.history.back();
			}
		}
	}

	window.onbeforeunload = function() {
		if(document.querySelector('.value.changed, .new .value') !== null) {
			// This message doesn't actually appear (in Firefox at least), but it's the thought that counts.
			// The "leave/remain" popup still appears and works as expected.
			return 'Wait! You have unsaved data!';
		} else {
			return undefined;
		}
	};

	/**
	 * Add divs that disappear randomly from contentEditable elements
	 * and fixes other stochastic events since browser apparently
	 * and hopelessly bork contentEditable in some subtle and innovative
	 * way with each minor release.
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
				// ...on Firefox, at least. The code below, obtained via trial and error, seems to work on both Firefox and Chrome.
				//let wrongSelection = window.getSelection();
				//let pointlessRange = document.createRange();
				//div.textContent = '?';
				//pointlessRange.selectNodeContents(div);
				//wrongSelection.removeAllRanges();
				//wrongSelection.addRange(pointlessRange);
				//document.execCommand('delete', false, null);

				let sel = window.getSelection();
				// First (and only) child is a text node...
				sel.collapse(div.childNodes[0], div.childNodes[0].textContent.length);
			} else if(!node.hasChildNodes()) {
				// Solitary <br> that escaped a <div> (happens somewhat randomly when deleting text): nuke it
				element.removeChild(node);
			}
		}
	}
}());
