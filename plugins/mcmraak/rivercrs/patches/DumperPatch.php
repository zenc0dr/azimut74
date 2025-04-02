<?php

namespace Mcmraak\Rivercrs\Patches;

class DumperPatch
{
    protected static ?self $instance = null;
    private string $path = 'cabins_decks.dump';
    private array $dump;

    public function __construct()
    {
        $this->path = storage_path($this->path);
        $this->dump = $this->getDump();
    }

//    public function __destruct()
//    {
//        $this->saveDump();
//    }

    # Singleton pattern
    public static function getInstance(): self
    {
        return self::$instance ?? self::$instance = new self();
    }

    private function getDump()
    {
        if (!file_exists($this->path)) {
            return [];
        }
        return unserialize(
            file_get_contents($this->path)
        );
    }

    private function saveDump()
    {
        file_put_contents(
            $this->path,
            serialize($this->dump)
        );
    }

    public function set(string $key, $value)
    {
        $this->dump[$key] = $value;
        $this->saveDump();
    }

    public function get(string $key)
    {
        return $this->dump[$key] ?? null;
    }

    public function garbageCollector()
    {
        $new_dump = [];
        foreach ($this->dump as $key => $value) {
            $ids = explode('.', $key);
            $ch = $ids[0];
            $de = $ids[1];
            $ca = $ids[2];
            $doubles = preg_grep("/$ch\.\b(?:(?!$de)\w)+\b\.$ca/", array_keys($this->dump));
            if (count($doubles)) {
                $text = join(' ', $doubles);
                echo "Добавлен дублёр $key > $text\n";
                $new_dump[$key] = $value;
            }
        }
        $diff = count($this->dump) - count($new_dump);

        echo "Удалено $diff\n";
        $this->dump = $new_dump;
        $this->saveDump();
    }

    public function count()
    {
        return count($this->getDump());
    }

    public function search(string $key)
    {
        $dump = $this->getDump();
        $key = str_replace('.', '\.', $key);
        $key = str_replace('*', '\d+', $key);
        $keys = preg_grep("/$key/", array_keys($dump));
        foreach ($keys as $key) {
            echo $key . ': ' . $dump[$key] . "\n";
        }
    }
}
