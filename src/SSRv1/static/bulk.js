(function () {
    'use strict';

    let bulkMove = document.getElementById('bulk-move');
    let bulkMoveButton = document.getElementById('bulk-move-button');
    bulkMoveButton.addEventListener('click', (ev) => {
        ev.preventDefault();
        let temp = bulkMoveButton.textContent;
        bulkMoveButton.textContent = bulkMoveButton.dataset.alternateText;
        bulkMoveButton.dataset.alternateText = temp;

        for(let el of bulkMove.getElementsByClassName('toggle')) {
            el.classList.toggle("d-none");
        }
    });
}());
