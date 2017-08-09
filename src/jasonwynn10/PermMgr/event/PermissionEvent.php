<?php
namespace jasonwynn10\PermMgr\event;

use jasonwynn10\PermMgr\ThePermissionManager;

use pocketmine\event\plugin\PluginEvent;
use pocketmine\permission\Permission;

class PermissionEvent extends PluginEvent {
	/** @var Permission|null $permission */
	protected $permission = null;

	/**
	 * PermissionEvent constructor.
	 *
	 * @param ThePermissionManager $plugin
	 * @param Permission $permission
	 */
	public function __construct(ThePermissionManager $plugin, Permission $permission = null) {
		parent::__construct($plugin);
		$this->permission = $permission;
	}

	public function getPermission() {
		return $this->permission;
	}

	public function setPermission(Permission $permission) {
		$this->permission = $permission;
	}
}