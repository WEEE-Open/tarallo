(async function() {
	"use strict";

	// For comparison in feature selectors (used for searches only)
	const operatorsStandard = new Map([['=', '='], ['<>', '≠']]);
	const operatorsOrdering = new Map([['>', '>'], ['>=', '≥'], ['<=', '≤'], ['<', '<']]);
	const operatorsPartial = new Map([['~', '≈'], ['!~', '≉']]);

	let searchButton = document.getElementById('searchbutton');
	document.getElementById('searchbuttons').addEventListener('click', buttonsClick);
	searchButton.addEventListener('click', searchButtonClick);

	for(let id of ['search-control-features', 'search-control-ancestor']) {
		let controls = document.getElementById(id);
		// noinspection JSUnresolvedFunction
		controls.querySelector('.selector button').addEventListener('click', addFeatureClick.bind(null, controls.querySelector('.selector select'), controls.querySelector('.own.features ul')));
	}

	/**
	 * Handle clicking the search buttons area
	 *
	 * @param ev Event
	 */
	function buttonsClick(ev) {
		if(ev.target.tagName !== 'BUTTON') {
			return;
		}

		let id = ev.target.dataset.for;

		document.getElementById(id).classList.toggle('hidden');
	}

	function addFeatureClick(select, featuresElement, deletedFeatures = null) {
		let name = select.value;
		addFeature(featuresElement, name, deletedFeatures);
	}

	/**
	 * Add a feature, or focus it if it already exists.
	 *
	 * @param {HTMLElement} featuresElement - The "own features" element
	 * @param {string} name - Feature name
	 * @param {Set<string>|null} deletedFeatures - Deleted features set, can be null if not tracked
	 */
	function addFeature(featuresElement, name, deletedFeatures = null) {
		let pseudoId = 'feature-edit-' + name;

		let duplicates = featuresElement.getElementsByClassName(pseudoId);
		if(duplicates.length > 0) {
			// There should be only one, hopefully
			focusFeatureValueInput(duplicates[0]);
			return;
		}

		let newElement = newFeature(name, pseudoId, null, getComparison);
		featuresElement.appendChild(newElement);

		focusFeatureValueInput(newElement);
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

	/**
	 * Get comparison dropdown menu, for a specific feature
	 *
	 * @param {string} type - Feature type (s, i, d, e)
	 *
	 * @return {HTMLElement}
	 */
	function getComparison(type) {
		let wrappingDiv = document.createElement('div');
		wrappingDiv.classList.add("comparison");

		let pointlessLabel = document.createElement('label');
		wrappingDiv.appendChild(pointlessLabel);

		let comparisonElement = document.createElement('select');
		if(type === 'i' || type === 'd') {
			optionsFromOperators(operatorsOrdering, comparisonElement);
		} else if(type === 's') {
			optionsFromOperators(operatorsPartial, comparisonElement);
		}
		optionsFromOperators(operatorsStandard, comparisonElement);

		pointlessLabel.appendChild(comparisonElement);

		return wrappingDiv;
	}

	async function searchButtonClick(ev) {
		let id = null;
		if(ev.target.dataset.searchId) {
			id = ev.target.dataset.searchId;
		}

		let query = {};

		if(!document.getElementById('search-control-code').classList.contains('hidden')) {
			query.code = document.getElementById('search-control-code-input').value;
			if(query.code === '') {
				delete query.code;
			}
		}
		// TODO: support multiple locations?
		if(!document.getElementById('search-control-location').classList.contains('hidden')) {
			let location = document.getElementById('search-control-location-input').value;
			if(location !== '') {
				query.locations = [location];
			}
		}
		if(!document.getElementById('search-control-features').classList.contains('hidden')) {
			query.features = getSelectedFeatures('search-control-features');
			if(query.features.length <= 0) {
				delete query.features;
			}
		}
		if(!document.getElementById('search-control-ancestor').classList.contains('hidden')) {
			query.ancestor = getSelectedFeatures('search-control-ancestor');
			if(query.ancestor.length <= 0) {
				delete query.ancestor;
			}
		}
		if(!document.getElementById('search-control-sort').classList.contains('hidden')) {
			let orderby = document.getElementById('search-control-sort-input').value;
			let direction = document.getElementById('search-control-sort-direction-input').value;
			query.sort = {};
			query.sort[orderby] = direction;
		}

		let uri, method;

		if(id === null) {
			uri = '/v1/search';
			method = 'POST';
		} else {
			uri = '/v1/search/' + id;
			method = 'PATCH';
		}

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
			if(result.status === 'success') {
				let id = result.data;
				goTo(id);
			} else {
				console.log(result);
			}
		} finally {
			searchButton.disabled = false;
		}
	}

	function goTo(code = null, page = null) {
		let query = window.location.search;
		let hash = window.location.hash;

		let idFragment = code === null ? '' : '/' + code;
		let pageFragment = page === null ? '' : '/page/' + page;

		window.location.href = '/search' + idFragment + pageFragment + query + hash;
	}

	function getSelectedFeatures(id) {
		let result = [];
		let featuresElements = document.getElementById(id).querySelectorAll('.features li');
		for(let li of featuresElements) {
			let value;
			let element = li.getElementsByClassName('value')[0];
			let name = element.dataset.internalName;
			switch(element.dataset.internalType) {
				case 'e':
					value = element.value;
					break;
				case 'i':
				case 'd':
					value = element.dataset.internalValue;
					break;
				case 's':
				default:
					let paragraphs = element.getElementsByTagName('DIV');
					let lines = [];
					for(let paragraph of paragraphs) {
						lines.push(paragraph.textContent);
					}
					value = lines.join('\n');
			}

			let comparison = li.querySelector('.comparison select').value;

			result.push([name, comparison, value]);
		}
		return result;
	}
}());
