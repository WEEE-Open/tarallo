<?php
/** @var \WEEEOpen\Tarallo\Server\User $user */
/** @var int[] $serials */
/** @var \WEEEOpen\Tarallo\Server\ItemIncomplete[] $missingData */
/** @var \WEEEOpen\Tarallo\Server\ItemIncomplete[] $lost */
$this->layout('main', ['title' => 'Rams Stats', 'user' => $user]);
$this->insert('stats::menu', ['currentPage' => 'rams']);
?>
<?php if(!empty($byStandard)): ?>
    <div class="statswrapper">
        <p>Rams by type</p>
        <table>
            <thead>
            <tr>
                <td>Type</td>
                <td>Count</td>
            </tr>
            </thead>
            <tbody>
            <?php foreach($byStandard as $type => $count): ?>
                <tr>
                    <td><?=$type?></td>
                    <td><?=$count?></td>
                </tr>
            <?php endforeach ?>
            </tbody>
        </table>
    </div>
<?php endif ?>
<?php if(!empty($byFormFactor)): ?>
    <div class="statswrapper">
        <p>Rams by form factor</p>
        <table>
            <thead>
            <tr>
                <td>Form Factor</td>
                <td>Count</td>
            </tr>
            </thead>
            <tbody>
            <?php foreach($byFormFactor as $formFactor => $count): ?>
                <tr>
                    <td><?=$formFactor?></td>
                    <td><?=$count?></td>
                </tr>
            <?php endforeach ?>
            </tbody>
        </table>
    </div>
<?php endif ?>
<?php if(!empty($bySize)): ?>
    <div class="statswrapper">
        <p>Rams by form factor</p>
        <table>
            <thead>
            <tr>
                <td>Size</td>
                <td>Count</td>
            </tr>
            </thead>
            <tbody>
            <?php foreach($bySize as $size => $count): ?>
                <tr>
                    <td><?=$size?></td>
                    <td><?=$count?></td>
                </tr>
            <?php endforeach ?>
            </tbody>
        </table>
    </div>
<?php endif ?>
<?php if(!empty($working)): ?>
    <div class="statswrapper">
        <p>Working Rams</p>
        <table>
            <thead>
            <tr>
                <td>Ram</td>
            </tr>
            </thead>
            <tbody>
            <?php foreach($leastRecent as $code): ?>
                <tr>
                    <td><a href="/item/<?=$code?>"><?=$code?></a></td>
                </tr>
            <?php endforeach ?>
            </tbody>
        </table>
    </div>
<?php endif ?>
