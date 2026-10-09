<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 状態(SubmissionStatus)にない 'pending' が既定値になっていたので、最初の状態 'received' にする。
        // 受付は SubmissionStateMachine::open() が状態を明示して作るが、直接 INSERT しても不正な値が入らないように
        DB::statement("ALTER TABLE submissions ALTER COLUMN status SET DEFAULT 'received'");
        DB::table('submissions')->where('status', 'pending')->update(['status' => 'in_review']);
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE submissions ALTER COLUMN status SET DEFAULT 'pending'");
    }
};
