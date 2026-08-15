<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = \App\Models\User::where('role', 'department_member')->first();
\Illuminate\Support\Facades\Auth::login($user);

$request = \Illuminate\Http\Request::create('/department/students', 'POST', [
    'student_number' => '888888',
    'full_name' => 'John Doe HTTP',
    'email' => '888888@cctt.edu.ly',
    'semester' => 8,
    'is_active' => true,
    'role' => 'student',
    'password' => '888888'
]);

$response = app()->handle($request);
echo "Response Status: " . $response->getStatusCode() . "\n";
echo "Response Content: " . $response->getContent() . "\n";
