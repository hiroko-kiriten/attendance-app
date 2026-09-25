<?php

namespace App\Http\Controllers\Api\V1;

// 基底Controllerを使用するため読み込む
use App\Http\Controllers\Controller;
// 勤怠一覧検索用のFormRequestを読み込む
use App\Http\Requests\Api\V1\IndexAttendanceRecordRequest;
// 勤怠登録用のFormRequestを読み込む
use App\Http\Requests\Api\V1\StoreAttendanceRecordRequest;
// 勤怠更新用のFormRequestを読み込む
use App\Http\Requests\Api\V1\UpdateAttendanceRecordRequest;
// 勤怠APIのResourceを読み込む
use App\Http\Resources\Api\V1\AttendanceRecordResource;
// 勤怠モデルを読み込む
use App\Models\AttendanceRecord;

class AttendanceRecordController extends Controller
{
    /**
     * 勤怠一覧を取得する
     */
    public function index(IndexAttendanceRecordRequest $request)
    {
        // 勤怠情報を取得するためのクエリを作成する
        $query = AttendanceRecord::with('user');

        // user_idが指定されている場合は、そのユーザーの勤怠だけに絞り込む
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // dateが指定されている場合は、その日付の勤怠だけに絞り込む
        if ($request->filled('date')) {
            $query->whereDate('date', $request->date);
        }

        // monthが指定されている場合は、その月の勤怠だけに絞り込む
        if ($request->filled('month')) {
            $query->whereBetween('date', [
                $request->month.'-01',
                date('Y-m-t', strtotime($request->month.'-01')),
            ]);
        }

        // 1ページあたりの件数を取得する（指定がなければ20件）
        $perPage = $request->input('per_page', 20);

        // 日付の新しい順に並べてページネーションする
        $attendanceRecords = $query
            ->latest('date')
            ->paginate($perPage);

        // API Resourceを使ってJSON形式で返す
        return AttendanceRecordResource::collection($attendanceRecords);
    }

    /**
     * 勤怠詳細を取得する
     */
    public function show(AttendanceRecord $attendanceRecord)
    {
        // この勤怠を閲覧する権限があるか確認する
        $this->authorize('view', $attendanceRecord);

        // 関連するユーザー・休憩・修正申請をまとめて読み込む
        // 1件取得したモデルに関連データを追加で読み込む場合は load()
        $attendanceRecord->load([
            'user',
            'breaks',
            'applications',
        ]);

        // API Resourceを使ってJSON形式で返す
        return new AttendanceRecordResource($attendanceRecord);
    }

    /**
     * 勤怠を新規登録する
     */
    public function store(StoreAttendanceRecordRequest $request)
    {
        // 勤怠を登録する権限があるか確認する
        $this->authorize('create', AttendanceRecord::class);

        // Formrequestでバリデーション済みのデータだけを取得する
        $data = $request->validated();

        // 認証中のユーザーIDを登録データに追加する
        $data['user_id'] = $request->user()->id;

        // 勤怠データをデータベースに登録する
        $attendanceRecord = AttendanceRecord::create($data);

        // 登録した勤怠にユーザー情報を読み込む
        $attendanceRecord->load('user');

        // 登録した勤怠を201 Createdで返す
        return (new AttendanceRecordResource($attendanceRecord))
            ->response()
            ->setStatusCode(201);
    }

    /**
     * 勤怠を更新する
     */
    public function update(
        UpdateAttendanceRecordRequest $request,
        AttendanceRecord $attendanceRecord
    ) {
        // この勤怠を更新する権限があるか確認する
        $this->authorize('update', $attendanceRecord);

        // バリデーション済みの更新データだけを取得する
        $data = $request->validated();

        // 勤怠データを更新する
        $attendanceRecord->update($data);

        // 更新後のユーザー情報を読み込む
        $attendanceRecord->load('user');

        // 更新した勤怠をAPI Resourceで返す
        return new AttendanceRecordResource($attendanceRecord);
    }

    /**
     * 勤怠を削除する
     */
    public function destroy(AttendanceRecord $attendanceRecord)
    {
        // この勤怠を削除する権限があるか確認する
        $this->authorize('delete', $attendanceRecord);

        // 勤怠データを削除する
        $attendanceRecord->delete();

        // 削除成功を204 No Contentで返す
        return response()->noContent();
    }
}
