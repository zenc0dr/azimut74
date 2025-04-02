<?php namespace Zen\Om\Console;

use Illuminate\Console\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputArgument;
use Zen\Om\Models\Item;

class Itemsseeder extends Command
{
    /**
     * @var string The console command name.
     */
    protected $name = 'om:itemsseeder';

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
        Item::truncate();
        for($i=1; $i<1000; $i++)
        {
            $item = new Item;
            $item->name = "Item $i";
            $item->slug = "item-$i";
            $item->category_id = 1;
            $item->save();
            echo "[$i] Create item: {$item->name}\n";
        }
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
