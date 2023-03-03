(function () {
    'use strict';

    for (let listInput of document.querySelectorAll('.item-list-input')) {
        let newInput = listInput.querySelector("input[type=text]");
        let newButton = listInput.querySelector("button");
        console.log(newInput, newButton);

        $(newInput).autoComplete({
            minLength: 3, preventEnter: true, resolverSettings: { url: "/v2/autosuggest/code", requestThrottling: 300 }, events: {
                searchPost: (list, el) => {
                    if (list.length > 0) {
                        newInput.dataset.lastValid = JSON.stringify(list);
                    }
                    return list;
                }
            }
        });
        $(newInput).on('blur', function () {
            let lastValid = JSON.parse(newInput.dataset.lastValid);
            if (newInput.value == '' || lastValid.includes(newInput.value)) return;
            newInput.value = lastValid[0] || '';
        });
    }

})();