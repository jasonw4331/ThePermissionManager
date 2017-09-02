<?php
namespace jasonwynn10\PermMgr\event;

use jasonwynn10\PermMgr\ThePermissionManager;

use pocketmine\permission\Permission;

class GroupPermissionEvent extends PermissionEvent {
	/** @var string $group */
	protected $group;
	/**
	 * GroupPermissionEvent constructor.
	 *
	 * @param ThePermissionManager $plugin
	 * @param string $group
	 * @param Permission|null $permission
	 * @param string $levelName
	 */
	public function __construct(ThePermissionManager $plugin, string $group, Permission $permission = null, string $levelName = "") {
		parent::__construct($plugin, $permission, $levelName, true);
		$this->group = $group;
	}

	/**
	 * @return string
	 */
	public function getGroup() : string {
		return $this->group;
	}

	/**
	 * @param string $group
	 */
	public function setGroup(string $group) {
		$this->group = $group;
	}
}