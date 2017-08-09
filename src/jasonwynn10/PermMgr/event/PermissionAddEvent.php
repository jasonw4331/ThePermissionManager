<?php
namespace jasonwynn10\PermMgr\event;

use pocketmine\event\Cancellable;

class PermissionAddEvent extends PermissionEvent implements Cancellable {
	public static $handlerList = null;
}