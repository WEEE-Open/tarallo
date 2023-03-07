(function () {
    'use strict';

    function hydrateListInput(listInput) {
        let hiddenInput = listInput.querySelector("input[type=hidden]");
        let newInput = listInput.querySelector("input[type=text]");
        let newButton = listInput.querySelector("button");

        $(newInput).autoComplete({
            minLength: 1,
            resolverSettings: { 
                url: "/v2/autosuggest/code",
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

        function renderItem(text) {
            let newItem = $('<li class="list-group-item p-0 d-flex align-items-center"><div class="p-2 pl-3 mr-auto">' + text + '</div><div class="btn btn-outline-danger" id="delete"><i class="fa fa-trash"></i></div></li>');
            newItem.find("#delete").on('click', () => {
                $(listInput).find(newItem).remove();
                let currentList = JSON.parse(hiddenInput.value || "[]");
                const index = currentList.indexOf(text);
                if (index > -1) { 
                    currentList.splice(index, 1);
                }
                hiddenInput.value = JSON.stringify(currentList);
                $(hiddenInput).trigger("input");
            });
            $(listInput).find(".input-group").before(newItem);
        }

        function useLastSuggestion(e) {
            e.preventDefault();
            let lastValid = JSON.parse(e.target.dataset.lastValid);
            if (e.target.value == '' || lastValid.includes(e.target.value)) return;
            e.target.value = lastValid[0] || '';
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

        $(newButton).on('click', function () {
            addToList(newInput.value);
        });

        JSON.parse(hiddenInput.value || "[]").forEach(item => {
            renderItem(item);
        });
    }

    for (let listInput of document.querySelectorAll('.item-list-input')) {
        hydrateListInput(listInput);
    }

    $("[name=ItemsList]").on("input", () => {
        console.log("changed!");
    })

})();