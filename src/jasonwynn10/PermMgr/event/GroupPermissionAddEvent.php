<?php
namespace jasonwynn10\PermMgr\event;

use pocketmine\event\Cancellable;

class GroupPermissionAddEvent extends GroupPermissionEvent implements Cancellable {
	public static $handlerList = null;
}