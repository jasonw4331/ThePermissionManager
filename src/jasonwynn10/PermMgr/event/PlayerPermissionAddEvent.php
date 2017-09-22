<?php
declare(strict_types=1);
namespace jasonwynn10\PermMgr\event;

use pocketmine\event\Cancellable;

class PlayerPermissionAddEvent extends PlayerPermissionEvent implements Cancellable {
	public static $handlerList = null;
}