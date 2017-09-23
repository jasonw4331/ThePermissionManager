<?php
declare(strict_types=1);
namespace jasonwynn10\PermMgr\event;

use pocketmine\event\Cancellable;

class PlayerPermissionRemoveEvent extends PlayerPermissionEvent implements Cancellable {
	public static $handlerList = null;
}