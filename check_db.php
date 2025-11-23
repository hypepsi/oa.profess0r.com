<?php

use App\Models\Workflow;
use App\Models\TaskType;
use Illuminate\Support\Facades\Schema;

require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

echo "--- Checking Database ---\n";

// 1. Check TaskType
try {
    echo "Checking TaskType table...\n";
    $count = TaskType::count();
    echo "TaskType count: $count\n";
    
    // Check columns
    $cols = Schema::getColumnListing('task_types');
    echo "TaskType columns: " . implode(', ', $cols) . "\n";
} catch (\Exception $e) {
    echo "ERROR in TaskType: " . $e->getMessage() . "\n";
}

// 2. Check Workflow Description
try {
    echo "Checking Workflow table...\n";
    $hasDesc = Schema::hasColumn('workflows', 'description');
    echo "Workflow has 'description' column? " . ($hasDesc ? "YES" : "NO") . "\n";
    
    if (!$hasDesc) {
        echo "WARNING: 'description' column is missing! This will cause 500 errors on save.\n";
    }
} catch (\Exception $e) {
    echo "ERROR in Workflow: " . $e->getMessage() . "\n";
}

echo "--- Done ---\n";
