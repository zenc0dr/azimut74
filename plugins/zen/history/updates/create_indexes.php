<?php namespace Zen\History\Updates;

use Schema;
use October\Rain\Database\Updates\Migration;

class CreateIndexes extends Migration
{
    public function up()
    {
        Schema::table('zen_history_links', function ($table)
        {
            // Индекс на visiter_id (ускоряет поиск по нему)
            $table->index('visiter_id');

            // Индекс на type_id (ускоряет фильтрацию по типу)
            $table->index('type_id');

            // Индекс на created_at (ускоряет сортировку по дате)
            $table->index('created_at');

            // Композитный индекс для ускорения выборок с несколькими условиями
            $table->index(['visiter_id', 'type_id', 'is_delete'], 'idx_visiter_type_deleted');
        });
    }

    public function down()
    {
        Schema::table('zen_history_links', function ($table)
        {
            $table->dropIndex('visiter_id');
            $table->dropIndex('type_id');
            $table->dropIndex('created_at');
            $table->dropIndex('idx_visiter_type_deleted');
        });
    }
}