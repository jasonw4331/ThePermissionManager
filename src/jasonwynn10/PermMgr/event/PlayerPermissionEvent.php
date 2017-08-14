<?php
namespace jasonwynn10\PermMgr\event;

use jasonwynn10\PermMgr\ThePermissionManager;
use pocketmine\permission\Permission;
use pocketmine\Player;

class PlayerPermissionEvent extends PermissionEvent {
	protected $player;
	/**
	 * PlayerPermissionEvent constructor.
	 *
	 * @param ThePermissionManager $plugin
	 * @param Player $player
	 * @param Permission|null $permission
	 * @param bool $isGroup
	 */
	public function __construct(ThePermissionManager $plugin, Player $player, Permission $permission = null, $isGroup = false){
		parent::__construct($plugin, $permission, $isGroup);
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
	public function setPlayer(Player $player) {
		$this->player = $player;
	}
}