<?php

namespace Database\Factories;

use App\Models\AttendanceRecord;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AttendanceCorrectionRequest>
 */
class AttendanceCorrectionRequestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'attendance_record_id' => AttendanceRecord::factory(),
            'user_id' => User::factory(),
            'new_clock_in' => '09:00:00',
            'new_clock_out' => '18:00:00',
            'comment' => fake()->sentence(),
            'approval_status' => '承認待ち',
            'new_date' => fake()->date(),
            'application_date' => fake()->date(),
        ];
    }
}