<?php namespace Zen\Reviews\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class BuilderTableCreateZenReviewsBindings extends Migration
{
    public function up()
    {
        Schema::create('zen_reviews_bindings', function($table)
        {
            $table->engine = 'InnoDB';
            $table->increments('id')->unsigned();
            $table->integer('review_id')->unsigned();
            $table->string('entity_type', 20);
            $table->integer('entity_id')->unsigned();
            $table->timestamp('created_at')->nullable();
            $table->timestamp('updated_at')->nullable();

            $table->unique('review_id');
            $table->index(['entity_type', 'entity_id']);
            $table->index('entity_type');
            $table->index('entity_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('zen_reviews_bindings');
    }
}