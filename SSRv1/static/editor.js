"use strict";

(function() {
	document.execCommand("defaultParagraphSeparator", false, "p");

	let deletedFeatures = new Set();

	let divs = document.querySelectorAll('.features.own.editing [contenteditable]');
	let selects = document.querySelectorAll('.features.own.editing select');
	let deletes = document.querySelectorAll('.features.own.editing .delete');

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
	 * Remove last error message (or any element, really)
	 *
	 * @param {HTMLElement|null} element to be removed, or null to remove last error message
	 */
	function removeError(element = null) {
		if(element === null) {
			let last = document.getElementById('feature-edit-last-error');
			if(last !== null) {
				last.parentElement.removeChild(last);
			}
		} else {
			element.parentElement.removeChild(element);
		}
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
		if(!ev.target.dataset.initialValue) {
			ev.target.classList.add('changed');
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
		console.log(ev.target.value);
		console.log(ev.target.dataset.initialValue);
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
			if(ev.target.dataset.internalType = 'i' && (newValue % 1 !== 0)) {
				// noinspection ExceptionCaughtLocallyJS
				throw new Error("fractional-not-allowed");
			}
			// Store new value
			ev.target.dataset.internalValue = newValue.toString();
			// Print it
			let lines = ev.target.getElementsByTagName('P');
			lines[0].textContent = valueToPrintable(unit, newValue);
			while(lines.length > 1) {
				let last = lines[lines.length - 1];
				last.parentElement.removeChild(last);
			}
			// Save if for later
			ev.target.dataset.previousValue = newValue.toString();
		} catch(e) {
			// rollback
			ev.target.dataset.internalValue = ev.target.dataset.previousValue;
			ev.target.getElementsByTagName('P')[0].textContent = valueToPrintable(unit, parseInt(ev.target.dataset.previousValue));
			// Display error message
			let templateThingThatShouldExist = document.getElementById('feature-edit-template-' + e.message);
			if(templateThingThatShouldExist === null) {
				// Unhandled exception!
				throw e;
			}
			let template = document.importNode(templateThingThatShouldExist.content, true);
			removeError(null);
			let item = document.querySelector('.item.editing');
			item.insertBefore(template, item.getElementsByTagName('HEADER')[0].nextElementSibling);
			// "template" is a document fragment, there's no way to get the element itself
			let message = document.querySelector('.item.editing .error.message');
			message.id = 'feature-edit-last-error';
			message.getElementsByTagName('BUTTON')[0].addEventListener('click', removeError.bind(null, message));
		}
		if(ev.target.dataset.internalValue === ev.target.dataset.initialValue) {
			ev.target.classList.remove('changed');
		} else {
			ev.target.classList.add('changed');
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
				return '' + value + ' ' + prefixToPrintable(prefix) + i +'B';
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
	 * @param int - 0 to 4
	 * @return {string}
	 */
	function prefixToPrintable(int) {
		switch(int) {
			case 0:
				return '';
			case 1:
				if(this.type === 'byte') {
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
			if(isNaN(number) || number <= 0) {
				throw new Error("negative-input")
			} else {
				return number;
			}
		}
		let i;
		for(i = 0; i < string.length; i++) {
			if (!((string[i] >= '0' && string[i] <= '9') || string[i] === '.' || string[i] === ',')) {
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
