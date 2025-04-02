<?php

namespace Zen\Deprecator\Classes;

use Zen\Deprecator\Models\Rule;
use Zen\Deprecator\Models\Attempt;

class Deprecator
{
    private string $signature;
    private ?Attempt $attempt;
    private string $code;

    protected static ?self $instance = null;

    public static function getInstance(): self
    {
        return self::$instance ?? self::$instance = new self();
    }

    public function code(string $code = null): Deprecator
    {
        if ($code) {
            $this->code = $code;
        }
        return $this;
    }

    public function catch(array $array)
    {
        $rule = Rule::query()->where('code', $this->code)->first();
        if (!$rule) {
            return;
        }

        $this->signature = md5(serialize($array) . $this->code);

        $this->attempt = Attempt::query()
            ->where('signature', $this->signature)
            ->first();

        if (!$this->attempt) {
            return;
        }

        $compare = now()->subMinutes($rule->ttl);

        if ($compare > $this->attempt->time) {
            return;
        }

        $diff = $this->attempt->time->diffInMinutes($compare);
        $diff += 1;

        $answer = str_replace('#TTL#', $rule->ttl, $rule->answer);
        $answer = str_replace('#DIFF#', $diff, $answer);

        echo $answer;
        exit;
    }

    public function save(): void
    {
        if ($this->attempt) {
            $this->attempt->update([
                'time' => now()
            ]);
        } else {
            Attempt::create([
                'signature' => $this->signature,
                'time' => now()
            ]);
        }
    }
}
