<?php namespace Zen\Om\Console;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;

class Urlcache extends Command
{
    /**
     * @var string The console command name.
     */
    protected $name = 'om:urlcache';

    /**
     * @var string The console command description.
     */
    protected $description = 'No description provided yet...';

    /**
     * Execute the console command.
     * @return void
     */
    public function handle()
    {
            $categories = \Zen\Om\Models\Category::get();
            echo "BEGIN\n";
            foreach ($categories as $category){
                $url = $category->url();
                \Zen\Om\Models\Category::where('id', '=', $category->id)
                    ->update([
                        'url_cache' => $url,
                    ]);
                echo "Update category {$category->name} url: $url\r";
            }
            echo "\nOK\n";

            $items = \Zen\Om\Models\Item::get();
            echo "Items: {$items->count()}\n";

            foreach ($items as $item){
                $url = $item->url();
                \Zen\Om\Models\Item::where('id', '=', $item->id)
                    ->update([
                        'url_cache' => $url,
                    ]);
                echo "Update item {$item->name} url: $url\r";
            }
            echo "\nOK\n";

        echo "END.\n";
        //$this->output->writeln('Hello world!');
    }
    public function updateItem($id, $url)
    {
        \Zen\Om\Models\Item::where('id', '=', $id)
            ->update([
                'url_cache' => $url,
            ]);
    }

    /**
     * Get the console command arguments.
     * @return array
     */
    protected function getArguments()
    {
        return [];
    }

    /**
     * Get the console command options.
     * @return array
     */
    protected function getOptions()
    {
        return [];
    }
}
