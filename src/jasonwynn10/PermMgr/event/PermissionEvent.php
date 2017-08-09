<?php
namespace jasonwynn10\PermMgr\event;

use jasonwynn10\PermMgr\ThePermissionManager;

use pocketmine\event\plugin\PluginEvent;
use pocketmine\permission\Permission;

class PermissionEvent extends PluginEvent {
	protected $permission;

	/**
	 * PermissionEvent constructor.
	 *
	 * @param ThePermissionManager $plugin
	 * @param Permission $permission
	 */
	public function __construct(ThePermissionManager $plugin, Permission $permission) {
		parent::__construct($plugin);
		$this->permission = $permission;
	}

	public function getPermission() : Permission {
		return $this->permission;
	}

	public function setPermission(Permission $permission) {
		$this->permission = $permission;
	}
}