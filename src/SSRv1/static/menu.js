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

	for(let el of document.querySelectorAll('.itembuttons .move')) {
		el.addEventListener('click', moveButtonInItemListener);
	}

	for(let el of document.querySelectorAll('.features.collapse')) {
		$(el).on('show.bs.collapse', toggleSummary);
		$(el).on('hidden.bs.collapse', toggleSummary);
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
			$(quickMove).collapse('hide');
			nav.classList.remove("mb-0");
		} else {
			quickMoveButton.classList.add("active");
			$(quickMove).collapse('show');
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

	async function moveInternal(code, parent) {
		for (let message of quickMove.getElementsByClassName('alert')) {
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

		let ok = quickMove.querySelector('.alert.alert-success');
		let warning = quickMove.querySelector('.alert.alert-warning');
		let error = quickMove.querySelector('.alert.alert-danger');

		let json;

		try {
			json = await response.json();
		} catch (e) {
			console.log(response);
			error.classList.remove('d-none');
			throw e;
		}

		if (response.ok) {
			while (ok.lastChild) {
				ok.removeChild(ok.lastChild);
			}

			let okText = document.createElement("span");
			ok.append(okText);
			let destination = document.createElement('a');
			destination.textContent = json['to'];
			destination.href = '/item/' + encodeURIComponent(json['to']);
			let item = document.createElement('a');
			item.textContent = json['code'];
			item.href = '/item/' + encodeURIComponent(json['code']);
			// Moved ... from ... to
			if (json['moved']) {
				okText.append(document.createTextNode("Moved "));
				okText.append(item)
				if (json['from'] !== null) {
					okText.append(document.createTextNode(" from "));
					let source = document.createElement('a');
					source.textContent = json['from'];
					source.href = '/item/' + encodeURIComponent(json['from']);
					okText.append(source)
				}
				okText.append(document.createTextNode(" to "));
			} else {
				okText.append(item)
				okText.append(document.createTextNode(" already in "));
			}
			// Destination
			okText.append(destination);
			if (typeof json['actual'] !== 'undefined') {
				let finalDestination = document.createElement('a');
				finalDestination.textContent = json['actual'];
				finalDestination.href = '/item/' + encodeURIComponent(json['actual']);

				okText.append(document.createTextNode(" ("));
				okText.append(finalDestination);
				okText.append(document.createTextNode(")"));
			}
			// Undo button
			if (json['moved']) {
				ok.append(document.createTextNode(" "));
				let undo = document.createElement("button");
				undo.textContent = "Undo";
				undo.classList.add('btn', 'btn-primary')
				undo.style.padding = "0.2em 0.4em";
				undo.style.margin = "-0.4em";
				undo.addEventListener('click', async (e) => {
					e.preventDefault();
					e.stopPropagation();

					let code = json['code'];
					let parent = json['from'];

					return await moveInternal(code, parent);
				});
				ok.append(undo);
			}

			ok.classList.remove('d-none');
			return true;
		}

		let errorText;
		//form.classList.add('was-validated');
		if (response.status === 404 && 'item' in json) {
			if (json.item === parent) {
				errorText = `Item ${parent} doesn't exist`;
				quickMoveLocation.setCustomValidity(errorText);
			} else {
				errorText = `Item ${code} doesn't exist`;
				quickMoveCode.setCustomValidity(errorText);
			}
			warning.classList.remove('d-none');
			warning.textContent = errorText;
		} else if (response.status === 401) {
			warning.classList.remove('d-none');
			warning.textContent = 'Session expired or logged out. Open another tab, log in and try again.';
		} else if (response.status === 400 && json.exception === 'WEEEOpen\\Tarallo\\ItemNestingException' && 'item' in json && 'other_item' in json) {
			if ('message' in json) {
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
	}

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

		return await moveInternal(code, parent);
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
		button.classList.add('active');
		button.setAttribute('aria-expanded', 'true');

		quickMoveCode.value = e.target.dataset.code;

		focusQuickMove();
	}

	function toggleSummary(e) {
		let summary = e.target.parentElement.querySelector('.summary');
		summary.classList.toggle('open');
	}
}());
