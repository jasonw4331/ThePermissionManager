<?php
declare(strict_types=1);
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
	abstract function init(IPlayer $player) : void;

	/**
	 * @param IPlayer $player
	 * @param string $levelName
	 *
	 * @return string[]
	 */
	abstract function getPlayerPermissions(IPlayer $player, string $levelName = "") : array;

	/**
	 * @param IPlayer $player
	 * @param string $levelName
	 *
	 * @return string[]
	 */
	public function getAllPlayerPermissions(IPlayer $player, string $levelName = "") : array {
		$playerPerms = $this->getPlayerPermissions($player, $levelName);
		foreach($this->getGroups($player) as $group)
			$playerPerms = array_merge($playerPerms, $this->plugin->getGroups()->getAllGroupPermissions($group, $levelName));
		return array_unique($playerPerms);
	}

	/**
	 * @return array
	 */
	abstract function getGroupPlayers() : array;

	/**
	 * @param IPlayer $player
	 * @param string[] $permissions
	 * @param string $levelName
	 *
	 * @return bool
	 */
	abstract function setPlayerPermissions(IPlayer $player, array $permissions, string $levelName = "") : bool;

	/**
	 * @param IPlayer $player
	 * @param string[] $permissions
	 * @param string $levelName
	 *
	 * @return bool
	 */
	public function addPlayerPermissions(IPlayer $player, array $permissions = [], string $levelName = "") : bool {
		$perms = $this->getPlayerPermissions($player, $levelName);
		$perms = array_merge($perms, $permissions);
		return $this->setPlayerPermissions($player, $perms, $levelName);
	}

	/**
	 * @param IPlayer $player
	 * @param string[] $permissions
	 * @param string $levelName
	 *
	 * @return bool
	 */
	public function removePlayerPermissions(IPlayer $player, array $permissions = [], string $levelName = "") : bool {
		$perms = $this->getPlayerPermissions($player, $levelName);
		foreach($permissions as $permission) {
			if(($key = array_search($permission, $this->getPlayerPermissions($player, $levelName))) !== false) {
				unset($perms[$key]);
			}
		}
		return $this->setPlayerPermissions($player, $perms, $levelName);
	}

	/**
	 * @param IPlayer $player
	 *
	 * @return bool
	 */
	public function sortPlayerPermissions(IPlayer $player) : bool {
		$permissions = array_unique($this->getPlayerPermissions($player));
		sort($permissions, SORT_NATURAL | SORT_FLAG_CASE);
		$this->setPlayerPermissions($player, $permissions);
		foreach($this->plugin->getServer()->getLevels() as $level) {
			$permissions = array_unique($this->getPlayerPermissions($player, $level->getName()));
			sort($permissions, SORT_NATURAL | SORT_FLAG_CASE);
			$this->setPlayerPermissions($player, $permissions, $level->getName());
		}
		return true;
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
	 * @return array
	 */
	abstract function getGroups(IPlayer $player) : array;

	/**
	 * @param IPlayer $player
	 * @param array $groups
	 *
	 * @return bool
	 */
	abstract function setGroups(IPlayer $player, array $groups) : bool;

	/**
	 * @param IPlayer $player
	 * @param string $group
	 *
	 * @return bool
	 */
	public function addGroup(IPlayer $player, string $group) : bool {
		$groups = $this->getGroups($player);
		$groups[] = $group;
		return $this->setGroups($player, $groups);
	}

	/**
	 * @param IPlayer $from
	 * @param IPlayer $to
	 *
	 * @return bool
	 */
	public function mergePermissions(IPlayer $from, IPlayer $to) : bool {
		foreach($this->plugin->getServer()->getLevels() as $level) {
			$fromPerms = $this->getPlayerPermissions($from, $level->getName());
			$toPerms = $this->getPlayerPermissions($from, $level->getName());
			$perms = array_unique(array_merge($fromPerms, $toPerms));
			$this->setPlayerPermissions($to, $perms, $level->getName());
		}
		$fromPerms = $this->getPlayerPermissions($from);
		$toPerms = $this->getPlayerPermissions($from);
		$perms = array_unique(array_merge($fromPerms, $toPerms));
		return $this->setPlayerPermissions($to, $perms);
	}
}