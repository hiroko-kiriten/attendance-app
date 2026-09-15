<?php
// このPolicyがApp\Policies名前空間に属することを指定する
namespace App\Policies;

// 勤怠記録モデルを読み込む
use App\Models\AttendanceRecord;

// ユーザーモデルを読み込む
use App\Models\User;

// 勤怠記録の認可処理を担当するPolicy
class AttendanceRecordPolicy
{
    /**
     * 勤怠一覧を閲覧できるか確認する
     */
    public function viewAny(User $user): bool
    {
        // 認証済みユーザーは一覧APIを利用できる
        return true;
    }

    /**
     * 勤怠詳細を閲覧できるか確認する
     */
    public function view(User $user, AttendanceRecord $attendanceRecord): bool
    {
        // ログイン中のユーザーと勤怠の所有者が同じなら許可する
        return $user->id === $attendanceRecord->user_id;
    }

    /**
     * 勤怠を登録できるか確認する
     */
    public function create(User $user): bool
    {
        // 認証済みユーザーは勤怠を登録できる
        return true;
    }

    /**
     * 勤怠を更新できるか確認する
     */
    public function update(User $user, AttendanceRecord $attendanceRecord): bool
    {
        // ログイン中のユーザーと勤怠の所有者が同じなら許可する
        return $user->id === $attendanceRecord->user_id;
    }

    /**
     * 勤怠を削除できるか確認する
     */
    public function delete(User $user, AttendanceRecord $attendanceRecord): bool
    {
        // ログイン中のユーザーと勤怠の所有者が同じなら許可する
        return $user->id === $attendanceRecord->user_id;
    }

    /**
     * 勤怠を復元できるか確認する
     */
    public function restore(User $user, AttendanceRecord $attendanceRecord): bool
    {
        // 今回のAPIでは勤怠の復元機能を使用しない
        //
    }

    /**
     * 勤怠を完全削除できるか確認する
     */
    public function forceDelete(User $user, AttendanceRecord $attendanceRecord): bool
    {
        // 今回のAPIでは完全削除機能を使用しない
        //
    }
}