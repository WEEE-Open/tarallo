(function() {
	"use strict";

	let quickMoveButton = document.getElementById('quickmovebutton');
	let quickMove = document.getElementById('quickmove');
	let nav = document.getElementById('menu');
	let quickMoveCode = document.getElementById('quickmovecode');
	let quickMoveLocation = document.getElementById('quickmovelocation');
	let quickMoveDo = quickMove.getElementsByClassName('do')[0];
	let quickMoveSwap = quickMove.getElementsByClassName('swap')[0];

	for(let message of quickMove.getElementsByClassName('message')) {
		message.classList.add('d-none');
	}

	let itembuttonMoves = document.querySelectorAll('.itembuttons .move');
	for(let el of itembuttonMoves) {
		el.addEventListener('click', moveButtonInItemListener);
	}

	function focusQuickMove() {
		let len = quickMoveCode.value.length;
		if (len <= 0) {
			quickMoveCode.focus();
		} else {
			quickMoveLocation.focus();
			len = quickMoveLocation.value.length;
			if (len > 0) {
				quickMoveLocation.setSelectionRange(len, len);
			}
		}
	}

	quickMoveButton.addEventListener('click', ev => {
		if(quickMoveButton.classList.contains("active")) {
			quickMoveButton.classList.remove("active");
			quickMove.classList.add("d-none");
			nav.classList.remove("mb-0");
		} else {
			quickMoveButton.classList.add("active");
			quickMove.classList.remove("d-none");
			nav.classList.add("mb-0");

			focusQuickMove();
		}
	});

	quickMoveSwap.addEventListener('click', function(e) {
		e.preventDefault();
		e.stopPropagation();
		let temp = quickMoveCode.value;
		quickMoveCode.value = quickMoveLocation.value;
		quickMoveLocation.value = temp;
	});

	quickMoveDo.addEventListener('click', async (e) => {
		e.preventDefault();
		e.stopPropagation();

		let form = quickMove.querySelector('form');
		//form.classList.remove('was-validated');
		quickMoveCode.setCustomValidity('');
		quickMoveLocation.setCustomValidity('');

		// Checks that inputs are not empty
		if(!form.checkValidity()) {
			//form.classList.add('was-validated');
			return false;
		}

		let code = quickMoveCode.value;
		let parent = quickMoveLocation.value;
		for(let message of quickMove.getElementsByClassName('alert')) {
			message.classList.add('d-none');
		}

		let response = await fetch('/v2/items/' + encodeURIComponent(code) + '/parent', {
			headers: {
				'Accept': 'application/json',
				'Content-Type': 'application/json'
			},
			method: 'PUT',
			body: JSON.stringify(parent)
		});

		if(response.ok) {
			quickMove.querySelector('.alert.alert-success').classList.remove('d-none');
			quickMove.querySelector('.alert.alert-success a').href = '/item/' + encodeURIComponent(code);
			return;
		}

		let warning = quickMove.querySelector('.alert.alert-warning');
		let error = quickMove.querySelector('.alert.alert-danger');

		let json;

		try {
			json = await response.json();
		} catch(e) {
			console.log(response);
			error.classList.remove('d-none');
			throw e;
		}

		console.log(json);

		let errorText;
		//form.classList.add('was-validated');
		if(response.status === 404 && 'item' in json) {
			if(json.item === parent) {
				errorText = `Item ${parent} doesn't exist`;
				quickMoveLocation.setCustomValidity(errorText);
			} else {
				errorText = `Item ${code} doesn't exist`;
				quickMoveCode.setCustomValidity(errorText);
			}
			warning.classList.remove('d-none');
			warning.textContent = errorText;
		} else if(response.status === 401) {
			warning.classList.remove('d-none');
			warning.textContent = 'Session expired or logged out. Open another tab, log in and try again.';
		} else if(response.status === 400 && json.exception === 'WEEEOpen\\Tarallo\\ItemNestingException' && 'item' in json && 'other_item' in json) {
			if('message' in json) {
				errorText = `Cannot place ${json.item} inside ${json.other_item}: ${json.message}`;
			} else {
				errorText = `Cannot place ${json.item} inside ${json.other_item}`;
			}
			warning.classList.remove('d-none');
			warning.textContent = errorText;
		} else {
			error.classList.remove('d-none');
		}
		return false;
	});

	quickMove.addEventListener('keydown', function(e) {
		if(e.key === "Enter") {
			e.preventDefault();
			quickMoveDo.click();
		} else if(e.altKey && e.ctrlKey && (e.key === 's' || e.key === 'S')) {
			e.preventDefault();
			quickMoveSwap.click();
		}
	});

	function moveButtonInItemListener(e) {
		e.preventDefault();
		e.stopPropagation();

		let button = document.getElementById('quickmovebutton');
		let bar = document.getElementById('quickmove');

		$(bar).collapse('show');
		// This should be part of .collapse('show');, but it only happens sometimes. Forcing it makes the bar appear half through the animation and then jump to the end and do weird things instead of appearing smoothly, but there's no other way.
		//bar.classList.remove('d-none');
		button.classList.add('active');
		button.setAttribute('aria-expanded', 'true');

		quickMoveCode.value = e.target.dataset.code;

		focusQuickMove();
	}
}());
