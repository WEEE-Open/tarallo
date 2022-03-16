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
    <form class="form-inline my-2 my-md-0" action="" method="POST">
        <div class="input-group mb-3">
            <label for="DonationName">Donation Name</label>
            <input class="form-control" placeholder="Donation Name" type="text" name="DonationName" id="DonationName">
        </div>
        <div class="input-group mb-3">
            <label for="Location">Location</label>
            <input class="form-control" placeholder="Location" type="text" name="Location" id="Location">
        </div>
    </form>
</div>