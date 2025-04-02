    <?php

    function menuToJson($items)
    {
        $items = cleanItems($items);
        return json_encode($items, 256);
    }

    function cleanItems($items): array
    {
        $filtered_items = [];
        foreach ($items as $item) {
            if ($item->viewBag['isHidden'] == 1) {
                continue;
            }

            if ($item->items) {
                $item->items = cleanItems($item->items);
            }

            $new_item = [
                'title' => $item->title
            ];

            if ($item->url !== 'dropdown') {
                $new_item['url'] = $item->url;
            }

            if ($item->items) {
                $new_item['items'] = $item->items;
            }

            $filtered_items[] = $new_item;
        }

        return $filtered_items;
    }
