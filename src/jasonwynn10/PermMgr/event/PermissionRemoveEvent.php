<?php
namespace jasonwynn10\PermMgr\event;

use pocketmine\event\Cancellable;

class PermissionRemoveEvent extends PermissionEvent implements Cancellable {
	public static $handlerList = null;
}