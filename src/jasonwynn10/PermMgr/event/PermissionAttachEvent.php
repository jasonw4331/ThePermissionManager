<?php
declare(strict_types=1);
namespace jasonwynn10\PermMgr\event;

use jasonwynn10\PermMgr\ThePermissionManager;

use pocketmine\event\plugin\PluginEvent;
use pocketmine\Player;

class PermissionAttachEvent extends PluginEvent {
	public static $handlerList = null;
	/** @var Player $player */
	private $player;

	/**
	 * PermissionAttachEvent constructor.
	 *
	 * @param ThePermissionManager $plugin
	 * @param Player $player
	 */
	public function __construct(ThePermissionManager $plugin, Player $player) {
		parent::__construct($plugin);
		$this->player = $player;
	}

	/**
	 * @return Player
	 */
	public function getPlayer() : Player {
		return $this->player;
	}

	/**
	 * @param Player $player
	 */
	public function setPlayer(Player $player) : void {
		$this->player = $player;
	}
}