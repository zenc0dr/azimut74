<?php namespace Mcmraak\Rivercrs\Classes;

use Config;
use Schema;
use DB;

/*
 * Small Queries SQLite
 *
    # При создании базы указать схему
    $schema = [
            'ships' => [
                'page' => 'integer',
                'eds_id'  => 'integer'
            ]
        ];
  $db = new SQS('filename', $schema);
  $db->model()->where('page', '>', 5)->get();

 *
 * */

class SQS
{
    public $db_path, $db_name, $schema;
    function __construct($db_name, $schema)
    {
        $this->db_name = $db_name;
        $this->schema = $schema;
        //$this->db_path = $db_path; //
        $storage_path = storage_path('rivercrs_sqlite/');

        if(!file_exists($storage_path)) {
            mkdir($storage_path, '0777');
        }

        $file_path = $storage_path.$db_name.'.sqlite';
        $this->db_path = $file_path;

        $this->connectDB();

        if(!file_exists($file_path)) {
            file_put_contents($file_path, '');
            $this->createDB();
        }
    }

    function connectDB()
    {
        Config::set('database.connections.'.$this->db_name, [
            'driver' => 'sqlite',
            'database' => $this->db_path,
            'prefix'   => '',
        ]);
    }

    function createDB()
    {
        foreach ($this->schema as $table_name => $fields) {
            Schema::connection($this->db_name)->create($table_name, function($table) use ($fields) {
                foreach ($fields as $field_name => $field_type) {
                    $table->{$field_type}($field_name)->nullable();
                }
            });
        }
    }

    function cleanDB()
    {
        unlink($this->db_path);
    }

    function model()
    {
        return DB::connection($this->db_name);
    }

}