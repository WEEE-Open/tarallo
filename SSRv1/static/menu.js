(function() {
	"use strict";

	let nav = document.getElementById('main');
	let top = document.getElementById('top');
	let quickView = top.querySelector(".quick.view");
	let quickMove = top.querySelector(".quick.move");

	document.getElementById('logout').addEventListener('click', () => window.location.href = '/logout');

	for(let message of quickMove.getElementsByClassName('message')) {
		message.style.display = 'none';
	}

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
			ev.target.classList.remove('selected');
			bar.classList.remove("open");
		} else {
			ev.target.classList.add('selected');
			bar.classList.add("open");
			bar.querySelector('input').focus();
		}
	});

	quickView.querySelector('button').addEventListener('click', function() {
		let code = quickView.querySelector('input').value;
		if(code !== "") {
			window.location.href = '/item/' + encodeURIComponent(code);
		}
	});

	quickMove.querySelector('button').addEventListener('click', async () => {
		let inputs = quickMove.querySelectorAll('input');
		let code = inputs[0].value;
		let parent = inputs[1].value;
		if(code !== "" && parent !== "") {
			inputs[0].setCustomValidity('');
			for(let message of quickMove.getElementsByClassName('message')) {
				message.style.display = 'none';
			}

			let response = await fetch('/v1/items/' + encodeURIComponent(code) + '/parent?fix', {
				headers: {
					'Accept': 'application/json',
					'Content-Type': 'application/json'
				},
				method: 'PUT',
				credentials: 'include',
				body: JSON.stringify(parent)
			});

			if(response.ok) {
				quickMove.getElementsByClassName('success')[0].style.display = '';
				quickMove.querySelector('.success.message a').href = '/item/' + encodeURIComponent(code);
				return;
			}

			let warning = quickMove.getElementsByClassName('warning')[0];
			let error = quickMove.getElementsByClassName('error')[0];

			if(response.status === 404) {
				warning.style.display = '';
				warning.textContent = `Item ${code} doesn't exist`;
				inputs[0].setCustomValidity(`Item ${code} doesn't exist`);
				return;
			}

			let jsend;

			try {
				jsend = await response.json();
			} catch(e) {
				error.style.display = '';
				console.log(response);
				throw e;
			}

			console.log(jsend);

			if(jsend.status === 'fail') {
				warning.style.display = '';
				if(typeof jsend.data['*'] === 'string') {
					warning.textContent = jsend.data['*'];
				} else {
					warning.textContent = jsend.data.toString();
				}
				return;
			}

			quickMove.getElementsByClassName('error')[0].style.display = '';
		}
	});

	quickView.addEventListener('keydown', function(e) {
		if(e.keyCode === 13) {
			e.preventDefault();
			quickView.getElementsByTagName('BUTTON')[0].click();
		}
	});

	quickMove.addEventListener('keydown', function(e) {
		if(e.keyCode === 13) {
			e.preventDefault();
			quickMove.getElementsByTagName('BUTTON')[0].click();
		}
	})

}());