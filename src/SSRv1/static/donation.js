(async function () {
    'use strict';

    function hydrateListInput(listInput) {
        let hiddenInput = listInput.querySelector("input[type=hidden]");
        let newInput = listInput.querySelector("input[type=text]");
        let newButton = listInput.querySelector("button");

        if (newInput.dataset.autocompleteUri) {
            $(newInput).autoComplete({
                minLength: 1,
                resolverSettings: { 
                    url: newInput.dataset.autocompleteUri,
                    requestThrottling: 100,
                    fail: () => {},
                }, events: {
                    searchPost: (list, el) => {
                        if (list.length > 0) {
                            newInput.dataset.lastValid = JSON.stringify(list);
                        }
                        return list;
                    }
                }
            });
        }

		function escapeHTML(str) {
			const p = document.createElement("p");
			p.appendChild(document.createTextNode(str));
			return p.innerHTML;
		}

        function renderItem(text) {
			let oldText;
			if (Array.isArray(text)) {
				oldText = text[0];
				text = text[1];
			}
            let newItem = $(`<li class="list-group-item p-0 d-flex align-items-center"><div class="p-2 pl-3 mr-auto name"></div>${(hiddenInput.dataset.edit ?? false) ? '<div class="btn btn-outline-primary" id="move-up"><i class="fas fa-chevron-up"></i></div><div class="btn btn-outline-primary" id="move-down"><i class="fas fa-chevron-down"></i></div>' : ''}${(hiddenInput.dataset.edit ?? false) ? '<div class="btn btn-outline-primary" id="edit"><i class="fas fa-pencil-alt"></i></div>' : ''}<div class="btn btn-outline-danger" id="delete"><i class="fa fa-trash"></i></div></li>`);
            newItem.find('.name').html(`<del id="old">${escapeHTML(oldText || "")}</del> ${escapeHTML(text)}`);
            newItem.find("#delete").on('click', () => {
                $(listInput).find(newItem).remove();
                let currentList = JSON.parse(hiddenInput.value || "[]");
				const index = currentList.findIndex(t => (Array.isArray(t) ? t[0] : t) === (oldText || text));
                if (index > -1) { 
                    currentList.splice(index, 1);
                }
                hiddenInput.value = JSON.stringify(currentList);
                $(hiddenInput).trigger("input");
            });
			newItem.find("#move-up").on('click', () => {
                let currentList = JSON.parse(hiddenInput.value || "[]");
				const index = currentList.findIndex(t => (Array.isArray(t) ? t[0] : t) === (oldText || text));
                if (index > 0) { 
                	$(newItem).insertBefore($(newItem).prev());
					[currentList[index], currentList[index-1]] = [currentList[index-1], currentList[index]];
					hiddenInput.value = JSON.stringify(currentList);
					$(hiddenInput).trigger("input");
                }
            });
			newItem.find("#move-down").on('click', () => {
                let currentList = JSON.parse(hiddenInput.value || "[]");
				const index = currentList.findIndex(t => (Array.isArray(t) ? t[0] : t) === (oldText || text));
                if (index > -1 && index < currentList.length - 1) { 
                	$(newItem).insertAfter($(newItem).next());
					[currentList[index], currentList[index+1]] = [currentList[index+1], currentList[index]];
					hiddenInput.value = JSON.stringify(currentList);
					$(hiddenInput).trigger("input");
                }
            });
			newItem.find("#edit").on('click', () => {
				swal({
					icon: "info",
					title: "Enter new name:",
					content: "input",
					buttons: {
						cancel: {
							text: "Cancel",
							value: null,
							visible: true,
							closeModal: true,
						},
						confirm: {
							text: "OK",
							value: true,
							visible: true,
							closeModal: true
						}
					}
				}).then((res) => {
					if (!res || res.trim() === "") return;
					res = res.trim();
					let currentList = JSON.parse(hiddenInput.value || "[]");
					const index = currentList.findIndex(t => (Array.isArray(t) ? t[0] : t) === (oldText || text));
					if (index > -1) { 
						if (res === (Array.isArray(currentList[index]) ? currentList[index][1] : currentList[index])) return;
						if (res === text) {
							currentList[index] = res;
							newItem.find('.name').html(`<del id="old"></del> ${escapeHTML(text)}`);
						} else {
							currentList[index] = [text, res];
							newItem.find('.name').html(`<del id="old">${escapeHTML(text || "")}</del> ${escapeHTML(res)}`);
						}
					}
					hiddenInput.value = JSON.stringify(currentList);
					$(hiddenInput).trigger("input");
				});
            });
            $(listInput).find(".input-group").before(newItem);
        }

        function useLastSuggestion(e) {
            e.preventDefault();
            if (newInput.dataset.autocompleteUri) {
                let lastValid = JSON.parse(e.target.dataset.lastValid || "[]");
                if (e.target.value == '' || lastValid.includes(e.target.value)) return;
                e.target.value = lastValid[0] || '';
            } else {
                // Everything is ok
            }
        }

        function addToList(text) {
            if (text == "") return;
            let currentList = JSON.parse(hiddenInput.value || "[]");
            if (currentList.indexOf(text) !== -1) {
                swal({
                    icon: "error",
                    title: "Item already in list"
                }).then(() => {
                    newInput.focus();
                });
                return;
            }
            currentList.push(text);
            renderItem(text);
            newInput.value = "";
            hiddenInput.value = JSON.stringify(currentList);
            $(hiddenInput).trigger("input");
        }

        $(newInput).on('blur', useLastSuggestion);
        $(newInput).on('keydown', function (e) {
            if (e.originalEvent.key === "Enter") {
                useLastSuggestion(e);
                addToList(e.target.value);
            }
        });
        $(newInput).on('paste', async function (e) {
            let pastedText = e.originalEvent.clipboardData.getData("text");
            if (pastedText.indexOf('\n') > -1) {
                e.preventDefault();
                newInput.blur();
                let currentList = JSON.parse(hiddenInput.value || "[]");
                let newItems = pastedText.split('\n').map(item => item.trim()).filter(item => item != '');
				let callback = (items => {
                    if (items.length === 0) {
                        swal({
                            icon: "error",
                            title: "All pasted items already in list"
                        }).then(() => {
                            newInput.focus();
                        });
                        return;
                    }
                    let displayList = document.createElement('ul');
                    displayList.classList.add("list-group")
                    items.forEach(item => {
                        let listItem = document.createElement('li');
                        listItem.innerText = item;
                        listItem.classList.add("list-group-item")
                        displayList.appendChild(listItem);
                    });
                    swal({
                        icon: "info",
                        title: "Confirm adding the following?",
                        content: displayList,
                        buttons: {
                            cancel: {
                                text: "Cancel",
                                value: null,
                                visible: true,
                                closeModal: true,
                            },
                            confirm: {
                                text: "OK",
                                value: true,
                                visible: true,
                                closeModal: true
                            }
                        }
                    }).then(res => {
                        if (res === true) {
                            items.forEach(addToList);
                        }
                    }).then(() => {
                        newInput.focus();
                    });
                });

				if (newInput.dataset.autocompleteUri) {
					Promise.all(newItems.map(item => {
						return fetch(newInput.dataset.autocompleteUri+'?q='+item).then(res => res.json()).then(res => res[0]).catch(() => null);
					})).then(items => items.filter(item => item != null && currentList.indexOf(item) === -1)).then(callback);
				} else {
					callback(newItems);
				}
            }
        })

        $(newButton).on('click', function () {
            addToList(newInput.value);
        });

        JSON.parse(hiddenInput.value || "[]").forEach(item => {
            renderItem(item);
        });
    }

    /**
     * 
     * @param {object} opt
     * @param {string} opt.title 
     * @param {string} opt.autocompleteUri (optional) 
     * @param {string} opt.hiddenName (optional)
     * @param {object} opt.initialData (optional)
     * @param {boolean} opt.edit (optional)
     * @param {boolean} opt.arrange (optional)
     */
    function createListInput(opt) {
        let newListInput = $('<label>test</label><ul class="list-group item-list-input"><input type="hidden"><div class="list-group-item input-group mb-3"><input type="text" class="form-control" placeholder="Add item" autocomplete="off"><div class="input-group-append"><button class="btn btn-secondary" type="button">Add</button></div></div></ul>');
        newListInput.get(0).innerText = opt.title;
        if (opt.autocompleteUri) newListInput.find('input[type=text]').get(0).dataset.autocompleteUri = opt.autocompleteUri;
        if (opt.hiddenName) newListInput.find('input[type=hidden]').attr("name", opt.hiddenName);
        if (opt.initialData) newListInput.find('input[type=hidden]').attr("value", JSON.stringify(opt.initialData));
		if (opt.edit === true) newListInput.find('input[type=hidden]').attr("data-edit", true);
		if (opt.arrange === true) newListInput.find('input[type=hidden]').attr("data-arrange", true);
        hydrateListInput(newListInput.get(1));
        return newListInput;
    }

    for (let listInput of document.querySelectorAll('.item-list-input')) {
        hydrateListInput(listInput);
    }

    $("input[name=Location]").autoComplete({
        minLength: 1,
        resolverSettings: { 
            url: "/v2/autosuggest/location",
            requestThrottling: 100,
            fail: () => {},
        }, events: {
            searchPost: (list, el) => {
                if (list.length > 0) {
                    newInput.dataset.lastValid = JSON.stringify(list);
                }
                return list;
            }
        }
    });

    let typesNames = await fetch("/features.json").then(res => res.json()).then(res => Object.values(res.features).find(f => f.findIndex(ff => ff.name == "type") != -1).find(ff => ff.name == "type").values).catch(() => []);

    let typesCache = {};
    let typesCount = {};
    let prec = [];

    $("[name=ItemsList]").on("input", function () {
        let curr = JSON.parse(this.value || "[]"); // Grab list of items from items list and parse it
        let added = curr.filter(item => !prec.includes(item));
        let deleted = prec.filter(item => !curr.includes(item));
        prec = curr;

        let missingFromCache = added.filter(item => !Object.keys(typesCache).includes(item));

        if (missingFromCache.length > 0) {
            fetch("/v2/stats/getTypesForItemCodes", {
                method: "POST",
                body: JSON.stringify(missingFromCache),
                credentials: "same-origin",
                headers: {
                    "Content-Type":"application/json"
                }
            }).then(res => res.json()).then((res) => {
                typesCache = {...typesCache, ...res};
            }).then(refreshTypes).catch(console.log);
        } else {
            refreshTypes();
        }

        function refreshTypes() {
            let tasksContainer = $("#tasksContainer");
            let allTasksInput = document.querySelector("input[name=Tasks]");

            let modifiedTasksCount = {};
            deleted.forEach(item => {
                modifiedTasksCount[typesCache[item]] = (modifiedTasksCount[typesCache[item]] || 0) - 1;
            });
            added.forEach(item => {
                modifiedTasksCount[typesCache[item]] = (modifiedTasksCount[typesCache[item]] || 0) + 1;
            });

            Object.entries(modifiedTasksCount).forEach(([type, count]) => {
                typesCount[type] = (typesCount[type] || 0) + count;
                if (typesCount[type] < 0) typesCount[type] = 0; // this shouldn't happen but just to be safe
                if (typesCount[type] === 0) {
                    delete typesCount[type];
                    $("#tasks-group-" + type).remove();
                } else if (count > 0 && typesCount[type] === count) {
                    let oldTasks = JSON.parse(allTasksInput.value || "{}")
                    let inputGroup = $('<div></div>');
                    inputGroup.attr("id", "tasks-group-" + type);
                    inputGroup.append(createListInput({
                        title: "Tasks for " + (typesNames[type] || "Other") + ":",
                        initialData: oldTasks[type],
						edit: true,
						arrange: true,
                    }));
                    inputGroup.find("input[type=hidden]").on('input', function () {
                        let groupTasks = JSON.parse(this.value || "[]");
                        let allTasks = JSON.parse(allTasksInput.value || "{}");
                        allTasks[type] = groupTasks;
                        allTasksInput.value = JSON.stringify(allTasks);
                    })
                    tasksContainer.append(inputGroup);
                }
            })

            if (Object.keys(typesCount).length > 0) {
                tasksContainer.removeClass('no-tasks');
            } else {
                tasksContainer.addClass('no-tasks');
            }
        }
    });

    $("[name=ItemsList]").trigger("input");

	$(".delete").on('click', () => {
		swal({
			icon: "warning",
			dangerMode: true,
			title: "Are you sure?",
			content: {
				element: 'div',
				attributes: {
					innerText: 'Deleting a donation will loose all progress and is NOT recoverable'
				}
			},
			buttons: {
				cancel: true,
				confirm: "Yes",
			},
		}).then((r) => {
			if (r !== true) return;
			swal({
				icon: "warning",
				dangerMode: true,
				title: "But like seriously?",
				content: {
					element: 'div',
					attributes: {
						innerText: 'I cannot stress enough how this is NOT RECOVERABLE'
					}
				},
				buttons: {
					cancel: true,
					confirm: "Yes",
				},
			}).then((r) => {
				if (r !== true) return;
				let tempLink = document.createElement("a");
				tempLink.href = "delete";
				tempLink.click();
			})
		});
	});

})();