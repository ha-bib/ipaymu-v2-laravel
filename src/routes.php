<?php

use Illuminate\Support\Facades\Route;
use Habz\IPaymuLaravel\IPaymu;

Route::get('demo', function () {
    // set buyer name
    $iPaymu = new IPaymu();

    $iPaymu->setBuyer([
        'name' => 'Habz',
        'phone' => '08123123139',
        'email' => 'habz@gmail.com',
    ]);

    // set your reference id (optional)
    $iPaymu->setReferenceId('123123');

    // set your expiredPayment
    $iPaymu->setExpired(24); // 24 hours

    // set cod param (optional)
    $iPaymu->setCOD([
        'deliveryArea' => "76111",
        'deliveryAddress' => "Merauke",
    ]);

    $carts = [];
    $carts = $iPaymu->add([
        'product_name' => 'Jacket', // product name (string)
        'price' => 244000, // price (float)
        'quantity' => 2, // quantity (int)
        'description' => 'Size Jumbo', // description
        'weight' => 1, // product weight (int) (optional)
        'length' => 1, // product length (int) (optional)
        'width' => 1, // product weight (int) (optional)
        'height' => 1 // product height (int) (optional)
    ]);
    $carts = $iPaymu->add([
        'product_name' => 'Sepatu', // product name (string)
        'price' => 104000, // price (float)
        'quantity' => 1, // quantity (int)
        'description' => 'Size 33', // description
        'weight' => 1, // product weight (int) (optional)
        'length' => 1, // product length (int) (optional)
        'width' => 1, // product weight (int) (optional)
        'height' => 1 // product height (int) (optional)
    ]);

    return $iPaymu->redirectPayment();
});
