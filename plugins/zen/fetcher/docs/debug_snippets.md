```php
$stream = fetcher()->stream('test.stream');
$stream->addHandler('Zen.Fetcher.Classes.Api.Debug.Playground.testBatch');
$stream->streamRun();
$stream->run();
```

