(function() {
	"use strict";

	function itemButtonsListener(ev) {
		if(ev.target.nodeName !== "BUTTON") {
			return;
		}

		let code = ev.target.parentElement.dataset.forItem;
		let brand = ev.target.parentElement.dataset.forProductBrand;
		let model = ev.target.parentElement.dataset.forProductModel;
		let variant = ev.target.parentElement.dataset.forProductVariant;

		let here = window.location.pathname;
		let query = window.location.search;
		let hash = '#code-' + code;

		if(ev.target.classList.contains("edit")) {
			if(typeof code === "undefined") {
				window.location.href = '/product/' + encodeURIComponent(brand) + '/' + encodeURIComponent(model) + '/' + encodeURIComponent(variant) + '/edit' + query;
			} else {
				window.location.href = here + '/edit/' + encodeURIComponent(code) + query + hash;
			}
		} else if(ev.target.classList.contains("addinside")) {
			window.location.href = here + '/new/item/' + encodeURIComponent(code) + query + hash;
		} else if(ev.target.classList.contains("history")) {
			if(typeof code === "undefined") {
				window.location.href = '/product/' + encodeURIComponent(brand) + '/' + encodeURIComponent(model) + '/' + encodeURIComponent(variant) + '/history' + query;
			} else {
				window.location.href = '/item/' + encodeURIComponent(code) + '/history' + query;
			}
		} else if(ev.target.classList.contains("view")) {
			if(typeof code === "undefined") {
				window.location.href = '/product/' + encodeURIComponent(brand) + '/' + encodeURIComponent(model) + '/' + encodeURIComponent(variant) + query;
			} else {
				window.location.href = '/item/' + encodeURIComponent(code) + query;
			}
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
			window.location.href = '/new/item?copy=' + encodeURIComponent(code) + query + hash;
		} else if(ev.target.classList.contains("items")) {
			window.location.href = '/product/' + encodeURIComponent(brand) + '/' + encodeURIComponent(model) + '/' + encodeURIComponent(variant) + '/items' + query;
		}
	}

	let itemButtons = document.querySelectorAll('.item .itembuttons');
	for(let el of itemButtons) {
		el.addEventListener('click', itemButtonsListener);
	}

}());