<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('project_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('role', ['owner', 'member'])->default('member');
            $table->enum('status', ['active', 'pending'])->default('pending');
            $table->string('invite_token', 64)->nullable()->unique();
            $table->timestamp('invited_at')->nullable();
            $table->timestamp('joined_at')->nullable();
            $table->timestamps();

            $table->unique(['project_id', 'user_id']);
        });

        // Migrate existing projects: insert owner as member
        $projects = \DB::table('projects')->get();
        foreach ($projects as $project) {
            \DB::table('project_members')->insert([
                'project_id' => $project->id,
                'user_id'    => $project->user_id,
                'role'       => 'owner',
                'status'     => 'active',
                'joined_at'  => $project->created_at,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('project_members');
    }
};
