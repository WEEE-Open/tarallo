(function() {
	"use strict";

	let nav = document.getElementById('main');
	let top = document.getElementById('top');
	let quickView = top.querySelector(".quick.view");
	let quickMove = top.querySelector(".quick.move");

	nav.addEventListener('click', ev => {
		// noinspection JSUnresolvedVariable
		if(!ev.target.classList || !ev.target.classList.contains('quick')) {
			return true;
		}
		// noinspection JSUnresolvedVariable
		let toggle = ev.target.dataset.toggle;

		let bar;
		if(toggle === 'move') {
			bar = quickMove;
		} else if(toggle === 'view') {
			bar = quickView;
		} else {
			return true;
		}

		if(bar.classList.contains("open")) {
			bar.classList.remove("open")
		} else {
			bar.classList.add("open");
		}
	});

	quickView.querySelector('button').addEventListener('click', function() {
		let code = quickView.querySelector('input').value;
		if(code !== "") {
			window.location.href = '/item/' + encodeURIComponent(code);
		}
	});

}());