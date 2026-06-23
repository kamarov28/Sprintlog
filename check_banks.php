<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "=== BRANCHES ===\n";
$branches = App\Models\Branch::all(['id', 'name', 'city']);
echo json_encode($branches, JSON_PRETTY_PRINT) . "\n\n";

echo "=== ALL BANK ACCOUNTS ===\n";
$accounts = App\Models\BankAccount::all(['id', 'branch_id', 'bank_name', 'account_number', 'account_holder']);
echo json_encode($accounts, JSON_PRETTY_PRINT) . "\n";
