(async function() {
	"use strict";

	let rowCounter = 0;
	let searchRows = document.getElementById("searchrows");

	// For comparison in feature selectors (used for searches only)
	const operatorsStandard = new Map([['=', '='], ['<>', '≠'], ['*', 'Any'], ['!', 'Not set']]);
	const operatorsOrdering = new Map([['>', '>'], ['>=', '≥'], ['<=', '≤'], ['<', '<']]);
	const operatorsPartial = new Map([['~', '≈'], ['!~', '≉']]);

	document.getElementById('searchbuttons').addEventListener('click', buttonsClick);
	document.getElementById('searchform').addEventListener('submit', searchButtonClick);
	let searchButton = document.getElementById('searchbutton');
	let searchCodeButton = document.getElementById('search-control-code');
	let searchLocationButton = document.getElementById('search-control-location');

	// Disable search button, since browser keep its state in memory even if the page is refreshed
	toggleSearchButton();

	/**
	 * Handle clicking the "Add..." search buttons
	 *
	 * @param ev Event
	 */
	function buttonsClick(ev) {
		if(ev.target.tagName !== 'BUTTON') {
			return;
		}

		// Create search row from template
		let templateName = ev.target.dataset.template;
		let template = document.importNode(document.getElementById(templateName).content, true);

		searchRows.appendChild(template);
		rowCounter++;

		// Set new ids
		let inserted = searchRows.querySelector("#search-row-container-new");
		inserted.id = "search-row-container-" + rowCounter;
		let replace = inserted.querySelectorAll('[for="search-row-new"], [id="search-row-new"]');
		let rowElCounter = 1;
		for(let el of replace) {
			if(typeof el.attributes["for"] !== "undefined" && el.attributes["for"].nodeValue === "search-row-new") {
				el.attributes["for"].nodeValue = "search-row-" + rowCounter + "-" + rowElCounter;
			}
			if(typeof el.attributes["id"] !== "undefined" && el.attributes["id"].nodeValue === "search-row-new") {
				el.attributes["id"].nodeValue = "search-row-" + rowCounter + "-" + rowElCounter++;
			}
		}

		// Add features list to dropdown, if present
		let features = inserted.querySelector('.allfeatures');
		if(features) {
			features.appendChild(document.importNode(document.getElementById('features-select-template').content, true));
			let comparison = inserted.querySelector('.comparison');
			if(comparison) {
				features.addEventListener('change', (ev) => {updateSearchRowFromFeature(ev.target.closest('.searchrow'), ev.target)})
				comparison.addEventListener('change', (ev) => {
					updateSearchRowFromComparison(ev.target.closest('.searchrow'), ev.target, features)
				})
				updateSearchRowFromFeature(inserted, features);
			}
		}
		// Listen to the delete button
		inserted.querySelector('button.delete').addEventListener('click', (ev) => { inserted.remove(); toggleSearchButton(); });

		// Enable tooltips
		tippy('[data-tippy-content]');

		// Enable search button
		toggleSearchButton();
	}

	function toggleSearchButton() {
		searchButton.disabled = searchRows.childElementCount <= 0;
		let codes = 0, locations = 0;
		for(let el of searchRows.children) {
			if(el.classList.contains("search-code")) {
				codes++;
			}
			if(el.classList.contains("search-location")) {
				locations++;
			}
		}

		searchCodeButton.disabled = codes > 0;
		searchLocationButton.disabled = locations > 0;
	}

	/**
	 * Update selectors and inputs when the selected feature changes
	 *
	 * @param {HTMLElement} rowContainer The row
	 * @param {HTMLSelectElement|null} features Optional, there's only one in each rowContainer anyway
	 */
	function updateSearchRowFromFeature(rowContainer, features = null) {
		if(!features) {
			features = rowContainer.querySelector('.allfeatures');
		}

		let comparisonElement = rowContainer.querySelector('.comparison');
		let type = window.featureTypes.get(features.value);

		if(comparisonElement.dataset.type !== type) {
			while(comparisonElement.lastChild) {
				comparisonElement.removeChild(comparisonElement.lastChild);
			}

			if (type === 'i' || type === 'd') {
				optionsFromOperators(operatorsOrdering, comparisonElement);
			} else if (type === 's') {
				optionsFromOperators(operatorsPartial, comparisonElement);
			}
			optionsFromOperators(operatorsStandard, comparisonElement);
			comparisonElement.dataset.type = type;
		}

		updateSearchRowFromComparison(rowContainer, comparisonElement, features)
	}

	function defaultOption() {
		let defaultOption = document.createElement('option');
		defaultOption.value = "";
		defaultOption.disabled = true;
		defaultOption.selected = true;
		defaultOption.hidden = true;
		return defaultOption;
	}

	/**
	 * Update selectors and inputs when the selected comparison changes
	 *
	 * @param {HTMLElement} rowContainer The row
	 * @param {HTMLSelectElement|null} comparisonElement Optional, there's only one in each rowContainer anyway
	 * @param {HTMLSelectElement|null} features Optional, there's only one in each rowContainer anyway
	 */
	function updateSearchRowFromComparison(rowContainer, comparisonElement = null, features = null) {
		if(!features) {
			features = rowContainer.querySelector('.allfeatures');
		}

		if(!comparisonElement) {
			comparisonElement = rowContainer.querySelector('.comparison');
		}

		let comparisonValue = rowContainer.querySelector('.comparisonvalue');

		while(comparisonValue.lastChild) {
			comparisonValue.removeChild(comparisonValue.lastChild);
		}

		let compare = comparisonElement.value;
		if(compare === '*' || compare === '!') {
			let blank = document.createElement('input');
			blank.classList.add('form-control');
			blank.setAttribute('aria-label', "Value");
			blank.type = 'text';
			blank.disabled = true;
			comparisonValue.appendChild(blank);
		} else {
			let type = window.featureTypes.get(features.value);
			let name = features.value;
			if(type === 'e') {
				let select = createFeatureValueSelector(type, name);
				comparisonValue.appendChild(select);
			} else {
				let input = createFeatureValueSelector(type, name);
				comparisonValue.appendChild(input);
			}
		}
	}

	/**
	 * Get options from operators
	 *
	 * @param {Map<string,string>} operarators
	 * @param {HTMLSelectElement|HTMLElement} select - It's a select but PHPStorm doesn't seem to understand that
	 */
	function optionsFromOperators(operarators, select) {
		for(let [operator, printable] of operarators) {
			let option = document.createElement('option');
			option.value = operator;
			option.textContent = printable;
			select.appendChild(option);
		}
	}

	async function searchButtonClick(ev) {
		// The answers that did it: https://stackoverflow.com/a/39470019
		// Thanks vzr, you're the only one that has figured this out in the entire universe
		if(ev.target.checkValidity()) {
			ev.preventDefault();
		} else {
			return;
		}

		let error = document.getElementById('search-error');
		let tip = document.getElementById('search-tip');
		error.classList.add('d-none');
		if(tip) tip.classList.add('d-none');

		let id = null;
		if(ev.target.dataset.searchId) {
			id = ev.target.dataset.searchId;
		}

		let query = {};
		// query.code = [];
		query.locations = [];
		query.features = [];
		query.ancestor = [];
		query.sort = {};

		let rows = searchRows.querySelectorAll('.searchrow');
		for(let row of rows) {
			if(row.classList.contains('search-code')) {
				query.code = row.querySelector('.comparisonvalue').value;
			} else if(row.classList.contains('search-location')) {
				query.locations.push(row.querySelector('.comparisonvalue').value);
			} else if(row.classList.contains('search-features')) {
				query.features.push(getSelectedFeatures(row));
			} else if(row.classList.contains('search-ancestor')) {
				query.ancestor.push(getSelectedFeatures(row));
			} else if(row.classList.contains('search-sort')) {
				query.sort[row.querySelector('.allfeatures').value] = row.querySelector('.sorting').value
			}
		}

		// if(query.code.length <= 0) {
		// 	delete query.code;
		// }
		if(query.locations.length <= 0) {
			delete query.locations;
		}
		if(query.features.length <= 0) {
			delete query.features;
		}
		if(query.ancestor.length <= 0) {
			delete query.ancestor;
		}
		if(query.sort.length <= 0) {
			delete query.sort;
		}

		let uri, method;

		if(id === null) {
			uri = '/v2/search';
			method = 'POST';
		} else {
			uri = '/v2/search/' + id;
			method = 'PATCH';
		}

		console.log(JSON.stringify(query));

		let oldbeforeunload = window.onbeforeunload;
		window.onbeforeunload = undefined;
		searchButton.disabled = true;

		try {
			let response = await fetch(uri, {
				headers: {
					'Accept': 'application/json',
					'Content-Type': 'application/json'
				},
				method: method,
				credentials: 'include',
				body: JSON.stringify(query)
			});

			let result = await response.json();
			if(response.ok) {
				goTo(result);
			} else if(response.status === 401) {
				error.textContent = 'Session expired or logged out. Open another tab, log in and try again.';
				error.classList.remove('d-none');
			} else {
				if('message' in result) {
					error.textContent = result['message'];
				} else {
					error.textContent = 'Error';
				}
				error.classList.remove('d-none');
				console.log(result);
			}
		} finally {
			searchButton.disabled = false;
			window.onbeforeunload = oldbeforeunload;
		}
	}

	function goTo(code = null, page = null) {
		let query = window.location.search;
		let hash = window.location.hash;

		let idFragment = code === null ? '' : '/' + code;
		let pageFragment = page === null ? '' : '/page/' + page;

		window.location.href = '/search' + idFragment + pageFragment + query + hash;
	}

	function createFeatureValueSelector(type, name) {
		let valueElement;
		switch(type) {
			case 'e':
				let options = window.featureValues.get(name);
				let optionsTranslated = window.featureValuesTranslated.get(name);
				let optionsArray = [];
				for(let i = 0; i < options.length; i++) {
					let option = document.createElement('option');
					option.value = options[i];
					option.textContent = optionsTranslated[i];
					optionsArray.push(option);
				}
				optionsArray.sort((a, b) => a.textContent.localeCompare(b.textContent, 'en'));

				valueElement = document.createElement('select');
				valueElement.classList.add('form-control');
				valueElement.setAttribute('aria-label', "Value");
				valueElement.appendChild(defaultOption());
				valueElement.required = true;

				for(let option of optionsArray) {
					valueElement.appendChild(option);
				}
				break;
			case 'i':
			case 'd':
				valueElement = document.createElement('input');
				valueElement.dataset.internalValue = '';
				valueElement.classList.add('form-control');
				valueElement.type = 'text';
				valueElement.required = true;
				valueElement.setAttribute('aria-label', "Value");
				valueElement.addEventListener('blur', numberChanged);

				break;
			case 's':
			default:
				valueElement = document.createElement('input');
				valueElement.classList.add('form-control');
				valueElement.type = 'text';
				valueElement.required = true;
				valueElement.setAttribute('aria-label', "Value");
				break;
		}

		valueElement.dataset.internalType = type;
		valueElement.dataset.internalName = name;
		valueElement.classList.add("value");
		valueElement.classList.add("changed");

		return valueElement;
	}

	/**
	 * Handle changing content of an editable div containing numbers
	 *
	 * @param ev Event
	 */
	function numberChanged(ev) {
		let unit;
		if(ev.target.dataset.unit) {
			unit = ev.target.dataset.unit;
		} else {
			// Extreme caching techniques
			unit = window.unitNameToType(ev.target.dataset.internalName);
			ev.target.dataset.unit = unit;
		}
		try {
			let newValue = window.unitPrintableToValue(unit, ev.target.value);
			if(ev.target.dataset.internalType === 'i' && (newValue % 1 !== 0)) {
				// noinspection ExceptionCaughtLocallyJS
				throw new Error("Value must represent an integer");
			}
			// Store new value
			ev.target.dataset.internalValue = newValue.toString();
			// Print it
			ev.target.value = window.unitValueToPrintable(unit, newValue);
		} catch(e) {
			// Rollback
			if(ev.target.dataset.internalValue === '') {
				ev.target.value = '';
			} else {
				ev.target.value = window.unitValueToPrintable(unit, parseInt(ev.target.dataset.internalValue));
			}
			// Display error message
			tippy(ev.target, {
				content: e.message,
				showOnCreate: true,
				onHidden(instance) { instance.destroy() },
			});
		}
	}

	function getSelectedFeatures(row) {
		let name = row.querySelector('.allfeatures').value;
		let comparison = row.querySelector('.comparison').value;

		let value;
		let element = row.querySelector('.comparisonvalue').firstElementChild;
		if(element.disabled) {
			value = null;
		} else {
			switch (element.dataset.internalType) {
				case 'e':
				case 's':
				default:
					value = element.value;
					break;
				case 'i':
				case 'd':
					value = element.dataset.internalValue;
					break;
			}
		}

		return [name, comparison, value];
	}

}());
