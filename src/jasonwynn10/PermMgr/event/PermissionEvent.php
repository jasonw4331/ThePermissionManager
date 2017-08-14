<?php
namespace jasonwynn10\PermMgr\event;

use jasonwynn10\PermMgr\ThePermissionManager;

use pocketmine\event\plugin\PluginEvent;
use pocketmine\permission\Permission;

class PermissionEvent extends PluginEvent {
	/** @var Permission|null $permission */
	protected $permission = null;

	/** @var bool $group */
	protected $isGroup = false;
	/**
	 * PermissionEvent constructor.
	 *
	 * @param ThePermissionManager $plugin
	 * @param Permission $permission
	 * @param bool $isGroup
	 */
	public function __construct(ThePermissionManager $plugin, Permission $permission = null, bool $isGroup = false) {
		parent::__construct($plugin);
		$this->permission = $permission;
		$this->isGroup = $isGroup;
	}

	public function getPermission() {
		return $this->permission;
	}

	public function setPermission(Permission $permission) {
		$this->permission = $permission;
	}

	public function isGroup() : bool {
		return $this->isGroup;
	}

	public function setIsGroup(bool $isGroup = true) {
		$this->isGroup = $isGroup;
	}
}