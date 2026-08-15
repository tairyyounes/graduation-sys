<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$request = \Illuminate\Http\Request::create('/department/students', 'POST', [
    'student_number' => '999999',
    'full_name' => 'John Doe Test',
    'email' => '999999@cctt.edu.ly',
    'semester' => 8,
    'is_active' => true,
    'role' => 'student',
    'password' => '999999'
]);

$user = \App\Models\User::where('role', 'department_member')->first();
$request->setUserResolver(function() use ($user) { return $user; });

$controller = app(\App\Http\Controllers\Department\StudentImportController::class);

try {
    $formRequest = \App\Http\Requests\AddingUserRequest::createFrom($request);
    $formRequest->setContainer(app());
    $formRequest->setRedirector(app(\Illuminate\Routing\Redirector::class));
    $formRequest->validateResolved();

    $response = $controller->store($formRequest);
    echo "Success: " . $response->getContent() . "\n";
} catch (\Illuminate\Validation\ValidationException $e) {
    echo "Validation Failed: " . json_encode($e->errors()) . "\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
