(function() {
	"use strict";

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

		document.getElementById(id).classList.toggle('disabled');
	}

	function addFeatureClick(select, featuresElement) {
		let name = select.value;
		let translatedName = select.options[select.selectedIndex].textContent;
		let pseudoId = 'feature-edit-' + name;

		let duplicates = featuresElement.getElementsByClassName(pseudoId);
		if(duplicates.length > 0) {
			duplicates[0].querySelector('.value').focus();
			return;
		}

		let newElement = newFeature(name, translatedName, pseudoId, null, true);
		featuresElement.appendChild(newElement);
		// TODO: replace with 'input, .value' once I figure out how to move the cursor to the right (= get this code working: https://stackoverflow.com/a/3866442)
		let input = newElement.querySelector('input');
		if(input) {
			input.focus();
		}
	}

	async function searchButtonClick(ev) {
		let id = null;
		if(ev.target.dataset.searchId) {
			id = ev.target.dataset.searchId;
		}

		let query = {};

		if(!document.getElementById('search-control-code').classList.contains('disabled')) {
			query.code = document.getElementById('search-control-code-input').value;
			if(query.code === '') {
				delete query.code;
			}
		}
		// TODO: support multiple locations?
		if(!document.getElementById('search-control-location').classList.contains('disabled')) {
			let location = document.getElementById('search-control-location-input').value;
			if(location !== '') {
				query.locations = [location];
			}
		}
		if(!document.getElementById('search-control-features').classList.contains('disabled')) {
			query.features = getSelectedFeatures('search-control-features');
			if(query.features.length <= 0) {
				delete query.features;
			}
		}
		if(!document.getElementById('search-control-ancestor').classList.contains('disabled')) {
			query.ancestor = getSelectedFeatures('search-control-ancestor');
			if(query.ancestor.length <= 0) {
				delete query.ancestor;
			}
		}
		if(!document.getElementById('search-control-sort').classList.contains('disabled')) {
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

		window.location.href = '/search' + idFragment + query + hash;
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
