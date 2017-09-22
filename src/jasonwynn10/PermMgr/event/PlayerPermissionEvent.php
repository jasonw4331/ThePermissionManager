<?php
declare(strict_types=1);
namespace jasonwynn10\PermMgr\event;

use jasonwynn10\PermMgr\ThePermissionManager;

use pocketmine\IPlayer;
use pocketmine\permission\Permission;

class PlayerPermissionEvent extends PermissionEvent {
	/** @var IPlayer $player */
	protected $player;
	/**
	 * PlayerPermissionEvent constructor.
	 *
	 * @param ThePermissionManager $plugin
	 * @param IPlayer $player
	 * @param Permission|null $permission
	 * @param string $levelName
	 * @param bool $isGroup
	 */
	public function __construct(ThePermissionManager $plugin, IPlayer $player, Permission $permission = null, string $levelName = "", bool $isGroup = false) {
		parent::__construct($plugin, $permission, $levelName, $isGroup);
		$this->player = $player;
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