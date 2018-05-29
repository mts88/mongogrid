<?php

namespace Mts88\MongoGrid\Facades;

use \Illuminate\Support\Facades\Facade;

class MongoGrid extends Facade {

    protected static function getFacadeAccessor() {
		return 'Mts88\MongoGrid\Services\MongoGrid'; 
	}

}
