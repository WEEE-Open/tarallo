(async function () {
	"use strict";

	// Beamed from the server to here in a giant JSON
	if (window.featureMaps === undefined) {
		throw new Error("features.js must be included before");
	}

	const {featureTypes, featureValues, featureValuesTranslated} = await window.featureMaps;

	let rowCounter = 0;
	let searchRows = document.getElementById("searchrows");

	// For comparison in feature selectors (used for searches only)
	const operatorsStandard = new Map([['=', '='], ['<>', '≠'], ['*', 'Any'], ['!', 'Not set']]);
	const operatorsOrdering = new Map([['>', '>'], ['>=', '≥'], ['<=', '≤'], ['<', '<']]);
	const operatorsPartial = new Map([['~', '≈'], ['!~', '≉']]);

	document.getElementById('searchbuttons').addEventListener('click', buttonsClick);
	let searchForm = document.getElementById('searchform');
	searchForm.addEventListener('submit', searchButtonClick);

	let searchButton = document.getElementById('searchbutton');
	let searchCodeButton = document.getElementById('search-control-code');
	let searchLocationButton = document.getElementById('search-control-location');

	const queryToRowType = new Map([["code", "search-template-code"], ["feature", "search-template-features"], ["c_feature", "search-template-ancestor"], ["location", "search-template-location"], ["sort", "search-template-sort"]]);

	let searchId = searchForm.dataset.searchId;
	let isRefine = !!searchId;
	console.log("id=", searchId);
	console.log("isRefine=", isRefine);

	let searchQuery = null;
	if (isRefine) {
		searchQuery = fetch(`/v2/search/query/${searchId}`, {
			headers: {
				'Accept': 'application/json',
				'Content-Type': 'application/json'
			},
			method: 'GET',
			credentials: 'include'
		}).then(r => {
			if (!r.ok) {
				throw new Error(`Couldn't fetch search query: HTTP ${r.status} ${r.statusText}`);
			}
			return r.json();
		}).then(q => {
			let searchQuery = {};
			for (let [key, value] of Object.entries(q)) {
				let rowType = queryToRowType.get(key);
				if (!rowType) {
					throw new Error(`Unknown query row type: ${key}`);
				}

				if (!Array.isArray(value)) {
					throw new Error(`Query contains non array valued key`);
				}

				if (key === "location" && value.length > 0) {
					addSearchRow(rowType, value);
				} else {
					for (const row of value) {
						addSearchRow(rowType, row);
					}
				}

				// Turn array into an object for easier access
				searchQuery[key] = new Map(value.map(o => [o.key, o.value]));
			}
			toggleSearchButton();

			return searchQuery;
		});
	}

	// Disable search button, since browser keep its state in memory even if the page is refreshed
	toggleSearchButton();

	function onLocationInput(e) {
		let value = e.detail.value;
		let t = e.detail.tagify;
		let url = t.DOM.originalInput.dataset.url;

		onLocationInput.abortController && onLocationInput.abortController.abort();
		onLocationInput.abortController = new AbortController();

		let signal = onLocationInput.abortController.signal;

		t.loading(true).dropdown.hide();
		fetch(`${url}?q=${value}`, { signal })
			.then(r => r.json())
			.then(r => {
				let m = r.map(e => ({value: e.name, color: e.color}));
				t.whitelist = [...m, ...t.value];
			})
			.finally(() => {
				setTimeout(() => {
					t.loading(false).dropdown.show(value);
				}, 50);
			});
	}

	function transformLocationTag(t) {
		if (t.color) {
			t.style = `border:1px solid ${t.color}; border-radius: 3px;`;
		}
		if (t.bgDark) {
			t.style = t.style ? t.style : "" + "--tag-bg:#DFDFAF;";
		}
	}

	function addSearchRow(rowType, preFill)
	{
		let template = document.getElementById(rowType).content;
		let frag = document.importNode(template, true);

		switch (rowType) {
			case "search-template-code":
				$(frag).find('.basicAutoComplete').autoComplete({minLength:3,resolverSettings:{requestThrottling:300}});
				break;
			case "search-template-location":
				let input = $(frag).find('input')[0];
				let tagify = new Tagify(input, {
					whitelist: preFill ? preFill.map(e => e.value) : [],
					dropdown: {
						highlightFirst: true,
					},
					enforceWhitelist: true,
					createInvalidTags: false,
					skipInvalid: true,
					transformTag: transformLocationTag,
					editTags: false
				});
				input.tagifyRef = tagify;

				tagify.on('input', debounce(onLocationInput, 200));
				break;
		}

		// Set new ids
		let id = rowCounter++;
		let node = frag.getElementById("search-row-container-new");
		node.id = `search-row-container-${id}`;

		let replace = node.querySelectorAll('[for="search-row-new"], [id="search-row-new"]');
		let rowElCounter = 1;
		for (let el of replace) {
			if (el.getAttribute("for") === "search-row-new") {
				el.setAttribute("for", `search-row-${id}-${rowElCounter}`);
			}
			if (el.getAttribute("id") === "search-row-new") {
				el.setAttribute("id", `search-row-${id}-${rowElCounter}`);
				rowElCounter++;
			}

			// This is to prevent from hitting enter and deleting the field instead of actually running the search
			el.addEventListener('keydown', ev => {
				if (ev.key === "Enter") {
					ev.preventDefault();
				}
			});
		}

		// Add features list to dropdown, if present
		let features = node.querySelector('.allfeatures');
		if (features) {
			features.appendChild(document.importNode(document.getElementById('features-select-template').content, true));
			let comparison = node.querySelector('.comparison');
			if (comparison) {
				features.addEventListener('change', (ev) => { updateSearchRowFromFeature(node, ev.target) })
				comparison.addEventListener('change', (ev) => {
					updateSearchRowFromComparison(node, ev.target, features)
				})
				updateSearchRowFromFeature(node, features);
			}
		}

		// Listen to the delete button
		node.querySelector('button.delete')
			.addEventListener('click', () => {
				node.remove();
				toggleSearchButton();
			});

		// Enable tooltips
		tippy('[data-tippy-content]');

		// Prefill fields if needed
		if (preFill) {
			if (preFill.key !== undefined) {
				node.dataset.typeId = preFill.key;
			}

			switch (rowType) {
				case "search-template-code":
					$(node).find('input').val(preFill.value);
					break;
				case "search-template-features": {
					let features = $(node).find('select.allfeatures')[0];
					features.value = preFill.value[0];
					updateSearchRowFromFeature(node, features);

					let comparison = $(node).find('select.comparison')[0];
					comparison.value = preFill.value[1];
					updateSearchRowFromComparison(node, comparison, features);
					$(node).find('input').val(preFill.value[2]);
					break;
				}
				case "search-template-ancestor": {
					let features = $(node).find('select.allfeatures')[0];
					features.value = preFill.value[0];
					updateSearchRowFromFeature(node, features);

					let comparison = $(node).find('select.comparison')[0];
					comparison.value = preFill.value[1];
					updateSearchRowFromComparison(node, comparison, features);
					$(node).find('input').val(preFill.value[2]);
					break;
				}
				case "search-template-location":
					$(node).find('input')[0].tagifyRef.addTags(preFill.map(l => ({value: l.value, key: l.key, bgDark: "red"})));
					break;
				case "search-template-sort":
					$(node).find('select.allfeatures').val(preFill.value.feature);
					$(node).find('select.sorting').val(preFill.value.direction);
					break;
				default:
					throw new Error(`Unknown rowType: ${rowType}`);
			}
		}

		searchRows.appendChild(node);
	}

	/**
	 * Handle clicking the "Add..." search buttons
	 *
	 * @param ev Event
	 */
	function buttonsClick(ev)
	{
		if (ev.target.tagName !== 'BUTTON') {
			return;
		}

		// Create search row from template
		addSearchRow(ev.target.dataset.template);

		// Enable search button
		toggleSearchButton();
	}

	function toggleSearchButton()
	{
		searchButton.disabled = searchRows.childElementCount <= 0;
		let codes = 0, locations = 0;
		for (let el of searchRows.children) {
			if (el.classList.contains("search-code")) {
				codes++;
			}
			if (el.classList.contains("search-location")) {
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
	function updateSearchRowFromFeature(rowContainer, features = null)
	{
		if (!features) {
			features = rowContainer.querySelector('.allfeatures');
		}

		let comparisonElement = rowContainer.querySelector('.comparison');
		let type = featureTypes.get(features.value);

		if (comparisonElement.dataset.type !== type) {
			while (comparisonElement.lastChild) {
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

	function defaultOption()
	{
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
	function updateSearchRowFromComparison(rowContainer, comparisonElement = null, features = null)
	{
		if (!features) {
			features = rowContainer.querySelector('.allfeatures');
		}

		if (!comparisonElement) {
			comparisonElement = rowContainer.querySelector('.comparison');
		}

		let comparisonValue = rowContainer.querySelector('.comparisonvalue');

		while (comparisonValue.lastChild) {
			comparisonValue.removeChild(comparisonValue.lastChild);
		}

		let compare = comparisonElement.value;
		if (compare === '*' || compare === '!') {
			let blank = document.createElement('input');
			blank.classList.add('form-control');
			blank.setAttribute('aria-label', "Value");
			blank.type = 'text';
			blank.disabled = true;
			comparisonValue.appendChild(blank);
		} else {
			let type = featureTypes.get(features.value);
			let name = features.value;
			if (type === 'e') {
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
	function optionsFromOperators(operarators, select)
	{
		for (let [operator, printable] of operarators) {
			let option = document.createElement('option');
			option.value = operator;
			option.textContent = printable;
			select.appendChild(option);
		}
	}

	function getDiff(prev)
	{
		let code = [], feature = [], c_feature = [], location = [], sort = [];
		let rows = searchRows.querySelectorAll('.searchrow');
		for (let row of rows) {
			let key = row.dataset.typeId ? parseInt(row.dataset.typeId) : null;
			if (row.classList.contains('search-code')) {
				console.log(typeof key);
				code.push({key, value: row.querySelector('.comparisonvalue').value});
			} else if (row.classList.contains('search-features')) {
				feature.push({key, value: getSelectedFeatures(row)});
			} else if (row.classList.contains('search-ancestor')) {
				c_feature.push({key, value: getSelectedFeatures(row)});
			} else if (row.classList.contains('search-location')) {
				let locs = JSON.parse(row.querySelector('input.comparisonvalue').value);
				location = location.concat(locs.map(e => { return {key: e.key !== undefined ? e.key : null, value: e.value}; }));
			} else if (row.classList.contains('search-sort')) {
				sort.push({key, value: {feature: row.querySelector('.allfeatures').value, direction: row.querySelector('.sorting').value}});
			}
		}

		let diff = {code, feature, c_feature, location, sort};

		for (const [type, filters] of Object.entries(prev)) {
			for (const [key, value] of filters.entries()) {
				let idx = diff[type].findIndex(e => e.key === key);
				if (idx === -1) {
					diff[type].push({key, value: null});
				} else if (diff[type][idx].value === value) {
					diff[type].splice(idx, 1);
				}
			}
		}

		console.log(diff);
		return diff;
	}

	async function searchButtonClick(ev)
	{
		// The answers that did it: https://stackoverflow.com/a/39470019
		// Thanks vzr, you're the only one that has figured this out in the entire universe
		if (ev.target.checkValidity()) {
			ev.preventDefault();
		} else {
			return;
		}

		const query = getDiff(isRefine ? await searchQuery : {});

		let error = document.getElementById('search-error');
		let tip = document.getElementById('search-tip');
		error.classList.add('d-none');
		if (tip) {
			tip.classList.add('d-none');
		}


		let uri, method;
		if (!isRefine) {
			uri = '/v2/search';
			method = 'POST';
		} else {
			uri = '/v2/search/' + searchId;
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

			if (response.status === 204) {
				goTo(searchId);
			}

			let result = await response.json();
			if (response.ok) {
				goTo(result);
			} else if (response.status === 401) {
				error.textContent = 'Session expired or logged out. Open another tab, log in and try again.';
				error.classList.remove('d-none');
			} else {
				if ('message' in result) {
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

	function goTo(code = null, page = null)
	{
		let query = window.location.search;
		let hash = window.location.hash;

		let idFragment = code === null ? '' : '/' + code;
		let pageFragment = page === null ? '' : '/page/' + page;

		window.location.href = '/search/advanced' + idFragment + pageFragment + query + hash;
	}

	function createFeatureValueSelector(type, name)
	{
		let valueElement;
		switch (type) {
			case 'e':
				let options = featureValues.get(name);
				let optionsTranslated = featureValuesTranslated.get(name);
				let optionsArray = [];
				for (let i = 0; i < options.length; i++) {
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

				for (let option of optionsArray) {
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
	function numberChanged(ev)
	{
		let unit;
		if (ev.target.dataset.unit) {
			unit = ev.target.dataset.unit;
		} else {
			// Extreme caching techniques
			unit = window.unitNameToType(ev.target.dataset.internalName);
			ev.target.dataset.unit = unit;
		}
		try {
			let newValue = window.unitPrintableToValue(unit, ev.target.value);
			if (ev.target.dataset.internalType === 'i' && (newValue % 1 !== 0)) {
				// noinspection ExceptionCaughtLocallyJS
				throw new Error("Value must represent an integer");
			}
			// Store new value
			ev.target.dataset.internalValue = newValue.toString();
			// Print it
			ev.target.value = window.unitValueToPrintable(unit, newValue);
		} catch (e) {
			// Rollback
			if (ev.target.dataset.internalValue === '') {
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

	function getSelectedFeatures(row)
	{
		let name = row.querySelector('.allfeatures').value;
		let comparison = row.querySelector('.comparison').value;

		let value;
		let element = row.querySelector('.comparisonvalue').firstElementChild;
		if (element.disabled) {
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
