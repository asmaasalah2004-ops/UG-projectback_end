<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_courses', function (Blueprint $table) {
            $table->integer('grade')->nullable()->change();
            $table->string('status')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('student_courses', function (Blueprint $table) {
            $table->integer('grade')->nullable(false)->change();
            $table->string('status')->nullable(false)->change();
        });
    }
};