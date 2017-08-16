<?php
namespace jasonwynn10\PermMgr\event;

use pocketmine\event\plugin\PluginEvent;
use pocketmine\Player;
use pocketmine\plugin\Plugin;

class GroupChangeEvent extends PluginEvent {
	public static $handlerList = null;

	/** @var string $oldGroup */
	protected $oldGroup;

	/** @var string $newGroup */
	protected $newGroup;

	/** @var Player $player */
	protected $player;
	/**
	 * GroupChangeEvent constructor.
	 *
	 * @param Plugin $plugin
	 * @param Player $player
	 * @param string $oldGroup
	 * @param string $newGroup
	 */
	public function __construct(Plugin $plugin, Player $player, string $oldGroup, string $newGroup) {
		parent::__construct($plugin);
		$this->oldGroup = $oldGroup;
		$this->newGroup = $newGroup;
		$this->player = $player;
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

	public function getPlayer() : Player {
		return $this->player;
	}

	public function setPlayer(Player $player) {
		$this->player = $player;
	}
}