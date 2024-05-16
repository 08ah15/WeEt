<?php

/**
 * MyList Module derived from Example module.
 */

declare(strict_types=1);

namespace MyListNamespace;

//use Fisharebest\Webtrees\Services\LinkedRecordService;

require __DIR__ . '/MyListModule.php';

// This script must return an object that implements ModuleCustomInterface.
// If the module's constructor does not take any parameters, you can simply instantiate it.
//
// If you are using dependency-injection in your module, you would ask webtrees to make the object for you.
// return Webtrees::make(ExampleModule::class);
// For an example, see the server-config module.

//    private LinkedRecordService $linked_record_service;

return new MyListModule();
