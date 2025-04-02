#Grinder
Управление изображениями
### Примеры
```php
# $path может быть путём или url
$thumb_url = Grinder::open($path)
            ->options([
                'width' => 500,
                'height' => 300,
                'quality' => 70,
                'extension' => 'jpg'
            ])
            ->getThumb();

# Или опции в строку
$thumb_url = Grinder::open($path)
            ->options('w300.h500.q70.ejpg')
            ->getThumb();

# Или цепочкой
$thumb_url = Grinder::open($path)
            ->width(500)
            ->getThumb();

# Массив из превью
$thumbs = Grinder::open($url)
            ->getThumbs([
                'w800', 'w800.ewebp',
                'w500', 'w500.ewebp',
                'w420', 'w420.ewebp',
                'w140', 'w140.ewebp',
            ]);

# Массив из превью с общей настройкой качества
$thumbs = Grinder::open($url)
            ->quality(80) // По умолчанию 70
            ->getThumbs([
                'w800', 'w800.ewebp',
                'w500', 'w500.ewebp',
                'w420', 'w420.ewebp',
                'w140', 'w140.ewebp',
            ]);
```

### $thumbs на выходе
```
array:8 [▼
  "w800" => "http://baby.zen/storage/app/uploads/public/5f4/e1e/764/thumb_128_800_0_0_0_auto.jpg"
  "w800.ewebp" => "http://baby.zen/storage/app/uploads/public/5f4/e1e/764/thumb_128_800_0_0_0_auto.webp"
  "w500" => "http://baby.zen/storage/app/uploads/public/5f4/e1e/764/thumb_128_500_0_0_0_auto.jpg"
  "w500.ewebp" => "http://baby.zen/storage/app/uploads/public/5f4/e1e/764/thumb_128_500_0_0_0_auto.webp"
  "w420" => "http://baby.zen/storage/app/uploads/public/5f4/e1e/764/thumb_128_420_0_0_0_auto.jpg"
  "w420.ewebp" => "http://baby.zen/storage/app/uploads/public/5f4/e1e/764/thumb_128_420_0_0_0_auto.webp"
  "w140" => "http://baby.zen/storage/app/uploads/public/5f4/e1e/764/thumb_128_140_0_0_0_auto.jpg"
  "w140.ewebp" => "http://baby.zen/storage/app/uploads/public/5f4/e1e/764/thumb_128_140_0_0_0_auto.webp"
]
```
