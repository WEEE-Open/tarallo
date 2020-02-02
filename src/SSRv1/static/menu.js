(function() {
	"use strict";

	let quickMoveButton = document.getElementById('quickmovebutton');
	let quickMove = document.getElementById('quickmove');
	let nav = document.getElementById('main');
	let quickMoveCode = document.getElementById('quickmovecode');
	let quickMoveLocation = document.getElementById('quickmovelocation');
	let quickMoveDo = quickMove.getElementsByClassName('do')[0];
	let quickMoveSwap = quickMove.getElementsByClassName('swap')[0];

	for(let message of quickMove.getElementsByClassName('message')) {
		message.classList.add('d-none');
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

			let len = quickMoveCode.value.length;
			if(len <= 0) {
				quickMoveCode.focus();
			} else {
				quickMoveLocation.focus();
				len = quickMoveLocation.value.length;
				if(len > 0) {
					quickMoveLocation.setSelectionRange(len, len);
				}
			}
		}
	});

	// quickMove.addEventListener('keypress', ev => {
	// 	if (ev.key === " " || ev.key === "Enter") {
	// 		// noinspection JSUnresolvedVariable
	// 		if(!ev.target.classList || !ev.target.classList.contains('quick')) {
	// 			return true;
	// 		}
	// 		toggleAdditionalControls(ev.target);
	// 	}
	// });

	quickMoveSwap.addEventListener('click', function(e) {
		e.preventDefault();
		let temp = quickMoveCode.value;
		quickMoveCode.value = quickMoveLocation.value;
		quickMoveLocation.value = temp;
	});

	quickMoveDo.addEventListener('click', async (e) => {
		e.preventDefault();
		let code = quickMoveCode.value;
		let parent = quickMoveLocation.value;
		if(code !== '' && parent !== '') {
			quickMoveCode.setCustomValidity('');
			quickMoveLocation.setCustomValidity('');
			for(let message of quickMove.getElementsByClassName('message')) {
				message.style.display = 'none';
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
				quickMove.getElementsByClassName('success')[0].style.display = '';
				quickMove.querySelector('.success.message a').href = '/item/' + encodeURIComponent(code);
				return;
			}

			let warning = quickMove.getElementsByClassName('warning')[0];
			let error = quickMove.getElementsByClassName('error')[0];

			let json;

			try {
				json = await response.json();
			} catch(e) {
				console.log(response);
				error.style.display = '';
				throw e;
			}

			console.log(json);

			let errorText;
			if(response.status === 404 && 'item' in json) {
				if(json.item === parent) {
					errorText = `Item ${parent} doesn't exist`;
					quickMoveLocation.setCustomValidity(errorText);
				} else {
					errorText = `Item ${code} doesn't exist`;
					quickMoveCode.setCustomValidity(errorText);
				}
				warning.style.display = '';
				warning.textContent = errorText;
			} else if(response.status === 401) {
				warning.textContent = 'Session expired or logged out. Open another tab, log in and try again.';
			} else if(response.status === 400 && json.exception === 'WEEEOpen\\Tarallo\\ItemNestingException' && 'item' in json && 'other_item' in json) {
				if('message' in json) {
					errorText = `Cannot place ${json.item} inside ${json.other_item}: ${json.message}`;
				} else {
					errorText = `Cannot place ${json.item} inside ${json.other_item}`;
				}
				warning.style.display = '';
				warning.textContent = errorText;
			} else {
				error.style.display = '';
			}
			return false;
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
	})

}());
