(function () {
	$("input[type=checkbox]").on("change", function (e) {
		if (e.currentTarget.dataset.donationId !== undefined && e.currentTarget.dataset.taskId !== undefined) {
			let parsed = e.currentTarget.dataset.taskId.match(/^(\S*):([-\d]+|all)$/);
			if (parsed[2] === "all") {
				fetch(`/v2/donation/${e.currentTarget.dataset.donationId}/tasks`, {
					method: "post",
					headers: {
						"Content-Type":"application/json"
					},
					body: JSON.stringify(Object.fromEntries($(`input[type=checkbox][data-donation-id=${e.currentTarget.dataset.donationId}]`).filter(function () {
							return this.dataset.taskId.match(new RegExp(`^${parsed[1]}:(\\d+)$`)) !== null;
						}).toArray().map(el => {
							el.checked = e.currentTarget.checked;
							return [el.dataset.taskId, e.currentTarget.checked];
						}))
					)
				});
			} else {
				fetch(`/v2/donation/${e.currentTarget.dataset.donationId}/tasks`, {
					method: "post",
					headers: {
						"Content-Type":"application/json"
					},
					body: JSON.stringify(Object.fromEntries([[e.currentTarget.dataset.taskId, e.currentTarget.checked]]))
				});
				if (parsed[2] != -1)
					$(`input[type=checkbox][data-donation-id=${e.currentTarget.dataset.donationId}][data-task-id="${parsed[1]}:all"]`).prop('checked', $(`input[type=checkbox][data-donation-id=${e.currentTarget.dataset.donationId}]`).filter(function () {
							return this.dataset.taskId.match(new RegExp(`^${parsed[1]}:(\\d+)$`)) !== null;
						}).toArray().every(el => el.checked));
			}
			try {
				let progress = Math.floor($(`input[type=checkbox][data-donation-id=${e.currentTarget.dataset.donationId}]:checked`).filter(function () {
					return this.dataset.taskId.match(/^\S*:([-\d]+)$/) !== null;
				}).length*100 / document.getElementById("totalTasks").value);
				document.getElementById("progressText").innerText = progress;
				document.getElementById("progressBar").style.width = progress + "%";
				document.getElementById("progressBar").setAttribute("aria-valuenow", progress);
			} catch (e) {}
		}
	});
})();