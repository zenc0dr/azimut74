<?php
$data = $model->data;
$data['url'] = env('APP_URL') . '/console/zen/grouptours/orders/update/' . $model->id;
$data['time'] = $model->created_at->format('d.m.Y H:i');
echo View::make('zen.grouptours::order', $data)->render();
