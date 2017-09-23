<?php
declare(strict_types=1);
namespace jasonwynn10\PermMgr\event;

use pocketmine\event\Cancellable;

class GroupPermissionRemoveEvent extends GroupPermissionEvent implements Cancellable {
	public static $handlerList = null;
}