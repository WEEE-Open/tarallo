<?php
/** @var \WEEEOpen\Tarallo\User $user */
/** @var string $location */
/** @var bool $locationSet */
/** @var DateTime $startDate */
/** @var int[] byNcore */
/** @var bool $startDateSet */
/** @var array[] $byTypeFrequency */
$this->layout('main', ['title' => 'Stats: Cpus', 'user' => $user, 'currentPage' => 'stats']);
$this->insert('stats::menu', ['currentPage' => 'cpus']);
date_default_timezone_set('Europe/Rome');
$this->insert('stats::header', ['location' => $location, 'locationSet' => $locationSet, 'startDate' => $startDate, 'startDateSet' => $startDateSet]);

