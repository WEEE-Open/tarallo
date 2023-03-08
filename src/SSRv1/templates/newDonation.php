<?php



$this->layout('main', ['title' => 'New donation', 'currentPage' => 'donation new', 'tooltips' => true]);
?>

<article class="container">
    <form class="row g-2" action="/donation/new" enctype="multipart/form-data" method="POST">
        <div class="itembuttons primary row mx-0 mt-2 justify-content-end w-100">
            <div class="row d-flex m-0 justify-content-between mr-auto">
                <h2 class="col-8 p-0">Donation</h2>
            </div>
            <input type="submit" id="submit" class="d-none">
            <label for="submit" class="btn btn-success btn-item col-4 col-sm-auto save" role="button">
                <i class="fa fa-save"></i>&nbsp;Create
            </label>
        </div>
        <div class="col-12 mb-3">
            <label for="DonationName">Donation Name: </label>
            <input class="form-control" placeholder="Donation Name" type="text" name="DonationName" id="DonationName">
        </div>
        <div class="col-12 mb-3">
            <label for="Location">Location: </label>
            <input class="form-control" placeholder="Location" type="text" name="Location" id="Location">
        </div>
        <div class="col-12 mb-3">
            <label for="Date">Date: </label>
            <input type="date" name="Date" id="datetime-local">
        </div>
        <div class="col-12 mb-3">
            <label>Items list:</label>
            <ul class="list-group item-list-input">
                <input type="hidden" name="ItemsList">
                <div class="list-group-item input-group mb-3">
                    <input type="text" class="form-control" placeholder="Add item" autocomplete="off" data-autocomplete-uri="/v2/autosuggest/code">
                    <div class="input-group-append">
                        <button class="btn btn-secondary" type="button">Add</button>
                    </div>
                </div>
            </ul>
        </div>
        <div class="col-12 mb-3 no-tasks" id="tasksContainer">
            <h5>Tasks:</h5>
            <div class="no-tasks-text"><i>No tasks to show, please add an item before adding tasks</i></div>
            <input type="hidden" name="tasks">
        </div>
    </form>
</article>
<script src="/static/donation.js"></script>