<?php
// Lab 3: JSON Response API Simulation
header('Content-Type: application/json');

$user = [
    "id"     => 1,
    "name"   => "Jeff",
    "email"  => "Jeff@gmail.com",
    "status" => "active"
];

echo json_encode($user);