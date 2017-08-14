<?php
namespace jasonwynn10\PermMgr\event;

use pocketmine\event\plugin\PluginEvent;
use pocketmine\plugin\Plugin;

class GroupChangeEvent extends PluginEvent {
	public static $handlerList = null;

	/** @var string $oldGroup */
	protected $oldGroup;

	/** @var string $newGroup */
	protected $newGroup;

	/**
	 * GroupChangeEvent constructor.
	 *
	 * @param Plugin $plugin
	 * @param string $oldGroup
	 * @param string $newGroup
	 */
	public function __construct(Plugin $plugin, string $oldGroup, string $newGroup) {
		parent::__construct($plugin);
		$this->oldGroup = $oldGroup;
		$this->newGroup = $newGroup;
	}

	public function getNewGroup() : string {
		return $this->newGroup;
	}

	public function setNewGroup(string $group) {
		$this->newGroup = $group;
	}

	public function getOldGroup() : string {
		return $this->oldGroup;
	}
}