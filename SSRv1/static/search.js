(function() {
	"use strict";

	document.getElementById('searchbuttons').addEventListener('click', buttonsClick);
	document.getElementById('searchbutton').addEventListener('click', searchButtonClick);

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

	function searchButtonClick(ev) {
		let id = null;
		if(ev.target.dataset.searchId) {
			id = ev.target.dataset.searchId;
		}


	}
}());
