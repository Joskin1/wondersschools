<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasTable('class_groups')) {
            Schema::create('class_groups', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->text('description')->nullable();
                $table->unsignedInteger('order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();

                $table->index('is_active');
                $table->index('order');
            });
        }

        if (Schema::hasTable('classrooms') && !Schema::hasColumn('classrooms', 'class_group_id')) {
            Schema::table('classrooms', function (Blueprint $table) {
                $table->foreignId('class_group_id')
                    ->nullable()
                    ->after('id')
                    ->constrained('class_groups')
                    ->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('classrooms') && Schema::hasColumn('classrooms', 'class_group_id')) {
            Schema::table('classrooms', function (Blueprint $table) {
                $table->dropForeign(['class_group_id']);
                $table->dropColumn('class_group_id');
            });
        }

        Schema::dropIfExists('class_groups');
    }
};
