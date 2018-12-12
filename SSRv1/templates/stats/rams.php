<?php
/** @var \WEEEOpen\Tarallo\Server\User $user */
/** @var string $location */
/** @var bool $locationSet */
/** @var DateTime $startDate */
/** @var bool $startDateSet */
$this->layout('main', ['title' => 'Rams Stats', 'user' => $user]);
$this->insert('stats::menu', ['currentPage' => 'rams']);
$this->insert('stats::header', ['location' => $location, 'locationSet' => $locationSet, 'startDate' => $startDate, 'startDateSet' => $startDateSet]);
?>
<div class="statswrapperwrapper">
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
                        <td><?=WEEEOpen\Tarallo\SSRv1\UltraFeature::printableValue(new \WEEEOpen\Tarallo\Server\Feature('ram-type', $type), $lang ?? 'en')?></td>
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
                        <td><?=WEEEOpen\Tarallo\SSRv1\UltraFeature::printableValue(new \WEEEOpen\Tarallo\Server\Feature('ram-form-factor', $formFactor), $lang ?? 'en')?></td>
                        <td><?=$count?></td>
                    </tr>
                <?php endforeach ?>
                </tbody>
            </table>
        </div>
    <?php endif ?>
    <?php if(!empty($bySize)): ?>
        <div class="statswrapper">
            <p>Rams by size</p>
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
                        <td><?=WEEEOpen\Tarallo\SSRv1\UltraFeature::printableValue(new \WEEEOpen\Tarallo\Server\Feature('capacity-byte', $size), $lang ?? 'en')?></td>
                        <td><?=$count?></td>
                    </tr>
                <?php endforeach ?>
                </tbody>
            </table>
        </div>
    <?php endif ?>
    <?php if(!empty($working)): ?>
        <div class="statswrapper large">
            <p>Working Rams</p>
            <table>
                <thead>
                <tr>
                    <td>Ram</td>
                </tr>
                </thead>
                <tbody>
                <?php foreach($working as $code): ?>
                    <tr>
                        <td><a href="/item/<?=$code?>"><?=$code?></a></td>
                    </tr>
                <?php endforeach ?>
                </tbody>
            </table>
        </div>
    <?php endif ?>
</div>