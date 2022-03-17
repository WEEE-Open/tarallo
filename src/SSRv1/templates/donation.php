<?php
/** @var \WEEEOpen\Tarallo\User $user */
/** @var array $donation */
$this->layout('main', ['title' => 'Donation', 'user' => $user]);
?>
<div class="container">
    <div class="row d-flex m-0 justify-content-between">
        <h2 class="col-8 p-0">Donazione</h2>
    </div>
    <br>
    <form class="row g-2" action="" method="POST">
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
            <input type="datetime-local" name="Date" id="datetime-local">
        </div>
        <div class="col-12 mb-3">
            <label for="ReferencedUser">Referenced User: </label>
            <input class="form-control" type="text" name="ReferencedUser" id="ReferencedUser" placeholder="Referenced User">
        </div>
        <div class="col-12 mb-3">
            <label for="Note">Note: </label>
            <textarea class="form-control" name="Note" id="Note" cols="30" rows="10"></textarea>
        </div>
        <div class="row d-flex m-0 justify-content-between">
            <div class="col-2">
                <button class="btn btn-primary">Aggiungi</button>
            </div>
            <div class="col-2">
                <button class="btn btn-success">Salva</button>
            </div>
            <div class="col-2">
                <button class="btn btn-secondary">Download</button>
            </div>
            <div class="col-2">
                <button class="btn btn-info">"Completata"</button>
            </div>
        </div>
    </form>
</div>