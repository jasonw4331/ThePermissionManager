<?php
namespace jasonwynn10\PermMgr\event;

use pocketmine\event\Cancellable;
use pocketmine\event\plugin\PluginEvent;
use pocketmine\IPlayer;
use pocketmine\plugin\Plugin;

class GroupChangeEvent extends PluginEvent implements Cancellable {
	public static $handlerList = null;

	/** @var string $oldGroup */
	protected $oldGroup;

	/** @var string $newGroup */
	protected $newGroup;

	/** @var IPlayer $player */
	protected $player;

	/**
	 * GroupChangeEvent constructor.
	 *
	 * @param Plugin $plugin
	 * @param IPlayer $player
	 * @param string $oldGroup
	 * @param string $newGroup
	 */
	public function __construct(Plugin $plugin, IPlayer $player, string $oldGroup, string $newGroup) {
		parent::__construct($plugin);
		$this->oldGroup = $oldGroup;
		$this->newGroup = $newGroup;
		$this->player = $player;
	}

	/**
	 * @return string
	 */
	public function getNewGroup() : string {
		return $this->newGroup;
	}

	/**
	 * @param string $group
	 */
	public function setNewGroup(string $group) {
		$this->newGroup = $group;
	}

	/**
	 * @return string
	 */
	public function getOldGroup() : string {
		return $this->oldGroup;
	}

	/**
	 * @return IPlayer
	 */
	public function getPlayer() : IPlayer {
		return $this->player;
	}

	/**
	 * @param IPlayer $player
	 */
	public function setPlayer(IPlayer $player) {
		$this->player = $player;
	}
}