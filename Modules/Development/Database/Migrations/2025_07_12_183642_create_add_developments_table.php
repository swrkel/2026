<?php

use App\User;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateAddDevelopmentsTable extends Migration
{
    public function up()
    {
        Schema::create('add_developments', function (Blueprint $table) {
            $table->id();
            $table->timestamp('datetime')->useCurrent();
            $table->string('doc_no')->unique();

            $table->unsignedInteger('user_id');
            $table->foreign('user_id')->references('id')->on('users');

            $table->string('task_heading');
            $table->enum('type', ['Task', 'Issue']);
            $table->foreignId('development_module_id')->constrained();
            $table->text('details');
            $table->string('related_doc_no')->nullable(); // Only used for Task
            $table->enum('priority', ['Urgent', 'Priority', 'Normal']);
            $table->json('visible_to_groups');
            $table->json('group_comments')->nullable();
            $table->enum('status', ['Pending', 'Not Completed', 'Completed'])->default('Pending');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('add_developments');
    }
}
