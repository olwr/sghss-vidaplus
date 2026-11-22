<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
// use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        Schema::table(config('activitylog.table_name', 'activity_log'), function (Blueprint $t) {
            $t->string('ip_address', 45)->nullable();
            $t->string('user_agent')->nullable();
            $t->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::table(config('activitylog.table_name', 'activity_log'), function (Blueprint $t) {
            $t->dropIndex(['created_at']);
            $t->dropColumn(['ip_address', 'user_agent']);
        });
    }
};
