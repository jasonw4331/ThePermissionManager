<?php
namespace jasonwynn10\PermMgr\event;

use jasonwynn10\PermMgr\ThePermissionManager;

use pocketmine\event\plugin\PluginEvent;
use pocketmine\permission\Permission;

class PermissionEvent extends PluginEvent {
	/** @var Permission|null $permission */
	protected $permission = null;

	/** @var bool $group */
	protected $group = false;
	/**
	 * PermissionEvent constructor.
	 *
	 * @param ThePermissionManager $plugin
	 * @param Permission $permission
	 */
	public function __construct(ThePermissionManager $plugin, Permission $permission = null, bool $group = false) {
		parent::__construct($plugin);
		$this->permission = $permission;
		$this->group = $group;
	}

	public function getPermission() {
		return $this->permission;
	}

	public function setPermission(Permission $permission) {
		$this->permission = $permission;
	}

	public function isGroup() : bool {
		return $this->group;
	}

	public function setGroup(bool $group = true) {
		$this->group = $group;
	}
}