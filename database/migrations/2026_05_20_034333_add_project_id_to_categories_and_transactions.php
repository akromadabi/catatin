<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\User;
use App\Models\Project;

return new class extends Migration
{
    public function up(): void
    {
        // Add project_id to categories
        Schema::table('categories', function (Blueprint $table) {
            $table->foreignId('project_id')->nullable()->after('user_id')->constrained('projects')->onDelete('cascade');
        });

        // Add project_id to transactions
        Schema::table('transactions', function (Blueprint $table) {
            $table->foreignId('project_id')->nullable()->after('user_id')->constrained('projects')->onDelete('cascade');
        });

        // Migrate existing data: create a default project for each user
        // and assign their existing categories & transactions to it
        User::all()->each(function ($user) {
            $project = Project::create([
                'user_id' => $user->id,
                'name'    => 'Keuangan Pribadi',
                'icon'    => '💰',
                'color'   => '#2563eb',
            ]);

            \DB::table('categories')
                ->where('user_id', $user->id)
                ->whereNull('project_id')
                ->update(['project_id' => $project->id]);

            \DB::table('transactions')
                ->where('user_id', $user->id)
                ->whereNull('project_id')
                ->update(['project_id' => $project->id]);
        });
    }

    public function down(): void
    {
        Schema::table('transactions', function (Blueprint $table) {
            $table->dropForeign(['project_id']);
            $table->dropColumn('project_id');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropForeign(['project_id']);
            $table->dropColumn('project_id');
        });
    }
};
