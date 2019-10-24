(function() {
	"use strict";

	function itemButtonsListener(ev) {
		if(ev.target.nodeName !== "BUTTON") {
			return;
		}

		let code = ev.target.parentElement.dataset.forItem;
		let here = window.location.pathname;
		let query = window.location.search;
		let hash = '#code-' + code;

		if(ev.target.classList.contains("edit")) {
			window.location.href = here + '/edit/' + encodeURIComponent(code) + query + hash;
		} else if(ev.target.classList.contains("addinside")) {
			window.location.href = here + '/add/' + encodeURIComponent(code) + query + hash;
		} else if(ev.target.classList.contains("history")) {
			window.location.href = '/history/' + encodeURIComponent(code) + query + hash;
		} else if(ev.target.classList.contains("view")) {
			window.location.href = '/item/' + encodeURIComponent(code) + query + hash;
		} else if(ev.target.classList.contains("move")) {
			let top = document.getElementById('top');
			let quickMoveButton = top.querySelector(".quick.move");
			let quickMoveBar = top.querySelector(".quick.move.bar");
			if(!quickMoveButton.classList.contains("selected")) {
				quickMoveButton.classList.add("selected");
				quickMoveBar.classList.add("open");
			}
			let from = quickMoveBar.querySelector('input.from');
			from.value = code;
			let inputLen = from.value.length;
			from.setSelectionRange(inputLen, inputLen);

			let to = quickMoveBar.querySelector('input.to');
			to.focus();
		} else if(ev.target.classList.contains("clone")) {
			window.location.href = '/add?copy=' + encodeURIComponent(code) + query + hash;
		}
	}

	let itemButtons = document.querySelectorAll('.item .itembuttons');
	for(let el of itemButtons) {
		el.addEventListener('click', itemButtonsListener);
	}

}());