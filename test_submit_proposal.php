<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$studentUser = \App\Models\User::where('role', 'student')->has('student')->first();
if (!$studentUser) {
    echo "No student user found.\n";
    exit;
}

\Illuminate\Support\Facades\Auth::login($studentUser);

$request = \Illuminate\Http\Request::create('/student/proposals', 'POST', [
    'title' => 'This is a test proposal title for my project',
    'problem' => 'This is a test problem statement that needs to have more than thirty words to pass the validation. One two three four five six seven eight nine ten. Eleven twelve thirteen fourteen fifteen sixteen seventeen eighteen nineteen twenty. Twenty one twenty two twenty three twenty four twenty five twenty six twenty seven twenty eight twenty nine thirty.',
    'solution' => 'This is a test proposed solution that needs to have more than thirty words to pass the validation. One two three four five six seven eight nine ten. Eleven twelve thirteen fourteen fifteen sixteen seventeen eighteen nineteen twenty. Twenty one twenty two twenty three twenty four twenty five twenty six twenty seven twenty eight twenty nine thirty.',
    'functions' => 'This is a test system functions that needs to have more than twenty words. One two three four five six seven eight nine ten. Eleven twelve thirteen fourteen fifteen sixteen seventeen eighteen nineteen twenty.',
    'objectives' => 'This is a test project objectives that needs to have more than twenty words. One two three four five six seven eight nine ten. Eleven twelve thirteen fourteen fifteen sixteen seventeen eighteen nineteen twenty.',
    'tags' => 'tag1, tag2, tag3',
    'tech' => 'PHP, Laravel, Vue'
]);

$request->setUserResolver(function() use ($studentUser) { return $studentUser; });

$controller = app(\App\Http\Controllers\Student\StudentProposalController::class);

try {
    $formRequest = \App\Http\Requests\StoreProposalRequest::createFrom($request);
    $formRequest->setContainer(app());
    $formRequest->setRedirector(app(\Illuminate\Routing\Redirector::class));
    $formRequest->validateResolved();

    $response = $controller->store($formRequest);
    $content = json_decode($response->getContent(), true);
    echo "Store Success: " . json_encode($content) . "\n";

    $proposalId = $content['proposal']['id'];
    $proposal = \App\Models\Proposal::find($proposalId);

    // Submit
    $submitRequest = \Illuminate\Http\Request::create("/student/proposals/{$proposalId}/submit", 'PUT');
    $submitRequest->setUserResolver(function() use ($studentUser) { return $studentUser; });
    $submitResponse = $controller->submit($submitRequest, $proposal);
    echo "Submit Success: " . $submitResponse->getContent() . "\n";

} catch (\Illuminate\Validation\ValidationException $e) {
    echo "Validation Failed: " . json_encode($e->errors()) . "\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
