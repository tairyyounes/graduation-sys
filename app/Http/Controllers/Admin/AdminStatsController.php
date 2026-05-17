<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Proposal;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class AdminStatsController extends Controller
{
    public function index(): JsonResponse
    {
        $totalProposals = Proposal::count();
        $totalUsers = User::count();
        $totalDepartments = Department::count();
        $semanticAccuracy = '94%'; // Simulated

        $cards = [
            ['title' => 'Proposals analyzed', 'value' => (string) $totalProposals],
            ['title' => 'User management', 'value' => (string) $totalUsers],
            ['title' => 'Departments', 'value' => (string) $totalDepartments],
            ['title' => 'Semantic accuracy', 'value' => $semanticAccuracy],
        ];

        // Mock bar chart for last 6 months, in a real app query by created_at grouped by month
        $submissionsBars = [
            ['month' => 'Jan', 'height' => 35],
            ['month' => 'Feb', 'height' => 55],
            ['month' => 'Mar', 'height' => 68],
            ['month' => 'Apr', 'height' => 90],
            ['month' => 'May', 'height' => 63],
            ['month' => 'Jun', 'height' => 52],
        ];

        return response()->json([
            'overviewCards' => $cards,
            'submissionsBars' => $submissionsBars,
        ]);
    }
}
