<?php namespace Zen\Cleaner\Console;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Illuminate\Filesystem\Filesystem;
use DB;
use Storage;

class Clean extends Command
{
    /**
     * @var string The console command name.
     */
    protected $name = 'cleaner:clean';

    /**
     * @var string The console command description.
     */
    protected $description = 'Clean storage';

    /**
     * Execute the console command.
     * @return void
     */
    public function handle()
    {
        $size_count = 0;
        $deleted_count = 0;

        $fs = new Filesystem;
        $uploads_path = storage_path('app/uploads/public');
        if(!file_exists($uploads_path)) return "$uploads_path not exist!\n";
        $directory_list = $fs->directories($uploads_path);
        $dir_c = count($directory_list);
        $dir_i = 0;
        foreach ($directory_list as $dir)
        {
            $file_list = $this->getFileList($dir);
            if(!count($file_list)) {
                $this->delDir($dir);
                //echo "Delete dir: $dir\n";
                continue;
            }

            foreach ($file_list as $file_item)
            {

                $file_path = $file_item['path'];
                $file_size = $file_item['size'];
                $file_name = basename($file_path);

                $test = DB::table('system_files')->where('disk_name', $file_name)->first();

                if(!$test) {
                    unlink($file_path);
                    $mb = $this->formatSizeUnits($size_count);
                    echo "[dir:$dir_i/$dir_c] Delete files: $deleted_count [$mb]           \r";
                    $size_count +=$file_size;
                    $deleted_count++;
                } else {
                    if(!$test->attachment_type) continue;
                    if(!class_exists($test->attachment_type)) continue;
                    $model_item = app($test->attachment_type)->find($test->attachment_id);

                    if(!$model_item) {
                        unlink($file_path);
                        $mb = $this->formatSizeUnits($size_count);
                        echo "[dir:$dir_i/$dir_c] Delete files: $deleted_count [$mb]           \r";
                        $size_count +=$file_size;
                        $deleted_count++;
                        DB::table('system_files')->where('id', $test->id)->delete();
                    }
                }
            }
            $dir_i++;
        }
        $mb = $this->formatSizeUnits($size_count);
        echo "\nDeleted $deleted_count [$mb]\n";
    }

    public function getFileList($dir)
    {
        $fs = new Filesystem;
        $files = $fs->allFiles($dir, 1);
        $file_list = [];
        foreach ($files as $file) {
            $file_path = $file->getRelativePathname();
            $full_file_path = $dir.'/'.$file_path;
            $file_list[] = [
                'path' => $full_file_path,
                'size' => filesize($full_file_path),
            ];
        }
        return $file_list;
    }

    public function delDir($folder) {
        if (is_dir($folder)) {
            $handle = opendir($folder);
            while ($subfile = readdir($handle)) {
                if ($subfile == '.' or $subfile == '..') continue;
                if (is_file($subfile)) unlink("{$folder}/{$subfile}");
                else $this->delDir("{$folder}/{$subfile}");
            }
            closedir($handle);
            rmdir($folder);
        } else
            unlink($folder);
    }

    function formatSizeUnits($bytes)
    {
        if ($bytes >= 1073741824)
        {
            $bytes = number_format($bytes / 1073741824, 2) . ' GB';
        }
        elseif ($bytes >= 1048576)
        {
            $bytes = number_format($bytes / 1048576, 2) . ' MB';
        }
        elseif ($bytes >= 1024)
        {
            $bytes = number_format($bytes / 1024, 2) . ' KB';
        }
        elseif ($bytes > 1)
        {
            $bytes = $bytes . ' bytes';
        }
        elseif ($bytes == 1)
        {
            $bytes = $bytes . ' byte';
        }
        else
        {
            $bytes = '0 bytes';
        }

        return $bytes;
    }

}
