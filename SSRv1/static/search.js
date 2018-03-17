(function() {
	"use strict";

	document.getElementById('searchbuttons').addEventListener('click', buttonsClick);

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
}());
