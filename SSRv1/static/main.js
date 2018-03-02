(function() {
	"use strict";

	let nav = document.getElementById('main');
	let top = document.getElementById('top');

	nav.addEventListener('click', function(ev) {
		// noinspection JSUnresolvedVariable
		if(!ev.target.classList || !ev.target.classList.contains('quick')) {
			return true;
		}
		// noinspection JSUnresolvedVariable
		let toggle = ev.target.dataset.toggle;

		let bar = top.querySelector(".quick." + toggle);

		if(bar.classList.contains("open")) {
			bar.classList.remove("open")
		} else {
			bar.classList.add("open");
		}
	});

}());