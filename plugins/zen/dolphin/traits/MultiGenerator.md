# MultiGenerator
Система для мультивыбора

### Create vars
```
public
    $ModelDump,
```

### afterSave
```php
function afterSave()
    {
        $generator_options = [
            [
                'model' => 'Model',
                'pivot' => 'model_pivot',
                'key' => 'other_key_id'
            ],
        ];

        $this->saveMultiGenerator($generator_options);
    }
```
