(function() {
	"use strict";

	let nav = document.getElementById('main');
	let top = document.getElementById('top');
	let quickView = top.querySelector(".quick.view.bar");
	let quickMove = top.querySelector(".quick.move.bar");
	let quickMoveCode = quickMove.querySelectorAll('input')[0];
	let quickMoveParent = quickMove.querySelectorAll('input')[1];

	document.getElementById('logout').addEventListener('click', () => window.location.href = '/logout');

	for(let message of quickMove.getElementsByClassName('message')) {
		message.style.display = 'none';
	}

	nav.addEventListener('click', ev => {
		// noinspection JSUnresolvedVariable
		if(!ev.target.classList || !ev.target.classList.contains('quick')) {
			return true;
		}
		toggleAdditionalControls(ev.target);
	});

	nav.addEventListener('keypress', ev => {
		if (ev.key === " " || ev.key === "Enter") {
			// noinspection JSUnresolvedVariable
			if(!ev.target.classList || !ev.target.classList.contains('quick')) {
				return true;
			}
			toggleAdditionalControls(ev.target);
		}
	});

	/**
	 * Show/hide additional menu controls
	 *
	 * @param {HTMLElement|EventTarget} target button
	 * @return {boolean}
	 */
	function toggleAdditionalControls(target) {
		let toggle = target.dataset.toggle;

		// not in the "foo bar" sense
		let bar;
		if(toggle === 'move') {
			bar = quickMove;
		} else if(toggle === 'view') {
			bar = quickView;
		} else {
			return true;
		}

		if(bar.classList.contains("open")) {
			target.classList.remove('selected');
			bar.classList.remove("open");
		} else {
			target.classList.add('selected');
			bar.classList.add("open");
			let input = bar.querySelector('input');
			input.focus();
			let inputLen = input.value.length;
			if(inputLen > 0) {
				input.setSelectionRange(inputLen, inputLen);
			}
		}
	}

	quickView.querySelector('button').addEventListener('click', function() {
		let code = quickView.querySelector('input').value;
		if(code !== "") {
			window.location.href = '/item/' + encodeURIComponent(code);
		}
	});

	quickMove.querySelector('button.swap').addEventListener('click', function() {
		let temp = quickMoveCode.value;
		quickMoveCode.value = quickMoveParent.value;
		quickMoveParent.value = temp;
	});

	quickMove.querySelector('button.do').addEventListener('click', async () => {
		let code = quickMoveCode.value;
		let parent = quickMoveParent.value;
		if(code !== '' && parent !== '') {
			quickMoveCode.setCustomValidity('');
			quickMoveParent.setCustomValidity('');
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
					quickMoveParent.setCustomValidity(errorText);
				} else {
					errorText = `Item ${code} doesn't exist`;
					quickMoveCode.setCustomValidity(errorText);
				}
				warning.style.display = '';
				warning.textContent = errorText;
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

	quickView.addEventListener('keydown', function(e) {
		if(e.key === "Enter") {
			e.preventDefault();
			quickView.getElementsByTagName('BUTTON')[0].click();
		}
	});

	let quickMoveDo = quickMove.getElementsByClassName('do')[0];
	let quickMoveSwap = quickMove.getElementsByClassName('swap')[0];
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
