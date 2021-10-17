<?php
/** @var \WEEEOpen\Tarallo\User $user */
/** @var array $cpusByBrand */
/** @var array $hddsByBrand */
$this->layout('main', ['title' => 'Stats: cool', 'user' => $user, 'currentPage' => 'stats', 'tooltips' => true, 'container' => true, 'bootstrapTable' => true]);
$this->insert('stats::menu', ['currentPage' => 'cool']);
?>

<div class="row">
    <?php if(!empty($cpusByBrand)): ?>
        <div class="col-12 col-xl-6">
            <table class="table table-borderless stats">
                <caption>CPUs count per brand</caption>
                <thead class="thead-dark">
                <tr>
                    <th data-sortable="true" scope="col">Brand</th>
                    <th data-sortable="true" scope="col">Count</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach($cpusByBrand as $nameBrand => $count): ?>
                    <tr>
                        <td><?=$this->e($nameBrand)?></td>
                        <td><?=$this->e($count)?></td>
                    </tr>
                <?php endforeach ?>
                </tbody>
            </table>
        </div>
    <?php endif ?>
    <?php if(!empty($hddsByBrand)): ?>
        <div class="col-12 col-xl-6">
            <table class="table table-borderless stats">
                <caption>HDDs count per brand</caption>
                <thead class="thead-dark">
                <tr>
                    <th data-sortable="true" scope="col">Brand</th>
                    <th data-sortable="true" scope="col">Count</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach($hddsByBrand as $nameBrand => $count): ?>
                    <tr>
                        <td><?=$this->e($nameBrand)?></td>
                        <td><?=$this->e($count)?></td>
                    </tr>
                <?php endforeach ?>
                </tbody>
            </table>
        </div>
    <?php endif ?>
</div>
