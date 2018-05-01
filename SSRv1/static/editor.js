(async function() {
	"use strict";

	window.newFeature = newFeature;

	// To generate unique IDs for features
	let featureIdsCounter = 0;

	let featureNames = new Map();
	let featureTypes = new Map();
	let featureValues = new Map();
	let featureValuesTranslated = new Map();

	for(let select of document.querySelectorAll('.allfeatures')) {
		select.appendChild(document.importNode(document.getElementById('features-select-template').content, true));
	}

	let response = await fetch('/features.json', {
		headers: {
			'Accept': 'application/json'
		},
		method: 'GET',
		credentials: 'include',
	});

	if(response.ok) {
		let everything = await response.json();

		// Rebuild the Maps. These were previously precomputed.
		for(let group of Object.keys(everything)) {
			let features = everything[group];
			for(let feature of features) {
				featureTypes.set(feature.name, feature.type);
				// noinspection JSUnresolvedVariable
				featureNames.set(feature.name, feature.printableName);
				if(feature.type === 'e') {
					featureValues.set(feature.name, Object.keys(feature.values));
					featureValuesTranslated.set(feature.name, Object.values(feature.values));
				}
			}
		}
	} else {
		console.error(response);
	}

	// Enable editor buttons, if some have been rendered server-side
	// noinspection JSUnresolvedVariable
	if(typeof activate === 'boolean' && activate) {
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
			itemEditing.querySelector('.itembuttons .save').addEventListener('click', saveModified.bind(null, deletedFeatures));
		}

		// noinspection JSUnresolvedFunction
		itemEditing.querySelector('.itembuttons .cancel').addEventListener('click', goBack.bind(null, null));

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
					el.querySelector('button').addEventListener('click', addFeatureClick.bind(null, el.querySelector('select'), featuresElement, deletedFeatures));
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
	 * Remove formatting when pasting into contentEditable
	 *
	 * @param {Event} e
	 */
	function sanitizePaste(e) {
		e.preventDefault();
		// noinspection JSUnresolvedVariable
		let text = e.clipboardData.getData("text/plain");
		document.execCommand("insertHTML", false, text);
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
		let element = template.children[0];
		element.id = 'feature-edit-last-error';
		element.getElementsByTagName('BUTTON')[0].addEventListener('click', removeError.bind(null, element));

		if(message !== null) {
			// firstChild is a text node
			element.firstChild.textContent = message;
		}

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
	 * @param {HTMLElement} featuresElement - The "own features" element
	 * @param {Set<string>|null} deletedFeatures - Deleted features set, can be null if not tracked
	 */
	function addFeatureClick(select, featuresElement, deletedFeatures = null) {
		let name = select.value;
		let pseudoId = 'feature-edit-' + name;

		let duplicates = featuresElement.getElementsByClassName(pseudoId);
		if(duplicates.length > 0) {
			// There should be only one, hopefully
			duplicates[0].querySelector('.value').focus();
			return null;
		}

		let newElement = addNewFeature(name, pseudoId, featuresElement, deletedFeatures);

		// TODO: replace with 'input, .value' once I figure out how to move the cursor to the right, or else the "line" div disappears spilling the text inside the outer div.
		let input = newElement.querySelector('input');
		if(input) {
			input.focus();
		}
	}

	/**
	 * Add a new and editable feature element to the "own features" section
	 *
	 * @param {string} name - Feature name
	 * @param {string} pseudoId - Unique element identifier (already confirmed to be unique), used as class
	 * @param {HTMLElement} featuresElement - The "own features" element
	 * @param {Set<string>|null} deletedFeatures - Deleted features set, can be null if not tracked
	 */
	function addNewFeature(name, pseudoId, featuresElement, deletedFeatures = null) {
		let newElement = newFeature(name, pseudoId, deletedFeatures);

		// If it's a new item and we're adding a type, attach this listener...
		if(name === 'type' && deletedFeatures === null) {
			newElement.getElementsByTagName('SELECT')[0].addEventListener('change', setTypeClick.bind(null, featuresElement, newElement))
		}

		// Remove from set of deleted features ("undelete"), if there's a set
		if(deletedFeatures !== null) {
			deletedFeatures.delete(name);
		}

		// Insert
		featuresElement.querySelector('.new ul').appendChild(newElement);
		return newElement;
	}

	/**
	 * Add empty features according to object type, if nothing other than type has been added.
	 *
	 * @param {HTMLElement} featuresElement - The "own features" element
	 * @param {HTMLSelectElement} select - The "select" that has been clicked, to get type
	 */
	function setTypeClick(featuresElement, select) {
		if(featuresElement.getElementsByTagName('LI').length > 1) {
			return;
		}

		let features;
		let type = select.getElementsByTagName('SELECT')[0].value;

		switch(type) {
			case 'case':
				features = ['cib', 'cib-old', 'other-code', 'os-license-version', 'os-license-code', 'brand', 'model', 'sn', 'usb-ports-n', 'working', 'motherboard-form-factor', 'psu-form-factor', 'power-connector', 'psu-volt', 'psu-ampere', 'arrival-batch', 'owner', 'color', 'software', 'notes'];
				break;
			case 'motherboard':
				features = ['brand', 'model', 'sn', 'motherboard-form-factor', 'key-bios-setup', 'key-boot-menu', 'cpu-socket', 'ram-form-factor', 'ram-type', 'agp-sockets-n', 'pci-sockets-n', 'pcie-sockets-n', 'sata-ports-n', 'ide-ports-n', 'jae-ports-n', 'game-ports-n', 'serial-ports-n', 'parallel-ports-n', 'usb-ports-n', 'firewire-ports-n', 'mini-firewire-ports-n', 'ethernet-ports-1000m-n', 'ethernet-ports-100m-n', 'rj11-ports-n', 'ps2-ports-n', 'integrated-graphics-brand', 'integrated-graphics-model', 'vga-ports-n', 'dvi-ports-n', 's-video-ports-n', 's-video-7pin-ports-n', 'mini-jack-ports-n', 'psu-connector-cpu', 'psu-connector-motherboard', 'working', 'color', 'owner', 'notes'];
				break;
			case 'cpu':
				features = ['brand', 'model', 'variant', 'core-n', 'isa', 'frequency-hertz', 'cpu-socket', 'integrated-graphics-brand', 'integrated-graphics-model', 'working', 'owner'];
				break;
			case 'ram':
				features = ['brand', 'model', 'sn', 'family', 'ram-type', 'ram-form-factor', 'frequency-hertz', 'capacity-byte', 'ram-ecc', 'working', 'color', 'owner', 'notes'];
				break;
			case 'hdd':
				features = ['brand', 'brand-manufacturer', 'model', 'sn', 'family', 'capacity-decibyte', 'hdd-odd-form-factor', 'spin-rate-rpm', 'mini-ide-ports-n', 'sata-ports-n', 'ide-ports-n', 'scsi-sca2-ports-n', 'scsi-db68-ports-n', 'data-erased', 'surface-scan', 'smart-data', 'working', 'owner'];
				break;
			case 'odd':
				features = ['brand', 'model', 'family', 'sn', 'odd-type', 'ide-ports-n', 'jae-ports-n', 'sata-ports-n', 'hdd-odd-form-factor', 'color', 'working', 'owner'];
				break;
			case 'fdd':
				features = ['brand', 'model', 'sn', 'color', 'working', 'owner'];
				break;
			case 'graphics-card':
				features = ['brand', 'brand-manufacturer', 'model', 'capacity-byte', 'vga-ports-n', 'dvi-ports-n', 'dms-59-ports-n', 's-video-ports-n', 's-video-7pin-ports-n', 'agp-sockets-n', 'pcie-sockets-n', 'pcie-power-pin-n', 'sn', 'color', 'working', 'owner'];
				break;
			case 'psu':
				features = ['brand', 'brand-manufacturer', 'model', 'sn', 'power-connector', 'power-rated-watt', 'psu-connector-cpu', 'psu-connector-motherboard', 'psu-form-factor', 'pcie-power-pin-n', 'sata-ports-n', 'color', 'working', 'owner'];
				break;
			case 'external-psu':
				features = ['brand', 'brand-manufacturer', 'model', 'sn', 'power-connector', 'psu-volt', 'psu-ampere', 'working', 'owner', 'notes'];
				break;
			case 'network-card':
				features = ['brand', 'model', 'pcie-sockets-n', 'pci-sockets-n', 'ethernet-ports-1000m-n', 'ethernet-ports-100m-n', 'ethernet-ports-10m-n', 'ethernet-ports-10base2-bnc-n', 'ethernet-ports-10base5-aui-n', 'mac', 'color', 'working', 'owner'];
				break;
			case 'audio-card':
			case 'other-card':
			case 'scsi-card':
			case 'modem-card':
			case 'wifi-card':
			case 'bluetooth-card':
			case 'tv-card':
				features = ['brand', 'model', 'pcie-sockets-n', 'pci-sockets-n', 'mini-pcie-sockets-n', 'mini-pci-sockets-n', 'color', 'working', 'owner'];
				break;
			case 'network-switch':
			case 'network-hub':
			case 'modem-router':
				features = ['brand', 'model', 'ethernet-ports-100m-n', 'power-connector', 'psu-volt', 'psu-ampere', 'color', 'working', 'owner', 'notes'];
				break;
			case 'keyboard':
			case 'mouse':
				features = ['brand', 'brand-manufacturer', 'model', 'sn', 'ps2-ports-n', 'usb-ports-n', 'color', 'working', 'owner'];
				break;
			case 'monitor':
				features = ['cib', 'cib-old', 'other-code', 'brand', 'model', 'sn', 'diagonal-inch', 'vga-ports-n', 'dvi-ports-n', 'hdmi-ports-n', 's-video-ports-n', 'usb-ports-n', 'power-connector', 'psu-volt', 'psu-ampere', 'working', 'notes'];
				break;
			case 'ports-bracket':
				features = ['usb-ports-n', 'serial-ports-n', 'parallel-ports-n', 'firewire-ports-n', 'color', 'owner'];
				break;
			case 'location':
				features = ['notes'];
				break;
			default:
				features = ['brand', 'model', 'working', 'owner', 'notes'];
				break;
		}

		for(let name of features) {
			addNewFeature(name, 'feature-edit-' + name, featuresElement, null);
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
	function newFeature(name, pseudoId, deletedFeatures, getComparison = null) {
		// Needed for labels
		let id = pseudoId + featureIdsCounter++;
		let type = featureTypes.get(name);

		let newElement = document.createElement("li");
		newElement.classList.add(pseudoId);
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
				valueElement.addEventListener("paste", sanitizePaste);

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
				valueElement.addEventListener("paste", sanitizePaste);

				div = document.createElement('div');
				div.textContent = '?'; // empty <div>s break everything
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
		let dropdown = clone.querySelector('.addfeatures .allfeatures');
		dropdown.appendChild(document.importNode(document.getElementById('features-select-template').content, true));
		clone.querySelector('.addfeatures button').addEventListener('click', addFeatureClick.bind(null, dropdown, featuresElement, null));
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
				displayError(null, 'Internal error: cannot find location');
				return;
			}
		}

		request.features = delta;
		request.contents = contents;

		toggleButtons(true);

		let method, uri;
		if(code) {
			method = 'PUT';
			uri = '/v1/items/' + encodeURIComponent(code) + '?fix';
		} else {
			method = 'POST';
			uri = '/v1/items?fix';
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

		try {
			await jsendMe(response, goBack, displayError.bind(null, null));
		} finally {
			toggleButtons(false);
		}
	}

	/**
	 * @return {Promise<void>} Nothing, really.
	 */
	async function saveModified(deletedFeatures) {
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
					onsuccess(jsend.data);
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
							console.error(jsend);
						}
					} else {
						// JSend error, or not a JSend response
						onerror(response.status.toString() + ': ' + jsend.message ? jsend.message : '');
						console.error(jsend);
					}
				}
			} catch(e) {
				// invalid JSON
				onerror(e.message);
				console.error(response);
			}
		} else {
			// not JSON
			let text = await response.text();
			onerror(response.status.toString() + ': ' + text);
			console.error(response);
		}
	}

	/**
	 * Go back to view mode. Or go forward to view mode, depending on tne point of view.
	 *
	 * @param {string|null} code - Newly added item code, to redirect there from add item page
	 */
	function goBack(code = null) {
		let here = window.location.pathname;
		let query = window.location.search;
		let hash = window.location.hash;

		let pieces = here.split('/');
		let penultimate = pieces[pieces.length - 2];
		if(penultimate === 'edit' || penultimate === 'add') {
			pieces.splice(pieces.length - 2);
			window.location.href = pieces.join('/') + query + hash;
		} else {
			if(code) {
				window.location.href = '/item/' + encodeURIComponent(code);
			} else {
				// This feels sooooo 2001
				window.history.back();
			}
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
				sel.collapse(div.childNodes[0], 1);
			}
		}
	}
}());
