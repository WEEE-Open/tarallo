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
    <form class="row g-2" action="/donation/update" enctype="multipart/form-data" method="POST">
        <div class="col-12 mb-3">
            <label for="DonationName">Donation Name: </label>
            <input class="form-control" placeholder="Donation Name"  value="<?php echo $donation['DonationName'] ?>" type="text" name="DonationName" id="DonationName">
        </div>
        <div class="col-12 mb-3">
            <label for="Location">Location: </label>
            <input class="form-control" value="<?php echo $donation['Location'] ?>" placeholder="Location" type="text" name="Location" id="Location">
        </div>
        <div class="col-12 mb-3 d-flex flex-row p-0 align-items-center">
            <div class="col">
            <label for="Date">Date: </label>
            <input type="date"  value="<?php echo $donation['Date'] ?>" name="Date" id="datetime-local">
            </div>
            <div class="form-check col">
                <input class="form-check-input" type="checkbox" value="" id="flexCheckChecked" <?php echo $donation['IsCompleted'] ? 'checked'  : '' ?> >
                <label class="form-check-label" for="flexCheckChecked">
                    Completed
                </label>
            </div>
        </div>
        <div class="col-12 mb-3">
            <label for="ReferencedUser">Referenced User: </label>
            <input class="form-control" value="<?php echo $donation['ReferenceUser'] ?>" type="text" name="ReferenceUser" id="ReferenceUser" placeholder="Reference User">
        </div>
        <div class="col-12 mb-3">
            <label for="Note">Note: </label>
            <textarea class="form-control"  name="Note" id="Note" cols="30" rows="10"><?php echo $donation['Note'] ?></textarea>
        </div>
        <div class="col-12 d-flex mb-2 p-0 justify-content-between">
            <div class="col">
                <button class="btn btn-success">Salva</button>
                <button class="btn btn-secondary mr-1">Download</button>
            </div>
            <div class="col-4 d-flex justify-content-end">
                <form action="" method="POST">
                    <button class="btn btn-primary mx-1">Aggiungi</button>
                    <input class="form-control" placeholder="Code" type="text" name="code" id="code">
                </form>
            </div>
        </div>
    </form>
</div>