<?php
namespace jasonwynn10\PermMgr\providers;

use jasonwynn10\PermMgr\ThePermissionManager;

use pocketmine\IPlayer;
use pocketmine\utils\Config;

abstract class DataProvider {
	/** @var ThePermissionManager $plugin */
	protected $plugin;

	/**
	 * DataProvider constructor.
	 *
	 * @param ThePermissionManager $plugin
	 */
	public function __construct(ThePermissionManager $plugin) {
		$this->plugin = $plugin;
	}

	/**
	 * @param IPlayer $player
	 *
	 * @return void
	 */
	abstract function init(IPlayer $player);

	/**
	 * @param IPlayer $player
	 *
	 * @return string[]
	 */
	abstract function getPlayerPermissions(IPlayer $player) : array;

	/**
	 * @param IPlayer $player
	 *
	 * @return string[]
	 */
	public function getAllPlayerPermissions(IPlayer $player) : array {
		$playerPerms = array_merge($this->getPlayerPermissions($player), $this->plugin->getGroups()->getAllGroupPermissions($this->getGroup($player)));
		return array_unique($playerPerms);
	}

	/**
	 * @param IPlayer $player
	 * @param string[] $permissions
	 *
	 * @return bool
	 */
	abstract function setPlayerPermissions(IPlayer $player, array $permissions) : bool;

	/**
	 * @param IPlayer $player
	 * @param string[] $permissions
	 *
	 * @return bool
	 */
	public function addPlayerPermissions(IPlayer $player, array $permissions = []) : bool {
		$perms = $this->getPlayerPermissions($player);
		$perms = array_merge($perms, $permissions);
		return $this->setPlayerPermissions($player, $perms);
	}

	/**
	 * @param IPlayer $player
	 * @param string[] $permissions
	 *
	 * @return bool
	 */
	public function removePlayerPermissions(IPlayer $player, array $permissions = []) : bool {
		$perms = $this->getPlayerPermissions($player);
		foreach($permissions as $permission) {
			if(($key = array_search($permission, $this->getPlayerPermissions($player))) !== false) {
				unset($perms[$key]);
			}
		}
		return $this->setPlayerPermissions($player, $perms);
	}

	/**
	 * @param IPlayer $player
	 *
	 * @return bool
	 */
	public function sortPlayerPermissions(IPlayer $player) : bool {
		$permissions = $this->getPlayerPermissions($player);
		$permissions = array_unique($permissions);
		sort($permissions, SORT_NATURAL | SORT_FLAG_CASE);
		return $this->setPlayerPermissions($player, $permissions);
	}

	/**
	 * @param IPlayer $player
	 *
	 * @throws \BadMethodCallException
	 * @return Config
	 */
	abstract function getPlayerConfig(IPlayer $player)  : Config;

	/**
	 * @param IPlayer $player
	 *
	 * @return string
	 */
	abstract function getGroup(IPlayer $player) : string;
}