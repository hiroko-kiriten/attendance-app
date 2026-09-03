<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Auth;
use App\Models\AttendanceCorrectionRequest;

class ApplicationController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        $applications = AttendanceCorrectionRequest::with('attendanceRecord')
            ->where('user_id', $user->id)
            ->get();

        $formattedApplications = $applications->map(function ($application) {
            return [
                'id' => $application->id,
                'approval_status' => $application->approval_status,
                'date' => $application->new_date->format('Y/m/d'),
                'comment' => $application->comment,
                'application_date' => $application->application_date,
            ];
        });

        return view('user.user-application-list', compact(
            'formattedApplications',
            'user'
        ));
    }
}