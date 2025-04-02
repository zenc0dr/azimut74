<?php namespace Zen\Worker\Controllers\EventController;

use View;

echo View::make('zen.worker::event.data', ['event' => $model])->render();
