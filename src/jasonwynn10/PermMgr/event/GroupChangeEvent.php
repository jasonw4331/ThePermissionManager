<?php
declare(strict_types=1);
namespace jasonwynn10\PermMgr\event;

use pocketmine\event\Cancellable;
use pocketmine\event\plugin\PluginEvent;
use pocketmine\IPlayer;
use pocketmine\plugin\Plugin;

class GroupChangeEvent extends PluginEvent implements Cancellable {
	public static $handlerList = null;

	/** @var array $oldGroup */
	protected $oldGroups;

	/** @var array $newGroup */
	protected $newGroups;

	/** @var IPlayer $player */
	protected $player;

	/**
	 * GroupChangeEvent constructor.
	 *
	 * @param Plugin $plugin
	 * @param IPlayer $player
	 * @param array $oldGroups
	 * @param array $newGroups
	 */
	public function __construct(Plugin $plugin, IPlayer $player, array $oldGroups, array $newGroups) {
		parent::__construct($plugin);
		$this->oldGroups = $oldGroups;
		$this->newGroups = $newGroups;
		$this->player = $player;
	}

	/**
	 * @return array
	 */
	public function getNewGroups() : array {
		return $this->newGroups;
	}

	/**
	 * @param array $groups
	 */
	public function setNewGroups(array $groups) : void {
		$this->newGroups = $groups;
	}

	public function addNewGroups(array $groups) : void {
		$this->newGroups = array_unique(array_merge($this->newGroups, $groups));
	}

	/**
	 * @return array
	 */
	public function getOldGroups() : array {
		return $this->oldGroups;
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
	public function setPlayer(IPlayer $player) : void {
		$this->player = $player;
	}
}