<?php namespace Zen\Reviews\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class AddIsPublishedToZenReviewsReviews extends Migration
{
    public function up()
    {
        Schema::table('zen_reviews_reviews', function ($table) {
            $table->boolean('is_published')->default(true)->after('phone');
        });
    }

    public function down()
    {
        Schema::table('zen_reviews_reviews', function ($table) {
            $table->dropColumn('is_published');
        });
    }
}
